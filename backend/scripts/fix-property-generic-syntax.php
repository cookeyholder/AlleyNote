<?php
declare(strict_types=1);

/**
 * 修復屬性範型語法錯誤的工具
 */

class PropertyGenericSyntaxFixer
{
    private int $filesFixed = 0;
    private int $totalFixes = 0;

    public function run(): void
    {
        echo "🔧 修復屬性中的無效範型語法...\n\n";

        $this->processDirectory('app/');
        $this->processDirectory('tests/');

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 修復統計\n";
        echo str_repeat("=", 50) . "\n";
        echo "修復的檔案: {$this->filesFixed}\n";
        echo "修復次數: {$this->totalFixes}\n";
        echo str_repeat("=", 50) . "\n";

        echo "✅ 屬性範型語法修復完成！\n";
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

        // 修復類屬性中的範型語法
        // 例如: private array<mixed> $config;
        $content = preg_replace_callback(
            '/(private|protected|public)\s+array<mixed>]+>\s+(\$\w+)/i',
            function ($matches) use (&$fixCount) {
                $fixCount++;
                return $matches[1] . ' array<mixed> ' . $matches[2];
            },
            $content
        );

        // 修復靜態屬性中的範型語法
        $content = preg_replace_callback(
            '/(private|protected|public)\s+static\s+array<mixed>]+>\s+(\$\w+)/i',
            function ($matches) use (&$fixCount) {
                $fixCount++;
                return $matches[1] . ' static array<mixed> ' . $matches[2];
            },
            $content
        );

        // 修復常數中的範型語法
        $content = preg_replace_callback(
            '/const\s+array<mixed>]+>\s+(\w+)/i',
            function ($matches) use (&$fixCount) {
                $fixCount++;
                return 'const array<mixed> ' . $matches[1];
            },
            $content
        );

        // 修復 PHP 8 屬性提升中的範型語法
        $content = preg_replace_callback(
            '/(public|private|protected)\s+(readonly\s+)?array<mixed>]+>\s+(\$\w+)/i',
            function ($matches) use (&$fixCount) {
                $fixCount++;
                $readonly = isset($matches[2]) ? $matches[2] : '';
                return $matches[1] . ' ' . $readonly . 'array<mixed> ' . $matches[3];
            },
            $content
        );

        // 檢查語法錯誤（特殊修復）
        $content = $this->applySpecialFixes($content, $fixCount);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->filesFixed++;
            $this->totalFixes += $fixCount;
            echo "✓ 修復 {$fixCount} 個問題在: " . basename($filePath) . "\n";
        }
    }

    private function applySpecialFixes(string $content, int &$fixCount): string
    {
        $originalContent = $content;

        // 修復任何剩餘的 `array<mixed>` 在不該出現的地方
        $patterns = [
            // 修復變數聲明中的錯誤
            '/(\$\w+\s*=\s*)array<mixed>]+>\s*\[/' => '$1array[',

            // 修復 foreach 中的錯誤
            '/foreach\s*\(\s*array<mixed>]+>\s+(\$\w+)/' => 'foreach (array<mixed> $1',

            // 修復 catch 中的錯誤
            '/catch\s*\(\s*array<mixed>]+>\s+(\$\w+)/' => 'catch (array<mixed> $1',

            // 修復其他變數類型聲明
            '/\barray]+>\s+(\$\w+)/' => 'array<mixed> $1',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $fixCount++;
            }
        }

        return $content;
    }
}

// 執行修復
$fixer = new PropertyGenericSyntaxFixer();
$fixer->run();
