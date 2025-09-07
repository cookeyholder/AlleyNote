<?php

declare(strict_types=1);

/**
 * 專門修復 PHPStan Level 10 argument.type 錯誤的腳本
 * 解決參數型別不匹配問題
 */

class ArgumentTypeFixer
{
    private string $baseDir;
    private array $appliedFixes = [];

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function fixAllFiles(): void
    {
        echo "🔧 開始修復 argument.type 錯誤...\n\n";

        // 1. 修復常見的型別不匹配問題
        $this->fixCommonTypeMismatches();

        // 2. 修復陣列型別問題
        $this->fixArrayTypeMismatches();

        // 3. 修復字串型別問題
        $this->fixStringTypeMismatches();

        $this->printReport();
    }

    private function fixCommonTypeMismatches(): void
    {
        echo "修復常見型別不匹配...\n";

        // 常見的修復模式
        $patterns = [
            // 修復 explode 的第二個參數 mixed -> string
            [
                'pattern' => '/explode\(([^,]+),\s*(\$\w+)\)/',
                'check' => 'is_string($2) ? $2 : (string)$2',
                'description' => 'explode 第二個參數型別修復'
            ],

            // 修復 in_array 的第二個參數 mixed -> array
            [
                'pattern' => '/in_array\(([^,]+),\s*(\$\w+)\)/',
                'check' => 'is_array($2) ? $2 : []',
                'description' => 'in_array 第二個參數型別修復'
            ],

            // 修復 array_map 的 callback 型別
            [
                'pattern' => '/array_map\(([^,]+),\s*(\$\w+)\)/',
                'check' => 'is_array($2) ? $2 : []',
                'description' => 'array_map 第二個參數型別修復'
            ],
        ];

        $files = $this->getPhpFiles();
        foreach ($files as $file) {
            $this->applyPatternsToFile($file, $patterns);
        }
    }

    private function fixArrayTypeMismatches(): void
    {
        echo "修復陣列型別不匹配...\n";

        // 針對特定的陣列型別問題
        $specificFixes = [
            // UserActivityDTO 建構函式的 topActiveUsers 參數
            [
                'file' => 'app/Application/DTOs/Statistics/UserActivityDTO.php',
                'search' => '@param array<string, mixed> $topActiveUsers',
                'replace' => '@param array<array<string, mixed>> $topActiveUsers',
                'description' => 'UserActivityDTO topActiveUsers 型別修復'
            ],

            // 其他已知的型別不匹配
            [
                'file' => 'app/Application/DTOs/Statistics/SourceDistributionDTO.php',
                'search' => '@return array<string, mixed>',
                'replace' => '@return list<array<string, mixed>>',
                'description' => 'SourceDistributionDTO getSourceRanking 返回型別修復'
            ],
        ];

        foreach ($specificFixes as $fix) {
            $this->applySpecificFix($fix);
        }
    }

    private function fixStringTypeMismatches(): void
    {
        echo "修復字串型別不匹配...\n";

        $files = $this->getPhpFiles();
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;

            // 修復 mixed 到 string 的轉換
            $content = preg_replace_callback(
                '/([a-zA-Z_]\w*)\s*\(\s*([^,)]+),\s*(\$\w+)\s*\)/',
                function ($matches) {
                    $function = $matches[1];
                    $firstArg = $matches[2];
                    $secondArg = $matches[3];

                    // 針對需要 string 第二參數的函數
                    if (in_array($function, ['explode', 'str_replace', 'strpos', 'substr'])) {
                        return "$function($firstArg, is_string($secondArg) ? $secondArg : (string)$secondArg)";
                    }

                    return $matches[0];
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

    private function applyPatternsToFile(string $file, array $patterns): void
    {
        $content = file_get_contents($file);
        if ($content === false) return;

        $originalContent = $content;

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback(
                $pattern['pattern'],
                function ($matches) use ($pattern) {
                    // 這裡需要更複雜的邏輯來處理型別檢查
                    return $matches[0]; // 暫時返回原始內容
                },
                $content
            );
        }

        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $relativePath = str_replace($this->baseDir . '/', '', $file);
            $this->appliedFixes[$relativePath] = ($this->appliedFixes[$relativePath] ?? 0) + 1;
        }
    }

    private function applySpecificFix(array $fix): void
    {
        $filePath = $this->baseDir . '/' . $fix['file'];

        if (!file_exists($filePath)) {
            echo "檔案不存在: {$fix['file']}\n";
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false) return;

        $newContent = str_replace($fix['search'], $fix['replace'], $content);

        if ($newContent !== $content) {
            file_put_contents($filePath, $newContent);
            $this->appliedFixes[$fix['file']] = ($this->appliedFixes[$fix['file']] ?? 0) + 1;
            echo "  ✅ {$fix['description']}\n";
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
        echo "總共修復了 " . array_sum($this->appliedFixes) . " 個 argument.type 錯誤\n\n";

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
$fixer = new ArgumentTypeFixer(__DIR__ . '/..');
$fixer->fixAllFiles();
