<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Quality;

/**
 * 統一的語法修復工具
 * 取代所有舊的語法修復腳本，提供統一的介面和功能
 */
final class UnifiedSyntaxFixer
{
    private int $fixedCount = 0;
    private array $processedFiles = [];
    private array $availableFixes = [
        'basic-syntax' => '修復基本語法錯誤',
        'generics' => '移除無效的泛型語法',
        'string-interpolation' => '修復字串插值問題',
        'try-catch' => '修復 try-catch 語法問題',
        'method-signatures' => '修復方法簽名問題',
        'property-syntax' => '修復屬性語法問題',
        'json-encode' => '修復 JSON 編碼問題',
        'isset-errors' => '修復 isset 相關問題',
    ];

    public function __construct(private readonly string $baseDir)
    {
    }

    public function run(array $fixTypes = []): void
    {
        if (empty($fixTypes)) {
            $fixTypes = array_keys($this->availableFixes);
        }

        echo "🔧 開始執行語法修復...\n";
        echo "修復類型: " . implode(', ', $fixTypes) . "\n\n";

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $this->processFile($file, $fixTypes);
        }

        echo "\n✅ 語法修復完成！\n";
        echo "- 處理檔案: " . count($this->processedFiles) . " 個\n";
        echo "- 修復問題: {$this->fixedCount} 個\n";
    }

    public function listAvailableFixes(): void
    {
        echo "可用的語法修復類型:\n\n";
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
                'basic-syntax' => $this->fixBasicSyntax($content),
                'generics' => $this->removeGenerics($content),
                'string-interpolation' => $this->fixStringInterpolation($content),
                'try-catch' => $this->fixTryCatch($content),
                'method-signatures' => $this->fixMethodSignatures($content),
                'property-syntax' => $this->fixPropertySyntax($content),
                'json-encode' => $this->fixJsonEncode($content),
                'isset-errors' => $this->fixIssetErrors($content),
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

    private function fixBasicSyntax(string $content): string
    {
        $patterns = [
            // 修復基本的語法錯誤
            '/\s+\?>/' => "\n?>",
            '/^\s*\n/' => '',
            '/\n\s*\n\s*\n/' => "\n\n",
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function removeGenerics(string $content): string
    {
        // 移除所有泛型語法
        $patterns = [
            '/([a-zA-Z_][a-zA-Z0-9_]*)<[^>]+>/' => '$1',
            '/array<[^>]+>/' => 'array',
            '/Collection<[^>]+>/' => 'Collection',
            '/Iterator<[^>]+>/' => 'Iterator',
            '/Iterable<[^>]+>/' => 'iterable',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixStringInterpolation(string $content): string
    {
        // 修復字串插值問題
        $patterns = [
            '/"\{([^}]+)\}"/' => '"$1"',
            "/'\\{([^}]+)\\}'/" => "'$1'",
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixTryCatch(string $content): string
    {
        // 修復 try-catch 語法問題
        $patterns = [
            '/catch\s*\(\s*(\w+)\s+\$([^)]+)\s*\)\s*\{/' => 'catch ($1 $$$2) {',
            '/catch\s*\(\s*\|\s*(\w+)\s*\)/' => 'catch ($1 $e)',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixMethodSignatures(string $content): string
    {
        // 修復方法簽名問題
        $patterns = [
            '/function\s+(\w+)\s*\([^)]*\)\s*:\s*([^{]+)<[^>]+>/' => 'function $1(): $2',
            '/public\s+function\s+(\w+)\s*\([^)]*\)\s*:\s*([^{]+)<[^>]+>/' => 'public function $1(): $2',
            '/private\s+function\s+(\w+)\s*\([^)]*\)\s*:\s*([^{]+)<[^>]+>/' => 'private function $1(): $2',
            '/protected\s+function\s+(\w+)\s*\([^)]*\)\s*:\s*([^{]+)<[^>]+>/' => 'protected function $1(): $2',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixPropertySyntax(string $content): string
    {
        // 修復屬性語法問題
        $patterns = [
            '/private\s+([^$\s]+)<[^>]+>\s+\$/' => 'private $1 $',
            '/protected\s+([^$\s]+)<[^>]+>\s+\$/' => 'protected $1 $',
            '/public\s+([^$\s]+)<[^>]+>\s+\$/' => 'public $1 $',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixJsonEncode(string $content): string
    {
        // 修復 JSON 編碼問題
        $patterns = [
            '/json_encode\([^)]+\)\s*\?\?\s*["\']["\']/' => 'json_encode($1) ?: "{}"',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixIssetErrors(string $content): string
    {
        // 修復 isset 相關問題
        $patterns = [
            '/isset\(\$([^)]+)\[["\']([^"\']+)["\']\]\)/' => 'isset($$1["$2"])',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

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

    $fixer = new UnifiedSyntaxFixer(dirname(__DIR__, 2));

    if (isset($argv[1]) && $argv[1] === '--list') {
        $fixer->listAvailableFixes();
        exit(0);
    }

    $fixTypes = array_slice($argv, 1);
    if (empty($fixTypes)) {
        echo "使用方式:\n";
        echo "  php unified-syntax-fixer.php [修復類型...]\n";
        echo "  php unified-syntax-fixer.php --list  (列出可用修復類型)\n\n";
        echo "範例:\n";
        echo "  php unified-syntax-fixer.php basic-syntax generics\n";
        echo "  php unified-syntax-fixer.php  (執行所有修復)\n";
        exit(1);
    }

    $fixer->run($fixTypes);
}
