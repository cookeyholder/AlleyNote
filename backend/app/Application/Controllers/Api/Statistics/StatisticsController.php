<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\Statistics;

use App\Application\Controllers\BaseController;
use App\Application\Services\Statistics\StatisticsApplicationService;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * 統計資料查詢 API 控制器.
 *
 * 提供統計資料的 REST API 端點
 */
class StatisticsController extends BaseController
{
    public function __construct(
        private StatisticsApplicationService $statisticsService,
        private LoggerInterface $logger,
    ) {}

    /**
     * 取得統計概覽.
     *
     * GET /api/statistics/overview
     */
    public function getOverview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $periodParam = $queryParams['period'] ?? 'monthly';
            $period = is_string($periodParam) ? $periodParam : 'monthly';

            $overview = $this->statisticsService->getOverview($period);

            $responseData = [
                'success' => true,
                'data' => $overview,
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('取得統計概覽失敗', ['error' => $e->getMessage()]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '取得統計概覽失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }

    /**
     * 取得文章統計.
     *
     * GET /api/statistics/posts
     */
    public function getPostStatistics(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $periodParam = $queryParams['period'] ?? 'monthly';
            $period = is_string($periodParam) ? $periodParam : 'monthly';
            $limitParam = $queryParams['limit'] ?? 10;
            $limit = max(1, min(100, is_numeric($limitParam) ? (int) $limitParam : 10));

            $postStats = $this->statisticsService->getPostStatistics($period, $limit);

            $responseData = [
                'success' => true,
                'data' => $postStats,
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('取得文章統計失敗', ['error' => $e->getMessage()]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '取得文章統計失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }

    /**
     * 取得來源統計.
     *
     * GET /api/statistics/sources
     */
    public function getSourceStatistics(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $periodParam = $queryParams['period'] ?? 'monthly';
            $period = is_string($periodParam) ? $periodParam : 'monthly';

            $sourceStats = $this->statisticsService->getSourceStatistics($period);

            $responseData = [
                'success' => true,
                'data' => $sourceStats,
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('取得來源統計失敗', ['error' => $e->getMessage()]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '取得來源統計失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }

    /**
     * 取得使用者活動統計.
     *
     * GET /api/statistics/users
     */
    public function getUserActivityStatistics(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $periodParam = $queryParams['period'] ?? 'monthly';
            $period = is_string($periodParam) ? $periodParam : 'monthly';

            $userActivityStats = $this->statisticsService->getUserActivityStatistics($period);

            $responseData = [
                'success' => true,
                'data' => $userActivityStats,
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('取得使用者活動統計失敗', ['error' => $e->getMessage()]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '取得使用者活動統計失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }

    /**
     * 取得熱門內容統計.
     *
     * GET /api/statistics/popular
     */
    public function getPopularContent(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $periodParam = $queryParams['period'] ?? 'monthly';
            $period = is_string($periodParam) ? $periodParam : 'monthly';
            $limitParam = $queryParams['limit'] ?? 20;
            $limit = max(1, min(50, is_numeric($limitParam) ? (int) $limitParam : 20));

            $popularContent = $this->statisticsService->getPopularContent($period, $limit);

            $responseData = [
                'success' => true,
                'data' => $popularContent,
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('取得熱門內容統計失敗', ['error' => $e->getMessage()]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '取得熱門內容統計失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }

    /**
     * 取得統計快照.
     *
     * GET /api/statistics/snapshot
     */
    public function getSnapshot(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            $periodParam = $queryParams['period'] ?? 'monthly';
            $period = is_string($periodParam) ? $periodParam : 'monthly';

            $snapshot = $this->statisticsService->getSnapshot($period);

            $responseData = [
                'success' => true,
                'data' => $snapshot,
                'timestamp' => time(),
            ];

            return $this->json($response, $responseData);
        } catch (Exception $e) {
            $this->logger->error('取得統計快照失敗', ['error' => $e->getMessage()]);

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '取得統計快照失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }
}
