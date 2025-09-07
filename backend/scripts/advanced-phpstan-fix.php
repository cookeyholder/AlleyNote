#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 進階 PHPStan Level 10 錯誤修復工具
 * 根據錯誤類型進行分類修復
 */

class PhpStanErrorFixer
{
    private string $baseDir;
    private array $fixedFiles = [];
    private array $errorStats = [];

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function run(): void
    {
        echo "=== PHPStan Level 10 錯誤修復工具 ===\n\n";

        // 1. 分析當前錯誤狀態
        $this->analyzeCurrentErrors();

        // 2. 執行分類修復
        $this->fixArrayTypeIssues();
        $this->fixArgumentTypeIssues();
        $this->fixReturnTypeIssues();
        $this->fixParameterTypeIssues();

        // 3. 顯示修復結果
        $this->showResults();
    }

    private function analyzeCurrentErrors(): void
    {
        echo "分析當前 PHPStan 錯誤...\n";

        $command = 'docker compose exec -T web ./vendor/bin/phpstan analyse app/ --memory-limit=1G --error-format=json 2>/dev/null';
        $output = shell_exec($command);

        if ($output) {
            $data = json_decode($output, true);
            if (isset($data['files'])) {
                foreach ($data['files'] as $file => $fileData) {
                    $this->errorStats[$file] = count($fileData['messages']);
                }
            }
        }

        $totalErrors = array_sum($this->errorStats);
        echo "發現 {$totalErrors} 個錯誤，分佈在 " . count($this->errorStats) . " 個檔案中\n\n";
    }

    private function fixArrayTypeIssues(): void
    {
        echo "修復 Array 型別規格問題...\n";

        $patterns = [
            // 建構函式參數陣列
            [
                'search' => '/(\s+public\s+)(array)(\s+\$\w+,)/',
                'replace' => '$1/** @var array<string, mixed> */\n$1$2$3',
                'description' => '建構函式陣列參數'
            ],

            // 方法參數陣列
            [
                'search' => '/(\s+)(array)(\s+\$\w+)(\s*=\s*\[\])?([,\)])/',
                'replace' => '$1/** @var array<string, mixed> */ $2$3$4$5',
                'description' => '方法參數陣列'
            ],

            // PHPDoc 回傳型別
            [
                'search' => '/(\s+\*\s+@return\s+)array(\s+)/',
                'replace' => '$1array<string, mixed>$2',
                'description' => 'PHPDoc 回傳型別'
            ],

            // 屬性陣列型別
            [
                'search' => '/(\s+private\s+)(array)(\s+\$\w+;)/',
                'replace' => '$1/** @var array<string, mixed> */\n$1$2$3',
                'description' => '私有屬性陣列'
            ],
        ];

        $this->applyPatterns($patterns);
    }

    private function fixArgumentTypeIssues(): void
    {
        echo "修復參數型別問題...\n";

        $patterns = [
            // 添加明確的 lambda 回傳型別
            [
                'search' => '/fn\(([^)]+)\)\s*=>\s*/',
                'replace' => 'fn($1): array => ',
                'description' => 'Lambda 函式回傳型別'
            ],

            // 修復 array_map 型別
            [
                'search' => '/array_map\(\s*fn\(([^)]+)\)\s*=>\s*\[/',
                'replace' => 'array_map(\n                fn($1): array => [',
                'description' => 'array_map 型別修復'
            ],
        ];

        $this->applyPatterns($patterns);
    }

    private function fixReturnTypeIssues(): void
    {
        echo "修復回傳型別問題...\n";

        $patterns = [
            // 修復 array_reduce 型別註解
            [
                'search' => '/(\s+)(return\s+array_reduce\()/',
                'replace' => '$1/** @var \\1|null */\n$1$2',
                'description' => 'array_reduce 回傳型別'
            ],

            // 修復 array_slice 型別註解
            [
                'search' => '/(\s+)(return\s+array_slice\()/',
                'replace' => '$1/** @var array<int, \\1> */\n$1$2',
                'description' => 'array_slice 回傳型別'
            ],
        ];

        $this->applyPatterns($patterns);
    }

    private function fixParameterTypeIssues(): void
    {
        echo "修復參數型別規格問題...\n";

        $patterns = [
            // 添加參數型別註解
            [
                'search' => '/(\s+\*\s+@param\s+)array(\s+\$\w+)/',
                'replace' => '$1array<string, mixed>$2',
                'description' => '參數型別註解'
            ],
        ];

        $this->applyPatterns($patterns);
    }

    private function applyPatterns(array $patterns): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);
            $originalContent = $content;
            $fileChanges = 0;

            foreach ($patterns as $pattern) {
                $newContent = preg_replace(
                    $pattern['search'],
                    $pattern['replace'],
                    $content
                );

                if ($newContent !== $content) {
                    $changes = preg_match_all($pattern['search'], $content);
                    $fileChanges += $changes;
                    $content = $newContent;
                }
            }

            if ($content !== $originalContent) {
                file_put_contents($filePath, $content);
                $relativePath = str_replace($this->baseDir . '/', '', $filePath);
                $this->fixedFiles[$relativePath] = $fileChanges;
                echo "  修復: {$relativePath} ({$fileChanges} 處修改)\n";
            }
        }
    }

    private function showResults(): void
    {
        echo "\n=== 修復結果 ===\n";
        echo "修復檔案數: " . count($this->fixedFiles) . "\n";
        echo "總修改次數: " . array_sum($this->fixedFiles) . "\n\n";

        if (!empty($this->fixedFiles)) {
            echo "修復的檔案:\n";
            foreach ($this->fixedFiles as $file => $changes) {
                echo "  - {$file}: {$changes} 處修改\n";
            }
        }

        echo "\n建議執行 PHPStan 檢查修復結果:\n";
        echo "docker compose exec -T web ./vendor/bin/phpstan analyse app/ --memory-limit=1G\n";
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $baseDir = '/var/www/html/app';

    if (!is_dir($baseDir)) {
        echo "錯誤：找不到 app 目錄\n";
        exit(1);
    }

    $fixer = new PhpStanErrorFixer($baseDir);
    $fixer->run();
}
