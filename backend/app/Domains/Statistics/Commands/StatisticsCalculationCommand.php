<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Commands;

use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * 統計計算定時任務指令。
 *
 * 負責定期計算統計快照，支援不同統計週期和錯誤重試機制
 */
readonly class StatisticsCalculationCommand
{
    /** 最大重試次數 */
    private const MAX_RETRIES = 3;

    /** 重試間隔（秒） */
    private const RETRY_DELAY = 30;

    /** 鎖定檔案路徑前綴 */
    private const LOCK_PREFIX = '/tmp/statistics_calculation_';

    /** 鎖定時間 (秒) */
    private const LOCK_TIMEOUT = 3600; // 1 小時

    public function __construct(
        private StatisticsApplicationService $statisticsService,
        private StatisticsCacheServiceInterface $cacheService,
        private LoggerInterface $logger,
    ) {}

    /**
     * 執行統計計算任務。
     *
     * @param string[] $periods 要計算的週期類型 ['daily', 'weekly', 'monthly']
     * @param bool $force 是否強制執行（忽略鎖定）
     * @param bool $skipCache 是否跳過快取檢查
     */
    public function execute(array $periods = ['daily', 'weekly', 'monthly'], bool $force = false, bool $skipCache = false): array
    {
        $startTime = microtime(true);
        $results = [];

        $this->logger->info('開始統計計算任務', [
            'periods' => $periods,
            'force' => $force,
            'skip_cache' => $skipCache,
            'start_time' => $startTime,
        ]);

        foreach ($periods as $periodName) {
            try {
                $periodType = $this->validateAndGetPeriodType($periodName);
                $lockFile = $this->getLockFilePath($periodName);

                if (!$force && $this->isLocked($lockFile)) {
                    $this->logger->warning('統計計算任務已被鎖定，跳過執行', [
                        'period' => $periodName,
                        'lock_file' => $lockFile,
                    ]);

                    $results[$periodName] = [
                        'success' => false,
                        'error' => '任務已被鎖定',
                        'skipped' => true,
                    ];
                    continue;
                }

                $results[$periodName] = $this->calculatePeriodStatistics(
                    $periodType,
                    $periodName,
                    $lockFile,
                    $skipCache,
                );
            } catch (Exception $e) {
                $this->logger->error('統計計算任務失敗', [
                    'period' => $periodName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $results[$periodName] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'duration' => 0,
                    'retries' => 0,
                ];
            }
        }

        $totalDuration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success'] === true));

        $this->logger->info('統計計算任務完成', [
            'total_duration' => $totalDuration,
            'total_periods' => count($periods),
            'success_count' => $successCount,
            'failure_count' => count($periods) - $successCount,
            'results' => $results,
        ]);

        return [
            'total_duration' => $totalDuration,
            'total_periods' => count($periods),
            'success_count' => $successCount,
            'failure_count' => count($periods) - $successCount,
            'results' => $results,
        ];
    }

    /**
     * 計算單一週期的統計資料。
     */
    private function calculatePeriodStatistics(PeriodType $periodType, string $periodName, string $lockFile, bool $skipCache): array
    {
        $startTime = microtime(true);
        $retryCount = 0;

        while ($retryCount <= self::MAX_RETRIES) {
            try {
                $this->createLock($lockFile);

                $period = $this->createPeriodForType($periodType);

                $this->logger->info('開始計算統計週期', [
                    'period_type' => $periodName,
                    'period_start' => $period->startDate->format('Y-m-d H:i:s'),
                    'period_end' => $period->endDate->format('Y-m-d H:i:s'),
                    'retry_count' => $retryCount,
                ]);

                // 檢查是否已有快取的快照
                if (!$skipCache) {
                    $cacheKey = $this->cacheService->getSnapshotCacheKey($period, $period->startDate);
                    if ($this->cacheService->has($cacheKey)) {
                        $this->logger->info('統計快照已存在於快取中，跳過計算', [
                            'period_type' => $periodName,
                            'cache_key' => $cacheKey,
                        ]);

                        $this->releaseLock($lockFile);

                        return [
                            'success' => true,
                            'duration' => microtime(true) - $startTime,
                            'retries' => $retryCount,
                            'cached' => true,
                            'period' => [
                                'type' => $periodName,
                                'start' => $period->startDate->format('Y-m-d H:i:s'),
                                'end' => $period->endDate->format('Y-m-d H:i:s'),
                            ],
                        ];
                    }
                }

                // 建立統計快照
                $snapshot = $this->statisticsService->createStatisticsSnapshot($period);

                // 預熱相關的快取
                $this->warmupCacheForPeriod($period);

                $duration = microtime(true) - $startTime;

                $this->logger->info('統計週期計算完成', [
                    'period_type' => $periodName,
                    'duration' => $duration,
                    'retry_count' => $retryCount,
                    'snapshot_id' => $snapshot->getId()->toString(),
                ]);

                $this->releaseLock($lockFile);

                return [
                    'success' => true,
                    'duration' => $duration,
                    'retries' => $retryCount,
                    'cached' => false,
                    'snapshot_id' => $snapshot->getId()->toString(),
                    'period' => [
                        'type' => $periodName,
                        'start' => $period->startDate->format('Y-m-d H:i:s'),
                        'end' => $period->endDate->format('Y-m-d H:i:s'),
                    ],
                ];
            } catch (Exception $e) {
                $this->releaseLock($lockFile);

                $retryCount++;

                if ($retryCount > self::MAX_RETRIES) {
                    throw new RuntimeException(
                        "統計計算失敗，已達最大重試次數 ({$retryCount}): " . $e->getMessage(),
                        0,
                        $e,
                    );
                }

                $this->logger->warning('統計計算失敗，準備重試', [
                    'period_type' => $periodName,
                    'retry_count' => $retryCount,
                    'max_retries' => self::MAX_RETRIES,
                    'error' => $e->getMessage(),
                ]);

                // 等待後重試
                sleep(self::RETRY_DELAY);
            }
        }

        throw new RuntimeException('統計計算意外終止');
    }

    /**
     * 預熱指定週期的快取。
     */
    private function warmupCacheForPeriod(StatisticsPeriod $period): void
    {
        try {
            $warmupCallbacks = [
                'overview' => function () use ($period) {
                    return $this->statisticsService->getStatisticsOverview($period);
                },
                'popular_content' => function () use ($period) {
                    return $this->statisticsService->analyzePopularContent($period, 10);
                },
            ];

            $results = $this->cacheService->warmup($warmupCallbacks);

            $this->logger->debug('快取預熱完成', [
                'period_type' => $period->type->value,
                'results' => $results,
            ]);
        } catch (Exception $e) {
            $this->logger->warning('快取預熱失敗', [
                'period_type' => $period->type->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 根據週期類型建立統計週期。
     */
    private function createPeriodForType(PeriodType $periodType): StatisticsPeriod
    {
        $now = new DateTimeImmutable();

        return match ($periodType) {
            PeriodType::DAILY => $this->createDailyPeriod($now),
            PeriodType::WEEKLY => $this->createWeeklyPeriod($now),
            PeriodType::MONTHLY => $this->createMonthlyPeriod($now),
            PeriodType::YEARLY => $this->createYearlyPeriod($now),
            default => throw new InvalidArgumentException("不支援的週期類型: {$periodType->value}"),
        };
    }

    /**
     * 建立日統計週期。
     */
    private function createDailyPeriod(DateTimeImmutable $date): StatisticsPeriod
    {
        $startDate = $date->modify('yesterday midnight');
        $endDate = $startDate->modify('+1 day -1 second');

        return StatisticsPeriod::create($startDate, $endDate, PeriodType::DAILY);
    }

    /**
     * 建立週統計週期。
     */
    private function createWeeklyPeriod(DateTimeImmutable $date): StatisticsPeriod
    {
        $startDate = $date->modify('last monday midnight');
        $endDate = $startDate->modify('+1 week -1 second');

        return StatisticsPeriod::create($startDate, $endDate, PeriodType::WEEKLY);
    }

    /**
     * 建立月統計週期。
     */
    private function createMonthlyPeriod(DateTimeImmutable $date): StatisticsPeriod
    {
        $startDate = $date->modify('first day of last month midnight');
        $endDate = $startDate->modify('last day of this month 23:59:59');

        return StatisticsPeriod::create($startDate, $endDate, PeriodType::MONTHLY);
    }

    /**
     * 建立年統計週期。
     */
    private function createYearlyPeriod(DateTimeImmutable $date): StatisticsPeriod
    {
        $startDate = $date->modify('first day of january last year midnight');
        $endDate = $startDate->modify('last day of december 23:59:59');

        return StatisticsPeriod::create($startDate, $endDate, PeriodType::YEARLY);
    }

    /**
     * 驗證並取得週期類型。
     */
    private function validateAndGetPeriodType(string $periodName): PeriodType
    {
        return match (strtolower($periodName)) {
            'daily' => PeriodType::DAILY,
            'weekly' => PeriodType::WEEKLY,
            'monthly' => PeriodType::MONTHLY,
            'yearly' => PeriodType::YEARLY,
            default => throw new InvalidArgumentException("無效的週期類型: {$periodName}"),
        };
    }

    /**
     * 取得鎖定檔案路徑。
     */
    private function getLockFilePath(string $periodName): string
    {
        return self::LOCK_PREFIX . $periodName . '.lock';
    }

    /**
     * 檢查是否已被鎖定。
     */
    private function isLocked(string $lockFile): bool
    {
        if (!file_exists($lockFile)) {
            return false;
        }

        $lockTime = (int) file_get_contents($lockFile);
        $currentTime = time();

        // 如果鎖定時間超過超時時間，視為過期
        if ($currentTime - $lockTime > self::LOCK_TIMEOUT) {
            $this->releaseLock($lockFile);

            return false;
        }

        return true;
    }

    /**
     * 建立鎖定檔案。
     */
    private function createLock(string $lockFile): void
    {
        if ($this->isLocked($lockFile)) {
            throw new RuntimeException("任務已被鎖定: {$lockFile}");
        }

        file_put_contents($lockFile, time());
    }

    /**
     * 釋放鎖定檔案。
     */
    private function releaseLock(string $lockFile): void
    {
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }

    /**
     * 清理所有過期的鎖定檔案。
     */
    public function cleanupExpiredLocks(): int
    {
        $lockPattern = self::LOCK_PREFIX . '*.lock';
        $lockFiles = glob($lockPattern);
        $cleanedCount = 0;

        foreach ($lockFiles as $lockFile) {
            if (!$this->isLocked($lockFile)) {
                $this->releaseLock($lockFile);
                $cleanedCount++;
            }
        }

        $this->logger->info('清理過期鎖定檔案', [
            'cleaned_count' => $cleanedCount,
            'total_found' => count($lockFiles),
        ]);

        return $cleanedCount;
    }

    /**
     * 取得任務狀態。
     */
    public function getStatus(): array
    {
        $periods = ['daily', 'weekly', 'monthly', 'yearly'];
        $status = [];

        foreach ($periods as $period) {
            $lockFile = $this->getLockFilePath($period);
            $isLocked = $this->isLocked($lockFile);
            $lockTime = null;

            if ($isLocked && file_exists($lockFile)) {
                $lockTime = (int) file_get_contents($lockFile);
            }

            $status[$period] = [
                'locked' => $isLocked,
                'lock_file' => $lockFile,
                'lock_time' => $lockTime,
                'lock_age_seconds' => $lockTime ? time() - $lockTime : null,
            ];
        }

        return [
            'periods' => $status,
            'lock_timeout' => self::LOCK_TIMEOUT,
            'max_retries' => self::MAX_RETRIES,
            'retry_delay' => self::RETRY_DELAY,
        ];
    }
}
