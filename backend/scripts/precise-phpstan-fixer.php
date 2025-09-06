<?php

declare(strict_types=1);

/**
 * 精確 PHPStan Level 10 修復工具
 * 只修復最安全和常見的錯誤類型
 */
class PrecisePHPStanLevel10Fixer
{
    private int $fixedFiles = 0;
    private array $fixCounts = [];

    public function __construct()
    {
        echo "🎯 啟動精確 PHPStan Level 10 修復工具...\n";
        echo "🔒 只修復最安全的錯誤類型\n\n";

        $this->fixCounts = [
            'array_generic' => 0,
            'self_return' => 0,
            'void_methods' => 0,
            'bool_methods' => 0,
            'mixed_params' => 0,
        ];
    }

    public function fixSafely(): void
    {
        echo "🔍 掃描 PHP 檔案...\n";
        $files = $this->getPhpFiles();
        echo "發現 " . count($files) . " 個 PHP 檔案\n\n";

        foreach ($files as $file) {
            $this->fixFileSafely($file);
        }

        $this->printSummary();
    }

    private function fixFileSafely(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 1. 只修復明顯的 array 泛型問題
        $content = $this->fixArrayGenerics($content);

        // 2. 只修復明顯的 self 返回型別
        $content = $this->fixSelfReturnTypes($content);

        // 3. 只修復明顯的 void 方法
        $content = $this->fixVoidMethods($content);

        // 4. 只修復明顯的 bool 方法
        $content = $this->fixBoolMethods($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles++;
            echo "  🔧 安全修復: " . basename($filePath) . "\n";
        }
    }

    private function fixArrayGenerics(string $content): string
    {
        // 只修復最明顯的 array 型別
        $patterns = [
            // @param array → @param array<string, mixed> (只有在沒有其他型別說明時)
            '/@param\s+array\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*(?:\n|\*\/|$)/' => '@param array<string, mixed> $\1',

            // @var array<mixed> → @var array<string, mixed> (只有在沒有其他型別說明時)
            '/@var\s+array\s*(?:\n|\*\/|$)/' => '@var array<string, mixed>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
                $this->fixCounts['array_generic']++;
            }
        }

        return $content;
    }

    private function fixSelfReturnTypes(string $content): string
    {
        // 修復建構子和工廠方法的 self 返回型別
        $content = preg_replace_callback(
            '/\/\*\*[\s\S]*?\*\/\s*\n\s*public\s+static\s+function\s+(create|make|from|of|build)\w*\s*\([^)]*\)\s*:\s*self\s*{/',
            function ($matches) {
                if (!preg_match('/@return\s+self/', $matches[0])) {
                    $fixed = str_replace('*/', "     * @return self\n     */", $matches[0]);
                    $this->fixCounts['self_return']++;
                    return $fixed;
                }
                return $matches[0];
            },
            $content
        );

        return $content;
    }

    private function fixVoidMethods(string $content): string
    {
        // 修復明顯的 void 方法
        $content = preg_replace_callback(
            '/\/\*\*[\s\S]*?\*\/\s*\n\s*(public|private|protected)\s+function\s+(set|add|remove|delete|clear|reset|init|execute)\w*\s*\([^)]*\)\s*:\s*void\s*{/',
            function ($matches) {
                if (!preg_match('/@return\s+void/', $matches[0])) {
                    $fixed = str_replace('*/', "     * @return void\n     */", $matches[0]);
                    $this->fixCounts['void_methods']++;
                    return $fixed;
                }
                return $matches[0];
            },
            $content
        );

        return $content;
    }

    private function fixBoolMethods(string $content): string
    {
        // 修復明顯的 bool 方法
        $content = preg_replace_callback(
            '/\/\*\*[\s\S]*?\*\/\s*\n\s*(public|private|protected)\s+function\s+(is|has|can|should|will|exists|contains|equals)\w*\s*\([^)]*\)\s*:\s*bool\s*{/',
            function ($matches) {
                if (!preg_match('/@return\s+bool/', $matches[0])) {
                    $fixed = str_replace('*/', "     * @return bool\n     */", $matches[0]);
                    $this->fixCounts['bool_methods']++;
                    return $fixed;
                }
                return $matches[0];
            },
            $content
        );

        return $content;
    }

    private function getPhpFiles(): array
    {
        $files = [];

        $directories = [
            'app/Application',
            'app/Domains',
            'app/Infrastructure',
            'app/Shared',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }

        return $files;
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🎯 精確修復完成報告\n";
        echo str_repeat("=", 60) . "\n";
        echo "修復的檔案數：{$this->fixedFiles}\n\n";

        echo "安全修復統計：\n";
        foreach ($this->fixCounts as $type => $count) {
            $description = match($type) {
                'array_generic' => 'Array 泛型型別',
                'self_return' => 'Self 返回型別',
                'void_methods' => 'Void 方法',
                'bool_methods' => 'Bool 方法',
                'mixed_params' => 'Mixed 參數',
                default => $type
            };
            echo sprintf("  %-20s: %3d 個修復\n", $description, $count);
        }

        echo "\n🧪 執行測試驗證：\n";
        echo "docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Validation/ValidationResultTest.php\n";
        echo "\n📊 檢查錯誤改善：\n";
        echo "docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G --error-format=table 2>&1 | tail -5\n";
        echo str_repeat("=", 60) . "\n";
    }
}

// 執行精確修復
try {
    $fixer = new PrecisePHPStanLevel10Fixer();
    $fixer->fixSafely();
} catch (Exception $e) {
    echo "❌ 修復過程中發生錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
