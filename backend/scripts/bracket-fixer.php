<?php

declare(strict_types=1);

/**
 * 括號修復器
 *
 * 專門修復不匹配的括號、引號等語法錯誤
 */

class BracketFixer
{
    private int $totalFixed = 0;
    private array $fixedFiles = [];

    public function fixBrackets(): void
    {
        echo "🔧 啟動括號修復器...\n\n";

        $phpFiles = $this->findPhpFiles();

        foreach ($phpFiles as $file) {
            $this->fixFile($file);
        }

        $this->printSummary();
    }

    private function findPhpFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../app')
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        return $phpFiles;
    }

    private function fixFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fixes = 0;

        // 修復 1: 檢查並修復引號問題
        $lines = explode("\n", $content);
        $fixedLines = [];

        foreach ($lines as $lineNum => $line) {
            $originalLine = $line;

            // 檢查引號配對
            $singleQuotes = substr_count($line, "'") - substr_count($line, "\\'");
            $doubleQuotes = substr_count($line, '"') - substr_count($line, '\\"');

            // 如果引號數量是奇數，可能有未閉合的引號
            if ($singleQuotes % 2 !== 0) {
                // 嘗試修復單引號
                if (strpos($line, "declare(strict_types=1);") === false &&
                    strpos($line, "<?php") === false &&
                    strpos($line, "*/") === false) {
                    $line = rtrim($line) . "'";
                    $fixes++;
                }
            }

            if ($doubleQuotes % 2 !== 0) {
                // 嘗試修復雙引號
                if (strpos($line, "declare(strict_types=1);") === false &&
                    strpos($line, "<?php") === false &&
                    strpos($line, "*/") === false) {
                    $line = rtrim($line) . '"';
                    $fixes++;
                }
            }

            $fixedLines[] = $line;
        }

        $content = implode("\n", $fixedLines);

        // 修復 2: 檢查並修復括號配對
        $this->fixBracketPairs($content, $fixes);

        // 修復 3: 修復常見的語法結構錯誤
        $specificFixes = [
            // 修復未閉合的陣列
            '/\[\s*$/' => '[]',

            // 修復未閉合的函式呼叫
            '/\(\s*$/' => '()',

            // 修復多餘的開放括號
            '/\{\s*\{/' => '{',
            '/\(\s*\(/' => '(',
            '/\[\s*\[/' => '[',

            // 修復缺少分號的行
            '/^(\s*)(.*[^;{}\s])(\s*)$/m' => '$1$2;$3',
        ];

        foreach ($specificFixes as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        // 如果有修復，保存檔案
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->totalFixed += $fixes;
            $this->fixedFiles[] = [
                'file' => str_replace(__DIR__ . '/../', '', $filePath),
                'fixes' => $fixes
            ];

            echo "🔧 修復: " . str_replace(__DIR__ . '/../', '', $filePath) . " ({$fixes} 個修復)\n";
        }
    }

    private function fixBracketPairs(string &$content, int &$fixes): void
    {
        // 統計各種括號
        $openCurly = substr_count($content, '{');
        $closeCurly = substr_count($content, '}');
        $openParen = substr_count($content, '(');
        $closeParen = substr_count($content, ')');
        $openSquare = substr_count($content, '[');
        $closeSquare = substr_count($content, ']');

        // 修復大括號不匹配
        if ($openCurly > $closeCurly) {
            $diff = $openCurly - $closeCurly;
            $content .= str_repeat("\n}", $diff);
            $fixes += $diff;
        } elseif ($closeCurly > $openCurly) {
            $diff = $closeCurly - $openCurly;
            // 移除多餘的閉合括號（從尾部開始）
            for ($i = 0; $i < $diff; $i++) {
                $lastPos = strrpos($content, '}');
                if ($lastPos !== false) {
                    $content = substr($content, 0, $lastPos) . substr($content, $lastPos + 1);
                    $fixes++;
                }
            }
        }

        // 修復圓括號不匹配
        if ($openParen > $closeParen) {
            $diff = $openParen - $closeParen;
            $content = rtrim($content) . str_repeat(')', $diff);
            $fixes += $diff;
        } elseif ($closeParen > $openParen) {
            $diff = $closeParen - $openParen;
            // 移除多餘的閉合括號
            for ($i = 0; $i < $diff; $i++) {
                $lastPos = strrpos($content, ')');
                if ($lastPos !== false) {
                    $content = substr($content, 0, $lastPos) . substr($content, $lastPos + 1);
                    $fixes++;
                }
            }
        }

        // 修復方括號不匹配
        if ($openSquare > $closeSquare) {
            $diff = $openSquare - $closeSquare;
            $content = rtrim($content) . str_repeat(']', $diff);
            $fixes += $diff;
        } elseif ($closeSquare > $openSquare) {
            $diff = $closeSquare - $openSquare;
            // 移除多餘的閉合括號
            for ($i = 0; $i < $diff; $i++) {
                $lastPos = strrpos($content, ']');
                if ($lastPos !== false) {
                    $content = substr($content, 0, $lastPos) . substr($content, $lastPos + 1);
                    $fixes++;
                }
            }
        }
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📋 括號修復報告\n";
        echo str_repeat("=", 50) . "\n";
        echo "總修復數量: {$this->totalFixed}\n";
        echo "修復檔案數: " . count($this->fixedFiles) . "\n\n";

        if (!empty($this->fixedFiles)) {
            echo "修復詳情:\n";
            foreach ($this->fixedFiles as $file) {
                echo "  🔧 {$file['file']}: {$file['fixes']} 個修復\n";
            }
        }

        echo "\n✅ 括號修復完成！\n";
        echo "💡 建議再次執行語法檢查確認修復效果\n";
    }
}

// 執行括號修復
$fixer = new BracketFixer();
$fixer->fixBrackets();
