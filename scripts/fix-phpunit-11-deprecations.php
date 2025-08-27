#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * PHPUnit 11 Deprecations 修復工具
 * 
 * 基於 Context7 MCP 查詢的 PHPUnit 11 deprecation 清單，
 * 自動修復所有已知的 deprecation 問題
 * 
 * @author GitHub Copilot
 * @since 1.0.0
 */

class PHPUnit11DeprecationFixer
{
    private array $stats = [
        'files_processed' => 0,
        'deprecations_fixed' => 0,
        'errors' => []
    ];

    private array $typeAssertionReplacements = [
        // PHPUnit 11.5.0 type assertion deprecations
        'assertContainsOnly' => [
            'array' => 'assertContainsOnlyArray',
            'bool' => 'assertContainsOnlyBool',
            'callable' => 'assertContainsOnlyCallable',
            'float' => 'assertContainsOnlyFloat',
            'int' => 'assertContainsOnlyInt',
            'iterable' => 'assertContainsOnlyIterable',
            'numeric' => 'assertContainsOnlyNumeric',
            'object' => 'assertContainsOnlyObject',
            'resource' => 'assertContainsOnlyResource',
            'scalar' => 'assertContainsOnlyScalar',
            'string' => 'assertContainsOnlyString',
        ],
        'assertNotContainsOnly' => [
            'array' => 'assertContainsNotOnlyArray',
            'bool' => 'assertContainsNotOnlyBool',
            'callable' => 'assertContainsNotOnlyCallable',
            'float' => 'assertContainsNotOnlyFloat',
            'int' => 'assertContainsNotOnlyInt',
            'iterable' => 'assertContainsNotOnlyIterable',
            'numeric' => 'assertContainsNotOnlyNumeric',
            'object' => 'assertContainsNotOnlyObject',
            'resource' => 'assertContainsNotOnlyResource',
            'scalar' => 'assertContainsNotOnlyScalar',
            'string' => 'assertContainsNotOnlyString',
        ]
    ];

    private array $isTypeReplacements = [
        'array' => 'isArray',
        'bool' => 'isBool',
        'callable' => 'isCallable',
        'float' => 'isFloat',
        'int' => 'isInt',
        'iterable' => 'isIterable',
        'null' => 'isNull',
        'numeric' => 'isNumeric',
        'object' => 'isObject',
        'resource' => 'isResource',
        'scalar' => 'isScalar',
        'string' => 'isString',
    ];

    public function fix(string $projectRoot): void
    {
        echo "🔧 開始修復 PHPUnit 11 Deprecations...\n";

        $testFiles = $this->findTestFiles($projectRoot);

        foreach ($testFiles as $filePath) {
            $this->processFile($filePath);
        }

        $this->printReport();
    }

    private function findTestFiles(string $projectRoot): array
    {
        $testFiles = [];
        $testDir = $projectRoot . '/tests';

        if (!is_dir($testDir)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($testDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $testFiles[] = $file->getRealPath();
            }
        }

        return $testFiles;
    }

    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復 assertContainsOnly deprecations
        $content = $this->fixContainsOnlyAssertions($content);

        // 修復 assertNotContainsOnly deprecations  
        $content = $this->fixNotContainsOnlyAssertions($content);

        // 修復 isType deprecations
        $content = $this->fixIsTypeAssertions($content);

        // 修復 @test annotation deprecations (轉換為 attributes)
        $content = $this->fixTestAnnotations($content);

        // 修復其他常見的 deprecation patterns
        $content = $this->fixMiscDeprecations($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->stats['files_processed']++;
            echo "  ✅ 修復: " . basename($filePath) . "\n";
        }
    }

    private function fixContainsOnlyAssertions(string $content): string
    {
        // Pattern: $this->assertContainsOnly('type', $array)
        $pattern = '/\$this->assertContainsOnly\s*\(\s*[\'"](\w+)[\'"]\s*,\s*([^)]+)\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $type = $matches[1];
            $arguments = $matches[2];

            if (isset($this->typeAssertionReplacements['assertContainsOnly'][$type])) {
                $newMethod = $this->typeAssertionReplacements['assertContainsOnly'][$type];
                $this->stats['deprecations_fixed']++;
                return "\$this->{$newMethod}({$arguments})";
            }

            return $matches[0];
        }, $content);
    }

    private function fixNotContainsOnlyAssertions(string $content): string
    {
        // Pattern: $this->assertNotContainsOnly('type', $array)
        $pattern = '/\$this->assertNotContainsOnly\s*\(\s*[\'"](\w+)[\'"]\s*,\s*([^)]+)\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $type = $matches[1];
            $arguments = $matches[2];

            if (isset($this->typeAssertionReplacements['assertNotContainsOnly'][$type])) {
                $newMethod = $this->typeAssertionReplacements['assertNotContainsOnly'][$type];
                $this->stats['deprecations_fixed']++;
                return "\$this->{$newMethod}({$arguments})";
            }

            return $matches[0];
        }, $content);
    }

    private function fixIsTypeAssertions(string $content): string
    {
        // Pattern: $this->isType('type', $value)
        $pattern = '/\$this->isType\s*\(\s*[\'"](\w+)[\'"]\s*,\s*([^)]+)\)/';

        return preg_replace_callback($pattern, function ($matches) {
            $type = $matches[1];
            $arguments = $matches[2];

            if (isset($this->isTypeReplacements[$type])) {
                $newMethod = $this->isTypeReplacements[$type];
                $this->stats['deprecations_fixed']++;
                return "\$this->{$newMethod}({$arguments})";
            }

            return $matches[0];
        }, $content);
    }

    private function fixTestAnnotations(string $content): string
    {
        // 將 /** @test */ annotations 轉換為 #[Test] attributes
        $pattern = '/\/\*\*\s*@test\s*\*\/\s*\n\s*public function/';

        if (preg_match($pattern, $content)) {
            // 確保有 use PHPUnit\Framework\Attributes\Test;
            if (!str_contains($content, 'use PHPUnit\\Framework\\Attributes\\Test;')) {
                $content = $this->addUseStatement($content, 'PHPUnit\\Framework\\Attributes\\Test');
            }

            $content = preg_replace($pattern, '#[Test]' . "\n    public function", $content);
            $this->stats['deprecations_fixed']++;
        }

        return $content;
    }

    private function fixMiscDeprecations(string $content): string
    {
        // 修復 @covers 和其他常見的 deprecation patterns

        // 如果有 @coversDefaultClass annotation，轉換為 attribute
        if (str_contains($content, '@coversDefaultClass')) {
            $pattern = '/\/\*\*[^*]*@coversDefaultClass\s+([^\s*]+)[^*]*\*\//';
            $content = preg_replace_callback($pattern, function ($matches) use ($content) {
                $className = $matches[1];
                return "#[CoversDefaultClass({$className})]";
            }, $content);

            // 確保有必要的 use 語句
            if (!str_contains($content, 'use PHPUnit\\Framework\\Attributes\\CoversDefaultClass;')) {
                $content = $this->addUseStatement($content, 'PHPUnit\\Framework\\Attributes\\CoversDefaultClass');
            }

            $this->stats['deprecations_fixed']++;
        }

        return $content;
    }

    private function addUseStatement(string $content, string $useClass): string
    {
        // 找到第一個 use 語句的位置
        if (preg_match('/^use\s+[^;]+;/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = (int)$matches[0][1];
            $newUse = "use {$useClass};\n";
            return substr_replace($content, $newUse, $insertPos, 0);
        }

        // 如果沒有 use 語句，在 namespace 後面加
        if (preg_match('/^namespace\s+[^;]+;\s*$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = (int)$matches[0][1] + strlen($matches[0][0]);
            $newUse = "\nuse {$useClass};\n";
            return substr_replace($content, $newUse, $insertPos, 0);
        }

        return $content;
    }

    private function printReport(): void
    {
        echo "\n📊 修復報告:\n";
        echo "- 處理檔案: {$this->stats['files_processed']} 個\n";
        echo "- 修復 Deprecations: {$this->stats['deprecations_fixed']} 個\n";

        if (!empty($this->stats['errors'])) {
            echo "\n❌ 錯誤:\n";
            foreach ($this->stats['errors'] as $error) {
                echo "  - {$error}\n";
            }
        }

        if ($this->stats['deprecations_fixed'] > 0) {
            echo "\n✅ PHPUnit 11 Deprecations 修復完成！\n";
        } else {
            echo "\n✨ 沒有發現需要修復的 deprecation 問題。\n";
        }
    }
}

// 執行修復
try {
    $fixer = new PHPUnit11DeprecationFixer();
    $fixer->fix(__DIR__ . '/..');
} catch (Exception $e) {
    echo "❌ 修復失敗: " . $e->getMessage() . "\n";
    exit(1);
}
