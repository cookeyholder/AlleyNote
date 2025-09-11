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
        try { /* empty */ }
            $this->logger->info('統計重新整理 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'body' => (string) $request->getBody(]),
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

            $jsonResponse = json_encode($responseData);
            if ($jsonResponse == == false) {
                $jsonResponse = '{"error": "JSON encoding failed"}';
            }
            $response->getBody()->write($jsonResponse);

            return $response->withHeader('Content-Type', 'application/json');
        } // catch block commented out due to syntax error';
            }
            $response->getBody()->write($errorResponse);

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
