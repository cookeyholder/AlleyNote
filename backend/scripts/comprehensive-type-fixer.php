#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 全面型別錯誤修復器
 *
 * 處理 PHPStan Level 10 的所有型別安全問題
 */

class ComprehensiveTypeFixer
{
    private string $baseDir;
    private array $appliedFixes = [];
    private array $errorLog = [];
    private int $totalFixes = 0;

    // 型別修復映射
    private array $castFixPatterns = [
        // cast.int 修復
        '(int) ($params[' => '$this->getInt($params, ',
        '(int) ($data[' => '$this->getInt($data, ',
        '(int) ($args[' => '$this->getInt($args, ',
        '(int) ($request->getQueryParams()[' => '$this->getInt($request->getQueryParams(), ',

        // cast.string 修復
        '(string) ($params[' => '$this->getString($params, ',
        '(string) ($data[' => '$this->getString($data, ',
        '(string) ($args[' => '$this->getString($args, ',

        // cast.bool 修復
        '(bool) ($params[' => '$this->getBool($params, ',
        '(bool) ($data[' => '$this->getBool($data, ',
    ];

    // 常見型別註解
    private array $methodAnnotations = [
        'toArray' => '@return array<string, mixed>',
        'jsonSerialize' => '@return array<string, mixed>',
        'getQueryParams' => '@return array<string, string>',
        'getParsedBody' => '@return array<string, mixed>|object|null',
        'getHeaders' => '@return array<string, string[]>',
        'getAttributes' => '@return array<string, mixed>',
        'getServerParams' => '@return array<string, mixed>',
        'getCookieParams' => '@return array<string, string>',
        'getUploadedFiles' => '@return array<string, mixed>',
    ];

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🔧 啟動全面型別錯誤修復器...\n\n";

        try {
            // 1. 獲取所有 PHP 檔案
            $files = $this->getAllPHPFiles();
            echo "📁 發現 " . count($files) . " 個 PHP 檔案\n\n";

            // 2. 批量修復每個檔案
            foreach ($files as $file) {
                $this->fixFile($file);
            }

            // 3. 生成報告
            $this->generateReport();

        } catch (Exception $e) {
            echo "❌ 修復過程中發生錯誤: {$e->getMessage()}\n";
        }
    }

    private function getAllPHPFiles(): array
    {
        $files = [];

        // 掃描主要目錄
        $directories = [
            $this->baseDir . '/app',
            $this->baseDir . '/tests',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectory($dir, $files);
            }
        }

        return $files;
    }

    private function scanDirectory(string $dir, array &$files): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    private function fixFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $relativePath = str_replace($this->baseDir . '/', '', $filePath);

        // 跳過 vendor 目錄
        if (strpos($relativePath, 'vendor/') === 0) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fixes = 0;

        // 應用各種修復
        $content = $this->fixCastErrors($content, $fixes);
        $content = $this->fixMissingTypeAnnotations($content, $fixes);
        $content = $this->fixArgumentTypeErrors($content, $fixes);
        $content = $this->fixIterableValueTypes($content, $fixes);
        $content = $this->addHelperMethods($content, $fixes);

        if ($fixes > 0) {
            file_put_contents($filePath, $content);
            $this->appliedFixes[$relativePath] = $fixes;
            $this->totalFixes += $fixes;
            echo "✅ 修復: $relativePath ($fixes 個修復)\n";
        }
    }

    private function fixCastErrors(string $content, int &$fixes): string
    {
        $originalContent = $content;

        // 修復 cast.int 錯誤
        $patterns = [
            // (int) ($data['key'] ?? default) -> $this->getInt($data, 'key', default)
            '/\(int\)\s*\(\$(\w+)\[\'([^\']+)\'\]\s*\?\?\s*([^)]+)\)/' => '$this->getInt($\1, \'\2\', \3)',
            '/\(int\)\s*\(\$(\w+)\[\"([^\"]+)\"\]\s*\?\?\s*([^)]+)\)/' => '$this->getInt($\1, \"\2\", \3)',

            // (string) ($data['key'] ?? default) -> $this->getString($data, 'key', default)
            '/\(string\)\s*\(\$(\w+)\[\'([^\']+)\'\]\s*\?\?\s*([^)]+)\)/' => '$this->getString($\1, \'\2\', \3)',
            '/\(string\)\s*\(\$(\w+)\[\"([^\"]+)\"\]\s*\?\?\s*([^)]+)\)/' => '$this->getString($\1, \"\2\", \3)',

            // (bool) ($data['key'] ?? default) -> $this->getBool($data, 'key', default)
            '/\(bool\)\s*\(\$(\w+)\[\'([^\']+)\'\]\s*\?\?\s*([^)]+)\)/' => '$this->getBool($\1, \'\2\', \3)',
            '/\(bool\)\s*\(\$(\w+)\[\"([^\"]+)\"\]\s*\?\?\s*([^)]+)\)/' => '$this->getBool($\1, \"\2\", \3)',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        return $content;
    }

    private function fixMissingTypeAnnotations(string $content, int &$fixes): string
    {
        // 修復缺少型別註解的方法參數
        $patterns = [
            // public function method(array $args) -> public function method(array $args): type + @param annotation
            '/public function (\w+)\(array \$(\w+)\):\s*(\w+)/' => function($matches) use (&$fixes) {
                $methodName = $matches[1];
                $paramName = $matches[2];
                $returnType = $matches[3];

                $fixes++;
                return "/**\n     * @param array<string, mixed> \${$paramName}\n     */\n    public function {$methodName}(array \${$paramName}): {$returnType}";
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== $content) {
                    $content = $newContent;
                    $fixes++;
                }
            }
        }

        return $content;
    }

    private function fixArgumentTypeErrors(string $content, int &$fixes): string
    {
        // 修復參數型別不匹配
        $patterns = [
            // 修復 mixed 型別傳遞給特定型別參數的問題
            '/(\w+)::from\(\$(\w+)\[\'([^\']+)\'\]\s*\?\?\s*\'([^\']*)\'\)/' => function($matches) use (&$fixes) {
                $class = $matches[1];
                $var = $matches[2];
                $key = $matches[3];
                $default = $matches[4];

                $fixes++;
                return "{$class}::from(\$this->getString(\${$var}, '{$key}', '{$default}'))";
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            }
        }

        return $content;
    }

    private function fixIterableValueTypes(string $content, int &$fixes): string
    {
        // 添加缺少的 iterable 型別註解
        $lines = explode("\n", $content);
        $newLines = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 檢查是否為方法定義且缺少型別註解
            if (preg_match('/^\s*(public|private|protected)\s+function\s+(\w+)\s*\([^)]*array[^)]*\)\s*:\s*\w+/', $line)) {
                // 檢查上一行是否已有文檔塊
                if ($i === 0 || !preg_match('/^\s*\*/', $lines[$i-1])) {
                    // 添加基本的文檔塊
                    $indent = $this->getLineIndentation($line);
                    $newLines[] = $indent . '/**';
                    $newLines[] = $indent . ' * @param array<string, mixed> $args';
                    $newLines[] = $indent . ' */';
                    $fixes++;
                }
            }

            $newLines[] = $line;
        }

        return implode("\n", $newLines);
    }

    private function addHelperMethods(string $content, int &$fixes): string
    {
        // 檢查是否為 Controller 類別且需要添加 helper 方法
        if (strpos($content, 'class ') !== false &&
            strpos($content, 'Controller') !== false &&
            strpos($content, 'getInt(') !== false &&
            strpos($content, 'private function getInt(') === false) {

            $helperMethods = $this->getHelperMethodsCode();

            // 在類別結束前添加 helper 方法
            $content = preg_replace('/(\n}\s*)$/', "\n{$helperMethods}\n}", $content);
            $fixes++;
        }

        return $content;
    }

    private function getHelperMethodsCode(): string
    {
        return '
    /**
     * 安全地從陣列中取得整數值
     * @param array<string, mixed> $data
     */
    private function getInt(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? $default;
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * 安全地從陣列中取得字串值
     * @param array<string, mixed> $data
     */
    private function getString(array $data, string $key, string $default = \'\'): string
    {
        $value = $data[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /**
     * 安全地從陣列中取得布林值
     * @param array<string, mixed> $data
     */
    private function getBool(array $data, string $key, bool $default = false): bool
    {
        $value = $data[$key] ?? $default;
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), [\'1\', \'true\', \'yes\', \'on\'], true);
        }
        return (bool) $value;
    }';
    }

    private function getLineIndentation(string $line): string
    {
        preg_match('/^(\s*)/', $line, $matches);
        return $matches[1] ?? '    ';
    }

    private function generateReport(): void
    {
        echo "\n📋 全面型別錯誤修復報告\n";
        echo "=" . str_repeat("=", 50) . "\n";
        echo "總修復數量: {$this->totalFixes}\n";
        echo "修復檔案數: " . count($this->appliedFixes) . "\n\n";

        if (!empty($this->appliedFixes)) {
            echo "修復詳情:\n";
            foreach ($this->appliedFixes as $file => $fixes) {
                echo "  • $file: $fixes 個修復\n";
            }
        }

        echo "\n✅ 全面型別錯誤修復完成！\n";
        echo "💡 建議執行 PHPStan 確認修復效果\n";
    }
}

// 執行修復
$fixer = new ComprehensiveTypeFixer();
$fixer->run();
