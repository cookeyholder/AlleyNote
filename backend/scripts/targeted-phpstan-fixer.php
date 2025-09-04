<?php

declare(strict_types=1);

/**
 * 針對目前 PHPStan 錯誤的精準修復工具
 */
class SpecificPhpstanFixer
{
    private int $fixCount = 0;
    private array<mixed> $processedFiles = [];

    public function run(): void
    {
        echo "開始精準 PHPStan 修復...\n";

        // 獲取 PHPStan 報告的錯誤檔案
        $errorFiles = $this->getErrorFiles();

        foreach ($errorFiles as $file) {
            if (file_exists($file)) {
                echo "處理檔案: $file\n";
                $this->processFile($file);
            }
        }

        echo "\n修復完成！\n";
        echo "總修復次數: {$this->fixCount}\n";
        echo "修復的檔案數: " . count($this->processedFiles) . "\n";
    }

    private function getErrorFiles(): array<mixed>
    {
        // 從當前目錄結構推斷需要修復的檔案
        return [
            '/var/www/html/app/Application/Controllers/Api/V1/AttachmentController.php',
            '/var/www/html/app/Application/Controllers/Api/V1/AuthController.php',
            '/var/www/html/app/Application/Controllers/Api/V1/PostController.php',
            '/var/www/html/app/Application/Controllers/PostController.php',
            '/var/www/html/app/Application/Controllers/BaseController.php',
            '/var/www/html/app/Infrastructure/Http/Response.php',
            '/var/www/html/app/Infrastructure/Http/ServerRequest.php',
            '/var/www/html/tests/Integration/AuthControllerTest.php',
            '/var/www/html/tests/Integration/PostControllerTest.php',
        ];
    }

    private function processFile(string $filePath): void
    {
        $originalContent = file_get_contents($filePath);
        $content = $originalContent;
        $hasChanges = false;

        // 1. 修復方法參數中缺少 array<mixed> value type 的問題
        $content = $this->fixArrayParameterTypes($content, $hasChanges);

        // 2. 修復 json_encode 可能回傳 false 的問題
        $content = $this->fixJsonEncodeStreamWrite($content, $hasChanges);

        // 3. 修復 null coalesce 不必要的問題
        $content = $this->fixUnnecessaryNullCoalesce($content, $hasChanges);

        // 4. 修復 array<mixed> return type 缺少 value type
        $content = $this->fixArrayReturnTypes($content, $hasChanges);

        // 5. 修復屬性類型宣告
        $content = $this->fixPropertyTypes($content, $hasChanges);

        if ($hasChanges && $this->isValidPhp($content)) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = str_replace('/var/www/html/', '', $filePath);
            echo "  ✓ 已修復\n";
        }
    }

    private function fixArrayParameterTypes(string $content, bool &$hasChanges): string
    {
        // 修復常見的 array<mixed> 參數類型問題
        $patterns = [
            // 修復 $args 參數 (常用於路由參數)
            '/function\s+(\w+)\([^)]*array<mixed>\s+\$args[^)]*\)/' => function ($matches) use (&$hasChanges) {
                $replacement = str_replace('array<mixed> $args', 'array<mixed> $args', $matches[0]);
                if ($replacement !== $matches[0]) {
                    $hasChanges = true;
                    $this->fixCount++;
                    return $replacement;
                }
                return $matches[0];
            },

            // 修復其他常見的 array<mixed> 參數
            '/function\s+(\w+)\([^)]*array<mixed>\s+\$(\w+)[^)]*\)/' => function ($matches) use (&$hasChanges) {
                $paramName = $matches[2];
                $typeHints = [
                    'data' => 'array<mixed>',
                    'params' => 'array<mixed>',
                    'options' => 'array<mixed>',
                    'headers' => 'array<mixed>',
                    'attributes' => 'array<mixed>',
                    'config' => 'array<mixed>',
                    'rules' => 'array<mixed>',
                    'filters' => 'array<mixed>',
                ];

                $type = $typeHints[$paramName] ?? 'array<mixed>';
                $replacement = str_replace("array<mixed> \${$paramName}", "{$type} \${$paramName}", $matches[0]);

                if ($replacement !== $matches[0]) {
                    $hasChanges = true;
                    $this->fixCount++;
                    return $replacement;
                }
                return $matches[0];
            }
        ];

        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }

        return $content;
    }

    private function fixJsonEncodeStreamWrite(string $content, bool &$hasChanges): string
    {
        // 修復 json_encode(...) ?? '' 不必要的問題，以及 StreamInterface::write() 參數問題
        $patterns = [
            // 修復 json_encode 結果直接傳給 write
            '/(\$\w+->write\()\s*json_encode\(([^)]+)\)\s*(\))/' => function ($matches) use (&$hasChanges) {
                $hasChanges = true;
                $this->fixCount++;
                return $matches[1] . 'json_encode(' . $matches[2] . ') ?: \'\'' . $matches[3];
            },

            // 修復已有 ?? '' 但 PHPStan 認為不必要的情況
            '/json_encode\(([^)]+)\)\s*\?\?\s*\'\'/' => function ($matches) use (&$hasChanges) {
                // 檢查 json_encode 的參數，看是否真的可能回傳 false
                $param = $matches[1];
                if (strpos($param, 'JSON_THROW_ON_ERROR') !== false) {
                    // 如果有 JSON_THROW_ON_ERROR，不會回傳 false
                    $hasChanges = true;
                    $this->fixCount++;
                    return 'json_encode(' . $param . ')';
                }
                // 否則保持原樣，但改成更明確的形式
                return $matches[0];
            }
        ];

        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }

        return $content;
    }

    private function fixUnnecessaryNullCoalesce(string $content, bool &$hasChanges): string
    {
        // 修復不必要的 null coalesce 操作
        $patterns = [
            // 移除明確不可能為 null 的變數的 ?? 操作
            '/\$this->(\w+)\s*\?\?\s*[\'"]([^\'"]*)[\'"]/e' => function ($matches) use (&$hasChanges) {
                // 這個需要更仔細的分析，先跳過自動修復
                return $matches[0];
            }
        ];

        return $content;
    }

    private function fixArrayReturnTypes(string $content, bool &$hasChanges): string
    {
        // 修復方法回傳類型中的 array<mixed> 缺少 value type
        $patterns = [
            // 修復 ): array<mixed> 改為具體的 array<mixed> type
            '/function\s+(\w+)\([^)]*\):\s*array<mixed>\s*\{/' => function ($matches) use (&$hasChanges) {
                $methodName = $matches[1];
                $returnTypes = [
                    'toArray' => 'array<mixed>',
                    'getHeaders' => 'array<mixed>',
                    'getAttributes' => 'array<mixed>',
                    'getParams' => 'array<mixed>',
                    'getData' => 'array<mixed>',
                    'getConfig' => 'array<mixed>',
                    'getAll' => 'array<mixed>',
                    'getList' => 'array<mixed>',
                ];

                $type = $returnTypes[$methodName] ?? 'array<mixed>';
                $replacement = str_replace(': array<mixed> {', ": {$type} {", $matches[0]);

                if ($replacement !== $matches[0]) {
                    $hasChanges = true;
                    $this->fixCount++;
                    return $replacement;
                }
                return $matches[0];
            }
        ];

        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }

        return $content;
    }

    private function fixPropertyTypes(string $content, bool &$hasChanges): string
    {
        // 修復類別屬性的類型宣告
        $patterns = [
            // 修復 private array<mixed> 屬性
            '/private\s+array<mixed>\s+\$(\w+);/' => function ($matches) use (&$hasChanges) {
                $propName = $matches[1];
                $typeHints = [
                    'headers' => 'array<mixed>',
                    'attributes' => 'array<mixed>',
                    'params' => 'array<mixed>',
                    'data' => 'array<mixed>',
                    'config' => 'array<mixed>',
                    'options' => 'array<mixed>',
                    'rules' => 'array<mixed>',
                    'filters' => 'array<mixed>',
                    'middleware' => 'array<mixed>',
                    'errors' => 'array<mixed>',
                ];

                $type = $typeHints[$propName] ?? 'array<mixed>';
                $hasChanges = true;
                $this->fixCount++;
                return "private {$type} \${$propName};";
            },

            // 修復 protected array<mixed> 屬性
            '/protected\s+array<mixed>\s+\$(\w+);/' => function ($matches) use (&$hasChanges) {
                $propName = $matches[1];
                $type = 'array<mixed>'; // 預設類型
                $hasChanges = true;
                $this->fixCount++;
                return "protected {$type} \${$propName};";
            }
        ];

        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }

        return $content;
    }

    private function isValidPhp(string $code): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpstan_fix_');
        file_put_contents($tempFile, $code);

        $result = shell_exec("php -l $tempFile 2>&1");
        unlink($tempFile);

        return strpos($result, 'No syntax errors detected') !== false;
    }
}

$fixer = new SpecificPhpstanFixer();
$fixer->run();
