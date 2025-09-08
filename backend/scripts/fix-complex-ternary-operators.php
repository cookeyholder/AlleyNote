<?php

declare(strict_types=1);

/**
 * 修復複雜三元運算符語法錯誤腳本
 *
 * 此腳本用於修復 PHP 8.4 中不支援的複雜三元運算符語法
 * 將複雜的 is_string(is_string($var) ? $var : (string) $var) 模式
 * 簡化為 is_string($var) ? $var : (string) $var
 */

class ComplexTernaryFixer
{
    private int $fixedFiles = 0;
    private int $totalFixes = 0;
    private array $fixedFilesList = [];

    public function __construct()
    {
        echo "🔧 開始修復複雜三元運算符語法錯誤...\n\n";
    }

    /**
     * 執行修復
     */
    public function run(): void
    {
        $this->findAndFixFiles();
        $this->printSummary();
    }

    /**
     * 尋找並修復檔案
     */
    private function findAndFixFiles(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('app', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }

    /**
     * 處理單個檔案
     */
    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixCount = 0;

        // 修復模式1: is_string(is_string($var) ? $var : (string) $var) ? ... : ...
        $content = $this->fixComplexIsStringPattern($content, $fixCount);

        // 修復模式2: json_decode(複雜三元運算符, true)
        $content = $this->fixJsonDecodePattern($content, $fixCount);

        // 修復模式3: 其他複雜的巢狀三元運算符
        $content = $this->fixNestedTernaryPattern($content, $fixCount);

        if ($fixCount > 0) {
            file_put_contents($filePath, $content);
            $this->fixedFiles++;
            $this->totalFixes += $fixCount;
            $this->fixedFilesList[] = [
                'file' => $filePath,
                'fixes' => $fixCount
            ];
            echo "✅ 修復 {$filePath}: {$fixCount} 個修復\n";
        }
    }

    /**
     * 修復複雜的 is_string 模式
     */
    private function fixComplexIsStringPattern(string $content, int &$fixCount): string
    {
        // 模式: is_string(is_string($var) ? $var : (string) $var) ? is_string($var) ? $var : (string) $var : (string)is_string($var) ? $var : (string) $var
        $pattern = '/is_string\(is_string\(([^)]+)\) \? \1 : \(string\) \1\) \? is_string\(\1\) \? \1 : \(string\) \1 : \(string\)is_string\(\1\) \? \1 : \(string\) \1/';
        $replacement = 'is_string($1) ? $1 : (string) $1';

        $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($newContent !== null) {
            $content = $newContent;
            $fixCount += $count;
        }

        return $content;
    }

    /**
     * 修復 json_decode 中的複雜模式
     */
    private function fixJsonDecodePattern(string $content, int &$fixCount): string
    {
        // 尋找 json_decode(複雜三元運算符, true) 模式
        $lines = explode("\n", $content);
        $modified = false;

        foreach ($lines as $index => $line) {
            if (strpos($line, 'json_decode(is_string(is_string(') !== false) {
                // 提取變數名稱
                if (preg_match('/json_decode\(is_string\(is_string\(([^)]+)\)/', $line, $matches)) {
                    $variable = $matches[1];

                    // 檢查是否是簡單變數還是複雜表達式
                    if (preg_match('/^\$\w+(\[.*\])?$/', $variable) ||
                        preg_match('/^\([^)]+\)$/', $variable)) {

                        // 簡單替換
                        $simplifiedLine = preg_replace(
                            '/json_decode\(is_string\(is_string\(([^)]+)\)[^,]+, true\)/',
                            'json_decode(is_string($1) ? $1 : (string) $1, true)',
                            $line
                        );

                        if ($simplifiedLine !== $line) {
                            $lines[$index] = $simplifiedLine;
                            $modified = true;
                            $fixCount++;
                        }
                    } else {
                        // 複雜表達式，需要提取到變數
                        $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                        $varName = '$stringData';

                        // 如果行中已經有 $stringData，使用不同的名稱
                        if (strpos($line, '$stringData') !== false) {
                            $varName = '$convertedString';
                        }

                        $newLines = [
                            $indent . $varName . ' = is_string(' . $variable . ') ? ' . $variable . ' : (string) ' . $variable . ';',
                            preg_replace(
                                '/json_decode\(is_string\(is_string\([^,]+, true\)/',
                                'json_decode(' . $varName . ', true)',
                                $line
                            )
                        ];

                        array_splice($lines, $index, 1, $newLines);
                        $modified = true;
                        $fixCount++;
                    }
                }
            }
        }

        if ($modified) {
            $content = implode("\n", $lines);
        }

        return $content;
    }

    /**
     * 修復其他巢狀三元運算符
     */
    private function fixNestedTernaryPattern(string $content, int &$fixCount): string
    {
        // 修復剩餘的複雜三元運算符
        $patterns = [
            // 模式: ? $var : (string) $var) ? $var : (string) $var
            '/\? ([^:]+) : \(string\) \1\) \? \1 : \(string\) \1/' => '? $1 : (string) $1',

            // 其他常見的複雜模式
            '/is_string\(([^)]+)\) \? \1 : \(string\) \1\) \? is_string\(\1\) \? \1 : \(string\) \1/' => 'is_string($1) ? $1 : (string) $1',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
            if ($newContent !== null && $count > 0) {
                $content = $newContent;
                $fixCount += $count;
            }
        }

        return $content;
    }

    /**
     * 列印摘要報告
     */
    private function printSummary(): void
    {
        echo "\n📊 修復報告:\n";
        echo "總共修復了 {$this->fixedFiles} 個檔案中的 {$this->totalFixes} 個複雜三元運算符\n\n";

        if (!empty($this->fixedFilesList)) {
            echo "修復詳情:\n";
            foreach ($this->fixedFilesList as $fileInfo) {
                echo "  {$fileInfo['file']}: {$fileInfo['fixes']} 個修復\n";
            }
        }

        echo "\n✅ 修復完成！建議執行 PHPStan 和測試檢查結果。\n";
    }
}

// 執行修復
$fixer = new ComplexTernaryFixer();
$fixer->run();
