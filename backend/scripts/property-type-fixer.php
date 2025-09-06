<?php

declare(strict_types=1);

/**
 * PHPStan Level 10 屬性型別修復工具
 * 專注於修復 missingType.iterableValue 錯誤
 */
class PropertyTypeFixer
{
    private int $fixedFiles = 0;
    private array $fixCounts = [];

    public function __construct()
    {
        echo "🏷️  啟動 PHPStan Level 10 屬性型別修復工具...\n";
        echo "🎯 專注修復 missingType.iterableValue 錯誤\n\n";

        $this->fixCounts = [
            'array_properties' => 0,
            'public_properties' => 0,
            'private_properties' => 0,
            'protected_properties' => 0,
        ];
    }

    public function fixPropertyTypes(): void
    {
        echo "🔍 掃描 PHP 檔案...\n";
        $files = $this->getPhpFiles();
        echo "發現 " . count($files) . " 個 PHP 檔案\n\n";

        foreach ($files as $file) {
            $this->fixFileProperties($file);
        }

        $this->printSummary();
    }

    private function fixFileProperties(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復屬性型別定義
        $content = $this->fixArrayProperties($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles++;
            echo "  🏷️  修復屬性: " . basename($filePath) . "\n";
        }
    }

    private function fixArrayProperties(string $content): string
    {
        // 修復 private array $property; 型別的屬性
        $content = preg_replace_callback(
            '/(private|protected|public)\s+array\s+\$([a-zA-Z_][a-zA-Z0-9_]*);/',
            function ($matches) {
                $visibility = $matches[1];
                $propertyName = $matches[2];

                // 在屬性前添加 @var 註解
                $this->fixCounts['array_properties']++;
                $this->fixCounts["{$visibility}_properties"]++;

                return "/**\n     * @var array<string, mixed>\n     */\n    {$matches[0]}";
            },
            $content
        );

        // 修復沒有型別註解的 array 屬性
        $content = preg_replace_callback(
            '/\/\*\*[\s\S]*?\*\/\s*\n\s*(private|protected|public)\s+array\s+\$([a-zA-Z_][a-zA-Z0-9_]*);/',
            function ($matches) {
                $visibility = $matches[1];
                $propertyName = $matches[2];
                $fullMatch = $matches[0];

                // 檢查是否已經有 @var 註解
                if (!preg_match('/@var\s+array/', $fullMatch)) {
                    // 添加 @var 註解
                    $fixed = str_replace('*/', "     * @var array<string, mixed>\n     */", $fullMatch);
                    $this->fixCounts['array_properties']++;
                    $this->fixCounts["{$visibility}_properties"]++;
                    return $fixed;
                }

                return $fullMatch;
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
        echo "🏷️  屬性型別修復完成報告\n";
        echo str_repeat("=", 60) . "\n";
        echo "修復的檔案數：{$this->fixedFiles}\n\n";

        echo "屬性修復統計：\n";
        foreach ($this->fixCounts as $type => $count) {
            $description = match($type) {
                'array_properties' => '陣列屬性',
                'public_properties' => '公開屬性',
                'private_properties' => '私有屬性',
                'protected_properties' => '受保護屬性',
                default => $type
            };
            echo sprintf("  %-20s: %3d 個修復\n", $description, $count);
        }

        echo "\n🧪 執行測試驗證：\n";
        echo "docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Validation/ValidationResultTest.php\n";
        echo "\n📊 檢查錯誤改善：\n";
        echo "docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G app/Shared/Validation/ValidationResult.php\n";
        echo str_repeat("=", 60) . "\n";
    }
}

// 執行屬性型別修復
try {
    $fixer = new PropertyTypeFixer();
    $fixer->fixPropertyTypes();
} catch (Exception $e) {
    echo "❌ 修復過程中發生錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
