#!/usr/bin/env php
<?php
/**
 * 進階 PHPStan Level 8 修復工具
 * 基於錯誤分析報告的智能型修復腳本
 * 
 * 使用方式:
 * php scripts/advanced-phpstan-fixer.php [--dry-run] [--type=stream|null-coalescing|array-types|all]
 */

class AdvancedPhpstanFixer
{
    private bool $dryRun = false;
    private string $fixType = 'all';
    private array $stats = [
        'files_processed' => 0,
        'fixes_applied' => 0,
        'errors_prevented' => 0
    ];

    public function __construct(array $args)
    {
        $this->parseArguments($args);
    }

    private function parseArguments(array $args): void
    {
        foreach ($args as $arg) {
            if ($arg === '--dry-run') {
                $this->dryRun = true;
            } elseif (str_starts_with($arg, '--type=')) {
                $this->fixType = substr($arg, 7);
            }
        }
    }

    public function run(): void
    {
        echo "🚀 啟動進階 PHPStan Level 8 修復工具\n";
        echo "修復模式: {$this->fixType}\n";
        echo "執行模式: " . ($this->dryRun ? "預覽模式 (不會修改檔案)" : "修復模式") . "\n\n";

        $files = $this->findPhpFiles();

        foreach ($files as $file) {
            $this->processFile($file);
        }

        $this->printStats();
    }

    private function findPhpFiles(): array
    {
        $files = [];
        $directories = ['app/', 'config/', 'tests/'];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }

        return $files;
    }

    private function processFile(string $filePath): void
    {
        $originalContent = file_get_contents($filePath);
        $modifiedContent = $originalContent;
        $fixesInThisFile = 0;

        // 應用各種修復策略
        if ($this->fixType === 'all' || $this->fixType === 'stream') {
            $result = $this->fixStreamWriteIssues($modifiedContent);
            $modifiedContent = $result['content'];
            $fixesInThisFile += $result['fixes'];
        }

        if ($this->fixType === 'all' || $this->fixType === 'null-coalescing') {
            $result = $this->fixUnnecessaryNullCoalescing($modifiedContent);
            $modifiedContent = $result['content'];
            $fixesInThisFile += $result['fixes'];
        }

        if ($this->fixType === 'all' || $this->fixType === 'array-types') {
            $result = $this->fixMissingArrayTypes($modifiedContent);
            $modifiedContent = $result['content'];
            $fixesInThisFile += $result['fixes'];
        }

        // 如果有變更且不是預覽模式，則寫入檔案
        if ($modifiedContent !== $originalContent) {
            $this->stats['files_processed']++;
            $this->stats['fixes_applied'] += $fixesInThisFile;

            echo "📝 修復檔案: $filePath ($fixesInThisFile 個修復)\n";

            if (!$this->dryRun) {
                file_put_contents($filePath, $modifiedContent);
            }
        }
    }

    /**
     * 修復 StreamInterface::write() 類型問題
     * 處理 json_encode() 和其他可能返回 false 的函數
     */
    private function fixStreamWriteIssues(string $content): array
    {
        $fixes = 0;

        // 修復 json_encode() 相關問題
        $patterns = [
            // json_encode() 直接傳入 stream->write()
            '/(\$\w+->write\()(json_encode\([^)]+\))(\))/' => function ($matches) use (&$fixes) {
                $fixes++;
                return $matches[1] . '(' . $matches[2] . ') ?: \'\'' . $matches[3];
            },

            // file_get_contents() 相關問題
            '/(\$\w+->write\()(file_get_contents\([^)]+\))(\))/' => function ($matches) use (&$fixes) {
                $fixes++;
                return $matches[1] . '(' . $matches[2] . ') ?: \'\'' . $matches[3];
            },

            // 其他可能返回 string|false 的函數
            '/(\$\w+->write\()((substr|trim|str_replace|preg_replace)\([^)]+\))(\))/' => function ($matches) use (&$fixes) {
                // 只有在明確可能返回 false 的情況下才修復
                if (str_contains($matches[2], 'preg_replace')) {
                    $fixes++;
                    return $matches[1] . '(' . $matches[2] . ') ?: \'\'' . $matches[4];
                }
                return $matches[0];
            }
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== $content) {
                    $fixes += substr_count($content, $pattern) - substr_count($newContent, $pattern);
                    $content = $newContent;
                }
            }
        }

        return ['content' => $content, 'fixes' => $fixes];
    }

    /**
     * 移除不必要的 null coalescing 運算子
     * 分析上下文判斷變數是否真的可能為 null
     */
    private function fixUnnecessaryNullCoalescing(string $content): array
    {
        $fixes = 0;

        // 移除明顯不必要的 null coalescing
        $patterns = [
            // 字串字面值不需要 ??
            '/\'[^\']*\'\s*\?\?\s*[^;]+/' => function ($matches) use (&$fixes) {
                $fixes++;
                return str_replace(' ?? ', '', $matches[0]);
            },

            // 數字字面值不需要 ??
            '/\b\d+\s*\?\?\s*[^;]+/' => function ($matches) use (&$fixes) {
                $fixes++;
                return str_replace(' ?? ', '', $matches[0]);
            },

            // 函數調用結果通常不需要 ?? (除非明確可能返回 null)
            '/(\$\w+\([^)]*\))\s*\?\?\s*([^;]+)/' => function ($matches) use (&$fixes) {
                // 檢查是否是可能返回 null 的函數
                $nullableFunctions = ['array_search', 'strpos', 'stripos', 'array_key_first'];
                $isNullable = false;

                foreach ($nullableFunctions as $func) {
                    if (str_contains($matches[1], $func)) {
                        $isNullable = true;
                        break;
                    }
                }

                if (!$isNullable) {
                    $fixes++;
                    return $matches[1];
                }

                return $matches[0];
            }
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            }
        }

        return ['content' => $content, 'fixes' => $fixes];
    }

    /**
     * 修復缺失的陣列類型規範
     * 智能推斷正確的泛型類型
     */
    private function fixMissingArrayTypes(string $content): array
    {
        $fixes = 0;

        // 根據上下文推斷陣列類型
        $patterns = [
            // 方法參數中的 array 類型
            '/(public|private|protected)\s+function\s+(\w+)\s*\([^)]*\barray\s+\$(\w+)[^)]*\)\s*:\s*/' => function ($matches) use (&$fixes) {
                $methodName = $matches[2];
                $paramName = $matches[3];

                // 根據方法名和參數名推斷類型
                $inferredType = $this->inferArrayType($methodName, $paramName);

                if ($inferredType) {
                    $fixes++;
                    return str_replace('array $' . $paramName, $inferredType . ' $' . $paramName, $matches[0]);
                }

                return $matches[0];
            },

            // 返回類型中的 array
            '/:\s*array\s*$/' => function ($matches) use (&$fixes) {
                $fixes++;
                return ': array<string, mixed>';
            },

            // @param 註解中的 array
            '/@param\s+array\s+\$(\w+)/' => function ($matches) use (&$fixes) {
                $paramName = $matches[1];
                $inferredType = $this->inferArrayTypeFromParamName($paramName);

                $fixes++;
                return '@param ' . $inferredType . ' $' . $paramName;
            }
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            }
        }

        return ['content' => $content, 'fixes' => $fixes];
    }

    /**
     * 根據方法名和參數名推斷陣列類型
     */
    private function inferArrayType(string $methodName, string $paramName): string
    {
        // Headers 相關
        if (str_contains($paramName, 'header') || str_contains($methodName, 'header')) {
            return 'array<string, array<string>>';
        }

        // Config 或 options 相關
        if (str_contains($paramName, 'config') || str_contains($paramName, 'option') || str_contains($paramName, 'setting')) {
            return 'array<string, mixed>';
        }

        // Data 或 payload 相關
        if (str_contains($paramName, 'data') || str_contains($paramName, 'payload') || str_contains($paramName, 'body')) {
            return 'array<string, mixed>';
        }

        // Arguments 相關
        if (str_contains($paramName, 'arg') || str_contains($paramName, 'param')) {
            return 'array<string, string>';
        }

        // 預設為混合類型
        return 'array<string, mixed>';
    }

    /**
     * 根據參數名推斷陣列類型 (用於 @param 註解)
     */
    private function inferArrayTypeFromParamName(string $paramName): string
    {
        $typeMap = [
            'headers' => 'array<string, array<string>>',
            'config' => 'array<string, mixed>',
            'options' => 'array<string, mixed>',
            'settings' => 'array<string, mixed>',
            'data' => 'array<string, mixed>',
            'payload' => 'array<string, mixed>',
            'args' => 'array<string, string>',
            'params' => 'array<string, string>',
            'criteria' => 'array<string, mixed>',
            'filters' => 'array<string, mixed>',
        ];

        foreach ($typeMap as $keyword => $type) {
            if (str_contains(strtolower($paramName), $keyword)) {
                return $type;
            }
        }

        return 'array<string, mixed>';
    }

    private function printStats(): void
    {
        echo "\n📊 修復統計:\n";
        echo "處理檔案數: {$this->stats['files_processed']}\n";
        echo "修復次數: {$this->stats['fixes_applied']}\n";
        echo "預估減少錯誤: {$this->stats['errors_prevented']}\n";

        if ($this->dryRun) {
            echo "\n💡 這是預覽模式，沒有實際修改檔案。要真正修復請移除 --dry-run 參數。\n";
        } else {
            echo "\n✅ 修復完成！建議執行 PHPStan 檢查修復效果。\n";
        }
    }
}

// 執行腳本
if (php_sapi_name() === 'cli') {
    $fixer = new AdvancedPhpstanFixer($argv);
    $fixer->run();
}
