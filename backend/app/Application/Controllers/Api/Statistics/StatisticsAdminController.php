<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\Statistics;

use App\Application\Controllers\BaseController;
use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Commands\StatisticsCalculationCommand;
use App\Domains\Statistics\Contracts\StatisticsCacheServiceInterface;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * 統計管理 API 控制器。
 *
 * 提供管理員專用的統計管理功能，包含重新整理、快取清除和健康檢查
 */
class StatisticsAdminController extends BaseController
{
    private StatisticsApplicationService $applicationService;

    private StatisticsCalculationCommand $calculationCommand;

    private StatisticsCacheServiceInterface $cacheService;

    private LoggerInterface $logger;

    public function __construct(
        StatisticsApplicationService $applicationService,
        StatisticsCalculationCommand $calculationCommand,
        StatisticsCacheServiceInterface $cacheService,
        LoggerInterface $logger,
    ) {
        $this->applicationService = $applicationService;
        $this->calculationCommand = $calculationCommand;
        $this->cacheService = $cacheService;
        $this->logger = $logger;
    }

    /**
     * 重新整理統計資料。
     *
     * POST /api/admin/statistics/refresh
     */
    public function refresh(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('統計重新整理 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'body' => (string) $request->getBody(),
            ]);

            $body = json_decode((string) $request->getBody(), true);
            if (!is_array($body)) {
                $body = [];
            }
            // 確保所有鍵都是字串
            $stringKeyBody = [];
            foreach ($body as $key => $value) {
                if (is_string($key)) {
                    $stringKeyBody[$key] = $value;
                }
            }
            $params = $this->validateRefreshParams($stringKeyBody);

            // 執行統計計算
            $periods = $params['periods'] ?? ['daily', 'weekly', 'monthly'];
            if (!is_array($periods)) {
                $periods = ['daily', 'weekly', 'monthly'];
            }
            $validatedPeriods = [];
            foreach ($periods as $period) {
                if (is_string($period)) {
                    $validatedPeriods[] = $period;
                }
            }
            $result = $this->calculationCommand->execute(
                $validatedPeriods,
                (bool) ($params['force'] ?? false),
                (bool) ($params['skip_cache'] ?? false),
            );

            $this->logger->info('統計重新整理 API 成功回應', [
                'success_count' => $result['success_count'],
                'failure_count' => $result['failure_count'],
                'total_duration' => $result['total_duration'],
            ]);

            $response->getBody()->write($this->successResponse($result, '統計資料重新整理完成'));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('統計重新整理 API 參數錯誤', [
                'error' => $e->getMessage(),
                'body' => (string) $request->getBody(),
            ]);

            $response->getBody()->write($this->errorResponse($e->getMessage(), 400));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (Exception $e) {
            $this->logger->error('統計重新整理 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('統計資料重新整理失敗', 500));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 清除統計快取。
     *
     * DELETE /api/admin/statistics/cache
     */
    public function clearCache(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('統計快取清除 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'query_params' => $request->getQueryParams(),
            ]);

            $queryParams = $request->getQueryParams();
            if (!is_array($queryParams)) {
                $queryParams = [];
            }
            // 確保所有查詢參數都是字串型態
            $stringParams = [];
            foreach ($queryParams as $key => $value) {
                if (is_string($key)) {
                    $stringParams[$key] = $value;
                }
            }
            $params = $this->validateClearCacheParams($stringParams);

            $cacheType = is_string($params['type']) ? $params['type'] : 'all';
            $result = match ($cacheType) {
                'all' => $this->cacheService->invalidateAllCache(),
                'overview' => $this->cacheService->invalidateOverviewCache(),
                'snapshot' => $this->cacheService->invalidateSnapshotCache(),
                'popular' => $this->cacheService->invalidatePopularContentCache(),
                'report' => $this->cacheService->invalidateReportCache(is_string($params['report_type'] ?? null) ? $params['report_type'] : 'general'),
                default => throw new InvalidArgumentException("不支援的快取類型: {$cacheType}"),
            };

            $this->logger->info('統計快取清除 API 成功回應', [
                'cache_type' => $params['type'],
                'success' => $result,
            ]);

            $message = $result ? '快取清除成功' : '快取清除部分失敗';
            $response->getBody()->write($this->successResponse(['success' => $result], $message));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('統計快取清除 API 參數錯誤', [
                'error' => $e->getMessage(),
                'query_params' => $request->getQueryParams(),
            ]);

            $response->getBody()->write($this->errorResponse($e->getMessage(), 400));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (Exception $e) {
            $this->logger->error('統計快取清除 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('快取清除失敗', 500));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得統計系統健康狀態。
     *
     * GET /api/admin/statistics/health
     */
    public function health(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('統計健康檢查 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
            ]);

            $healthData = $this->gatherHealthData();

            $this->logger->info('統計健康檢查 API 成功回應', [
                'overall_status' => $healthData['overall_status'],
                'components_count' => count((array) $healthData['components']),
            ]);

            $response->getBody()->write($this->successResponse($healthData, '健康檢查完成'));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('統計健康檢查 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('健康檢查失敗', 500));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得統計任務狀態。
     *
     * GET /api/admin/statistics/status
     */
    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('統計任務狀態 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
            ]);

            $status = $this->calculationCommand->getStatus();

            $this->logger->info('統計任務狀態 API 成功回應', [
                'periods_count' => count((array) $status['periods']),
            ]);

            $response->getBody()->write($this->successResponse($status, '任務狀態取得成功'));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('統計任務狀態 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('任務狀態取得失敗', 500));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 清理過期鎖定檔案。
     *
     * POST /api/admin/statistics/cleanup
     */
    public function cleanup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->logger->info('統計清理 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
            ]);

            $cleanedCount = $this->calculationCommand->cleanupExpiredLocks();
            $cacheCleanup = $this->cacheService->cleanup();

            $result = [
                'expired_locks_cleaned' => $cleanedCount,
                'cache_cleanup' => $cacheCleanup,
            ];

            $this->logger->info('統計清理 API 成功回應', [
                'expired_locks_cleaned' => $cleanedCount,
                'cache_cleanup_success' => !isset($cacheCleanup['error']),
            ]);

            $response->getBody()->write($this->successResponse($result, '清理作業完成'));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('統計清理 API 執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response->getBody()->write($this->errorResponse('清理作業失敗', 500));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 驗證重新整理參數。
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    /**\n      * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function validateRefreshParams(array $body): array
    {
        $params = [];

        // 驗證週期類型
        if (isset($body['periods'])) {
            if (!is_array($body['periods'])) {
                throw new InvalidArgumentException('週期參數必須是陣列');
            }

            $validPeriods = ['daily', 'weekly', 'monthly', 'yearly'];
            foreach ($body['periods'] as $period) {
                $periodStr = is_string($period) ? $period : '';
                if ($periodStr === '') {
                    throw new InvalidArgumentException('週期類型不能為空');
                }
                if (!in_array($periodStr, $validPeriods)) {
                    throw new InvalidArgumentException("無效的週期類型: {$periodStr}");
                }
            }

            $params['periods'] = $body['periods'];
        }

        // 驗證布林參數
        if (isset($body['force'])) {
            if (!is_bool($body['force'])) {
                throw new InvalidArgumentException('force 參數必須是布林值');
            }
            $params['force'] = $body['force'];
        }

        if (isset($body['skip_cache'])) {
            if (!is_bool($body['skip_cache'])) {
                throw new InvalidArgumentException('skip_cache 參數必須是布林值');
            }
            $params['skip_cache'] = $body['skip_cache'];
        }

        return $params;
    }

    /**
     * 驗證快取清除參數。
     *
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    /**\n      * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    private function validateClearCacheParams(array $queryParams): array
    {
        $params = [];

        // 驗證快取類型
        $validTypes = ['all', 'overview', 'snapshot', 'popular', 'report'];
        $type = is_string($queryParams['type'] ?? null) ? $queryParams['type'] : 'all';

        if (!in_array($type, $validTypes)) {
            throw new InvalidArgumentException("無效的快取類型: {$type}。支援的類型: " . implode(', ', $validTypes));
        }

        $params['type'] = $type;

        // 如果是報告快取，驗證報告類型
        if ($type === 'report' && isset($queryParams['report_type'])) {
            $validReportTypes = ['general', 'detailed', 'summary'];
            $reportType = is_string($queryParams['report_type']) ? $queryParams['report_type'] : '';
            if (!in_array($reportType, $validReportTypes)) {
                throw new InvalidArgumentException("無效的報告類型: {$reportType}");
            }
            $params['report_type'] = $queryParams['report_type'];
        }

        return $params;
    }

    /**
     * 收集健康檢查資料。
     *
     * @return array<string, mixed>
     */
    private function gatherHealthData(): array
    {
        $components = [];
        $overallStatus = 'healthy';

        // 檢查快取服務健康狀態
        try {
            $cacheHealth = $this->cacheService->isHealthy();
            $cacheStats = $this->cacheService->getStats();

            $components['cache'] = [
                'status' => $cacheHealth ? 'healthy' : 'unhealthy',
                'details' => $cacheStats,
            ];

            if (!$cacheHealth) {
                $overallStatus = 'degraded';
            }
        } catch (Exception $e) {
            $components['cache'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            $overallStatus = 'unhealthy';
        }

        // 檢查統計任務狀態
        try {
            $taskStatus = $this->calculationCommand->getStatus();
            $periods = is_array($taskStatus['periods'] ?? null) ? $taskStatus['periods'] : [];
            $lockedTasks = array_filter($periods, function ($p): bool {
                return is_array($p) && ($p['locked'] ?? false) === true;
            });

            $components['tasks'] = [
                'status' => count($lockedTasks) === 0 ? 'healthy' : 'busy',
                'details' => $taskStatus,
            ];

            if (count($lockedTasks) > 0) {
                $overallStatus = 'busy';
            }
        } catch (Exception $e) {
            $components['tasks'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            $overallStatus = 'unhealthy';
        }

        // 檢查應用服務健康狀態
        try {
            // 嘗試呼叫一個簡單的服務方法來檢查健康狀態
            $testPeriod = StatisticsPeriod::create(
                new DateTimeImmutable('today'),
                new DateTimeImmutable('tomorrow -1 second'),
                PeriodType::DAILY,
            );

            $this->applicationService->getStatisticsOverview($testPeriod);
            $serviceHealth = true;

            $components['application'] = [
                'status' => 'healthy',
                'timestamp' => new DateTimeImmutable()->format('c'),
            ];
        } catch (Exception $e) {
            $components['application'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            $overallStatus = 'unhealthy';
        }

        return [
            'overall_status' => $overallStatus,
            'timestamp' => new DateTimeImmutable()->format('c'),
            'components' => $components,
            'summary' => [
                'total_components' => count($components),
                'healthy_components' => count(array_filter($components, fn($c) => $c['status'] === 'healthy')),
                'unhealthy_components' => count(array_filter($components, fn($c) => $c['status'] === 'unhealthy')),
                'error_components' => count(array_filter($components, fn($c) => $c['status'] === 'error')),
            ],
        ];
    }
}
