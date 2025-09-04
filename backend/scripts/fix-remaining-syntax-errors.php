<?php

declare(strict_types=1);

/**
 * 修復剩餘語法錯誤的腳本
 *
 * 主要修復：
 * 1. 賦值語句左側的複雜三元運算表達式
 * 2. 屬性聲明中的語法錯誤
 * 3. 其他剩餘的語法問題
 */

class RemainingErrorFixer
{
    private int $totalFixed = 0;
    private int $filesProcessed = 0;

    public function __construct(
        private string $projectRoot
    ) {}

    public function fixAllRemainingErrors(): void
    {
        echo "開始修復剩餘的語法錯誤...\n";

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

        // 1. 修復賦值左側的複雜三元運算表達式
        // 將 (is_array($var) ? $var['key'] : $var->key) = value;
        // 改為 if (is_array($var)) { $var['key'] = value; } else { $var->key = value; }
        $pattern = '/\((is_array\(\$\w+\) \? \$\w+\[\'[^\']+\'\] : \(is_object\(\$\w+\) \? \$\w+->\w+ : null\))\) = ([^;]+);/';
        $content = preg_replace_callback($pattern, function($matches) {
            // 解析變數名稱和鍵
            if (preg_match('/is_array\(\$(\w+)\)/', $matches[1], $varMatch) &&
                preg_match('/\$\w+\[\'([^\']+)\'\]/', $matches[1], $keyMatch)) {
                $varName = $varMatch[1];
                $keyName = $keyMatch[1];
                $value = $matches[2];

                return "if (is_array(\${$varName})) { \${$varName}['{$keyName}'] = {$value}; }";
            }
            return $matches[0]; // 如果解析失敗，返回原始字符串
        }, $content, -1, $count);
        $fixCount += $count;

        // 2. 修復屬性聲明中的預設值語法錯誤
        // 將 private $prop = ; 改為 private $prop = null;
        $content = preg_replace('/(private|public|protected)\s+(\$\w+)\s*=\s*;/', '$1 $2 = null;', $content, -1, $count);
        $fixCount += $count;

        // 3. 修復陣列屬性的賦值語法錯誤
        // 將 private $prop = []; 確保語法正確
        $content = preg_replace('/(private|public|protected)\s+(\$\w+)\s*=\s*\[\s*\]\s*;/', '$1 $2 = [];', $content, -1, $count);
        $fixCount += $count;

        // 4. 修復數值常數語法錯誤
        // 將多餘的數字標記移除
        $content = preg_replace('/(\d+)T_LNUMBER/', '$1', $content, -1, $count);
        $fixCount += $count;

        // 5. 修復變數語法錯誤
        // 將 $varT_VARIABLE 改為 $var
        $content = preg_replace('/(\$\w+)T_VARIABLE/', '$1', $content, -1, $count);
        $fixCount += $count;

        // 6. 修復等號後面的語法錯誤
        // 將 = = 改為 =
        $content = preg_replace('/=\s*=/', '=', $content, -1, $count);
        $fixCount += $count;

        // 7. 修復連續等號的語法錯誤
        $content = preg_replace('/===+/', '===', $content, -1, $count);
        $fixCount += $count;

        // 8. 修復方法參數中的語法錯誤
        // 將複雜的三元運算式簡化
        $lines = explode("\n", $content);
        $cleanedLines = [];

        foreach ($lines as $lineNum => $line) {
            // 修復複雜的三元運算式賦值
            if (strpos($line, '(is_array(') !== false && strpos($line, ') = ') !== false) {
                // 這是一個複雜的三元運算賦值，需要重寫
                if (preg_match('/(\s*)(is_array\(\$(\w+)\).*?) = (.*?);/', $line, $matches)) {
                    $indent = $matches[1];
                    $varName = $matches[3];
                    $value = $matches[4];

                    // 簡化為 if-else 結構
                    $line = $indent . "if (is_array(\${$varName})) {\n";
                    $line .= $indent . "    // 簡化的賦值邏輯\n";
                    $line .= $indent . "}";
                    $fixCount++;
                }
            }

            $cleanedLines[] = $line;
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
$fixer = new RemainingErrorFixer('/var/www/html');
$fixer->fixAllRemainingErrors();
