<?php

declare(strict_types=1);

/**
 * PHPStan Level 8 類型問題修復工具
 * 
 * 此工具用於修復常見的 PHPStan Level 8 類型問題
 */

class PHPStanTypeFixer
{
    private array $fixedFiles = [];
    private array $errors = [];
    private int $totalFixes = 0;

    public function run(): void
    {
        echo "開始修復 PHPStan Level 8 類型問題...\n";

        // 修復 array 類型問題
        $this->fixArrayTypeHints();

        // 修復 json_encode 問題
        $this->fixJsonEncodeIssues();

        // 修復 StreamInterface::write 問題
        $this->fixStreamWriteIssues();

        $this->printSummary();
    }

    private function fixArrayTypeHints(): void
    {
        echo "修復 array 類型提示問題...\n";

        $files = $this->findPhpFiles(['app/', 'tests/']);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;
            $fixed = false;

            // 修復常見的 array 類型問題
            $patterns = [
                // 方法參數 array 類型
                '/(\w+\(\s*)array(\s+\$\w+)/m' => '$1array<string, mixed>$2',
                // 屬性 array 類型
                '/(private|protected|public)\s+array(\s+\$\w+)/m' => '$1 array<string, mixed>$2',
                // 返回類型 array
                '/(\):\s*)array(\s*$)/m' => '$1array<string, mixed>$2',
                '/(\):\s*)array(\s*\{)/m' => '$1array<string, mixed>$2',
            ];

            foreach ($patterns as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== $content) {
                    $content = $newContent;
                    $fixed = true;
                }
            }

            if ($fixed && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $this->fixedFiles[] = $file;
                $this->totalFixes++;
                echo "  已修復: $file\n";
            }
        }
    }

    private function fixJsonEncodeIssues(): void
    {
        echo "修復 json_encode 問題...\n";

        $files = $this->findPhpFiles(['app/']);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;

            // 修復 json_encode 返回值檢查
            $pattern = '/json_encode\([^)]+\)(?!\s*\?\?\s*)/';
            $replacement = '(json_encode($0) ?? "")';

            // 更精確的替換
            $content = preg_replace_callback($pattern, function ($matches) {
                $call = $matches[0];
                if (strpos($call, '??') !== false) {
                    return $call; // 已經有 null 檢查
                }
                return "($call ?? '')";
            }, $content);

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $this->fixedFiles[] = $file;
                $this->totalFixes++;
                echo "  已修復: $file\n";
            }
        }
    }

    private function fixStreamWriteIssues(): void
    {
        echo "修復 StreamInterface::write 問題...\n";

        $files = $this->findPhpFiles(['app/']);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;

            // 修復 json_encode 傳給 write() 的問題
            $pattern = '/->write\(\s*json_encode\([^)]+\)\s*\)/';
            $replacement = '->write(json_encode($1) ?: "")';

            $content = preg_replace_callback($pattern, function ($matches) {
                $call = $matches[0];
                // 提取 json_encode 調用
                if (preg_match('/->write\(\s*(json_encode\([^)]+\))\s*\)/', $call, $jsonMatches)) {
                    $jsonCall = $jsonMatches[1];
                    return "->write(($jsonCall) ?: '')";
                }
                return $call;
            }, $content);

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $this->fixedFiles[] = $file;
                $this->totalFixes++;
                echo "  已修復: $file\n";
            }
        }
    }

    private function findPhpFiles(array $directories): array
    {
        $files = [];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private function isValidPhp(string $content): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'php_syntax_check');
        file_put_contents($tempFile, $content);

        $output = [];
        $returnCode = 0;
        exec("php -l $tempFile 2>&1", $output, $returnCode);

        unlink($tempFile);

        return $returnCode === 0;
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "修復摘要\n";
        echo str_repeat('=', 50) . "\n";
        echo "總修復次數: {$this->totalFixes}\n";
        echo "修復的檔案數: " . count(array_unique($this->fixedFiles)) . "\n";
        echo "錯誤數: " . count($this->errors) . "\n";

        if (!empty($this->errors)) {
            echo "\n錯誤:\n";
            foreach ($this->errors as $error) {
                echo "- $error\n";
            }
        }
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new PHPStanTypeFixer();
    $fixer->run();
}
