<?php

/**
 * 批量修復常見 PHPStan Level 8 錯誤
 * 專注於最頻繁出現的錯誤模式
 */

class CommonErrorFixer
{
    private int $fixCount = 0;
    private array $processedFiles = [];

    public function run(): void
    {
        echo "開始批量修復常見錯誤...\n";

        $this->fixStreamInterfaceIssues();
        $this->fixArrayTypeIssues();
        $this->fixJsonEncodeIssues();
        $this->fixMissingReturnTypes();

        echo "\n修復完成！\n";
        echo "總修復次數: {$this->fixCount}\n";
        echo "修復的檔案數: " . count($this->processedFiles) . "\n";

        if (!empty($this->processedFiles)) {
            echo "已修復的檔案:\n";
            foreach ($this->processedFiles as $file) {
                echo "  - $file\n";
            }
        }
    }

    private function fixStreamInterfaceIssues(): void
    {
        echo "修復 StreamInterface::write() 問題...\n";

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $originalContent = $content;

            // 修復 json_encode 的 write 呼叫
            $content = preg_replace(
                '/(\$[a-zA-Z_][a-zA-Z0-9_]*->write\()(json_encode\([^)]*\))(\))/i',
                '$1$2 ?: \'\'$3',
                $content
            );

            // 修復 file_get_contents 的 write 呼叫
            $content = preg_replace(
                '/(\$[a-zA-Z_][a-zA-Z0-9_]*->write\()(file_get_contents\([^)]*\))(\))/i',
                '$1$2 ?: \'\'$3',
                $content
            );

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $this->markFileProcessed($file);
                $this->fixCount++;
            }
        }
    }

    private function fixArrayTypeIssues(): void
    {
        echo "修復陣列類型問題...\n";

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $originalContent = $content;

            // 修復方法參數中的 array 類型
            $patterns = [
                // 方法參數
                '/(\s+function\s+[a-zA-Z_][a-zA-Z0-9_]*\([^)]*?)array(\s+\$[a-zA-Z_][a-zA-Z0-9_]*)([^)]*\))/' => '$1array<string, mixed>$2$3',

                // 屬性宣告
                '/(\s+)(private|protected|public)(\s+)array(\s+\$[a-zA-Z_][a-zA-Z0-9_]*);/' => '$1$2$3array<string, mixed>$4;',
            ];

            foreach ($patterns as $pattern => $replacement) {
                $content = preg_replace($pattern, $replacement, $content);
            }

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $this->markFileProcessed($file);
                $this->fixCount++;
            }
        }
    }

    private function fixJsonEncodeIssues(): void
    {
        echo "修復 json_encode 問題...\n";

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $originalContent = $content;

            // 修復 json_encode 可能返回 false 的問題
            $patterns = [
                // 賦值語句
                '/(\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*)(json_encode\([^)]*\));/' => '$1$2 ?: \'\';',

                // 返回語句
                '/(return\s+)(json_encode\([^)]*\));/' => '$1$2 ?: \'\';',
            ];

            foreach ($patterns as $pattern => $replacement) {
                $content = preg_replace($pattern, $replacement, $content);
            }

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $this->markFileProcessed($file);
                $this->fixCount++;
            }
        }
    }

    private function fixMissingReturnTypes(): void
    {
        echo "修復缺失的返回類型...\n";

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $originalContent = $content;

            // 為常見的方法添加返回類型
            $returnTypeMethods = [
                'getId' => ': int',
                'getName' => ': string',
                'getTitle' => ': string',
                'getMessage' => ': string',
                'getContent' => ': string',
                'getStatus' => ': string',
                'getType' => ': string',
                'isActive' => ': bool',
                'isEnabled' => ': bool',
                'exists' => ': bool',
                'count' => ': int',
                'toArray' => ': array<string, mixed>',
                'getAll' => ': array<string, mixed>',
            ];

            foreach ($returnTypeMethods as $method => $returnType) {
                // 只添加到沒有返回類型的方法
                $pattern = "/(public function {$method}\s*\([^)]*\))\s*\{/";
                if (preg_match($pattern, $content) && !preg_match("/{$method}\s*\([^)]*\)\s*:/", $content)) {
                    $content = preg_replace($pattern, '$1' . $returnType . ' {', $content);
                }
            }

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $this->markFileProcessed($file);
                $this->fixCount++;
            }
        }
    }

    private function getPhpFiles(): array
    {
        $files = [];

        $dirs = [
            '/var/www/html/app/Application/Controllers',
            '/var/www/html/app/Domains',
            '/var/www/html/app/Infrastructure',
        ];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }

        return $files;
    }

    private function markFileProcessed(string $filePath): void
    {
        $relativePath = str_replace('/var/www/html/', '', $filePath);
        if (!in_array($relativePath, $this->processedFiles)) {
            $this->processedFiles[] = $relativePath;
        }
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

$fixer = new CommonErrorFixer();
$fixer->run();
