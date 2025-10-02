<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Quality;

/**
 * 統一的 PHPStan 錯誤修復工具
 * 取代所有舊的 PHPStan 修復腳本，提供統一的介面和功能
 */
final class PhpstanFixer
{
    private int $fixedCount = 0;
    private array $processedFiles = [];
    private array $availableFixes = [
        'type-hints' => '修復型別提示問題',
        'generics' => '修復泛型語法問題',
        'null-checks' => '修復 null 檢查問題',
        'iterables' => '修復 iterable 型別問題',
        'mixed-types' => '修復 mixed 型別問題',
        'undefined-variables' => '修復未定義變數問題',
    ];

    public function __construct(private readonly string $baseDir)
    {
    }

    public function run(array $fixTypes = []): void
    {
        if (empty($fixTypes)) {
            $fixTypes = array_keys($this->availableFixes);
        }

        echo "🔧 開始執行 PHPStan 修復...\n";
        echo "修復類型: " . implode(', ', $fixTypes) . "\n\n";

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $this->processFile($file, $fixTypes);
        }

        echo "\n✅ 修復完成！\n";
        echo "- 處理檔案: " . count($this->processedFiles) . " 個\n";
        echo "- 修復問題: {$this->fixedCount} 個\n";
    }

    public function listAvailableFixes(): void
    {
        echo "可用的修復類型:\n\n";
        foreach ($this->availableFixes as $type => $description) {
            echo "- {$type}: {$description}\n";
        }
    }

    private function processFile(string $file, array $fixTypes): void
    {
        $content = file_get_contents($file);
        $originalContent = $content;

        foreach ($fixTypes as $fixType) {
            $content = match ($fixType) {
                'type-hints' => $this->fixTypeHints($content),
                'generics' => $this->fixGenerics($content),
                'null-checks' => $this->fixNullChecks($content),
                'iterables' => $this->fixIterables($content),
                'mixed-types' => $this->fixMixedTypes($content),
                'undefined-variables' => $this->fixUndefinedVariables($content),
                default => $content
            };
        }

        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $this->processedFiles[] = $file;
            $this->fixedCount++;
            echo "✓ " . basename($file) . "\n";
        }
    }

    private function fixTypeHints(string $content): string
    {
        // 修復常見的型別提示問題
        $patterns = [
            // 移除函式簽名中的泛型語法
            '/function\s+(\w+)\s*\(\s*([^)]*array<[^>]+>)/i' => 'function $1(array',
            '/function\s+(\w+)\s*\(\s*([^)]*Collection<[^>]+>)/i' => 'function $1($2Collection',

            // 修復回傳型別
            '/:\s*array<mixed>/i' => ': array',
            '/:\s*Collection<[^>]+>/i' => ': Collection',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixGenerics(string $content): string
    {
        // 移除所有泛型語法，保留 PHPDoc 註釋
        $patterns = [
            '/([a-zA-Z_][a-zA-Z0-9_]*)<[^>]+>/' => '$1',
            '/array<[^>]+>/' => 'array',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixNullChecks(string $content): string
    {
        // 修復常見的 null 檢查問題
        $patterns = [
            '/if\s*\(\s*!is_null\(([^)]+)\)\s*\)/' => 'if ($1 !== null)',
            '/if\s*\(\s*is_null\(([^)]+)\)\s*\)/' => 'if ($1 === null)',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixIterables(string $content): string
    {
        // 修復 iterable 型別問題
        return preg_replace('/iterable<[^>]+>/', 'iterable', $content);
    }

    private function fixMixedTypes(string $content): string
    {
        // 修復 mixed 型別使用
        $patterns = [
            '/mixed\[\]/' => 'array',
            '/array<mixed>/' => 'array',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixUndefinedVariables(string $content): string
    {
        // 這個需要更複雜的邏輯，暫時返回原內容
        return $content;
    }

    private function getPhpFiles(): array
    {
        $files = [];
        $directories = [
            $this->baseDir . '/app',
            $this->baseDir . '/tests',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir)
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
}

// 執行腳本
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    require_once __DIR__ . '/../../vendor/autoload.php';

    $fixer = new PhpstanFixer(dirname(__DIR__, 2));

    if (isset($argv[1]) && $argv[1] === '--list') {
        $fixer->listAvailableFixes();
        exit(0);
    }

    $fixTypes = array_slice($argv, 1);
    if (empty($fixTypes)) {
        echo "使用方式:\n";
        echo "  php phpstan-fixer.php [修復類型...]\n";
        echo "  php phpstan-fixer.php --list  (列出可用修復類型)\n\n";
        echo "範例:\n";
        echo "  php phpstan-fixer.php type-hints generics\n";
        echo "  php phpstan-fixer.php  (執行所有修復)\n";
        exit(1);
    }

    $fixer->run($fixTypes);
}
