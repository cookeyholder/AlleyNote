<?php

declare(strict_types=1);

namespace App\Application\Controllers\Health;

use App\Application\Controllers\BaseController;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class HealthController extends BaseController
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}
    #[OA\Get(
        path: '/health',
        summary: '健康檢查端點',
        tags: ['health'],
        responses: [new OA\Response(response: 200, description: '系統正常運行')],
    )]
    public function check(Request $request, Response $response): Response
    {
        try {
            $healthData = [
                'status' => 'ok',
                'timestamp' => date('c'),
                'service' => 'AlleyNote API',
                'version' => '1.0.0',
            ];

            $successResponse = $this->successResponse($healthData, '系統運行正常');
            $response->getBody()->write(($successResponse ?: ''));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger?->error('健康檢查失敗', ['error' => $e->getMessage()]);
            error_log('HealthController error: ' . $e->getMessage());

            return $this->json($response, [
                'success' => false,
                'error' => [
                    'message' => '健康檢查失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => time(),
            ], 500);
        }
    }
}
