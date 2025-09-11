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
        private ?LoggerInterface $logger = null
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

            $jsonResponse = json_encode($healthData);
            if ($jsonResponse === false) {
                $jsonResponse = '{"error": "JSON encoding failed"}';
            }
            $response->getBody()->write($jsonResponse);

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = json_encode([
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('c'),
            ]);
            if ($errorResponse === false) {
                $errorResponse = '{"error": "JSON encoding failed"}';
            }
            $response->getBody()->write($errorResponse);

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
