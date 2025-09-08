<?php

declare(strict_types=1);

/**
 * 修復多重存取修飾符的腳本
 *
 * 專門處理 "Multiple access type modifiers are not allowed" 錯誤
 * 移除重複的 public/private/protected 修飾符，保留最後一個
 */

class MultipleAccessModifiersFixer
{
    private array $fixedFiles = [];
    private int $totalFixes = 0;
    private array $errorStats = [];

    public function run(): void
    {
        echo "🔧 修復多重存取修飾符錯誤...\n";

        $this->scanAndFixFiles();
        $this->generateSummary();

        echo "\n✅ 多重存取修飾符修復完成！\n";
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

        // 修復各種多重存取修飾符問題
        $content = $this->fixMultipleAccessModifiers($content, $filePath, $fixesInFile);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = $filePath;
            $this->totalFixes += $fixesInFile;
            echo "修復檔案: $filePath (修復 {$fixesInFile} 個問題)\n";
        }
    }

    private function fixMultipleAccessModifiers(string $content, string $filePath, int &$fixesInFile): string
    {
        $lines = explode("\n", $content);
        $fixedLines = [];
        $changed = false;

        foreach ($lines as $lineNum => $line) {
            $originalLine = $line;
            $modifiedLine = $line;

            // 檢查是否包含多個存取修飾符
            $accessModifiers = ['public', 'private', 'protected'];
            $foundModifiers = [];

            foreach ($accessModifiers as $modifier) {
                if (preg_match_all('/\b' . $modifier . '\b/', $line, $matches)) {
                    $count = count($matches[0]);
                    if ($count > 0) {
                        $foundModifiers[$modifier] = $count;
                    }
                }
            }

            // 如果有多個相同的修飾符，只保留一個
            foreach ($foundModifiers as $modifier => $count) {
                if ($count > 1) {
                    // 移除多餘的修飾符，只保留最後一個
                    $positions = [];
                    preg_match_all('/\b' . $modifier . '\b/', $modifiedLine, $matches, PREG_OFFSET_CAPTURE);

                    // 從後往前移除，保留最後一個
                    for ($i = count($matches[0]) - 2; $i >= 0; $i--) {
                        $offset = $matches[0][$i][1];
                        $length = strlen($modifier);

                        // 檢查修飾符前後是否有空格需要一起移除
                        $beforeChar = $offset > 0 ? $modifiedLine[$offset - 1] : '';
                        $afterChar = ($offset + $length < strlen($modifiedLine)) ? $modifiedLine[$offset + $length] : '';

                        if ($beforeChar === ' ') {
                            $offset--;
                            $length++;
                        } elseif ($afterChar === ' ') {
                            $length++;
                        }

                        $modifiedLine = substr_replace($modifiedLine, '', $offset, $length);
                    }

                    $fixesInFile++;
                    $changed = true;
                    echo "  修復 {$modifier} 重複修飾符在 " . basename($filePath) . " 第 " . ($lineNum + 1) . " 行\n";
                }
            }

            // 檢查是否有不同的存取修飾符在同一行（例如 public private function）
            $differentModifiersCount = count(array_keys($foundModifiers));
            if ($differentModifiersCount > 1) {
                // 保留最後出現的修飾符
                $lastModifier = '';
                $lastPosition = -1;

                foreach ($accessModifiers as $modifier) {
                    if (isset($foundModifiers[$modifier])) {
                        if (($pos = strrpos($modifiedLine, $modifier)) !== false) {
                            if ($pos > $lastPosition) {
                                $lastPosition = $pos;
                                $lastModifier = $modifier;
                            }
                        }
                    }
                }

                // 移除所有其他修飾符
                foreach ($accessModifiers as $modifier) {
                    if ($modifier !== $lastModifier && isset($foundModifiers[$modifier])) {
                        $modifiedLine = preg_replace('/\b' . $modifier . '\s*/', '', $modifiedLine);
                    }
                }

                $fixesInFile++;
                $changed = true;
                echo "  修復混合存取修飾符在 " . basename($filePath) . " 第 " . ($lineNum + 1) . " 行，保留 {$lastModifier}\n";
            }

            $fixedLines[] = $modifiedLine;
        }

        return implode("\n", $fixedLines);
    }

    private function generateSummary(): void
    {
        echo "\n📊 多重存取修飾符修復摘要:\n";
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
        echo "  4. 檢查方法的可見性是否符合預期\n";

        echo "\n📈 預期改善:\n";
        echo "  - 減少 'Multiple access type modifiers are not allowed' 錯誤\n";
        echo "  - 清理重複的 public/private/protected 修飾符\n";
        echo "  - 改善類方法的語法正確性\n";
        echo "  - 保持原有的方法可見性意圖\n";

        echo "\n🔍 修復規則:\n";
        echo "  - 相同修飾符重複: 保留最後一個\n";
        echo "  - 不同修飾符混合: 保留最後出現的修飾符\n";
        echo "  - 自動清理多餘空格\n";
        echo "  - 保持原有代碼結構\n";
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new MultipleAccessModifiersFixer();
    $fixer->run();
}
