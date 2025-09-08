<?php

declare(strict_types=1);

/**
 * 修復箭頭語法錯誤腳本
 *
 * 修復常見的語法錯誤：
 * - self => :CONSTANT 應該是 self::CONSTANT
 * - ClassName => :class 應該是 ClassName::class
 * - DateTime => :RFC3339 應該是 DateTime::RFC3339
 */

class ArrowSyntaxErrorFixer
{
    private int $filesProcessed = 0;
    private int $issuesFixed = 0;
    private array $fixedPatterns = [];

    public function run(): void
    {
        echo "🔧 修復箭頭語法錯誤 (=> : 改為 ::)...\n";

        $this->processAllPhpFiles();

        echo "\n✅ 箭頭語法錯誤修復完成！\n";
        echo "📊 處理了 {$this->filesProcessed} 個檔案，修正了 {$this->issuesFixed} 個問題\n";

        if (!empty($this->fixedPatterns)) {
            echo "\n🎯 修復的模式：\n";
            foreach ($this->fixedPatterns as $pattern => $count) {
                echo "  - {$pattern}: {$count} 次\n";
            }
        }
    }

    private function processAllPhpFiles(): void
    {
        $directories = [
            __DIR__ . '/../app',
            __DIR__ . '/../tests',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->processDirectory($dir);
            }
        }
    }

    private function processDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }

    private function processFile(string $filePath): void
    {
        $originalContent = file_get_contents($filePath);
        if ($originalContent === false) {
            return;
        }

        $content = $originalContent;
        $hasChanges = false;

        // 修復各種 => : 語法錯誤
        $content = $this->fixArrowColonSyntax($content, $hasChanges, $filePath);

        if ($hasChanges) {
            file_put_contents($filePath, $content);
            echo "修復檔案: " . str_replace(__DIR__ . '/../', '', $filePath) . "\n";
        }

        $this->filesProcessed++;
    }

    private function fixArrowColonSyntax(string $content, bool &$hasChanges, string $filePath): string
    {
        $patterns = [
            // self => :CONSTANT -> self::CONSTANT
            '/\bself\s*=>\s*:([A-Z_][A-Z0-9_]*)\b/' => 'self::$1',

            // static => :CONSTANT -> static::CONSTANT
            '/\bstatic\s*=>\s*:([A-Z_][A-Z0-9_]*)\b/' => 'static::$1',

            // parent => :CONSTANT -> parent::CONSTANT
            '/\bparent\s*=>\s*:([A-Z_][A-Z0-9_]*)\b/' => 'parent::$1',

            // ClassName => :class -> ClassName::class
            '/\b([A-Z][a-zA-Z0-9_]*)\s*=>\s*:class\b/' => '$1::class',

            // ClassName => :CONSTANT -> ClassName::CONSTANT
            '/\b([A-Z][a-zA-Z0-9_]*)\s*=>\s*:([A-Z_][A-Z0-9_]*)\b/' => '$1::$2',

            // 特殊情況：DateTime => :RFC3339 等
            '/\bDateTime\s*=>\s*:([A-Z0-9_]+)\b/' => 'DateTime::$1',

            // 修復 CoversClass 屬性中的語法錯誤
            '/CoversClass\(([A-Za-z0-9_\\\\]+)\s*=>\s*:class\)/' => 'CoversClass($1::class)',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $matches = [];
                preg_match_all($pattern, $content, $matches);
                $count = count($matches[0]);

                if ($count > 0) {
                    $patternKey = str_replace(['/', '\\b', '\\s*', '\\(', '\\)', '\\[', '\\]'], '', $pattern);
                    $this->fixedPatterns[$patternKey] = ($this->fixedPatterns[$patternKey] ?? 0) + $count;
                    $this->issuesFixed += $count;
                    $hasChanges = true;
                    $content = $newContent;

                    echo "  修復模式 '{$pattern}' 在 " . basename($filePath) . " 中 {$count} 次\n";
                }
            }
        }

        return $content;
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new ArrowSyntaxErrorFixer();
    $fixer->run();
} else {
    echo "此腳本只能在命令列執行\n";
}
