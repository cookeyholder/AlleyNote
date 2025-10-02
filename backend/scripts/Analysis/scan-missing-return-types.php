<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Analysis;

require_once __DIR__ . '/../ScriptBootstrap.php';

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * 掃描缺少回傳型別宣告的函式
 */
class ReturnTypeScanner
{
    private array $results = [];
    private int $totalFunctions = 0;
    private int $missingReturnTypes = 0;

    public function scan(string $directory): void
    {
        echo "Scanning directory: {$directory}\n";
        
        if (!is_dir($directory)) {
            echo "Directory does not exist!\n";
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i');

        $fileCount = 0;
        foreach ($phpFiles as $file) {
            if ($file->isFile()) {
                $fileCount++;
                $this->scanFile($file->getPathname());
            }
        }
        
        echo "Total PHP files scanned: {$fileCount}\n";
    }

    private function scanFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        // 跳過測試檔案
        if (str_contains($filePath, '/tests/') || str_contains($filePath, 'Test.php')) {
            return;
        }

        // 跳過 vendor 目錄
        if (str_contains($filePath, '/vendor/')) {
            return;
        }

        // 跳過 scripts 目錄（腳本檔案）
        if (str_contains($filePath, '/scripts/')) {
            return;
        }

        // 找出所有函式定義
        // 匹配各種函式定義形式
        $patterns = [
            // public/protected/private [static] function name(): returnType
            '/^\s*(public|protected|private)\s+(static\s+)?function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*:?\s*([^\n{;]*)/m',
            // static public/protected/private function name(): returnType  
            '/^\s*static\s+(public|protected|private)\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*:?\s*([^\n{;]*)/m',
        ];
        
        $allMatches = [];
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
            $allMatches = array_merge($allMatches, $matches);
        }

        foreach ($allMatches as $match) {
            // 判斷是第一個還是第二個 pattern
            if (count($match) === 5) {
                // 第一個 pattern
                $visibility = $match[1][0];
                $functionName = $match[3][0];
                $returnType = trim($match[4][0]);
            } else {
                // 第二個 pattern (static 在前)
                $visibility = 'static ' . $match[1][0];
                $functionName = $match[2][0];
                $returnType = trim($match[3][0]);
            }

            // 跳過建構子和析構子
            if (in_array($functionName, ['__construct', '__destruct', '__clone'])) {
                continue;
            }
            
            $this->totalFunctions++;

            // 檢查是否有回傳型別
            if (empty($returnType)) {
                $this->missingReturnTypes++;
                $lineNumber = $this->getLineNumber($content, $match[0][1]);
                
                $relativePath = str_replace('/var/www/html/', '', $filePath);
                
                if (!isset($this->results[$relativePath])) {
                    $this->results[$relativePath] = [];
                }
                
                $this->results[$relativePath][] = [
                    'function' => $functionName,
                    'visibility' => $visibility,
                    'line' => $lineNumber,
                ];
            }
        }
    }

    private function getLineNumber(string $content, int $offset): int
    {
        return substr_count($content, "\n", 0, $offset) + 1;
    }

    public function generateReport(): string
    {
        $report = "# 缺少回傳型別宣告的函式掃描報告\n\n";
        $report .= "**生成時間**: " . date('Y-m-d H:i:s') . "\n\n";
        
        $report .= "## 📊 總覽\n\n";
        $report .= "- **總函式數**: {$this->totalFunctions}\n";
        $report .= "- **缺少回傳型別**: {$this->missingReturnTypes}\n";
        $coverage = $this->totalFunctions > 0 
            ? round((($this->totalFunctions - $this->missingReturnTypes) / $this->totalFunctions) * 100, 2)
            : 0;
        $report .= "- **回傳型別覆蓋率**: {$coverage}%\n\n";

        if (empty($this->results)) {
            $report .= "✅ 所有函式都有回傳型別宣告！\n";
            return $report;
        }

        // 按照檔案分組並排序
        ksort($this->results);

        $report .= "## 📋 詳細清單\n\n";
        
        $count = 1;
        foreach ($this->results as $file => $functions) {
            $report .= "### {$count}. {$file}\n\n";
            $report .= "缺少回傳型別的函式: " . count($functions) . " 個\n\n";
            
            foreach ($functions as $func) {
                $report .= "- **Line {$func['line']}**: `{$func['visibility']} function {$func['function']}()`\n";
            }
            
            $report .= "\n";
            $count++;
        }

        // 統計最需要修復的檔案（前10個）
        $fileStats = [];
        foreach ($this->results as $file => $functions) {
            $fileStats[$file] = count($functions);
        }
        arsort($fileStats);
        $topFiles = array_slice($fileStats, 0, 10, true);

        $report .= "## 🔥 最需要修復的檔案（前10個）\n\n";
        $rank = 1;
        foreach ($topFiles as $file => $count) {
            $report .= "{$rank}. **{$file}**: {$count} 個函式\n";
            $rank++;
        }

        return $report;
    }

    public function saveReport(string $outputPath): void
    {
        $report = $this->generateReport();
        file_put_contents($outputPath, $report);
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getMissingCount(): int
    {
        return $this->missingReturnTypes;
    }
}

// 執行掃描
echo "🔍 開始掃描缺少回傳型別的函式...\n";

$scanner = new ReturnTypeScanner();
$scanner->scan(__DIR__ . '/../../app');

$outputPath = __DIR__ . '/../../storage/missing-return-types.md';
$scanner->saveReport($outputPath);

echo $scanner->generateReport();
echo "\n📝 詳細報告已儲存至: {$outputPath}\n";
echo "✅ 掃描完成！\n";
