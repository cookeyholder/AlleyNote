<?php

declare(strict_types=1);

/**
 * 語法錯誤修復腳本
 */

class SyntaxErrorFixer
{
    private int $filesProcessed = 0;
    private int $issuesFixed = 0;

    public function run(): void
    {
        echo "🔧 修復語法錯誤...\n";

        $this->processAllPhpFiles();

        echo "\n✅ 語法錯誤修復完成！\n";
        echo "📊 處理了 {$this->filesProcessed} 個檔案，修正了 {$this->issuesFixed} 個問題\n";
    }

    private function processAllPhpFiles(): void
    {
        $directories = [
            __DIR__ . '/../app',
            __DIR__ . '/../tests',
        ];

        foreach ($directories as $dir) {
            $this->processDirectory($dir);
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

        // 修復不正確的 isset() 語法
        $content = $this->fixInvalidIssetSyntax($content, $hasChanges);

        // 修復不正確的 unset() 語法
        $content = $this->fixInvalidUnsetSyntax($content, $hasChanges);

        if ($hasChanges) {
            file_put_contents($filePath, $content);
            $this->issuesFixed++;
            echo "修復檔案: " . basename($filePath) . "\n";
        }

        $this->filesProcessed++;
    }

    private function fixInvalidIssetSyntax(string $content, bool &$hasChanges): string
    {
        // 修復類似 isset((is_array($var) && isset((is_array($var) ? $var['key'] : (is_object($var) ? $var->key : null)))) ? (is_array($var) ? $var['key'] : (is_object($var) ? $var->key : null)) : null) 的語法
        $pattern = '/isset\(\(is_array\(\$\w+\)\s*&&\s*isset\(\$\w+\[\'[^\']+\'\]\)\)\s*\?\s*\$\w+\[\'[^\']+\'\]\s*:\s*null\)/';

        $newContent = preg_replace_callback($pattern, function($matches) {
            // 提取變數名和鍵
            preg_match('/is_array\((\$\w+)\).*isset\((\$\w+)\[\'([^\']+)\'\]/', $matches[0], $innerMatches);
            if (count($innerMatches) >= 4) {
                return "isset({$innerMatches[2]}['{$innerMatches[3]}'])";
            }
            return $matches[0];
        }, $content);

        if ($newContent !== $content) {
            $content = $newContent;
            $hasChanges = true;
        }

        return $content;
    }

    private function fixInvalidUnsetSyntax(string $content, bool &$hasChanges): string
    {
        // 修復類似 unset((is_array($var) && isset((is_array($var) ? $var['key'] : (is_object($var) ? $var->key : null)))) ? (is_array($var) ? $var['key'] : (is_object($var) ? $var->key : null)) : null) 的語法
        $pattern = '/unset\(\(is_array\(\$\w+\)\s*&&\s*isset\(\$\w+\[\'[^\']+\'\]\)\)\s*\?\s*\$\w+\[\'[^\']+\'\]\s*:\s*null\)/';

        $newContent = preg_replace_callback($pattern, function($matches) {
            // 提取變數名和鍵
            preg_match('/is_array\((\$\w+)\).*isset\((\$\w+)\[\'([^\']+)\'\]/', $matches[0], $innerMatches);
            if (count($innerMatches) >= 4) {
                return "unset({$innerMatches[2]}['{$innerMatches[3]}'])";
            }
            return $matches[0];
        }, $content);

        if ($newContent !== $content) {
            $content = $newContent;
            $hasChanges = true;
        }

        return $content;
    }
}

// 執行修復
$fixer = new SyntaxErrorFixer();
$fixer->run();
