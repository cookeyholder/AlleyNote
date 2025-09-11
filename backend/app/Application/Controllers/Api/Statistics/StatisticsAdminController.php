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

            // 執行統計重新整理
            $this->statisticsService->refreshStatistics();

            // 回傳成功回應
            $responseData = [
                'success' => true,
                'message' => '統計資料重新整理完成',
                'timestamp' => date('c'),
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error('統計重新整理失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $responseData = [
                'success' => false,
                'error' => '統計資料重新整理失敗',
                'message' => $e->getMessage(),
            ];

            $response->getBody()->write(json_encode($responseData) ?: '{"error": "JSON encoding failed"}');

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
