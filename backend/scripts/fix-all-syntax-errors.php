<?php

declare(strict_types=1);

/**
 * 修復所有語法錯誤的腳本
 *
 * 根據 Context7 的權威資料，PHP 不支援在函式簽名中使用泛型語法。
 * 這個腳本會移除所有無效的泛型語法，只保留 PHPDoc 註解。
 */

class SyntaxErrorFixer
{
    private int $totalFixed = 0;
    private int $filesProcessed = 0;

    public function __construct(
        private string $projectRoot
    ) {}

    public function fixAllSyntaxErrors(): void
    {
        echo "開始修復所有語法錯誤...\n";

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot . '/app')
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $this->fixFileErrors($file->getPathname());
        }

        echo "修復完成！處理了 {$this->filesProcessed} 個檔案，修復了 {$this->totalFixed} 個語法錯誤。\n";
    }

    private function fixFileErrors(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixCount = 0;

        // 1. 修復函式/方法簽名中的泛型語法 (如 function foo<T>())
        $content = preg_replace('/(\bfunction\s+\w+)<[^>]+>(\s*\()/i', '$1$2', $content, -1, $count);
        $fixCount += $count;

        // 2. 修復方法簽名中的泛型語法 (如 public function foo<T>())
        $content = preg_replace('/(public|private|protected)\s+(function\s+\w+)<[^>]+>(\s*\()/i', '$1 $2$3', $content, -1, $count);
        $fixCount += $count;

        // 3. 修復屬性聲明中的泛型語法 (如 private array<string> $property)
        $content = preg_replace('/(\bprivate|\bpublic|\bprotected)\s+(\w+)<[^>]+>(\s+\$\w+)/i', '$1 $2$3', $content, -1, $count);
        $fixCount += $count;

        // 4. 修復變數類型提示中的泛型語法 (如 $var<Type>)
        $content = preg_replace('/(\$\w+)<[^>]+>/i', '$1', $content, -1, $count);
        $fixCount += $count;

        // 5. 修復 return 類型中的泛型語法 (如 ): array<string> {)
        $content = preg_replace('/\):\s*\w+<[^>]+>(\s*\{)/i', '): mixed$1', $content, -1, $count);
        $fixCount += $count;

        // 6. 修復類別聲明中的泛型語法 (如 class Foo<T>)
        $content = preg_replace('/(class\s+\w+)<[^>]+>(\s+)/i', '$1$2', $content, -1, $count);
        $fixCount += $count;

        // 7. 修復介面聲明中的泛型語法 (如 interface Foo<T>)
        $content = preg_replace('/(interface\s+\w+)<[^>]+>(\s+)/i', '$1$2', $content, -1, $count);
        $fixCount += $count;

        // 8. 修復參數中的泛型語法 (如 function foo(array<string> $param))
        $content = preg_replace('/(\w+)<[^>]+>(\s+\$\w+)/i', '$1$2', $content, -1, $count);
        $fixCount += $count;

        // 9. 修復 instanceof 檢查中的泛型語法
        $content = preg_replace('/instanceof\s+(\w+)<[^>]+>/i', 'instanceof $1', $content, -1, $count);
        $fixCount += $count;

        // 10. 修復 new 語句中的泛型語法
        $content = preg_replace('/new\s+(\w+)<[^>]+>/i', 'new $1', $content, -1, $count);
        $fixCount += $count;

        // 11. 修復 use 語句中的泛型語法
        $content = preg_replace('/use\s+([\\\\]?\w+(?:\\\\[\\\\]?\w+)*)<[^>]+>/i', 'use $1', $content, -1, $count);
        $fixCount += $count;

        // 12. 修復 catch 語句中的泛型語法
        $content = preg_replace('/catch\s*\(\s*(\w+)<[^>]+>(\s+\$\w+)\)/i', 'catch ($1$2)', $content, -1, $count);
        $fixCount += $count;

        // 13. 修復多行語法錯誤的問題
        $lines = explode("\n", $content);
        $cleanedLines = [];
        $inMultiLineComment = false;

        foreach ($lines as $line) {
            // 跳過多行註解
            if (strpos($line, '/*') !== false) {
                $inMultiLineComment = true;
            }
            if ($inMultiLineComment) {
                $cleanedLines[] = $line;
                if (strpos($line, '*/') !== false) {
                    $inMultiLineComment = false;
                }
                continue;
            }

            // 跳過單行註解
            if (trim($line) === '' || strpos(trim($line), '//') === 0 || strpos(trim($line), '*') === 0) {
                $cleanedLines[] = $line;
                continue;
            }

            // 移除行內的泛型語法
            $cleanedLine = preg_replace('/<[^>]+>/', '', $line, -1, $lineCount);
            if ($lineCount > 0) {
                $fixCount += $lineCount;
            }

            $cleanedLines[] = $cleanedLine;
        }

        $content = implode("\n", $cleanedLines);

        // 只有在內容真的有改變時才寫入檔案
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->totalFixed += $fixCount;
            $this->filesProcessed++;

            $relativePath = str_replace($this->projectRoot . '/', '', $filePath);
            echo "修復了 {$relativePath} 中的 {$fixCount} 個語法錯誤\n";
        }
    }
}

// 執行修復
$fixer = new SyntaxErrorFixer('/var/www/html');
$fixer->fixAllSyntaxErrors();
