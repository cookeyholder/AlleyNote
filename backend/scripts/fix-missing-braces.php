<?php

declare(strict_types=1);

/**
 * 修復缺少方法閉合括號的腳本
 *
 * 專門處理以下語法錯誤：
 * - unexpected token "public" (通常是前一個方法缺少閉合括號)
 * - unexpected token "private"
 * - unexpected token "protected"
 * - unexpected EOF (檔案結尾缺少括號)
 */

class MissingBracesFixer
{
    private array $fixedFiles = [];
    private int $totalFixes = 0;
    private array $errorStats = [];

    public function run(): void
    {
        echo "🔧 修復缺少方法閉合括號的語法錯誤...\n";

        $this->scanAndFixFiles();
        $this->generateSummary();

        echo "\n✅ 方法閉合括號修復完成！\n";
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

        // 修復缺少的方法閉合括號
        $content = $this->fixMissingMethodBraces($content, $filePath, $fixesInFile);

        // 修復缺少的類閉合括號
        $content = $this->fixMissingClassBraces($content, $filePath, $fixesInFile);

        // 修復缺少的條件語句閉合括號
        $content = $this->fixMissingControlStructureBraces($content, $filePath, $fixesInFile);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = $filePath;
            $this->totalFixes += $fixesInFile;
            echo "修復檔案: $filePath (修復 {$fixesInFile} 個問題)\n";
        }
    }

    private function fixMissingMethodBraces(string $content, string $filePath, int &$fixesInFile): string
    {
        $lines = explode("\n", $content);
        $fixedLines = [];
        $braceStack = [];
        $inMethod = false;
        $methodIndentLevel = 0;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmedLine = trim($line);

            // 檢測方法開始
            if (preg_match('/^\s*(public|private|protected)\s+function\s+/', $line)) {
                $inMethod = true;
                $methodIndentLevel = strlen($line) - strlen(ltrim($line));
                $braceStack = [];
            }

            // 計算括號
            $openBraces = substr_count($line, '{');
            $closeBraces = substr_count($line, '}');

            for ($j = 0; $j < $openBraces; $j++) {
                $braceStack[] = '{';
            }
            for ($j = 0; $j < $closeBraces; $j++) {
                array_pop($braceStack);
            }

            $fixedLines[] = $line;

            // 檢查是否遇到下一個方法或類結束
            $nextLineIndex = $i + 1;
            if ($nextLineIndex < count($lines)) {
                $nextLine = trim($lines[$nextLineIndex]);

                // 如果下一行是新的方法或類結束，但當前方法的括號未閉合
                if ($inMethod && count($braceStack) > 0 &&
                    (preg_match('/^(public|private|protected)\s+function\s+/', $nextLine) ||
                     $nextLine === '}' && strlen($lines[$nextLineIndex]) - strlen(ltrim($lines[$nextLineIndex])) < $methodIndentLevel)) {

                    // 添加缺少的閉合括號
                    $indent = str_repeat(' ', $methodIndentLevel);
                    while (count($braceStack) > 0) {
                        $fixedLines[] = $indent . '}';
                        array_pop($braceStack);
                        $fixesInFile++;
                        echo "  修復缺少的方法閉合括號在 " . basename($filePath) . " 第 " . ($i + 1) . " 行後\n";
                    }
                    $inMethod = false;
                }
            }

            // 如果到檔案結尾，方法仍未閉合
            if ($nextLineIndex >= count($lines) && $inMethod && count($braceStack) > 0) {
                $indent = str_repeat(' ', $methodIndentLevel);
                while (count($braceStack) > 0) {
                    $fixedLines[] = $indent . '}';
                    array_pop($braceStack);
                    $fixesInFile++;
                    echo "  修復檔案結尾缺少的方法閉合括號在 " . basename($filePath) . "\n";
                }
            }
        }

        return implode("\n", $fixedLines);
    }

    private function fixMissingClassBraces(string $content, string $filePath, int &$fixesInFile): string
    {
        // 檢查類是否有適當的閉合括號
        $classCount = preg_match_all('/^\s*class\s+\w+/m', $content);
        $totalOpenBraces = substr_count($content, '{');
        $totalCloseBraces = substr_count($content, '}');

        // 如果開括號比閉括號多，在檔案結尾添加缺少的閉括號
        if ($totalOpenBraces > $totalCloseBraces) {
            $missingBraces = $totalOpenBraces - $totalCloseBraces;

            // 檢查最後幾行是否已經有閉括號
            $lines = explode("\n", $content);
            $lastNonEmptyLine = '';
            foreach (array_reverse($lines) as $line) {
                if (trim($line) !== '') {
                    $lastNonEmptyLine = trim($line);
                    break;
                }
            }

            // 只有當最後一行不是閉括號時才添加
            if ($lastNonEmptyLine !== '}') {
                for ($i = 0; $i < $missingBraces; $i++) {
                    $content .= "\n}";
                    $fixesInFile++;
                    echo "  修復檔案結尾缺少的類閉合括號在 " . basename($filePath) . "\n";
                }
            }
        }

        return $content;
    }

    private function fixMissingControlStructureBraces(string $content, string $filePath, int &$fixesInFile): string
    {
        // 修復 if/else/for/while 等控制結構的缺少括號
        $patterns = [
            // if 語句後缺少開括號
            [
                'pattern' => '/(\s*if\s*\([^)]+\))\s*\n(\s*)([^{}\s])/m',
                'replacement' => '$1 {' . "\n" . '$2$3',
                'description' => 'if 語句缺少開括號'
            ],
            // else 語句後缺少開括號
            [
                'pattern' => '/(\s*else)\s*\n(\s*)([^{}\s])/m',
                'replacement' => '$1 {' . "\n" . '$2$3',
                'description' => 'else 語句缺少開括號'
            ],
            // foreach 語句後缺少開括號
            [
                'pattern' => '/(\s*foreach\s*\([^)]+\))\s*\n(\s*)([^{}\s])/m',
                'replacement' => '$1 {' . "\n" . '$2$3',
                'description' => 'foreach 語句缺少開括號'
            ]
        ];

        foreach ($patterns as $pattern) {
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

        return $content;
    }

    private function generateSummary(): void
    {
        echo "\n📊 方法閉合括號修復摘要:\n";
        echo "==================================================\n";
        echo "修復的檔案數: " . count($this->fixedFiles) . "\n";
        echo "總修復數量: {$this->totalFixes}\n";

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
        echo "  4. 檢查程式邏輯是否仍然正確\n";

        echo "\n📈 預期改善:\n";
        echo "  - 減少 'unexpected token \"public\"' 錯誤\n";
        echo "  - 減少 'unexpected token \"private\"' 錯誤\n";
        echo "  - 減少 'unexpected EOF' 錯誤\n";
        echo "  - 改善類和方法的語法結構正確性\n";
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new MissingBracesFixer();
    $fixer->run();
}
