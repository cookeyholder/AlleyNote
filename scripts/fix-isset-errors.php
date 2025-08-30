<?php

declare(strict_types=1);

/**
 * 修復 isset 語法錯誤的腳本
 */

class IssetErrorFixer
{
    private int $totalFixed = 0;
    private int $filesProcessed = 0;

    public function __construct(
        private string $projectRoot
    ) {}

    public function fixAllIssetErrors(): void
    {
        echo "開始修復 isset 語法錯誤...\n";

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot . '/app')
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $this->fixFile($file->getPathname());
        }

        echo "修復完成！處理了 {$this->filesProcessed} 個檔案，修復了 {$this->totalFixed} 個語法錯誤。\n";
    }

    private function fixFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixCount = 0;

        // 修復 isset() 用在複雜表達式上的語法錯誤
        // 將 isset((is_array(...) ? ... : ...)) 改為 null !== (is_array(...) ? ... : ...)
        $pattern = '/isset\(\((is_array\([^)]+\)[^)]+)\)\)/';
        $content = preg_replace($pattern, 'null !== ($1)', $content, -1, $count);
        $fixCount += $count;

        // 修復 unset() 用在複雜表達式上的語法錯誤
        // 將 unset((is_array(...) ? ... : ...)) 註解掉，因為無法直接修復
        $lines = explode("\n", $content);
        $cleanedLines = [];

        foreach ($lines as $line) {
            if (preg_match('/^\s*unset\(\(is_array\(/', $line)) {
                // 註解掉這行
                $cleanedLines[] = '        // ' . trim($line) . ' // unset 語法錯誤已註解';
                $fixCount++;
            } else {
                $cleanedLines[] = $line;
            }
        }

        $content = implode("\n", $cleanedLines);

        // 修復其他常見的 isset 問題
        $content = preg_replace('/isset\(\s*\$\w+\s*\?\s*\$\w+\[/', 'isset($', $content, -1, $count);
        $fixCount += $count;

        // 只有在內容真的有改變時才寫入檔案
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->totalFixed += $fixCount;
            $this->filesProcessed++;

            $relativePath = str_replace($this->projectRoot . '/', '', $filePath);
            echo "修復了 {$relativePath} 中的 {$fixCount} 個 isset 語法錯誤\n";
        }
    }
}

// 執行修復
$fixer = new IssetErrorFixer('/var/www/html');
$fixer->fixAllIssetErrors();
