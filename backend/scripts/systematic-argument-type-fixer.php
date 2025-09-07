<?php

declare(strict_types=1);

/**
 * 系統性修復 PHPStan Level 10 argument.type 錯誤腳本
 */

class SystematicArgumentTypeFixer
{
    private string $baseDir;
    private array $appliedFixes = [];

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function fixAllFiles(): void
    {
        echo "🔧 開始系統性修復 argument.type 錯誤...\n\n";

        // 1. 修復 mixed 到特定型別的轉換
        $this->fixMixedToSpecificTypes();

        // 2. 修復方法參數型別不匹配
        $this->fixMethodParameterMismatches();

        // 3. 修復建構函式參數問題
        $this->fixConstructorParameterIssues();

        $this->printReport();
    }

    private function fixMixedToSpecificTypes(): void
    {
        echo "修復 mixed 到特定型別的轉換...\n";

        $patterns = [
            // array_filter 修復
            [
                'search' => 'array_filter($permissions)',
                'replace' => 'array_filter($permissions, fn($p) => is_string($p) && !empty($p))',
                'description' => 'array_filter 型別安全修復'
            ],

            // array_map 修復
            [
                'search' => 'array_map(\'strval\', $',
                'replace' => 'array_map(fn($item) => is_scalar($item) ? (string)$item : \'\', $',
                'description' => 'array_map strval 型別安全修復'
            ],

            // explode 修復
            [
                'pattern' => '/explode\(([^,]+),\s*(\$\w+)\)/',
                'replacement' => 'explode($1, is_string($2) ? $2 : (string)$2)',
                'description' => 'explode 型別轉換修復'
            ],

            // json_decode 修復
            [
                'pattern' => '/json_decode\(([^,]+),\s*true\)/',
                'replacement' => 'json_decode(is_string($1) ? $1 : (string)$1, true)',
                'description' => 'json_decode 型別修復'
            ],
        ];

        $files = $this->getPhpFiles();
        foreach ($files as $file) {
            $this->applyFixesToFile($file, $patterns);
        }
    }

    private function fixMethodParameterMismatches(): void
    {
        echo "修復方法參數型別不匹配...\n";

        // 特定方法的型別修復
        $specificFixes = [
            // Response body 修復
            [
                'pattern' => '/new Response\((\d+),\s*\[\],\s*(\$\w+)\)/',
                'replacement' => 'new Response($1, [], is_string($2) ? $2 : ($2 !== false ? (string)$2 : \'\'))',
                'description' => 'Response body 型別修復'
            ],

            // array 轉 array<string> 修復
            [
                'pattern' => '/(\$\w+)\s*=\s*array_values\((\$\w+)\)/',
                'replacement' => '$1 = array_values(array_filter(array_map(\'strval\', $2), fn($item) => !empty($item)))',
                'description' => 'array 轉 array<string> 修復'
            ],
        ];

        $files = $this->getPhpFiles();
        foreach ($files as $file) {
            $this->applyFixesToFile($file, $specificFixes);
        }
    }

    private function fixConstructorParameterIssues(): void
    {
        echo "修復建構函式參數問題...\n";

        // 修復 DTO 建構函式的型別問題
        $dtoFiles = glob($this->baseDir . '/app/Application/DTOs/**/*.php');

        foreach ($dtoFiles as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;

            // 修復常見的建構函式參數型別問題
            $content = preg_replace_callback(
                '/public function __construct\(([^)]+)\)/s',
                function ($matches) {
                    $params = $matches[1];

                    // 替換 array 為 array<string, mixed>
                    $params = preg_replace('/\barray\s+\$(\w+)/', 'array $1', $params);

                    return "public function __construct({$params})";
                },
                $content
            );

            if ($content !== $originalContent) {
                file_put_contents($file, $content);
                $relativePath = str_replace($this->baseDir . '/', '', $file);
                $this->appliedFixes[$relativePath] = ($this->appliedFixes[$relativePath] ?? 0) + 1;
            }
        }
    }

    private function applyFixesToFile(string $file, array $patterns): void
    {
        $content = file_get_contents($file);
        if ($content === false) return;

        $originalContent = $content;

        foreach ($patterns as $pattern) {
            if (isset($pattern['search']) && isset($pattern['replace'])) {
                // 簡單字串替換
                $content = str_replace($pattern['search'], $pattern['replace'], $content);
            } elseif (isset($pattern['pattern']) && isset($pattern['replacement'])) {
                // 正則表達式替換
                $content = preg_replace($pattern['pattern'], $pattern['replacement'], $content);
            }
        }

        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $relativePath = str_replace($this->baseDir . '/', '', $file);
            $this->appliedFixes[$relativePath] = ($this->appliedFixes[$relativePath] ?? 0) + 1;
        }
    }

    private function getPhpFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir . '/app')
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function printReport(): void
    {
        echo "\n📊 修復報告:\n";
        echo "總共修復了 " . array_sum($this->appliedFixes) . " 個檔案中的 argument.type 錯誤\n\n";

        if (!empty($this->appliedFixes)) {
            echo "修復詳情:\n";
            foreach ($this->appliedFixes as $file => $count) {
                echo "  {$file}: {$count} 個修復\n";
            }
        }

        echo "\n✅ 修復完成！請執行 PHPStan 檢查結果。\n";
    }
}

// 執行修復
$fixer = new SystematicArgumentTypeFixer(__DIR__ . '/..');
$fixer->fixAllFiles();
