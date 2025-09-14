<?php

declare(strict_types=1);

namespace App\Application\Controllers\Web;

use Exception;
use OpenApi\Generator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

class SwaggerController
{
    /**
     * 產生並傳回 OpenAPI JSON 規格.
     */
    public function docs(Request $request, Response $response): Response
    {
        try {
            // 暫時捕獲所有輸出，避免警告訊息影響 JSON 格式
            ob_start();

            // 掃描專案中的註解以產生 OpenAPI 規格
            $basePath = dirname(__DIR__, 3); // 調整到正確的 app 目錄
            $openapi = Generator::scan([
                $basePath . '/Application/Controllers',
                $basePath . '/Domains',
                $basePath . '/Infrastructure',
            ]);

            // 清除任何輸出的警告訊息
            ob_end_clean();

            if ($openapi === null) {
                throw new Exception('Failed to generate OpenAPI documentation');
            }

            $json = $openapi->toJson();
            $response->getBody()->write($json ?: '{"error": "Failed to serialize OpenAPI spec"}');

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (Throwable $e) {
            $error = [
                'success' => false,
                'error' => [
                    'message' => 'OpenAPI 規格產生失敗',
                    'details' => $e->getMessage(),
                ],
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            $errorJson = json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $response->getBody()->write($errorJson ?: '{"error": "JSON encoding failed"}');

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                ->withHeader('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * 顯示 Swagger UI 介面.
     */
    public function ui(Request $request, Response $response): Response
    {
        try {
            $html = $this->generateSwaggerUiHtml();
            $response->getBody()->write($html);

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'text/html; charset=UTF-8')
                ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->withHeader('Pragma', 'no-cache')
                ->withHeader('Expires', '0');
        } catch (Exception $e) {
            $errorHtml = $this->generateErrorHtml($e->getMessage());
            $response->getBody()->write($errorHtml);

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    /**
     * 產生 Swagger UI HTML.
     */
    private function generateSwaggerUiHtml(): string
    {
        return <<<HTML
            <!DOCTYPE html>
            <html lang="zh-TW">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>AlleyNote API Documentation</title>
                <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css" />
                <style>
                    .swagger-ui .topbar { display: none; }
                    .swagger-ui .info { margin: 30px 0; }
                    .swagger-ui .info .title { color: #3b4151; }
                    body { margin: 0; padding: 20px; }
                </style>
            </head>
            <body>
                <div id="swagger-ui"></div>

                <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
                <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js"></script>
                <script>
                    window.onload = function() {
                        try {
                            SwaggerUIBundle({
                                url: '/api/docs',
                                dom_id: '#swagger-ui',
                                deepLinking: true,
                                presets: [
                                    SwaggerUIBundle.presets.apis,
                                    SwaggerUIStandalonePreset
                                ],
                                plugins: [
                                    SwaggerUIBundle.plugins.DownloadUrl
                                ],
                                layout: "StandaloneLayout",
                                docExpansion: "none",
                                defaultModelsExpandDepth: 1,
                                defaultModelExpandDepth: 1
                            });
                        } catch (error) {
                            console.error('Failed to initialize Swagger UI:', error);
                        }
                    };
                </script>
            </body>
            </html>
            HTML;
    }

    /**
     * 產生錯誤頁面 HTML.
     */
    private function generateErrorHtml(string $errorMessage): string
    {
        return <<<HTML
            <!DOCTYPE html>
            <html lang="zh-TW">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>AlleyNote API - 錯誤</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background-color: #f5f5f5; }
                    .error-container {
                        max-width: 600px;
                        margin: 0 auto;
                        background: white;
                        padding: 40px;
                        border-radius: 8px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }
                    .error-title { color: #e74c3c; font-size: 24px; margin-bottom: 20px; }
                    .error-message { color: #666; line-height: 1.6; }
                    .back-link {
                        display: inline-block;
                        margin-top: 20px;
                        color: #3498db;
                        text-decoration: none;
                    }
                    .back-link:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <h1 class="error-title">API 文件載入失敗</h1>
                    <p class="error-message">無法載入 API 文件，錯誤訊息：{$errorMessage}</p>
                    <a href="/" class="back-link">← 返回首頁</a>
                </div>
            </body>
            </html>
            HTML;
    }

    /**
     * 取得 API 基本資訊.
     */
    public function info(Request $request, Response $response): Response
    {
        try {
            $info = [
                'name' => 'AlleyNote API',
                'version' => '1.0.0',
                'description' => 'AlleyNote 公布欄系統 REST API',
                'documentation' => [
                    'ui_url' => '/api/docs/ui',
                    'spec_url' => '/api/docs',
                    'format' => 'OpenAPI 3.0',
                ],
                'contact' => [
                    'name' => 'AlleyNote Team',
                    'email' => 'contact@alleynote.example.com',
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT',
                ],
                'endpoints' => [
                    'health_check' => '/api/health',
                    'documentation' => '/api/docs',
                    'ui' => '/api/docs/ui',
                    'info' => '/api/info',
                ],
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'timezone' => date_default_timezone_get(),
                    'timestamp' => date('Y-m-d H:i:s'),
                ],
            ];

            $infoJson = json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $response->getBody()->write($infoJson ?: '{"error": "JSON encoding failed"}');

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                ->withHeader('Cache-Control', 'public, max-age=3600');
        } catch (Exception $e) {
            $errorJson = json_encode(['error' => 'Failed to get API info']);
            $response->getBody()->write($errorJson ?: '{"error": "JSON encoding failed"}');

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }
    }

    /**
     * API 健康檢查端點.
     */
    public function health(Request $request, Response $response): Response
    {
        try {
            $health = [
                'status' => 'healthy',
                'service' => 'AlleyNote API Documentation',
                'timestamp' => date('Y-m-d H:i:s'),
                'uptime' => $this->getUptime(),
                'memory_usage' => [
                    'current' => $this->formatBytes(memory_get_usage(true)),
                    'peak' => $this->formatBytes(memory_get_peak_usage(true)),
                ],
            ];

            $healthJson = json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $response->getBody()->write($healthJson ?: '{"status": "unknown"}');

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8')
                ->withHeader('Cache-Control', 'no-cache');
        } catch (Exception $e) {
            $errorJson = json_encode(['status' => 'unhealthy', 'error' => $e->getMessage()]);
            $response->getBody()->write($errorJson ?: '{"status": "unknown"}');

            return $response
                ->withStatus(503)
                ->withHeader('Content-Type', 'application/json; charset=UTF-8');
        }
    }

    /**
     * 取得系統運行時間.
     */
    private function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = sys_getloadavg();
            if ($uptime !== false) {
                return sprintf('Load: %.2f, %.2f, %.2f', $uptime[0], $uptime[1], $uptime[2]);
            }
        }

        return 'N/A';
    }

    /**
     * 格式化位元組數.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return sprintf(
            '%.2f %s',
            $bytes / (1024 ** $power),
            $units[min($power, count($units) - 1)],
        );
    }
}
