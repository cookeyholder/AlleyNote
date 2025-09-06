#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 強化版語法錯誤修復腳本
 * 
 * 專門處理自動化修復過程中產生的文檔註解語法錯誤
 */

class AdvancedSyntaxFixer
{
    private string $baseDir;
    private array $fixedFiles = [];
    private int $totalFixes = 0;

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🔧 啟動強化版語法錯誤修復器...\n\n";

        // 獲取有語法錯誤的檔案
        $errorFiles = $this->getSyntaxErrorFiles();
        
        foreach ($errorFiles as $file) {
            $this->fixFile($file);
        }

        $this->generateReport();
    }

    private function getSyntaxErrorFiles(): array
    {
        echo "🔍 掃描語法錯誤...\n";

        $command = './vendor/bin/phpstan analyse --memory-limit=1G 2>&1';
        $output = shell_exec($command);

        if (!$output) {
            echo "無法執行 PHPStan\n";
            return [];
        }

        $files = [];
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            if (preg_match('/^\s*Line\s+(.+\.php)/', $line, $matches)) {
                $file = trim($matches[1]);
                if (!in_array($file, $files)) {
                    $files[] = $file;
                }
            }
        }

        echo "發現 " . count($files) . " 個有語法錯誤的檔案\n\n";
        return $files;
    }

    private function fixFile(string $file): void
    {
        $filePath = $this->baseDir . '/' . $file;
        if (!file_exists($filePath)) {
            echo "⚠️  檔案不存在: $file\n";
            return;
        }

        echo "🔧 修復檔案: $file\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復各種語法錯誤模式
        $content = $this->fixDocBlockErrors($content);
        $content = $this->fixOrphanedAnnotations($content);
        $content = $this->fixMalformedDocBlocks($content);
        $content = $this->fixDuplicateAnnotations($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = $file;
            $this->totalFixes++;
            echo "  ✅ 已修復\n";
        } else {
            echo "  ℹ️  無需修復\n";
        }

        echo "\n";
    }

    private function fixDocBlockErrors(string $content): string
    {
        // 修復錯誤的文檔塊結構，如：
        // */
        // * @param ...
        $content = preg_replace('/(\s*\*\/)\s*\n\s*\*\s*@(param|return)\s+([^\n]+)/m', '$1', $content);

        // 修復孤立的參數註解
        $content = preg_replace('/^(\s*)\*\s*@(param|return)\s+([^\n]+)\n(\s*)(public|private|protected|function)/m', '$1/**\n$1 * @$2 $3\n$1 */\n$4$5', $content);

        return $content;
    }

    private function fixOrphanedAnnotations(string $content): string
    {
        // 修復孤立的 @param 行（不在文檔塊中）
        $lines = explode("\n", $content);
        $fixedLines = [];
        $i = 0;

        while ($i < count($lines)) {
            $line = $lines[$i];
            
            // 檢查是否為孤立的註解
            if (preg_match('/^\s*\*\s*@(param|return)\s+/', $line) && 
                ($i === 0 || !preg_match('/^\s*\*/', $lines[$i-1]))) {
                
                // 找到對應的方法
                $methodLine = $this->findNextMethodLine($lines, $i);
                
                if ($methodLine !== -1) {
                    // 創建文檔塊
                    $indent = $this->getIndentation($lines[$methodLine]);
                    $fixedLines[] = $indent . '/**';
                    $fixedLines[] = $line;
                    
                    // 收集所有連續的註解
                    $j = $i + 1;
                    while ($j < count($lines) && preg_match('/^\s*\*\s*@(param|return)\s+/', $lines[$j])) {
                        $fixedLines[] = $lines[$j];
                        $j++;
                    }
                    
                    $fixedLines[] = $indent . ' */';
                    $i = $j - 1;
                } else {
                    $fixedLines[] = $line;
                }
            } else {
                $fixedLines[] = $line;
            }
            
            $i++;
        }

        return implode("\n", $fixedLines);
    }

    private function fixMalformedDocBlocks(string $content): string
    {
        // 修復格式錯誤的文檔塊
        $content = preg_replace('/(\s*)\/\*\*\s*\n\s*\*\s*\n\s*\*\s*@(param|return)/m', '$1/**\n$1 * @$2', $content);
        
        // 修復缺少開始的文檔塊
        $content = preg_replace('/^(\s*)\*\s*@(param|return)\s+([^\n]+)(\n(?:\s*\*\s*@(?:param|return)\s+[^\n]+\n)*)\s*(public|private|protected)/m', '$1/**\n$1 * @$2 $3$4$1 */\n$1$5', $content);

        return $content;
    }

    private function fixDuplicateAnnotations(string $content): string
    {
        // 移除重複的 @param 註解
        $content = preg_replace('/(\s*\*\s*@param\s+[^\n]+)\n\s*\*\s*@param\s+[^\n]+(?=\n)/m', '$1', $content);
        
        // 移除重複的 @return 註解
        $content = preg_replace('/(\s*\*\s*@return\s+[^\n]+)\n\s*\*\s*@return\s+[^\n]+(?=\n)/m', '$1', $content);

        return $content;
    }

    private function findNextMethodLine(array $lines, int $startIndex): int
    {
        for ($i = $startIndex; $i < count($lines) && $i < $startIndex + 10; $i++) {
            if (preg_match('/\s*(public|private|protected)\s+(static\s+)?function\s+/', $lines[$i])) {
                return $i;
            }
        }
        return -1;
    }

    private function getIndentation(string $line): string
    {
        preg_match('/^(\s*)/', $line, $matches);
        return $matches[1] ?? '    ';
    }

    private function generateReport(): void
    {
        echo "\n📋 語法修復報告\n";
        echo "=" . str_repeat("=", 50) . "\n";
        echo "總修復檔案數: {$this->totalFixes}\n\n";

        if (!empty($this->fixedFiles)) {
            echo "修復的檔案:\n";
            foreach ($this->fixedFiles as $file) {
                echo "  • $file\n";
            }
        }

        echo "\n✅ 語法錯誤修復完成！\n";
        echo "💡 現在可以繼續處理其他類型錯誤\n";
    }
}

// 執行修復
$fixer = new AdvancedSyntaxFixer();
$fixer->run();
