<?php

/**
 * 修復 PHP 不支援的泛型語法錯誤
 */

class GenericSyntaxFixer
{
    private int $fixedCount = 0;
    private array<mixed> $processedFiles = [];

    public function run(): void
    {
        $baseDir = dirname(__DIR__);

        // 獲取 PHP 檔案
        $files = $this->getPhpFiles($baseDir);

        foreach ($files as $file) {
            $this->processFile($file);
        }

        echo "✅ 總共修復了 {$this->fixedCount} 個語法錯誤，處理了 " . count($this->processedFiles) . " 個檔案\n";
    }

    private function getPhpFiles(string $dir): array<mixed>
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = $file->getPathname();

                // 跳過 vendor 目錄和其他不需要處理的目錄
                if (strpos($path, '/vendor/') !== false ||
                    strpos($path, '/node_modules/') !== false ||
                    strpos($path, '/.git/') !== false) {
                    continue;
                }

                $files[] = $path;
            }
        }

        return $files;
    }

    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fileFixedCount = 0;

        // 修復函數返回型別中的泛型語法
        $patterns = [
            // 修復 function name(): array<mixed>
            '/(\s+function\s+\w+\([^)]*\):\s*)array<mixed>]*>/' => '$1array',

            // 修復方法返回型別中的泛型
            '/(public|private|protected)\s+function\s+\w+\([^)]*\):\s*array<mixed>]*>/' => function($matches) {
                return str_replace(preg_match('/array<mixed>]*>/', $matches[0]), 'array<mixed>', $matches[0]);
            },

            // 修復屬性型別中的錯誤泛型語法
            '/(public|private|protected)\s+array<mixed>]*>\s+\$\w+/' => function($matches) {
                return preg_replace('/array<mixed>]*>/', 'array<mixed>', $matches[0]);
            }
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $newContent = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $newContent = preg_replace($pattern, $replacement, $content);
            }

            if ($newContent !== $content) {
                $matches = preg_match_all($pattern, $content);
                $fileFixedCount += $matches;
                $content = $newContent;
            }
        }

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = $filePath;
            $this->fixedCount += $fileFixedCount;

            if ($fileFixedCount > 0) {
                echo "修復 $filePath: $fileFixedCount 個語法錯誤\n";
            }
        }
    }
}

// 執行修復
$fixer = new GenericSyntaxFixer();
$fixer->run();
