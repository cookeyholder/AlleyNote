<?php

declare(strict_types=1);

/**
 * 修復重複的類型註解腳本
 *
 * 專門處理批量修復後產生的重複類型註解問題，例如：
 * - @return array<string, mixed><string, mixed>
 * - @param array<string, mixed> $data with duplicate annotations
 * - array $tagIds with duplicate type annotations
 */

class DuplicateTypeAnnotationFixer
{
    private array $fixedFiles = [];
    private int $totalFixes = 0;

    public function __construct(private string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function fixAllFiles(): void
    {
        echo "🔧 開始修復重複類型註解...\n\n";

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $this->fixFile($file);
        }

        $this->printReport();
    }

    private function getPhpFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function fixFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixCount = 0;

        // 修復 PHPDoc 中的重複類型註解
        $patterns = [
            // @return array<string, mixed><string, mixed>
            '/@return\s+array<([^>]+)><([^>]+)>/',
            // @param array<string, mixed><string, mixed>
            '/@param\s+array<([^>]+)><([^>]+)>/',
            // @var array<string, mixed><string, mixed>
            '/@var\s+array<([^>]+)><([^>]+)>/',
        ];

        $replacements = [
            '@return array<$1>',
            '@param array<$1>',
            '@var array<$1>',
        ];

        foreach ($patterns as $index => $pattern) {
            $newContent = preg_replace($pattern, $replacements[$index], $content);
            if ($newContent !== $content) {
                $fixCount += preg_match_all($pattern, $content);
                $content = $newContent;
            }
        }

        // 修復參數中的重複註解
        // array $data/** @var array<string, mixed> */
        $content = preg_replace(
            '/array\s+(\$\w+)\/\*\*\s*@var\s+array<[^>]*>\s*\*\//',
            'array $1',
            $content,
            -1,
            $parameterFixes
        );
        $fixCount += $parameterFixes;

        // 修復方法定義中的重複註解
        // /** @var array<string, mixed> */ array $filters/** @var array<string, mixed> */
        $content = preg_replace(
            '/\/\*\*\s*@var\s+array<[^>]*>\s*\*\/\s*array\s+(\$\w+)\/\*\*\s*@var\s+array<[^>]*>\s*\*\//',
            'array $1',
            $content,
            -1,
            $methodFixes
        );
        $fixCount += $methodFixes;

        // 修復簡單的重複參數註解
        // /** @var array<string, mixed> */ array $tagIds/** @var array<string, mixed> */
        $content = preg_replace(
            '/\/\*\*\s*@var\s+[^*]+\*\/\s*array\s+(\$\w+)\/\*\*\s*@var\s+[^*]+\*\//',
            'array $1',
            $content,
            -1,
            $simpleFixes
        );
        $fixCount += $simpleFixes;

        // 修復返回類型註解中包含額外文字的情況
        // @return array<string, mixed><string, int> 權杖統計資訊
        $content = preg_replace(
            '/@return\s+array<([^>]+)><([^>]+)>\s+(.+)/',
            '@return array<$1> $3',
            $content,
            -1,
            $returnTextFixes
        );
        $fixCount += $returnTextFixes;

        // 只在有修復時才寫入檔案
        if ($fixCount > 0 && $content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = [
                'file' => $filePath,
                'fixes' => $fixCount
            ];
            $this->totalFixes += $fixCount;

            echo "✅ 修復檔案: " . basename($filePath) . " ({$fixCount} 處修復)\n";
        }
    }

    private function printReport(): void
    {
        echo "\n📊 修復報告:\n";
        echo "修復檔案數: " . count($this->fixedFiles) . "\n";
        echo "總修復數: {$this->totalFixes}\n\n";

        if (!empty($this->fixedFiles)) {
            echo "修復詳情:\n";
            foreach ($this->fixedFiles as $file) {
                $relativePath = str_replace($this->baseDir . '/', '', $file['file']);
                echo "  {$relativePath}: {$file['fixes']} 個修復\n";
            }
        }

        echo "\n✅ 重複類型註解修復完成！\n";
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $baseDir = dirname(__DIR__) . '/app';
    $fixer = new DuplicateTypeAnnotationFixer($baseDir);
    $fixer->fixAllFiles();
}
