<?php

declare(strict_types=1);

// 載入 Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

use App\Application;
use App\Infrastructure\Http\ServerRequestFactory;
use OpenApi\Generator;

// 設定錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 設定時區
date_default_timezone_set('Asia/Taipei');

// 取得請求資訊
$requestUri = (is_array($_SERVER) ? $_SERVER['REQUEST_URI'] : (is_object($_SERVER) ? $_SERVER->REQUEST_URI : null)) ?? '/';
$requestMethod = (is_array($_SERVER) ? $_SERVER['REQUEST_METHOD'] : (is_object($_SERVER) ? $_SERVER->REQUEST_METHOD : null)) ?? 'GET';

// 移除查詢參數
$path = parse_url($requestUri, PHP_URL_PATH);

// 檢查是否為特殊路由（文件系統等），使用舊的路由處理
$specialRoutes = ['/api/docs', '/api/docs/ui', '/docs'];
$useNewRoutingSystem = !in_array($path, $specialRoutes, true);

// 路由處理
try {
    if ($useNewRoutingSystem) {
        // 使用新的路由系統
        try {
            $application = new Application();
            $request = ServerRequestFactory::fromGlobals();
            $response = $application->run($request);

            // 輸出回應
            http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header($name . ': ' . $value, false);
                }
            }

            echo $response->getBody();
            exit;
        } catch (Exception $e) {
            // 如果新路由系統失敗，回退到舊系統
            error_log("新路由系統錯誤: " . $e->getMessage());
            // 繼續使用下方的舊路由處理
        }
    }

    // 舊的路由處理系統（向後相容）
    switch ($path) {
        case '/api/docs':
            // API 文件 JSON 格式
            generateApiDocs();
            break;

        case '/api/docs/ui':
            // Swagger UI 介面
            showSwaggerUI();
            break;

        case '/docs':
            // 重新導向到 Swagger UI
            header('Location: /api/docs/ui', true, 302);
            exit;

        case '/':
        default:
            // 預設首頁 - 顯示系統狀態
            showHomePage();
            break;
    }
} catch (Exception $e) {
    // 錯誤處理
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'timestamp' => (new DateTime())->format('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * 產生 API 文件
 */
function generateApiDocs(): void
{
    try {
        // 檢查是否存在預生成的文件
        $preGeneratedFile = __DIR__ . '/api-docs.json';

        if (file_exists($preGeneratedFile)) {
            // 使用預生成的文件
            $jsonContent = file_get_contents($preGeneratedFile);
            $apiDoc = json_decode($jsonContent, true);

            // 驗證文件格式
            if ($apiDoc && isset((is_array($apiDoc) ? $apiDoc['openapi'] : (is_object($apiDoc) ? $apiDoc->openapi : null))) && isset((is_array($apiDoc) ? $apiDoc['paths'] : (is_object($apiDoc) ? $apiDoc->paths : null)))) {
                header('Content-Type: application/json');
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

                echo $jsonContent;
                return;
            }
        }

        // 如果沒有預生成文件或文件無效，動態生成
        $basePath = dirname(__DIR__);
        $scanPaths = [
            $basePath . '/src/Controllers',
            $basePath . '/src/Schemas',
            $basePath . '/src/OpenApi'
        ];

        $openapi = Generator::scan($scanPaths);
        $json = $openapi->toJson();

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

        echo $json;
    } catch (Exception $e) {
        // 錯誤處理 - 返回基本文件
        $errorDoc = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'AlleyNote API',
                'version' => '1.0.0',
                'description' => 'AlleyNote 公布欄系統 API 文件 (錯誤模式)'
            ],
            'servers' => [
                [
                    'url' => 'http://localhost/api',
                    'description' => 'Development server'
                ]
            ],
            'paths' => [],
            'components' => [
                'schemas' => [
                    'Error' => [
                        'type' => 'object',
                        'properties' => [
                            'error' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'debug' => ['type' => 'string']
                        ]
                    ]
                ]
            ],
            'x-generation-error' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ];

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

        echo json_encode($errorDoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
/**
 * 顯示 Swagger UI
 */
function showSwaggerUI(): void
{
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>API Documentation
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

    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
}

/**
 * 顯示首頁
 */
function showHomePage(): void
{
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'AlleyNote 公布欄系統開發環境已成功設定',
        'timestamp' => (new DateTime('now', new DateTimeZone('Asia/Taipei')))->format('c'),
        'php_version' => PHP_VERSION,
        'available_endpoints' => [
            'swagger_ui' => '/api/docs/ui',
            'api_docs' => '/api/docs',
            'docs_redirect' => '/docs'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
