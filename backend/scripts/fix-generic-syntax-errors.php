<?php
declare(strict_types=1);

/**
 * 修復無效範型語法錯誤的工具
 */

class GenericSyntaxFixer
{
    private int $filesFixed = 0;
    private int $totalFixes = 0;

    public function run(): void
    {
        echo "🔧 修復無效的範型語法...\n\n";

        $this->processDirectory('app/');
        $this->processDirectory('tests/');

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 修復統計\n";
        echo str_repeat("=", 50) . "\n";
        echo "修復的檔案: {$this->filesFixed}\n";
        echo "修復次數: {$this->totalFixes}\n";
        echo str_repeat("=", 50) . "\n";

        echo "✅ 範型語法修復完成！\n";
    }

    private function processDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->fixFile($file->getPathname());
            }
        }
    }

    private function fixFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixCount = 0;

        // 修復函數參數中的範型語法
        // 例如: function foo(array<mixed> $param)
        $content = preg_replace_callback(
            '/(\w+\s*\(.*?)array<mixed>]+>\s+(\$\w+)/i',
            function ($matches) use (&$fixCount) {
                $fixCount++;
                return $matches[1] . 'array<mixed> ' . $matches[2];
            },
            $content
        );

        // 修復方法參數中的範型語法
        // 例如: public function foo(array<mixed> $param)
        $content = preg_replace_callback(
            '/(public|private|protected)\s+function\s+\w+\s*\([^)]*array<mixed>]+>\s+(\$\w+)/i',
            function ($matches) use (&$fixCount) {
                $fixCount++;
                // 將 array<mixed> $param 替換為 array<mixed> $param
                return preg_replace('/array<mixed>]+>\s+/', 'array<mixed> ', $matches[0]);
            },
            $content
        );

        // 修復靜態方法參數中的範型語法
        $content = preg_replace_callback(
            '/(public|private|protected)\s+static\s+function\s+\w+\s*\([^)]*array<mixed>]+>\s+(\$\w+)/i',
            function ($matches) use (&$fixCount) {
                $fixCount++;
                return preg_replace('/array<mixed>]+>\s+/', 'array<mixed> ', $matches[0]);
            },
            $content
        );

        // 修復返回類型中的範型語法（僅在函數/方法簽名中）
        // 例如: ): array<mixed> 但要保留 @return array<mixed>
        $content = preg_replace_callback(
            '/(\):\s*)array<mixed>]+>/',
            function ($matches) use (&$fixCount) {
                $fixCount++;
                return $matches[1] . 'array<mixed>';
            },
            $content
        );

        // 確保不修復註解中的範型語法
        // 保持 @param array<mixed> $param 和 @return array<mixed> 不變

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->filesFixed++;
            $this->totalFixes += $fixCount;
            echo "✓ 修復 {$fixCount} 個問題在: " . basename($filePath) . "\n";
        }
    }
}

// 執行修復
$fixer = new GenericSyntaxFixer();
$fixer->run();
