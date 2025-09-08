<?php

declare(strict_types=1);

/**
 * 修復重複運算符語法錯誤腳本
 *
 * 專門處理以下語法錯誤：
 * - == == (重複等號)
 * - => => (重複箭頭)
 * - !! (重複感嘆號)
 * - && && (重複邏輯AND)
 * - || || (重複邏輯OR)
 */

class DuplicateOperatorsFixer
{
    private array $fixedFiles = [];
    private int $totalFixes = 0;
    private array $fixPatterns = [];

    public function __construct()
    {
        $this->initializeFixPatterns();
    }

    public function run(): void
    {
        echo "🔧 修復重複運算符語法錯誤...\n";

        $this->scanAndFixFiles();
        $this->generateSummary();

        echo "\n✅ 重複運算符修復完成！\n";
    }

    private function initializeFixPatterns(): void
    {
        $this->fixPatterns = [
            // 重複等號：== == -> ==
            [
                'pattern' => '/==\s*==/',
                'replacement' => '==',
                'description' => '重複等號'
            ],
            // 重複不等號：!= != -> !=
            [
                'pattern' => '/!=\s*!=/',
                'replacement' => '!=',
                'description' => '重複不等號'
            ],
            // 重複嚴格等號：=== === -> ===
            [
                'pattern' => '/===\s*===/',
                'replacement' => '===',
                'description' => '重複嚴格等號'
            ],
            // 重複嚴格不等號：!== !== -> !==
            [
                'pattern' => '/!==\s*!==/',
                'replacement' => '!==',
                'description' => '重複嚴格不等號'
            ],
            // 重複箭頭運算符：=> => -> =>
            [
                'pattern' => '/=>\s*=>/',
                'replacement' => '=>',
                'description' => '重複箭頭運算符'
            ],
            // 重複邏輯AND：&& && -> &&
            [
                'pattern' => '/&&\s*&&/',
                'replacement' => '&&',
                'description' => '重複邏輯AND'
            ],
            // 重複邏輯OR：|| || -> ||
            [
                'pattern' => '/\|\|\s*\|\|/',
                'replacement' => '||',
                'description' => '重複邏輯OR'
            ],
            // 重複感嘆號：!! -> !
            [
                'pattern' => '/!!\s*/',
                'replacement' => '!',
                'description' => '重複感嘆號'
            ],
            // 重複加號：++ ++ -> ++
            [
                'pattern' => '/\+\+\s*\+\+/',
                'replacement' => '++',
                'description' => '重複遞增運算符'
            ],
            // 重複減號：-- -- -> --
            [
                'pattern' => '/--\s*--/',
                'replacement' => '--',
                'description' => '重複遞減運算符'
            ],
            // 重複大於：>> -> >
            [
                'pattern' => '/>\s*>(?![=>])/',
                'replacement' => '>',
                'description' => '重複大於號'
            ],
            // 重複小於：<< -> <
            [
                'pattern' => '/<\s*<(?![<=])/',
                'replacement' => '<',
                'description' => '重複小於號'
            ],
            // 重複問號：?? ?? -> ??
            [
                'pattern' => '/\?\?\s*\?\?/',
                'replacement' => '??',
                'description' => '重複空合併運算符'
            ]
        ];
    }

    private function scanAndFixFiles(): void
    {
        $directories = [
            'app/Application',
            'app/Domains',
            'app/Infrastructure',
            'app/Shared',
            'tests'
        ];

        foreach ($directories as $directory) {
            $this->processDirectory($directory);
        }
    }

    private function processDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }

    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixesInFile = 0;

        foreach ($this->fixPatterns as $pattern) {
            $matches = [];
            if (preg_match_all($pattern['pattern'], $content, $matches)) {
                $content = preg_replace($pattern['pattern'], $pattern['replacement'], $content);
                $fixCount = count($matches[0]);
                $fixesInFile += $fixCount;

                if ($fixCount > 0) {
                    echo "  修復 {$fixCount} 個 '{$pattern['description']}' 在 " . basename($filePath) . "\n";
                }
            }
        }

        // 特殊處理：修復陣列語法錯誤
        $content = $this->fixArraySyntaxErrors($content, $filePath, $fixesInFile);

        // 特殊處理：修復字串插值錯誤
        $content = $this->fixStringInterpolationErrors($content, $filePath, $fixesInFile);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = $filePath;
            $this->totalFixes += $fixesInFile;
            echo "修復檔案: $filePath\n";
        }
    }

    private function fixArraySyntaxErrors(string $content, string $filePath, int &$fixesInFile): string
    {
        // 修復陣列中的重複箭頭：'key' => => 'value' -> 'key' => 'value'
        $pattern = '/([\'"][^\'"]*[\'"])\s*=>\s*=>\s*/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '$1 => ', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個陣列箭頭語法錯誤在 " . basename($filePath) . "\n";
        }

        // 修復陣列中的多餘逗號：,, -> ,
        $pattern = '/,\s*,+/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, ',', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個多餘逗號在 " . basename($filePath) . "\n";
        }

        return $content;
    }

    private function fixStringInterpolationErrors(string $content, string $filePath, int &$fixesInFile): string
    {
        // 修復字串插值中的語法錯誤："string {$var} more" 的格式問題
        $pattern = '/"\s*\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\s*"/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '"{$$$1}"', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個字串插值語法錯誤在 " . basename($filePath) . "\n";
        }

        // 修復多重字串連接：. . -> .
        $pattern = '/\.\s*\./';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '.', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個重複字串連接符在 " . basename($filePath) . "\n";
        }

        return $content;
    }

    private function generateSummary(): void
    {
        echo "\n📊 重複運算符修復摘要:\n";
        echo "==================================================\n";
        echo "修復的檔案數: " . count($this->fixedFiles) . "\n";
        echo "總修復數量: {$this->totalFixes}\n";

        if (count($this->fixedFiles) > 0) {
            echo "\n🎯 修復的主要模式:\n";
            foreach ($this->fixPatterns as $pattern) {
                echo "  - {$pattern['description']}: {$pattern['pattern']}\n";
            }

            echo "\n📁 修復的檔案:\n";
            foreach (array_slice($this->fixedFiles, 0, 10) as $file) {
                echo "  - " . basename($file) . "\n";
            }

            if (count($this->fixedFiles) > 10) {
                $remaining = count($this->fixedFiles) - 10;
                echo "  ... 還有 {$remaining} 個檔案\n";
            }
        }

        echo "\n💡 修復完成後建議:\n";
        echo "  1. 執行 PHPStan 檢查修復效果\n";
        echo "  2. 運行測試確保功能正常\n";
        echo "  3. 檢查是否還有其他語法錯誤\n";
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new DuplicateOperatorsFixer();
    $fixer->run();
}
