<?php

declare(strict_types=1);

/**
 * 簡化的 Argument Type 錯誤修復腳本
 */

require_once __DIR__ . '/../vendor/autoload.php';

class SimpleArgumentTypeFixer
{
    private string $rootDir;
    private array $results = [];

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function run(): void
    {
        echo "🔧 簡化 Argument Type 修復腳本開始執行...\n";

        // 重點修復的檔案
        $targetFiles = [
            '/app/Application/Middleware/RateLimitMiddleware.php',
            '/app/Infrastructure/Services/RateLimitService.php',
            '/app/Shared/Validation/Validator.php'
        ];

        foreach ($targetFiles as $file) {
            $fullPath = $this->rootDir . $file;
            if (file_exists($fullPath)) {
                $this->processFile($fullPath);
            }
        }

        $this->showResults();
    }

    private function processFile(string $filePath): void
    {
        $originalContent = file_get_contents($filePath);
        $content = $originalContent;
        $changes = 0;

        // 修復 RateLimitMiddleware 的配置訪問
        if (strpos($filePath, 'RateLimitMiddleware.php') !== false) {
            // 修復配置陣列訪問
            $pattern = '/\$config\[([^\]]+)\]/';
            $replacement = 'is_array($config) && array_key_exists($1, $config) ? $config[$1] : null';
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $changes++;
            }

            // 修復類型轉換
            $content = str_replace(
                '$config[\'max_requests\']',
                'is_numeric($config[\'max_requests\'] ?? 0) ? (int)$config[\'max_requests\'] : 100',
                $content,
                $count
            );
            $changes += $count;

            $content = str_replace(
                '$config[\'time_window\']',
                'is_numeric($config[\'time_window\'] ?? 0) ? (int)$config[\'time_window\'] : 3600',
                $content,
                $count
            );
            $changes += $count;
        }

        // 修復 Validator 的字串處理
        if (strpos($filePath, 'Validator.php') !== false) {
            // 修復 str_replace 參數
            $pattern = '/str_replace\(\s*(\$\w+),\s*(\$\w+),\s*(\$\w+)\s*\)/';
            $replacement = 'str_replace(is_string($1) ? $1 : \'\', is_string($2) ? $2 : \'\', is_string($3) ? $3 : \'\')';
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $changes++;
            }
        }

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->results[] = [
                'file' => $filePath,
                'changes' => $changes
            ];
            echo "✅ 修復檔案: " . basename($filePath) . " (變更: $changes)\n";
        }
    }

    private function showResults(): void
    {
        echo "\n📊 修復結果統計:\n";
        echo "總計修復檔案數: " . count($this->results) . "\n";

        $totalChanges = array_sum(array_column($this->results, 'changes'));
        echo "總計變更數: $totalChanges\n";

        echo "\n✅ 簡化 Argument Type 修復完成！\n";
    }
}

// 執行修復
$fixer = new SimpleArgumentTypeFixer(__DIR__ . '/..');
$fixer->run();
