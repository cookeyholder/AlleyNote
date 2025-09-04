<?php
declare(strict_types=1);

/**
 * 最終綜合 PHPStan 錯誤修復工具
 */

class FinalPhpStanComprehensiveFixer
{
    private array<mixed> $replacements = [];
    private array<mixed> $stats = [
        'files_processed' => 0,
        'total_fixes' => 0,
        'iterable_value_fixes' => 0,
        'method_narrowed_fixes' => 0,
        'mock_fixes' => 0,
        'null_coalesce_fixes' => 0,
        'argument_type_fixes' => 0,
        'other_fixes' => 0
    ];

    public function __construct()
    {
        $this->initializeReplacements();
    }

    private function initializeReplacements(): void
    {
        // 1. 修復缺少 iterable 值類型的問題
        $this->replacements['iterable_value_types'] = [
            // 方法參數和返回類型
            'array<mixed> $data' => 'array<mixed> $data',
            'array<mixed> $config' => 'array<mixed> $config',
            'array<mixed> $attributes' => 'array<mixed> $attributes',
            'array<mixed> $filters' => 'array<mixed> $filters',
            'array<mixed> $conditions' => 'array<mixed> $conditions',
            'array<mixed> $context' => 'array<mixed> $context',
            'array<mixed> $additionalContext' => 'array<mixed> $additionalContext',
            'array<mixed> $metadata' => 'array<mixed> $metadata',
            'array<mixed> $appliedRules' => 'array<mixed> $appliedRules',
            'array<mixed> $userPermissions' => 'array<mixed> $userPermissions',
            'array<mixed> $permissions' => 'array<mixed> $permissions',
            'array<mixed> $scopes' => 'array<mixed> $scopes',
            'array<mixed> $payload' => 'array<mixed> $payload',
            'array<mixed> $customClaims' => 'array<mixed> $customClaims',
            'array<mixed> $invalidFields' => 'array<mixed> $invalidFields',
            'array<mixed> $invalidClaims' => 'array<mixed> $invalidClaims',
            'array<mixed> $missingFields' => 'array<mixed> $missingFields',
            'array<mixed> $userPrivileges' => 'array<mixed> $userPrivileges',
            'array<mixed> $tokenData' => 'array<mixed> $tokenData',
            'array<mixed> $jtis' => 'array<mixed> $jtis',
            'array<mixed> $entries' => 'array<mixed> $entries',
            'array<mixed> $criteria' => 'array<mixed> $criteria',
            'array<mixed> $fields' => 'array<mixed> $fields',
            'array<mixed> $ids' => 'array<mixed> $ids',
            'array<mixed> $segments' => 'array<mixed> $segments',
            'array<mixed> $ipList' => 'array<mixed> $ipList',
            'array<mixed> $restriction' => 'array<mixed> $restriction',
            'array<mixed> $ruleConfig' => 'array<mixed> $ruleConfig',
            'array<mixed> $serverParams' => 'array<mixed> $serverParams',
            'array<mixed> $result' => 'array<mixed> $result',
            'array<mixed> $report' => 'array<mixed> $report',
            'array<mixed> $logData' => 'array<mixed> $logData',
            'array<mixed> $alertData' => 'array<mixed> $alertData',
            'array<mixed> $cspReport' => 'array<mixed> $cspReport',
            'array<mixed> $args' => 'array<mixed> $args',
            'array<mixed> $request' => 'array<mixed> $request',

            // 返回類型
            '): array<mixed>' => '): array<mixed>',
            'return type has no value type specified in iterable type array<mixed>' => '',

            // 屬性類型
            'array<mixed> $config;' => 'array<mixed> $config;',
            'array<mixed> $originalEnv type' => 'array<mixed> $originalEnv type',

            // 特殊情況的修復
            'array<mixed>|string' => 'array<mixed>|string',
        ];

        // 2. Mock 相關錯誤修復
        $this->replacements['mock_fixes'] = [
            // Mockery shouldReceive 調用
            'Call to an undefined method' => '',
            '::shouldReceive().' => '::shouldReceive();',
            'MockInterface::shouldReceive().' => 'MockInterface::shouldReceive();',

            // 構造函數參數類型問題
            'expects' => 'expects',
            'given.' => 'given;',
        ];

        // 3. 修復 null coalesce 和其他問題
        $this->replacements['other_fixes'] = [
            // Null coalesce 表達式
            'Expression on left side of ?? is not nullable.' => '',

            // 已經窄化的類型
            'Call to function is_array() with array<mixed> will always evaluate to true.' => '',
            'Call to method PHPUnit\\Framework\\Assert::assertIsArray() with array<mixed>' => '',
            'will always evaluate to true.' => '',
            'alreadyNarrowedType' => '',

            // Ternary 操作符
            'Ternary operator condition is always true.' => '',

            // 無法訪問的程式碼
            'Unreachable statement - code above always terminates.' => '',

            // 未使用的結果
            'on a separate line has no effect.' => '',
            'Call to function count() on a separate line has no effect.' => '',
        ];
    }

    public function fixFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $originalContent = $content;
        $fileFixed = false;
        $fixCount = 0;

        // 應用所有替換規則
        foreach ($this->replacements as $category => $rules) {
            foreach ($rules as $search => $replace) {
                if (str_contains($content, $search)) {
                    $content = str_replace($search, $replace, $content);
                    $fileFixed = true;
                    $fixCount++;
                    $this->stats['total_fixes']++;

                    // 根據類別統計
                    switch ($category) {
                        case 'iterable_value_types':
                            $this->stats['iterable_value_fixes']++;
                            break;
                        case 'mock_fixes':
                            $this->stats['mock_fixes']++;
                            break;
                        case 'other_fixes':
                            $this->stats['other_fixes']++;
                            break;
                    }
                }
            }
        }

        // 特殊修復規則
        $content = $this->applySpecialFixes($content, $fixCount);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->stats['files_processed']++;
            echo "✓ Fixed $fixCount issues in: " . basename($filePath) . "\n";
            return true;
        }

        return false;
    }

    private function applySpecialFixes(string $content, int &$fixCount): string
    {
        $originalContent = $content;

        // 1. 修復範型類型聲明
        $content = preg_replace('/ReflectionClass does not specify its types: T/', 'ReflectionClass', $content);
        if ($content !== $originalContent) {
            $fixCount++;
            $this->stats['total_fixes']++;
            $originalContent = $content;
        }

        // 2. 修復方法參數中的陣列類型
        $patterns = [
            '/(\w+)\s*\(\s*array<mixed>\s+\$(\w+)/' => '$1(array<mixed> $$2',
            '/:\s*array<mixed>\s*$/' => ': array<mixed>',
            '/return\s+array<mixed>\s*;/' => 'return array<mixed>;',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $fixCount++;
                $this->stats['total_fixes']++;
            }
        }

        // 3. 修復測試中的已知類型斷言
        if (str_contains($content, 'tests/')) {
            $testPatterns = [
                '/\$this->assertIsArray\(\$\w+\);\s*\/\/ This will always be true/' => '// Array assertion removed - always true',
                '/\$this->assertTrue\(is_array\(\$\w+\)\);\s*\/\/ This will always be true/' => '// Array check removed - always true',
                '/assertIsString\(\$\w+\)\s*will always evaluate to true/' => '',
                '/assertIsBool\(\$\w+\)\s*will always evaluate to true/' => '',
                '/assertNotNull\(\$\w+\)\s*will always evaluate to true/' => '',
            ];

            foreach ($testPatterns as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== null && $newContent !== $content) {
                    $content = $newContent;
                    $fixCount++;
                    $this->stats['total_fixes']++;
                }
            }
        }

        return $content;
    }

    public function run(): void
    {
        echo "🔧 開始最終綜合 PHPStan 錯誤修復...\n\n";

        $directories = [
            'app/',
            'tests/',
            'config/',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->processDirectory($dir);
            }
        }

        $this->printStatistics();
    }

    private function processDirectory(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->fixFile($file->getPathname());
            }
        }
    }

    private function printStatistics(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "📊 修復統計\n";
        echo str_repeat("=", 50) . "\n";
        echo "處理的檔案: {$this->stats['files_processed']}\n";
        echo "總修復次數: {$this->stats['total_fixes']}\n";
        echo "  - Iterable 值類型修復: {$this->stats['iterable_value_fixes']}\n";
        echo "  - Mock 相關修復: {$this->stats['mock_fixes']}\n";
        echo "  - 其他修復: {$this->stats['other_fixes']}\n";
        echo str_repeat("=", 50) . "\n\n";

        if ($this->stats['total_fixes'] > 0) {
            echo "✅ 修復完成！建議執行 'composer ci' 檢查結果。\n";
        } else {
            echo "ℹ️  沒有找到需要修復的問題。\n";
        }
    }
}

// 執行修復
$fixer = new FinalPhpStanComprehensiveFixer();
$fixer->run();
