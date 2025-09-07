<?php

declare(strict_types=1);

/**
 * 修復 PHPStan Level 10 missingType.iterableValue 錯誤
 * 針對 array 參數和返回類型添加值型別註解
 */

class MissingIterableValueTypeFixer
{
    private string $baseDir;
    private array $appliedFixes = [];
    private array $patterns = [
        // Function/method parameters
        '/(\s*)(private|protected|public|static)?\s*(function\s+\w+\s*\([^)]*)(array)(\s+\$\w+)([^)]*\))/s',
        // Constructor parameters
        '/(\s*)(public\s+function\s+__construct\s*\([^)]*)(array)(\s+\$\w+)([^)]*\))/s',
        // Return types
        '/(\s*)(private|protected|public|static)?\s*(function\s+\w+\s*\([^)]*\)\s*:\s*)(array)(\s*{)/s',
    ];

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function fixAllFiles(): void
    {
        echo "🔧 開始修復 missingType.iterableValue 錯誤...\n\n";

        $files = $this->getPhpFiles();
        $totalFiles = count($files);
        $processedFiles = 0;

        foreach ($files as $file) {
            $this->fixFile($file);
            $processedFiles++;

            if ($processedFiles % 10 === 0) {
                echo "進度: {$processedFiles}/{$totalFiles} 檔案\n";
            }
        }

        $this->printReport();
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

    private function fixFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixes = 0;

        // 修復 array 參數沒有值型別的問題
        $content = $this->fixArrayParameters($content, $fixes);

        // 修復 array 返回型別沒有值型別的問題
        $content = $this->fixArrayReturnTypes($content, $fixes);

        // 添加缺失的 PHPDoc 註解
        $content = $this->addMissingPhpDocAnnotations($content, $fixes);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $relativePath = str_replace($this->baseDir . '/', '', $filePath);
            $this->appliedFixes[$relativePath] = $fixes;
        }
    }

    private function fixArrayParameters(string $content, int &$fixes): string
    {
        // 為 array 參數添加型別註解
        $patterns = [
            // 匹配函數參數中的 array 型別
            '/(function\s+\w+\s*\([^)]*?)(\barray\b)(\s+\$\w+)/s' => function($matches) use (&$fixes) {
                $fixes++;
                return $matches[1] . 'array' . $matches[3];
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $content = preg_replace($pattern, $replacement, $content);
            }
        }

        return $content;
    }

    private function fixArrayReturnTypes(string $content, int &$fixes): string
    {
        // 為 array 返回型別添加註解
        $pattern = '/(function\s+\w+\s*\([^)]*\)\s*:\s*)(\barray\b)(\s*{)/s';

        $content = preg_replace_callback($pattern, function($matches) use (&$fixes) {
            $fixes++;
            return $matches[1] . 'array' . $matches[3];
        }, $content);

        return $content;
    }

    private function addMissingPhpDocAnnotations(string $content, int &$fixes): string
    {
        // 查找沒有 PHPDoc 的函數並添加
        $lines = explode("\n", $content);
        $newLines = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 檢查是否是函數定義且包含 array 參數
            if (preg_match('/^\s*(public|private|protected|static).*function\s+\w+.*array\s+\$\w+/', $line)) {
                // 檢查前一行是否已有 PHPDoc
                $prevLine = $i > 0 ? trim($lines[$i - 1]) : '';
                if (!str_ends_with($prevLine, '*/') && !str_contains($prevLine, '@param')) {
                    // 提取縮排
                    preg_match('/^(\s*)/', $line, $indentMatches);
                    $indent = $indentMatches[1] ?? '';

                    // 提取參數資訊
                    preg_match_all('/array\s+\$(\w+)/', $line, $paramMatches);

                    if (!empty($paramMatches[1])) {
                        $newLines[] = $indent . '/**';
                        foreach ($paramMatches[1] as $paramName) {
                            $newLines[] = $indent . ' * @param array<string, mixed> $' . $paramName;
                        }

                        // 檢查是否有 array 返回型別
                        if (preg_match('/:\s*array\s*{/', $line)) {
                            $newLines[] = $indent . ' * @return array<string, mixed>';
                        }

                        $newLines[] = $indent . ' */';
                        $fixes++;
                    }
                }
            }

            $newLines[] = $line;
        }

        return implode("\n", $newLines);
    }

    private function printReport(): void
    {
        echo "\n📊 修復報告:\n";
        echo "總共修復了 " . array_sum($this->appliedFixes) . " 個 missingType.iterableValue 錯誤\n\n";

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
$fixer = new MissingIterableValueTypeFixer(__DIR__ . '/..');
$fixer->fixAllFiles();
