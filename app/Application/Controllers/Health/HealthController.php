<?php

declare(strict_types=1);

namespace App\Application\Controllers\Health;

use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HealthController extends BaseController
{
    #[OA\Get(
        path: '/health',
        summary: '健康檢查端點',
        tags: ['health'],
        responses: [new OA\Response(response: 200, description: '系統正常運行')]
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
            $response->getBody()->write($successResponse);

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = $this->handleException($e);
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
