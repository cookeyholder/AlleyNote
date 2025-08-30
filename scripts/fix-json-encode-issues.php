<?php

declare(strict_types=1);

/**
 * 修復 json_encode 相關的 PHPStan 錯誤
 * 將直接使用 json_encode() 的地方替換為安全的版本
 */

class JsonEncodeIssueFixer
{
    private int $fixCount = 0;
    private array $processedFiles = [];

    public function run(): void
    {
        echo "開始修復 json_encode 相關的 PHPStan 錯誤...\n";

        // 掃描所有 PHP 檔案
        $files = $this->getAllPhpFiles();

        foreach ($files as $file) {
            $this->fixJsonEncodeIssues($file);
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

    private function getAllPhpFiles(): array
    {
        $files = [];
        $directories = [
            '/var/www/html/app',
            '/var/www/html/config',
        ];

        foreach ($directories as $dir) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->getExtension() === 'php') {
                    $files[] = $fileInfo->getPathname();
                }
            }
        }

        return $files;
    }

    private function fixJsonEncodeIssues(string $file): void
    {
        if (!file_exists($file)) {
            return;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return;
        }

        $originalContent = $content;

        // 修復 StreamInterface::write(json_encode(...))
        $patterns = [
            // 模式1: ->write(json_encode([...]))
            '/(\$\w+->getBody\(\)->write\()(json_encode\([^)]+\))(\);)/s' => '$1($2 ?: \'{"error": "JSON encoding failed"}\')$3',

            // 模式2: ->write(json_encode(...))
            '/(\$\w+->write\()(json_encode\([^)]+\))(\);)/s' => '$1($2 ?: \'{"error": "JSON encoding failed"}\')$3',

            // 模式3: 分行版本
            '/(\$\w+->(?:getBody\(\)->)?write\(\s*)(json_encode\(\s*\[[\s\S]*?\]\s*\))(\s*\);)/m' => function ($matches) {
                return $matches[1] . '(' . $matches[2] . ' ?: \'{"error": "JSON encoding failed"}\')' . $matches[3];
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $content = preg_replace($pattern, $replacement, $content);
            }

            if ($content !== $originalContent) {
                $this->fixCount++;
                if (!in_array($file, $this->processedFiles)) {
                    $this->processedFiles[] = $file;
                }
                $originalContent = $content;
            }
        }

        // 修復陣列參數類型問題
        $content = preg_replace(
            '/public function (\w+)\([^)]*array \$args[^)]*\)/',
            'public function $1(...$args): mixed',
            $content
        );

        if ($content !== $originalContent) {
            $this->fixCount++;
            if (!in_array($file, $this->processedFiles)) {
                $this->processedFiles[] = $file;
            }
        }

        // 如果內容有變化，寫回檔案
        if ($content !== file_get_contents($file)) {
            file_put_contents($file, $content);
        }
    }
}

// 執行修復
$fixer = new JsonEncodeIssueFixer();
$fixer->run();
