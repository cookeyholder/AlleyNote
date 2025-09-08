<?php

declare(strict_types=1);

/**
 * 修復未閉合方括號的腳本
 *
 * 專門處理以下語法錯誤：
 * - Unclosed '[' does not match ')'
 * - isset($array[key) -> isset($array[key])
 * - $array[key) -> $array[key]
 * - 其他方括號不匹配問題
 */

class UnclosedBracketsFixer
{
    private array $fixedFiles = [];
    private int $totalFixes = 0;
    private array $errorStats = [];

    public function run(): void
    {
        echo "🔧 修復未閉合方括號的語法錯誤...\n";

        $this->scanAndFixFiles();
        $this->generateSummary();

        echo "\n✅ 未閉合方括號修復完成！\n";
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

        // 修復各種未閉合方括號問題
        $content = $this->fixUnclosedBrackets($content, $filePath, $fixesInFile);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = $filePath;
            $this->totalFixes += $fixesInFile;
            echo "修復檔案: $filePath (修復 {$fixesInFile} 個問題)\n";
        }
    }

    private function fixUnclosedBrackets(string $content, string $filePath, int &$fixesInFile): string
    {
        $patterns = [
            // isset($array[key) -> isset($array[key])
            [
                'pattern' => '/isset\s*\(\s*([^)]*\[[^]]*)\)\s*\)/s',
                'replacement' => 'isset($1])',
                'description' => 'isset 函數中未閉合的方括號'
            ],
            // empty($array[key) -> empty($array[key])
            [
                'pattern' => '/empty\s*\(\s*([^)]*\[[^]]*)\)\s*\)/s',
                'replacement' => 'empty($1])',
                'description' => 'empty 函數中未閉合的方括號'
            ],
            // unset($array[key) -> unset($array[key])
            [
                'pattern' => '/unset\s*\(\s*([^)]*\[[^]]*)\)\s*\)/s',
                'replacement' => 'unset($1])',
                'description' => 'unset 函數中未閉合的方括號'
            ],
            // $variable[key) -> $variable[key]
            [
                'pattern' => '/(\$\w+\[[^]]*)\)\s*([;,\s])/s',
                'replacement' => '$1]$2',
                'description' => '變數存取中未閉合的方括號'
            ],
            // array_key_exists($key, $array[index) -> array_key_exists($key, $array[index])
            [
                'pattern' => '/array_key_exists\s*\(\s*([^,]+),\s*([^)]*\[[^]]*)\)\s*\)/s',
                'replacement' => 'array_key_exists($1, $2])',
                'description' => 'array_key_exists 函數中未閉合的方括號'
            ],
            // in_array($value, $array[key) -> in_array($value, $array[key])
            [
                'pattern' => '/in_array\s*\(\s*([^,]+),\s*([^)]*\[[^]]*)\)\s*\)/s',
                'replacement' => 'in_array($1, $2])',
                'description' => 'in_array 函數中未閉合的方括號'
            ]
        ];

        foreach ($patterns as $pattern) {
            $matches = [];
            if (preg_match_all($pattern['pattern'], $content, $matches)) {
                $content = preg_replace($pattern['pattern'], $pattern['replacement'], $content);
                $fixCount = count($matches[0]);
                $fixesInFile += $fixCount;
                $this->errorStats[$pattern['description']] = ($this->errorStats[$pattern['description']] ?? 0) + $fixCount;

                if ($fixCount > 0) {
                    echo "  修復 {$fixCount} 個 '{$pattern['description']}' 在 " . basename($filePath) . "\n";
                }
            }
        }

        // 使用更精確的逐行分析來修復複雜情況
        $content = $this->fixComplexUnclosedBrackets($content, $filePath, $fixesInFile);

        return $content;
    }

    private function fixComplexUnclosedBrackets(string $content, string $filePath, int &$fixesInFile): string
    {
        $lines = explode("\n", $content);
        $fixedLines = [];
        $changed = false;

        foreach ($lines as $lineNum => $line) {
            $originalLine = $line;

            // 檢查每一行是否有未匹配的方括號
            $openBrackets = substr_count($line, '[');
            $closeBrackets = substr_count($line, ']');
            $openParens = substr_count($line, '(');
            $closeParens = substr_count($line, ')');

            // 如果有未匹配的開方括號和閉圓括號，可能需要修復
            if ($openBrackets > $closeBrackets && $closeParens > $openParens) {
                // 常見模式：isset($array[key) 缺少 ]
                if (preg_match('/isset\s*\(\s*([^)]*\[+[^]]*)\)/', $line, $matches)) {
                    $bracketContent = $matches[1];
                    $openBracketsInMatch = substr_count($bracketContent, '[');
                    $closeBracketsInMatch = substr_count($bracketContent, ']');

                    if ($openBracketsInMatch > $closeBracketsInMatch) {
                        $missingBrackets = $openBracketsInMatch - $closeBracketsInMatch;
                        $replacement = $bracketContent . str_repeat(']', $missingBrackets);
                        $line = str_replace($bracketContent, $replacement, $line);
                        $fixesInFile++;
                        $changed = true;
                        echo "  修復 isset 中未閉合方括號在 " . basename($filePath) . " 第 " . ($lineNum + 1) . " 行\n";
                    }
                }

                // 處理其他函數中的類似問題
                $functions = ['empty', 'unset', 'array_key_exists', 'in_array', 'count', 'is_array'];
                foreach ($functions as $func) {
                    if (preg_match("/{$func}\s*\(\s*([^)]*\[+[^]]*)\)/", $line, $matches)) {
                        $bracketContent = $matches[1];
                        $openBracketsInMatch = substr_count($bracketContent, '[');
                        $closeBracketsInMatch = substr_count($bracketContent, ']');

                        if ($openBracketsInMatch > $closeBracketsInMatch) {
                            $missingBrackets = $openBracketsInMatch - $closeBracketsInMatch;
                            $replacement = $bracketContent . str_repeat(']', $missingBrackets);
                            $line = str_replace($bracketContent, $replacement, $line);
                            $fixesInFile++;
                            $changed = true;
                            echo "  修復 {$func} 中未閉合方括號在 " . basename($filePath) . " 第 " . ($lineNum + 1) . " 行\n";
                        }
                    }
                }

                // 處理簡單的變數存取 $var[key)
                if (preg_match('/(\$\w+\[[^]]*)\)\s*([;,\s])/', $line, $matches)) {
                    $replacement = $matches[1] . ']' . $matches[2];
                    $line = preg_replace('/(\$\w+\[[^]]*)\)\s*([;,\s])/', $replacement, $line);
                    $fixesInFile++;
                    $changed = true;
                    echo "  修復變數存取中未閉合方括號在 " . basename($filePath) . " 第 " . ($lineNum + 1) . " 行\n";
                }
            }

            $fixedLines[] = $line;
        }

        return implode("\n", $fixedLines);
    }

    private function generateSummary(): void
    {
        echo "\n📊 未閉合方括號修復摘要:\n";
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
        echo "  1. 執行 PHP 語法檢查: php -l filename.php\n";
        echo "  2. 運行 PHPStan 檢查修復效果\n";
        echo "  3. 運行測試確保功能正常\n";
        echo "  4. 檢查修復後的陣列存取邏輯\n";

        echo "\n📈 預期改善:\n";
        echo "  - 減少 'Unclosed \\[' does not match \\')\\'' 錯誤\n";
        echo "  - 修復 isset, empty, unset 等函數中的方括號問題\n";
        echo "  - 改善陣列存取語法正確性\n";
        echo "  - 恢復受影響檔案的 PHP 語法檢查通過\n";
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new UnclosedBracketsFixer();
    $fixer->run();
}
