<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HealthController
{
    #[OA\Get(
        path: '/api/health',
        summary: '健康檢查端點',
        tags: ['health'],
        responses: [new OA\Response(response: 200, description: '系統正常運行')],
    )]
    public function check(Request $request, Response $response): Response
    {
        $response->getBody()->write((json_encode([
            'status' => 'ok',
            'timestamp' => date('c'),
            'service' => 'AlleyNote API',
        ]) ?: '{"error": "JSON encoding failed"}'));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
