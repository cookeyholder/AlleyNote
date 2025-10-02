<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Commands;

use App\Domains\Statistics\Contracts\StatisticsAggregationServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateInterval;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * 統計計算定時任務命令.
 *
 * 負責定期計算統計快照的背景任務，支援不同統計週期、
 * 錯誤重試機制、執行日誌記錄，並確保並行安全執行。
 */
final class StatisticsCalculationCommand
{
    /** 預設重試次數 */
    private const DEFAULT_MAX_RETRIES = 3;

    /** 重試延遲（秒） */
    private const RETRY_DELAY_SECONDS = 5;

    /** 並行執行鎖定文件路徑 */
    private const LOCK_FILE_PREFIX = 'statistics_calculation_';

    /** 支援的統計週期類型 */
    private const SUPPORTED_PERIODS = [
        'daily' => PeriodType::DAILY,
        'weekly' => PeriodType::WEEKLY,
        'monthly' => PeriodType::MONTHLY,
    ];

    /** @var array<string, mixed> 執行統計 */
    private array $executionStats = [
        'start_time' => null,
        'end_time' => null,
        'total_snapshots' => 0,
        'successful_snapshots' => 0,
        'failed_snapshots' => 0,
        'retries' => 0,
        'errors' => [],
    ];

    public function __construct(
        private readonly StatisticsAggregationServiceInterface $aggregationService,
        private readonly StatisticsRepositoryInterface $statisticsRepository,
        private readonly StatisticsCacheServiceInterface $cacheService,
        private readonly LoggerInterface $logger,
        private readonly string $lockFileDir = '/tmp',
    ) {}

    /**
     * 執行統計計算任務.
     *
     * @param array<string> $periods 要計算的週期類型 ['daily', 'weekly', 'monthly']
     * @param int $maxRetries 最大重試次數
     * @param bool $force 是否強制重新計算現有快照
     * @return array<string, mixed> 執行結果統計
     */
    public function execute(
        array $periods = ['daily'],
        int $maxRetries = self::DEFAULT_MAX_RETRIES,
        bool $force = false,
    ): array {
        $this->initializeExecution();

        // 驗證輸入參數
        $this->validatePeriods($periods);

        $lockFile = null;

        try {
            // 獲取執行鎖定，確保並行安全
            $lockFile = $this->acquireExecutionLock($periods);

            $this->logger->info('開始統計計算任務', [
                'periods' => $periods,
                'max_retries' => $maxRetries,
                'force' => $force,
            ]);

            // 計算每個週期的統計快照
            foreach ($periods as $periodType) {
                $this->calculatePeriodStatistics($periodType, $maxRetries, $force);
            }

            $this->finalizeExecution();

            $this->logger->info('統計計算任務完成', $this->executionStats);

            return $this->executionStats;
        } catch (Throwable $e) {
            $this->handleExecutionError($e);

            throw $e;
        } finally {
            // 釋放執行鎖定
            if ($lockFile !== null) {
                $this->releaseExecutionLock($lockFile);
            }
        }
    }

    /**
     * 獲取指定週期的可用統計快照類型.
     *
     * @return array<string> 統計快照類型列表
     */
    public function getAvailableSnapshotTypes(): array
    {
        return [
            StatisticsSnapshot::TYPE_OVERVIEW,
            StatisticsSnapshot::TYPE_POSTS,
            StatisticsSnapshot::TYPE_USERS,
            StatisticsSnapshot::TYPE_POPULAR,
        ];
    }

    /**
     * 驗證週期類型參數.
     *
     * @param array<string> $periods
     * @throws RuntimeException 當週期類型無效時
     */
    private function validatePeriods(array $periods): void
    {
        if (empty($periods)) {
            throw new RuntimeException('至少需要指定一個統計週期');
        }

        $invalidPeriods = array_diff($periods, array_keys(self::SUPPORTED_PERIODS));
        if (!empty($invalidPeriods)) {
            throw new RuntimeException('不支援的統計週期: ' . implode(', ', $invalidPeriods));
        }
    }

    /**
     * 計算特定週期的統計快照.
     */
    private function calculatePeriodStatistics(string $periodType, int $maxRetries, bool $force): void
    {
        $period = $this->createStatisticsPeriod($periodType);
        $snapshotTypes = $this->getAvailableSnapshotTypes();

        $this->logger->info('開始計算週期統計', [
            'period_type' => $periodType,
            'period_start' => $period->startTime->format('Y-m-d H:i:s'),
            'period_end' => $period->endTime->format('Y-m-d H:i:s'),
            'snapshot_types' => count($snapshotTypes),
        ]);

        foreach ($snapshotTypes as $snapshotType) {
            $this->calculateSnapshotWithRetry($snapshotType, $period, $maxRetries, $force);
        }
    }

    /**
     * 建立統計週期物件.
     */
    private function createStatisticsPeriod(string $periodType): StatisticsPeriod
    {
        $now = new DateTimeImmutable();
        $periodTypeEnum = self::SUPPORTED_PERIODS[$periodType];

        [$startTime, $endTime] = match ($periodTypeEnum) {
            PeriodType::DAILY => [
                $now->modify('yesterday midnight'),
                $now->modify('yesterday midnight')->add(new DateInterval('P1D'))->modify('-1 second'),
            ],
            PeriodType::WEEKLY => [
                $now->modify('last monday midnight')->modify('-1 week'),
                $now->modify('last monday midnight')->modify('-1 week')->add(new DateInterval('P7D'))->modify('-1 second'),
            ],
            PeriodType::MONTHLY => [
                $now->modify('first day of last month midnight'),
                $now->modify('first day of last month midnight')->add(new DateInterval('P1M'))->modify('-1 second'),
            ],
        };

        return new StatisticsPeriod($periodTypeEnum, $startTime, $endTime);
    }

    /**
     * 帶重試機制計算統計快照.
     */
    private function calculateSnapshotWithRetry(
        string $snapshotType,
        StatisticsPeriod $period,
        int $maxRetries,
        bool $force,
    ): void {
        $this->incrementStat('total_snapshots');
        $retryCount = 0;

        while ($retryCount <= $maxRetries) {
            try {
                // 檢查是否已存在快照（非強制模式）
                if (!$force && $this->snapshotExists($snapshotType, $period)) {
                    $this->logger->debug('統計快照已存在，跳過', [
                        'snapshot_type' => $snapshotType,
                        'period_type' => $period->type->value,
                    ]);

                    $this->incrementStat('successful_snapshots');

                    return;
                }

                // 計算統計快照
                $snapshot = $this->calculateSingleSnapshot($snapshotType, $period);

                // 儲存快照
                $this->statisticsRepository->save($snapshot);

                // 清除相關快取
                $this->clearRelatedCache($snapshotType);

                $this->logger->info('統計快照計算成功', [
                    'snapshot_type' => $snapshotType,
                    'period_type' => $period->type->value,
                    'retry_count' => $retryCount,
                ]);

                $this->incrementStat('successful_snapshots');

                return;
            } catch (Throwable $e) {
                $retryCount++;
                $this->incrementStat('retries');

                $this->logger->warning('統計快照計算失敗', [
                    'snapshot_type' => $snapshotType,
                    'period_type' => $period->type->value,
                    'retry_count' => $retryCount,
                    'max_retries' => $maxRetries,
                    'error' => $e->getMessage(),
                ]);

                if ($retryCount > $maxRetries) {
                    $this->incrementStat('failed_snapshots');
                    $this->addError([
                        'snapshot_type' => $snapshotType,
                        'period_type' => $period->type->value,
                        'error' => $e->getMessage(),
                        'retries' => $retryCount - 1,
                    ]);

                    $this->logger->error('統計快照計算最終失敗', [
                        'snapshot_type' => $snapshotType,
                        'period_type' => $period->type->value,
                        'total_retries' => $retryCount - 1,
                        'error' => $e->getMessage(),
                    ]);

                    break;
                }

                // 重試前等待
                if ($retryCount <= $maxRetries) {
                    sleep(self::RETRY_DELAY_SECONDS * $retryCount);
                }
            }
        }
    }

    /**
     * 計算單一統計快照.
     */
    private function calculateSingleSnapshot(string $snapshotType, StatisticsPeriod $period): StatisticsSnapshot
    {
        $startTime = microtime(true);

        try {
            // 根據快照類型呼叫對應的聚合方法
            $snapshot = match ($snapshotType) {
                StatisticsSnapshot::TYPE_OVERVIEW => $this->aggregationService->createOverviewSnapshot($period),
                StatisticsSnapshot::TYPE_POSTS => $this->aggregationService->createPostsSnapshot($period),
                StatisticsSnapshot::TYPE_USERS => $this->aggregationService->createUsersSnapshot($period),
                StatisticsSnapshot::TYPE_POPULAR => $this->aggregationService->createPopularSnapshot($period),
                default => throw new RuntimeException("不支援的快照類型: {$snapshotType}"),
            };

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->debug('統計資料聚合完成', [
                'snapshot_type' => $snapshotType,
                'period_type' => $period->type->value,
                'duration_ms' => $duration,
            ]);

            return $snapshot;
        } catch (Throwable $e) {
            $this->logger->error('統計資料聚合失敗', [
                'snapshot_type' => $snapshotType,
                'period_type' => $period->type->value,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                "統計快照計算失敗: {$snapshotType} ({$period->type->value}): {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    /**
     * 檢查統計快照是否已存在.
     */
    private function snapshotExists(string $snapshotType, StatisticsPeriod $period): bool
    {
        return $this->statisticsRepository->exists($snapshotType, $period);
    }

    /**
     * 清除相關快取.
     */
    private function clearRelatedCache(string $snapshotType): void
    {
        try {
            $tags = ['statistics', $snapshotType];
            $this->cacheService->flushByTags($tags);

            $this->logger->debug('統計快取清除完成', [
                'snapshot_type' => $snapshotType,
                'cache_tags' => $tags,
            ]);
        } catch (Throwable $e) {
            $this->logger->warning('統計快取清除失敗', [
                'snapshot_type' => $snapshotType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 獲取執行鎖定文件.
     */
    private function acquireExecutionLock(array $periods): string
    {
        $lockFileName = self::LOCK_FILE_PREFIX . md5(implode('_', $periods));
        $lockFilePath = $this->lockFileDir . '/' . $lockFileName . '.lock';

        // 檢查是否已有其他進程在執行
        if (file_exists($lockFilePath)) {
            $lockContent = file_get_contents($lockFilePath);
            if ($lockContent !== false) {
                $lockData = json_decode($lockContent, true);
                if (is_array($lockData) && isset($lockData['pid']) && is_int($lockData['pid'])) {
                    // 檢查進程是否仍在運行（Unix系統）
                    if (function_exists('posix_kill') && posix_kill($lockData['pid'], 0)) {
                        throw new RuntimeException('統計計算任務已在其他進程中執行');
                    }
                }
            }

            // 清理過期的鎖定文件
            unlink($lockFilePath);
        }

        // 建立新的鎖定文件
        $lockData = [
            'pid' => getmypid(),
            'start_time' => time(),
            'periods' => $periods,
        ];

        if (!file_put_contents($lockFilePath, json_encode($lockData))) {
            throw new RuntimeException('無法建立執行鎖定文件');
        }

        return $lockFilePath;
    }

    /**
     * 釋放執行鎖定文件.
     */
    private function releaseExecutionLock(string $lockFilePath): void
    {
        if (file_exists($lockFilePath)) {
            unlink($lockFilePath);
        }
    }

    /**
     * 初始化執行統計.
     */
    private function initializeExecution(): void
    {
        $this->executionStats = [
            'start_time' => microtime(true),
            'end_time' => null,
            'total_snapshots' => 0,
            'successful_snapshots' => 0,
            'failed_snapshots' => 0,
            'retries' => 0,
            'errors' => [],
        ];
    }

    /**
     * 完成執行統計.
     */
    private function finalizeExecution(): void
    {
        $endTime = microtime(true);
        $startTime = is_float($this->executionStats['start_time'] ?? null) ? $this->executionStats['start_time'] : $endTime;

        $this->executionStats['end_time'] = $endTime;
        $this->executionStats['duration_ms'] = round(($endTime - $startTime) * 1000, 2);
    }

    /**
     * 增加統計計數器.
     */
    private function incrementStat(string $key): void
    {
        $current = is_int($this->executionStats[$key] ?? null) ? $this->executionStats[$key] : 0;
        $this->executionStats[$key] = $current + 1;
    }

    /**
     * 新增錯誤記錄.
     *
     * @param array<string, mixed> $error
     */
    private function addError(array $error): void
    {
        $errors = is_array($this->executionStats['errors'] ?? null) ? $this->executionStats['errors'] : [];
        $errors[] = $error;
        $this->executionStats['errors'] = $errors;
    }

    /**
     * 處理執行錯誤.
     */
    private function handleExecutionError(Throwable $e): void
    {
        $this->finalizeExecution();

        $this->logger->error('統計計算任務執行失敗', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'execution_stats' => $this->executionStats,
        ]);
    }
}
