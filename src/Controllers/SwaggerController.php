<?php

declare(strict_types=1);

namespace App\Controllers;

use OpenApi\Generator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class SwaggerController
{
    /**
     * 產生並傳回 OpenAPI JSON 規格
     */
    public function docs(Request $request, Response $response): Response
    {
        try {
            // 掃描專案中的註解以產生 OpenAPI 規格
            $basePath = dirname(__DIR__, 2);
            $openapi = Generator::scan([
                $basePath . '/src/Controllers',
                $basePath . '/src/Schemas',
                $basePath . '/src/OpenApi'
            ]);

            $json = $openapi->toJson();

            $response->getBody()->write($json);
            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            $error = [
                'error' => 'OpenAPI 掃描失敗',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            $response->getBody()->write(json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }
    }

    /**
     * 顯示 Swagger UI 介面
     */
    public function ui(Request $request, Response $response): Response
    {
        $html = $this->generateSwaggerUiHtml();

        $response->getBody()->write($html);
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * 產生 Swagger UI HTML
     */
    private function generateSwaggerUiHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>AlleyNote API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css" />
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js"></script>
    <script>
    window.onload = function() {
        SwaggerUIBundle({
            url: '/api/docs',
            dom_id: '#swagger-ui',
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ]
        });
    };
    </script>
</body>
</html>
HTML;
    }

    /**
     * 取得 API 基本資訊
     */
    public function info(Request $request, Response $response): Response
    {
        $info = [
            'name' => 'AlleyNote API',
            'version' => '1.0.0',
            'description' => 'AlleyNote 公布欄系統 REST API',
            'documentation_url' => '/api/docs/ui',
            'openapi_spec_url' => '/api/docs',
            'contact' => [
                'name' => 'AlleyNote Team',
                'email' => 'contact@alleynote.example.com'
            ],
            'license' => [
                'name' => 'MIT',
                'url' => 'https://opensource.org/licenses/MIT'
            ]
        ];

        $response->getBody()->write(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }
}
