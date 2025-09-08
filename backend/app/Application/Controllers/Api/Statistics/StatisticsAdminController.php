<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\Statistics;

use App\Application\Controllers\BaseController;
use App\Application\Services\Statistics\StatisticsApplicationService;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class StatisticsAdminController extends BaseController
{
    public function __construct(
        private StatisticsApplicationService $statisticsService,
        private LoggerInterface $logger,
    ) {}

    /**
     * 重新整理統計資料.
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

            $bodyString = (string) $request->getBody();
            $body = json_decode($bodyString, true);
            if (!is_array($body)) {
                $body = [];
            }

            // 確保所有鍵都是字串
            $options = [];
            foreach ($body as $key => $value) {
                if (is_string($key)) {
                    $options[$key] = $value;
                }
            }

            $startTime = microtime(true);

            // 執行統計重新整理
            $result = $this->statisticsService->refreshStatistics($options);

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $this->logger->info('統計重新整理完成', [
                'duration_ms' => $duration,
                'options' => $options,
                'result' => $result,
            ]);

            $responseData = [
                'success' => true,
                'data' => $result,
                'meta' => [
                    'duration_ms' => $duration,
                    'timestamp' => time(),
                ],
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('統計重新整理失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '統計重新整理失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }

    /**
     * 取得統計資料狀態.
     *
     * GET /api/admin/statistics/status
     */
    public function getStatus(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $status = $this->statisticsService->getStatisticsStatus();

            $responseData = [
                'success' => true,
                'data' => $status,
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('取得統計狀態失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '取得統計狀態失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }

    /**
     * 取得統計健康檢查.
     *
     * GET /api/admin/statistics/health
     */
    public function getHealth(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $health = $this->statisticsService->getHealthCheck();

            $responseData = [
                'success' => true,
                'data' => $health,
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('統計健康檢查失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '統計健康檢查失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }

    /**
     * 清理統計資料.
     *
     * POST /api/admin/statistics/cleanup
     */
    public function cleanup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $bodyString = (string) $request->getBody();
            $body = json_decode($bodyString, true);
            if (!is_array($body)) {
                $body = [];
            }

            $options = [];
            foreach ($body as $key => $value) {
                if (is_string($key)) {
                    $options[$key] = $value;
                }
            }

            $result = $this->statisticsService->cleanupStatistics($options);

            $this->logger->info('統計資料清理完成', [
                'options' => $options,
                'result' => $result,
            ]);

            $responseData = [
                'success' => true,
                'data' => $result,
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('統計資料清理失敗', [
                'error' => $e->getMessage(),
            ]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '統計資料清理失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }
}
