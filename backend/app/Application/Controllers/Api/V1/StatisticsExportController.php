<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Application\Controllers\BaseController;
use App\Domains\Statistics\Services\StatisticsExportService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 統計報表匯出控制器.
 */
class StatisticsExportController extends BaseController
{
    public function __construct(
        private readonly StatisticsExportService $exportService,
    ) {}

    /**
     * 匯出文章瀏覽統計為 CSV.
     *
     * GET /api/statistics/export/views/csv
     */
    #[OA\Get(
        path: '/api/statistics/export/views/csv',
        summary: '匯出文章瀏覽統計為 CSV',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'CSV 文件'),
        ],
    )]
    public function exportViewsCSV(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $postId = isset($params['post_id']) ? (int) $params['post_id'] : null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $csv = $this->exportService->exportViewsToCSV($postId, $startDate, $endDate);

        $filename = 'post_views_' . date('Y-m-d_His') . '.csv';

        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'max-age=0');
    }

    /**
     * 匯出綜合分析報告為 CSV.
     *
     * GET /api/statistics/export/comprehensive/csv
     */
    #[OA\Get(
        path: '/api/statistics/export/comprehensive/csv',
        summary: '匯出綜合分析報告為 CSV',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'CSV 文件'),
        ],
    )]
    public function exportComprehensiveCSV(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $postId = isset($params['post_id']) ? (int) $params['post_id'] : null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $csv = $this->exportService->exportComprehensiveReportToCSV($postId, $startDate, $endDate);

        $filename = 'comprehensive_report_' . date('Y-m-d_His') . '.csv';

        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'max-age=0');
    }

    /**
     * 匯出為 JSON.
     *
     * GET /api/statistics/export/comprehensive/json
     */
    #[OA\Get(
        path: '/api/statistics/export/comprehensive/json',
        summary: '匯出綜合分析報告為 JSON',
        tags: ['statistics'],
        parameters: [
            new OA\Parameter(name: 'post_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'JSON 文件'),
        ],
    )]
    public function exportJSON(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $postId = isset($params['post_id']) ? (int) $params['post_id'] : null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $json = $this->exportService->exportToJSON($postId, $startDate, $endDate);

        $filename = 'comprehensive_report_' . date('Y-m-d_His') . '.json';

        $response->getBody()->write($json);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'max-age=0');
    }
}
