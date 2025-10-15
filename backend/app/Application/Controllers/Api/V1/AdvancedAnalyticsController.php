<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Statistics\Services\AdvancedAnalyticsService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 進階分析 API 控制器.
 */
class AdvancedAnalyticsController extends BaseController
{
    public function __construct(
        private readonly AdvancedAnalyticsService $analyticsService,
    ) {}

    /**
     * 獲取裝置類型統計.
     *
     * GET /api/statistics/analytics/device-types
     */
    #[OA\Get(
        path: '/api/statistics/analytics/device-types',
        summary: '獲取裝置類型統計',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ],
    )]
    public function getDeviceTypes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $postId = isset($params['post_id']) ? (int) $params['post_id'] : null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $stats = $this->analyticsService->getDeviceTypeStats($postId, $startDate, $endDate);

        return $this->json($response, [
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * 獲取瀏覽器統計.
     *
     * GET /api/statistics/analytics/browsers
     */
    #[OA\Get(
        path: '/api/statistics/analytics/browsers',
        summary: '獲取瀏覽器統計',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ],
    )]
    public function getBrowsers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $postId = isset($params['post_id']) ? (int) $params['post_id'] : null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $stats = $this->analyticsService->getBrowserStats($postId, $startDate, $endDate);

        return $this->json($response, [
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * 獲取操作系統統計.
     *
     * GET /api/statistics/analytics/operating-systems
     */
    #[OA\Get(
        path: '/api/statistics/analytics/operating-systems',
        summary: '獲取操作系統統計',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ],
    )]
    public function getOperatingSystems(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $postId = isset($params['post_id']) ? (int) $params['post_id'] : null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $stats = $this->analyticsService->getOSStats($postId, $startDate, $endDate);

        return $this->json($response, [
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * 獲取來源統計.
     *
     * GET /api/statistics/analytics/referrers
     */
    #[OA\Get(
        path: '/api/statistics/analytics/referrers',
        summary: '獲取來源統計',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ],
    )]
    public function getReferrers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $postId = isset($params['post_id']) ? (int) $params['post_id'] : null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;
        $limit = isset($params['limit']) ? (int) $params['limit'] : 10;

        $stats = $this->analyticsService->getReferrerStats($postId, $startDate, $endDate, $limit);

        return $this->json($response, [
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * 獲取時段分布統計.
     *
     * GET /api/statistics/analytics/hourly-distribution
     */
    #[OA\Get(
        path: '/api/statistics/analytics/hourly-distribution',
        summary: '獲取時段分布統計',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ],
    )]
    public function getHourlyDistribution(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $postId = isset($params['post_id']) ? (int) $params['post_id'] : null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $stats = $this->analyticsService->getHourlyDistribution($postId, $startDate, $endDate);

        return $this->json($response, [
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * 獲取綜合分析報告.
     *
     * GET /api/statistics/analytics/comprehensive
     */
    #[OA\Get(
        path: '/api/statistics/analytics/comprehensive',
        summary: '獲取綜合分析報告',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: '成功'),
        ],
    )]
    public function getComprehensiveReport(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $postId = isset($params['post_id']) ? (int) $params['post_id'] : null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $report = $this->analyticsService->getComprehensiveReport($postId, $startDate, $endDate);

        return $this->json($response, [
            'success' => true,
            'data' => $report,
        ]);
    }
}
