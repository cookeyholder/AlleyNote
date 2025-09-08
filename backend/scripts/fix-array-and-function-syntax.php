<?php

declare(strict_types=1);

/**
 * 修復陣列和函數調用語法錯誤腳本
 *
 * 專門處理以下語法錯誤：
 * - unexpected T_DOUBLE_ARROW, expecting ')'
 * - unexpected ']', expecting ')'
 * - Cannot use empty array elements in arrays
 * - unexpected T_STRING, expecting ')'
 * - unexpected '}', expecting EOF
 */

class ArrayAndFunctionSyntaxFixer
{
    private array $fixedFiles = [];
    private int $totalFixes = 0;
    private array $fixPatterns = [];
    private array $errorStats = [];

    public function __construct()
    {
        $this->initializeFixPatterns();
    }

    public function run(): void
    {
        echo "🔧 修復陣列和函數調用語法錯誤...\n";

        $this->scanAndFixFiles();
        $this->generateSummary();

        echo "\n✅ 陣列和函數語法修復完成！\n";
    }

    private function initializeFixPatterns(): void
    {
        $this->fixPatterns = [
            // 修復陣列中錯誤的雙箭頭語法
            'array_double_arrow' => [
                'pattern' => '/\[\s*([^,\[\]]*)\s*=>\s*=>\s*([^,\[\]]*)\s*\]/',
                'replacement' => '[$1 => $2]',
                'description' => '陣列雙箭頭語法錯誤'
            ],
            // 修復函數調用中的錯誤陣列語法
            'function_array_syntax' => [
                'pattern' => '/(\w+)\(\s*\[\s*([^,\[\]]*)\s*=>\s*\]\s*\)/',
                'replacement' => '$1([$2])',
                'description' => '函數參數陣列語法錯誤'
            ],
            // 修復空陣列元素
            'empty_array_elements' => [
                'pattern' => '/\[\s*,\s*([^,\[\]]+)\s*\]/',
                'replacement' => '[$1]',
                'description' => '空陣列元素'
            ],
            // 修復陣列中多餘的逗號
            'trailing_comma_array' => [
                'pattern' => '/\[\s*([^,\[\]]+)\s*,\s*,\s*\]/',
                'replacement' => '[$1]',
                'description' => '陣列多餘逗號'
            ],
            // 修復函數調用中的錯誤語法
            'function_call_syntax' => [
                'pattern' => '/(\w+)\(\s*\]\s*\)/',
                'replacement' => '$1()',
                'description' => '函數調用語法錯誤'
            ],
            // 修復陣列解構語法錯誤
            'array_destructure' => [
                'pattern' => '/\[\s*([^,\[\]]*)\s*\]\s*=>\s*([^,;]+);/',
                'replacement' => '[$1] = $2;',
                'description' => '陣列解構語法錯誤'
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

        // 基本模式修復
        foreach ($this->fixPatterns as $patternName => $pattern) {
            $matches = [];
            if (preg_match_all($pattern['pattern'], $content, $matches)) {
                $content = preg_replace($pattern['pattern'], $pattern['replacement'], $content);
                $fixCount = count($matches[0]);
                $fixesInFile += $fixCount;
                $this->errorStats[$patternName] = ($this->errorStats[$patternName] ?? 0) + $fixCount;

                if ($fixCount > 0) {
                    echo "  修復 {$fixCount} 個 '{$pattern['description']}' 在 " . basename($filePath) . "\n";
                }
            }
        }

        // 專門處理複雜的陣列語法錯誤
        $content = $this->fixComplexArraySyntax($content, $filePath, $fixesInFile);

        // 專門處理函數調用語法錯誤
        $content = $this->fixFunctionCallSyntax($content, $filePath, $fixesInFile);

        // 專門處理字串插值和連接錯誤
        $content = $this->fixStringAndConcatenationErrors($content, $filePath, $fixesInFile);

        // 專門處理條件語句和循環語法錯誤
        $content = $this->fixControlStructureSyntax($content, $filePath, $fixesInFile);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = $filePath;
            $this->totalFixes += $fixesInFile;
            echo "修復檔案: $filePath\n";
        }
    }

    private function fixComplexArraySyntax(string $content, string $filePath, int &$fixesInFile): string
    {
        // 修復：[,] -> []
        $pattern = '/\[\s*,\s*\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '[]', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個空陣列逗號錯誤在 " . basename($filePath) . "\n";
        }

        // 修復：['key' => => 'value'] -> ['key' => 'value']
        $pattern = '/\[\s*([\'"][^\'"]*[\'"])\s*=>\s*=>\s*([^,\]]*)\s*\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '[$1 => $2]', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個陣列雙箭頭錯誤在 " . basename($filePath) . "\n";
        }

        // 修復：array(,value) -> array(value)
        $pattern = '/array\(\s*,\s*([^,)]+)\s*\)/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, 'array($1)', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個 array() 語法錯誤在 " . basename($filePath) . "\n";
        }

        // 修復：[value,] -> [value]
        $pattern = '/\[\s*([^,\[\]]+)\s*,\s*\]/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '[$1]', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個尾隨逗號錯誤在 " . basename($filePath) . "\n";
        }

        return $content;
    }

    private function fixFunctionCallSyntax(string $content, string $filePath, int &$fixesInFile): string
    {
        // 修復：function(]) -> function()
        $pattern = '/(\w+)\(\s*\]\s*\)/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '$1()', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個函數調用語法錯誤在 " . basename($filePath) . "\n";
        }

        // 修復：function([key =>]) -> function(['key' => null])
        $pattern = '/(\w+)\(\s*\[\s*([^,\[\]]*)\s*=>\s*\]\s*\)/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '$1([$2 => null])', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個函數參數陣列錯誤在 " . basename($filePath) . "\n";
        }

        // 修復：function(,arg) -> function(arg)
        $pattern = '/(\w+)\(\s*,\s*([^,)]+)\s*\)/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '$1($2)', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個函數參數逗號錯誤在 " . basename($filePath) . "\n";
        }

        return $content;
    }

    private function fixStringAndConcatenationErrors(string $content, string $filePath, int &$fixesInFile): string
    {
        // 修復：'string' . . 'string' -> 'string' . 'string'
        $pattern = '/([\'"][^\'"]*[\'"])\s*\.\s*\.\s*([\'"][^\'"]*[\'"])/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '$1 . $2', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個字串連接錯誤在 " . basename($filePath) . "\n";
        }

        // 修復字串插值錯誤："string {$var $var}" -> "string {$var}"
        $pattern = '/(".*?\{\$\w+)\s+(\$\w+.*?\}")/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, '$1}$2', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個字串插值錯誤在 " . basename($filePath) . "\n";
        }

        return $content;
    }

    private function fixControlStructureSyntax(string $content, string $filePath, int &$fixesInFile): string
    {
        // 修復：if ($condition] -> if ($condition)
        $pattern = '/if\s*\(\s*([^)]*)\]\s*\)/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, 'if ($1)', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個 if 語句語法錯誤在 " . basename($filePath) . "\n";
        }

        // 修復：foreach ($array as $key =>] -> foreach ($array as $key => $value)
        $pattern = '/foreach\s*\(\s*([^)]+)\s+as\s+([^=]+)=>\s*\]\s*\)/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, 'foreach ($1 as $2 => $value)', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個 foreach 語法錯誤在 " . basename($filePath) . "\n";
        }

        // 修復：while ($condition] -> while ($condition)
        $pattern = '/while\s*\(\s*([^)]*)\]\s*\)/';
        if (preg_match_all($pattern, $content, $matches)) {
            $content = preg_replace($pattern, 'while ($1)', $content);
            $fixCount = count($matches[0]);
            $fixesInFile += $fixCount;
            echo "  修復 {$fixCount} 個 while 語句語法錯誤在 " . basename($filePath) . "\n";
        }

        return $content;
    }

    private function generateSummary(): void
    {
        echo "\n📊 陣列和函數語法修復摘要:\n";
        echo "==================================================\n";
        echo "修復的檔案數: " . count($this->fixedFiles) . "\n";
        echo "總修復數量: {$this->totalFixes}\n";

        if (!empty($this->errorStats)) {
            echo "\n🎯 修復的錯誤類型統計:\n";
            foreach ($this->errorStats as $type => $count) {
                echo "  - {$type}: {$count} 個\n";
            }
        }

        if (count($this->fixedFiles) > 0) {
            echo "\n📁 修復的檔案:\n";
            foreach (array_slice($this->fixedFiles, 0, 15) as $file) {
                echo "  - " . basename($file) . "\n";
            }

            if (count($this->fixedFiles) > 15) {
                $remaining = count($this->fixedFiles) - 15;
                echo "  ... 還有 {$remaining} 個檔案\n";
            }
        }

        echo "\n💡 修復完成後建議:\n";
        echo "  1. 執行 PHPStan 檢查修復效果\n";
        echo "  2. 運行測試確保功能正常\n";
        echo "  3. 檢查是否還有其他語法錯誤\n";
        echo "  4. 如果還有語法錯誤，可能需要手動修復複雜情況\n";

        echo "\n📈 預期改善:\n";
        echo "  - 減少 'unexpected T_DOUBLE_ARROW' 錯誤\n";
        echo "  - 減少 'unexpected ]' 錯誤\n";
        echo "  - 減少 'Cannot use empty array elements' 錯誤\n";
        echo "  - 改善陣列和函數調用語法正確性\n";
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new ArrayAndFunctionSyntaxFixer();
    $fixer->run();
}
