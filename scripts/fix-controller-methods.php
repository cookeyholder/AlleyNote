<?php

declare(strict_types=1);

/**
 * 修復控制器方法簽名和語法錯誤
 */

class ControllerMethodFixer
{
    private int $fixCount = 0;
    private array $processedFiles = [];

    public function run(): void
    {
        echo "開始修復控制器方法簽名和語法錯誤...\n";

        $controllerFiles = [
            '/var/www/html/app/Application/Controllers/Api/V1/AttachmentController.php',
            '/var/www/html/app/Application/Controllers/Api/V1/PostController.php',
            '/var/www/html/app/Application/Controllers/Api/V1/ActivityLogController.php',
            '/var/www/html/app/Application/Controllers/TestController.php',
        ];

        foreach ($controllerFiles as $file) {
            if (file_exists($file)) {
                $this->fixControllerFile($file);
            }
        }

        echo "\n修復完成！\n";
        echo "總修復次數: {$this->fixCount}\n";
        echo "修復的檔案數: " . count($this->processedFiles) . "\n";

        if (!empty($this->processedFiles)) {
            echo "已修復的檔案:\n";
            foreach ($this->processedFiles as $file) {
                echo "  - $file\n";
            }
        }
    }

    private function fixControllerFile(string $file): void
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return;
        }

        $originalContent = $content;

        // 修復錯誤的函式簽名：public function method(...$args): mixed: string
        $content = preg_replace(
            '/public function (\w+)\(\.\.\.(\$\w+)\): mixed: string/',
            'public function $1(Request $request, Response $response, array $args): Response',
            $content
        );

        // 修復錯誤的函式簽名：public function method(...$args): mixed
        $content = preg_replace(
            '/public function (\w+)\(\.\.\.(\$\w+)\): mixed/',
            'public function $1(Request $request, Response $response, array $args): Response',
            $content
        );

        // 確保有正確的匯入
        if (strpos($content, 'use Psr\Http\Message\ResponseInterface as Response;') === false) {
            $content = str_replace(
                'use OpenApi\Attributes as OA;',
                "use OpenApi\Attributes as OA;\nuse Psr\Http\Message\ResponseInterface as Response;\nuse Psr\Http\Message\ServerRequestInterface as Request;",
                $content
            );
        }

        // 修復 successResponse 和 errorResponse 的使用方式
        $successPattern = '/return \$this->successResponse\(([^,]+),\s*([^)]+)\);/';
        $content = preg_replace_callback($successPattern, function($matches) {
            return sprintf(
                '$jsonResponse = json_encode([
                \'success\' => true,
                \'data\' => %s,
                \'message\' => %s,
            ]);
            $response->getBody()->write($jsonResponse ?: \'{"error": "JSON encoding failed"}\');
            return $response->withHeader(\'Content-Type\', \'application/json\');',
                $matches[1],
                $matches[2]
            );
        }, $content);

        $errorPattern = '/return \$this->errorResponse\(([^,]+),\s*(\d+)\);/';
        $content = preg_replace_callback($errorPattern, function($matches) {
            return sprintf(
                '$errorResponse = json_encode([
                \'success\' => false,
                \'message\' => %s,
                \'error_code\' => %s,
            ]);
            $response->getBody()->write($errorResponse ?: \'{"error": "JSON encoding failed"}\');
            return $response->withHeader(\'Content-Type\', \'application/json\')->withStatus(%s);',
                $matches[1],
                $matches[2],
                $matches[2]
            );
        }, $content);

        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $this->fixCount++;
            $this->processedFiles[] = $file;
            echo "  ✓ 已修復: $file\n";
        }
    }
}

// 執行修復
$fixer = new ControllerMethodFixer();
$fixer->run();