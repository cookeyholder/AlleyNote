<?php

declare(strict_types=1);

/**
 * 🧠 精準智慧 PHPStan Level 10 修復工具 v2.0
 *
 * 基於 ValidationResult.php 的成功經驗，精確修復常見錯誤模式
 */
class PreciseSmartPHPStanFixer
{
    private int $fixedFiles = 0;
    private array $fixCounts = [];
    private array $processedFiles = [];
    private bool $dryRun = false;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
        echo "🧠 精準智慧 PHPStan Level 10 修復工具 v2.0\n";
        echo "📚 基於 ValidationResult 成功模式\n";
        echo "模式：" . ($this->dryRun ? '預覽模式' : '修復模式') . "\n\n";

        $this->fixCounts = [
            'array_generics' => 0,
            'method_return_types' => 0,
            'type_casting' => 0,
            'phpDocs' => 0,
        ];
    }

    public function fixTargetedFiles(): void
    {
        // 根據之前的成功經驗，專注於特定檔案類型
        $targetPatterns = [
            'DTOs',        // Data Transfer Objects
            'ValueObjects', // Value Objects
            'Entities',    // Domain Entities
            'Services',    // Service classes
            'Controllers', // Controllers
            'Repositories', // Repositories
        ];

        foreach ($targetPatterns as $pattern) {
            $this->processFilesByPattern($pattern);
        }

        $this->printSummary();
    }

    private function processFilesByPattern(string $pattern): void
    {
        echo "🔍 處理 $pattern 檔案...\n";

        $files = $this->findFilesByPattern($pattern);
        $count = 0;

        foreach ($files as $file) {
            if ($count >= 5) { // 限制每種類型最多處理 5 個檔案
                echo "  ⚡ 已達到單批次限制，跳到下一種類型\n";
                break;
            }

            if ($this->fixFileCarefully($file)) {
                $count++;
            }
        }

        echo "\n";
    }

    private function findFilesByPattern(string $pattern): array
    {
        $command = "find app -name '*{$pattern}*.php' -type f | head -10";
        $output = shell_exec($command);

        return $output ? array_filter(explode("\n", trim($output))) : [];
    }

    private function fixFileCarefully(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 1. 安全地修復 Array 泛型型別
        $content = $this->fixArrayGenerics($content);

        // 2. 精確地添加方法返回型別註解
        $content = $this->fixMethodReturnTypeDocComments($content);

        // 3. 修復型別轉換問題
        $content = $this->fixTypeCastingIssues($content);

        // 4. 驗證 PHPDoc 語法
        if (!$this->validatePhpDocSyntax($content)) {
            echo "  ⚠️  PHPDoc 語法錯誤，跳過: " . basename($filePath) . "\n";
            return false;
        }

        if ($content !== $originalContent) {
            if (!$this->dryRun) {
                file_put_contents($filePath, $content);
            }
            $this->fixedFiles++;
            $this->processedFiles[] = basename($filePath);
            echo "  ✅ 修復: " . basename($filePath) . "\n";
            return true;
        }

        return false;
    }

    private function fixArrayGenerics(string $content): string
    {
        // 精確匹配需要修復的 Array 模式
        $patterns = [
            // 修復屬性的 Array 型別
            '/(\s+)(private|protected|public)\s+array\s+\$([a-zA-Z_][a-zA-Z0-9_]*);/' => function($matches) {
                $this->fixCounts['array_generics']++;
                $indent = $matches[1];
                $visibility = $matches[2];
                $property = $matches[3];

                // 根據變數名推測型別
                $type = $this->guessArrayType($property);

                return "{$indent}/** @var {$type} */\n{$indent}{$visibility} array \${$property};";
            },
        ];

        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }

        return $content;
    }

    private function guessArrayType(string $propertyName): string
    {
        // 基於命名慣例猜測型別
        $typeMap = [
            'errors' => 'array<string, array<string>>',
            'rules' => 'array<string, array<string>>',
            'data' => 'array<string, mixed>',
            'config' => 'array<string, mixed>',
            'options' => 'array<string, mixed>',
            'params' => 'array<string, mixed>',
            'items' => 'array<int, mixed>',
            'list' => 'array<int, mixed>',
            'cache' => 'array<string, mixed>',
        ];

        foreach ($typeMap as $keyword => $type) {
            if (stripos($propertyName, $keyword) !== false) {
                return $type;
            }
        }

        return 'array<string, mixed>';
    }

    private function fixMethodReturnTypeDocComments(string $content): string
    {
        // 為返回 array 的方法添加精確的 PHPDoc
        $pattern = '/(public|private|protected)\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*:\s*array\s*\{/';

        $fixCounts = &$this->fixCounts;
        $content = preg_replace_callback($pattern, function($matches) use (&$fixCounts, $content) {
            $visibility = $matches[1];
            $methodName = $matches[2];

            // 根據方法名推測返回型別
            $returnType = $this->guessReturnType($methodName);

            // 檢查是否已經有 PHPDoc
            $beforeMatch = substr($content, 0, strpos($content, $matches[0]));
            if (!preg_match('/\*\/\s*$/', $beforeMatch)) {
                $fixCounts['method_return_types']++;

                $indentation = $this->getIndentation($matches[0], $content);
                $phpDoc = "{$indentation}/**\n{$indentation} * @return {$returnType}\n{$indentation} */\n{$indentation}";

                return $phpDoc . $matches[0];
            }

            return $matches[0];
        }, $content);

        return $content;
    }

    private function guessReturnType(string $methodName): string
    {
        $returnTypeMap = [
            'getErrors' => 'array<string, array<string>>',
            'getRules' => 'array<string, array<string>>',
            'getData' => 'array<string, mixed>',
            'getConfig' => 'array<string, mixed>',
            'getOptions' => 'array<string, mixed>',
            'getList' => 'array<int, mixed>',
            'getAll' => 'array<string>',
            'toArray' => 'array<string, mixed>',
            'jsonSerialize' => 'array<string, mixed>',
        ];

        foreach ($returnTypeMap as $pattern => $type) {
            if (stripos($methodName, $pattern) !== false || $methodName === $pattern) {
                return $type;
            }
        }

        return 'array<string, mixed>';
    }

    private function fixTypeCastingIssues(string $content): string
    {
        // 修復常見的型別轉換問題
        $fixes = [
            // 修復 array access 問題
            '/return \$this->([a-zA-Z_][a-zA-Z0-9_]*)\[\$([a-zA-Z_][a-zA-Z0-9_]*)\] \?\? \[\];/' =>
                'return (array)($this->$1[$$$2] ?? []);',

            // 修復 array_merge 參數問題
            '/array_merge\(\.\.\.\$this->([a-zA-Z_][a-zA-Z0-9_]*)\)/' =>
                'array_merge(...array_values($this->$1))',
        ];

        foreach ($fixes as $pattern => $replacement) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
                $this->fixCounts['type_casting']++;
            }
        }

        return $content;
    }

    private function validatePhpDocSyntax(string $content): bool
    {
        // 簡單的 PHPDoc 語法驗證
        $invalidPatterns = [
            '/@return\s+array<[^>]*>>/i',  // 雙重 >
            '/@param\s+array<[^>]*>>/i',   // 雙重 >
            '/@var\s+array<[^>]*>>/i',     // 雙重 >
        ];

        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
    }

    private function getIndentation(string $match, string $content): string
    {
        $position = strpos($content, $match);
        $beforeMatch = substr($content, 0, $position);
        $lastNewline = strrpos($beforeMatch, "\n");

        if ($lastNewline === false) {
            return '';
        }

        $line = substr($beforeMatch, $lastNewline + 1);
        return str_repeat(' ', strlen($line) - strlen(ltrim($line)));
    }

    private function printSummary(): void
    {
        echo str_repeat("=", 60) . "\n";
        echo "🧠 精準智慧修復完成報告\n";
        echo str_repeat("=", 60) . "\n";
        echo "處理模式：" . ($this->dryRun ? '預覽模式' : '修復模式') . "\n";
        echo "修復的檔案數：{$this->fixedFiles}\n\n";

        echo "修復類別統計：\n";
        foreach ($this->fixCounts as $type => $count) {
            $description = match($type) {
                'array_generics' => 'Array 泛型型別',
                'method_return_types' => '方法返回型別註解',
                'type_casting' => '型別轉換問題',
                'phpDocs' => 'PHPDoc 語法修復',
                default => $type
            };
            echo sprintf("  %-20s: %3d 個修復\n", $description, $count);
        }

        if (!empty($this->processedFiles)) {
            echo "\n📁 已處理的檔案：\n";
            foreach ($this->processedFiles as $file) {
                echo "  ✅ $file\n";
            }
        }

        echo "\n🎯 建議下一步：\n";
        echo "1. 驗證核心功能：docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Validation/ValidationResultTest.php\n";
        echo "2. 檢查改善情況：docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G --error-format=table 2>&1 | tail -5\n";
        echo "3. 若效果良好，可重複執行進行更多修復\n";
        echo str_repeat("=", 60) . "\n";
    }
}

// 執行修復
$dryRun = in_array('--dry-run', $argv);
$fixer = new PreciseSmartPHPStanFixer($dryRun);
$fixer->fixTargetedFiles();
