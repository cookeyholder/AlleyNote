#!/usr/bin/env php
<?php
/**
 * PHP 泛型語法錯誤修復工具
 * 修復在方法參數中錯誤使用泛型語法的問題
 * 
 * 根據 Context7 MCP 查詢結果：
 * - PHP 只支援在 PHPDoc 註解中使用泛型語法
 * - 實際的類型聲明中不能使用泛型語法
 * 
 * 使用方式:
 * php scripts/fix-php-generic-syntax.php [--dry-run]
 */

class PhpGenericSyntaxFixer
{
    private bool $dryRun = false;
    private int $fixCount = 0;
    private array<mixed> $fixedFiles = [];

    public function __construct(array<mixed> $args)
    {
        $this->dryRun = in_array('--dry-run', $args);
    }

    public function run(): void
    {
        echo "🔧 PHP 泛型語法錯誤修復工具\n";
        echo "模式: " . ($this->dryRun ? "預覽模式" : "修復模式") . "\n\n";

        $files = $this->findPhpFiles();

        foreach ($files as $file) {
            $this->processFile($file);
        }

        $this->printReport();
    }

    private function findPhpFiles(): array<mixed>
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
        $modifiedContent = $this->fixGenericSyntaxErrors($originalContent);

        if ($modifiedContent !== $originalContent) {
            $this->fixedFiles[] = $filePath;
            echo "📝 修復檔案: $filePath\n";

            if (!$this->dryRun) {
                file_put_contents($filePath, $modifiedContent);
            }
        }
    }

    private function fixGenericSyntaxErrors(string $content): string
    {
        $fixCount = 0;

        // 修復模式 1: 方法參數中的泛型語法錯誤
        // 例如: function method(array<mixed> $param) => function method(array<mixed> $param)
        $patterns = [
            // 匹配方法參數中的泛型陣列類型
            '/(\b(?:public|private|protected)\s+function\s+\w+\s*\([^)]*?)array<mixed>]+>(\s+\$\w+[^)]*)\)/' => function ($matches) use (&$fixCount) {
                $fixCount++;
                return $matches[1] . 'array<mixed>' . $matches[2] . ')';
            },

            // 匹配普通函數參數中的泛型陣列類型
            '/(\bfunction\s+\w+\s*\([^)]*?)array<mixed>]+>(\s+\$\w+[^)]*)\)/' => function ($matches) use (&$fixCount) {
                $fixCount++;
                return $matches[1] . 'array<mixed>' . $matches[2] . ')';
            },

            // 修復返回類型中的泛型語法
            '/(\)\s*:\s*)array<mixed>]+>(\s*\{)/' => function ($matches) use (&$fixCount) {
                $fixCount++;
                return $matches[1] . 'array<mixed>' . $matches[2];
            },

            // 修復建構子參數中的泛型語法
            '/(\b__construct\s*\([^)]*?)array<mixed>]+>(\s+\$\w+[^)]*)\)/' => function ($matches) use (&$fixCount) {
                $fixCount++;
                return $matches[1] . 'array<mixed>' . $matches[2] . ')';
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            }
        }

        $this->fixCount += $fixCount;

        return $content;
    }

    private function printReport(): void
    {
        echo "\n📊 修復統計:\n";
        echo "修復檔案數: " . count($this->fixedFiles) . "\n";
        echo "總修復次數: {$this->fixCount}\n";

        if (!empty($this->fixedFiles)) {
            echo "\n修復的檔案:\n";
            foreach ($this->fixedFiles as $file) {
                echo "  - $file\n";
            }
        }

        if ($this->dryRun) {
            echo "\n💡 這是預覽模式，要真正修復請移除 --dry-run 參數。\n";
        } else {
            echo "\n✅ 修復完成！建議執行 PHPStan 檢查修復效果。\n";
            echo "執行指令: sudo docker compose exec web ./vendor/bin/phpstan analyse --level=8\n";
        }

        echo "\n📝 重要提醒:\n";
        echo "- PHP 只支援在 PHPDoc 註解中使用泛型語法 (如 @param array<mixed> \$param)\n";
        echo "- 實際的類型聲明中不能使用泛型語法 (如 array<mixed> \$param)\n";
        echo "- 修復工具已將錯誤的泛型語法轉換為正確的 PHP 語法\n";
    }
}

// 執行腳本
if (php_sapi_name() === 'cli') {
    $fixer = new PhpGenericSyntaxFixer($argv);
    $fixer->run();
}
