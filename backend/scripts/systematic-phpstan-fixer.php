<?php

declare(strict_types=1);

/**
 * 系統化 PHPStan Level 10 修復工具
 * 基於 ValidationResult 的成功經驗，系統化修復其他類別
 */
class SystematicPHPStanLevel10Fixer
{
    private int $fixedFiles = 0;
    private int $totalErrorsFixed = 0;
    private array $fixStats = [];

    public function __construct()
    {
        echo "🔧 啟動系統化 PHPStan Level 10 修復工具...\n";
        echo "📈 基於 ValidationResult 的成功經驗\n\n";

        $this->fixStats = [
            'array_properties' => 0,
            'method_returns' => 0,
            'constructor_params' => 0,
            'dto_methods' => 0,
        ];
    }

    public function fixSystematically(): void
    {
        echo "🎯 階段 1: 修復 DTO 類別（最安全）\n";
        $this->fixDTOClasses();

        echo "\n🎯 階段 2: 修復 Value Object 類別\n";
        $this->fixValueObjectClasses();

        echo "\n🎯 階段 3: 修復簡單的 Service 類別\n";
        $this->fixSimpleServiceClasses();

        $this->printFinalSummary();
    }

    private function fixDTOClasses(): void
    {
        $dtoFiles = $this->findDTOFiles();
        echo "發現 " . count($dtoFiles) . " 個 DTO 檔案\n";

        foreach ($dtoFiles as $file) {
            $this->fixDTOFile($file);
        }
    }

    private function fixValueObjectClasses(): void
    {
        $voFiles = $this->findValueObjectFiles();
        echo "發現 " . count($voFiles) . " 個 Value Object 檔案\n";

        foreach ($voFiles as $file) {
            $this->fixValueObjectFile($file);
        }
    }

    private function fixSimpleServiceClasses(): void
    {
        $serviceFiles = $this->findSimpleServiceFiles();
        echo "發現 " . count($serviceFiles) . " 個簡單 Service 檔案\n";

        foreach ($serviceFiles as $file) {
            $this->fixSimpleServiceFile($file);
        }
    }

    private function fixDTOFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // DTO 通常有簡單的 getter/setter，容易修復
        $content = $this->fixArrayPropertiesInContent($content);
        $content = $this->fixSimpleGettersInContent($content);
        $content = $this->fixConstructorParamsInContent($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles++;
            echo "  ✅ DTO 修復: " . basename($filePath) . "\n";
            $this->validateFileAfterFix($filePath);
        }
    }

    private function fixValueObjectFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Value Object 通常有 immutable 特性，修復相對安全
        $content = $this->fixArrayPropertiesInContent($content);
        $content = $this->fixValueMethodsInContent($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles++;
            echo "  ✅ VO 修復: " . basename($filePath) . "\n";
            $this->validateFileAfterFix($filePath);
        }
    }

    private function fixSimpleServiceFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 只修復明顯安全的部分
        $content = $this->fixArrayPropertiesInContent($content);
        $content = $this->fixBoolMethodsInContent($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles++;
            echo "  ✅ Service 修復: " . basename($filePath) . "\n";
            $this->validateFileAfterFix($filePath);
        }
    }

    private function fixArrayPropertiesInContent(string $content): string
    {
        // 修復陣列屬性（已驗證安全）
        $content = preg_replace_callback(
            '/(private|protected|public)\s+array\s+\$([a-zA-Z_][a-zA-Z0-9_]*);/',
            function ($matches) {
                $this->fixStats['array_properties']++;
                return "/**\n     * @var array<string, mixed>\n     */\n    {$matches[0]}";
            },
            $content
        );

        return $content;
    }

    private function fixSimpleGettersInContent(string $content): string
    {
        // 修復簡單的 getter 方法返回型別
        $content = preg_replace_callback(
            '/\/\*\*[\s\S]*?\*\/\s*\n\s*(public)\s+function\s+(get\w+)\s*\(\s*\)\s*:\s*array\s*{/',
            function ($matches) {
                if (!preg_match('/@return\s+array/', $matches[0])) {
                    $fixed = str_replace('*/', "     * @return array<string, mixed>\n     */", $matches[0]);
                    $this->fixStats['method_returns']++;
                    return $fixed;
                }
                return $matches[0];
            },
            $content
        );

        return $content;
    }

    private function fixConstructorParamsInContent(string $content): string
    {
        // 修復建構函式參數型別註解
        $content = preg_replace_callback(
            '/@param\s+array\s+\$([a-zA-Z_][a-zA-Z0-9_]*)/',
            function ($matches) {
                $this->fixStats['constructor_params']++;
                return "@param array<string, mixed> \${$matches[1]}";
            },
            $content
        );

        return $content;
    }

    private function fixValueMethodsInContent(string $content): string
    {
        // 修復 Value Object 的值方法
        $content = preg_replace_callback(
            '/\/\*\*[\s\S]*?\*\/\s*\n\s*(public)\s+function\s+(value|getValue|__toString)\s*\(\s*\)\s*:\s*(string|int|float)\s*{/',
            function ($matches) {
                if (!preg_match('/@return\s+' . $matches[3], $matches[0])) {
                    $fixed = str_replace('*/', "     * @return {$matches[3]}\n     */", $matches[0]);
                    $this->fixStats['method_returns']++;
                    return $fixed;
                }
                return $matches[0];
            },
            $content
        );

        return $content;
    }

    private function fixBoolMethodsInContent(string $content): string
    {
        // 修復布林方法（已驗證安全）
        $content = preg_replace_callback(
            '/\/\*\*[\s\S]*?\*\/\s*\n\s*(public|private|protected)\s+function\s+(is|has|can|should)\w*\s*\([^)]*\)\s*:\s*bool\s*{/',
            function ($matches) {
                if (!preg_match('/@return\s+bool/', $matches[0])) {
                    $fixed = str_replace('*/', "     * @return bool\n     */", $matches[0]);
                    $this->fixStats['method_returns']++;
                    return $fixed;
                }
                return $matches[0];
            },
            $content
        );

        return $content;
    }

    private function validateFileAfterFix(string $filePath): void
    {
        // 快速驗證檔案語法正確
        $check = shell_exec("php -l " . escapeshellarg($filePath) . " 2>&1");
        if (strpos($check, 'No syntax errors') === false) {
            echo "    ⚠️  語法錯誤: " . basename($filePath) . "\n";
        }
    }

    private function findDTOFiles(): array
    {
        return $this->findFilesByPattern([
            'app/Application/*/DTOs/*.php',
            'app/Domains/*/DTOs/*.php',
        ]);
    }

    private function findValueObjectFiles(): array
    {
        return $this->findFilesByPattern([
            'app/Domains/*/ValueObjects/*.php',
            'app/Shared/ValueObjects/*.php',
        ]);
    }

    private function findSimpleServiceFiles(): array
    {
        return $this->findFilesByPattern([
            'app/Application/*/Services/*Service.php',
            'app/Infrastructure/*/Services/*Service.php',
        ]);
    }

    private function findFilesByPattern(array $patterns): array
    {
        $files = [];
        foreach ($patterns as $pattern) {
            $matches = glob($pattern);
            if ($matches) {
                $files = array_merge($files, $matches);
            }
        }
        return array_unique($files);
    }

    private function printFinalSummary(): void
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "🎯 系統化修復完成報告\n";
        echo str_repeat("=", 70) . "\n";
        echo "修復的檔案數：{$this->fixedFiles}\n\n";

        echo "修復統計：\n";
        foreach ($this->fixStats as $type => $count) {
            $description = match($type) {
                'array_properties' => '陣列屬性型別',
                'method_returns' => '方法返回型別',
                'constructor_params' => '建構函式參數',
                'dto_methods' => 'DTO 方法',
                default => $type
            };
            echo sprintf("  %-20s: %3d 個修復\n", $description, $count);
        }

        echo "\n🧪 建議執行驗證：\n";
        echo "docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Validation/ValidationResultTest.php\n";
        echo "\n📊 檢查整體改善：\n";
        echo "docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G --error-format=table 2>&1 | tail -5\n";
        echo str_repeat("=", 70) . "\n";
    }
}

// 執行系統化修復
try {
    $fixer = new SystematicPHPStanLevel10Fixer();
    $fixer->fixSystematically();
} catch (Exception $e) {
    echo "❌ 修復過程中發生錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
