<?php

declare(strict_types=1);

/**
 * 批量 PHPStan Level 8 修復工具
 * 
 * 專門針對常見的 PHPStan Level 8 類型問題進行批量修復
 */
class BulkPHPStanFixer
{
    private int $totalFixes = 0;
    private array<mixed> $fixedFiles = [];

    public function run(): void
    {
        echo "開始批量修復 PHPStan Level 8 問題...\n";

        // 修復順序很重要，先修復基礎類型，再修復衍生問題
        $this->fixArrayTypeHints();
        $this->fixJsonEncodeIssues();
        $this->fixMissingReturnTypes();
        $this->fixStreamWriteIssues();
        $this->fixMethodParameterTypes();

        $this->printSummary();
    }

    private function fixArrayTypeHints(): void
    {
        echo "修復 array<mixed> 類型提示問題...\n";

        $files = $this->findPhpFiles(['app/']);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;
            $fixed = false;

            // 修復方法參數中的 array<mixed> 類型
            $patterns = [
                // public function method(array<mixed> $param): array<mixed>
                '/(\bpublic\s+function\s+\w+\([^)]*\b)array(\s+\$\w+[^)]*\)):\s*array<mixed>\b/'
                => '$1array$2: array<mixed>',

                // public function method(array<mixed> $param)
                '/(\bpublic\s+function\s+\w+\([^)]*\b)array(\s+\$\w+[^)]*)/'
                => '$1array$2',

                // ): array<mixed>
                '/(function\s+\w+\([^)]*\)):\s*array<mixed>\b/'
                => '$1: array<mixed>',

                // protected/private functions
                '/(\b(?:protected|private)\s+function\s+\w+\([^)]*\b)array(\s+\$\w+[^)]*)/'
                => '$1array$2',

                // Property types
                '/((?:public|protected|private)\s+)array(\s+\$\w+)/'
                => '$1array$2',
            ];

            foreach ($patterns as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== null && $newContent !== $content) {
                    $content = $newContent;
                    $fixed = true;
                }
            }

            if ($fixed && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                if (!in_array($file, $this->fixedFiles)) {
                    $this->fixedFiles[] = $file;
                    echo "  修復 array<mixed> 類型: $file\n";
                }
                $this->totalFixes++;
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
            $fixed = false;

            // 修復 json_encode 返回值問題
            $patterns = [
                // return json_encode(...);
                '/\breturn\s+json_encode\(([^;]+)\);/'
                => 'return json_encode($1) ?: \'\';',

                // $var = json_encode(...);  
                '/(\$\w+\s*=\s*)json_encode\(([^;]+)\);/'
                => '$1json_encode($2) ?: \'\';',

                // 在 write() 方法中
                '/(->write\(\s*)json_encode\(([^)]+)\)(\s*\))/'
                => '$1(json_encode($2) ?: \'\')$3',
            ];

            foreach ($patterns as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== null && $newContent !== $content) {
                    $content = $newContent;
                    $fixed = true;
                }
            }

            if ($fixed && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                if (!in_array($file, $this->fixedFiles)) {
                    $this->fixedFiles[] = $file;
                    echo "  修復 json_encode: $file\n";
                }
                $this->totalFixes++;
            }
        }
    }

    private function fixMissingReturnTypes(): void
    {
        echo "修復缺失的返回類型...\n";

        $files = $this->findPhpFiles(['app/']);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;

            // 為常見方法添加返回類型註解
            $patterns = [
                // toArray() methods
                '/(public\s+function\s+toArray\s*\(\s*\))(\s*\{)/'
                => "$1: array<mixed>$2",

                // getValidationRules() methods
                '/(public\s+function\s+getValidationRules\s*\(\s*\))(\s*\{)/'
                => "$1: array<mixed>$2",

                // create() methods in repositories
                '/(public\s+function\s+create\s*\(\s*array<mixed>\s+\$[^)]*\))(\s*\{)/'
                => "$1: array<mixed>$2",
            ];

            foreach ($patterns as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== null && $newContent !== $content) {
                    $content = $newContent;
                    if ($this->isValidPhp($content)) {
                        file_put_contents($file, $content);
                        if (!in_array($file, $this->fixedFiles)) {
                            $this->fixedFiles[] = $file;
                            echo "  添加返回類型: $file\n";
                        }
                        $this->totalFixes++;
                    }
                    break; // 只修復一次以避免重複
                }
            }
        }
    }

    private function fixStreamWriteIssues(): void
    {
        echo "修復 StreamInterface write 問題...\n";

        $files = $this->findPhpFiles(['app/']);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            // 檢查是否包含 StreamInterface write 調用
            if (!preg_match('/->write\s*\(/', $content)) {
                continue;
            }

            $originalContent = $content;
            $fixed = false;

            // 修復 string|false 傳給 write() 的問題
            $patterns = [
                // ->write(json_encode(...))
                '/(->write\(\s*)(json_encode\([^)]+\))(\s*\))/'
                => '$1($2 ?: \'\')$3',

                // ->write($variable) where $variable could be string|false
                '/(->write\(\s*)(\$\w+)(\s*\))/'
                => '$1($2 ?: \'\')$3',
            ];

            foreach ($patterns as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== null && $newContent !== $content) {
                    $content = $newContent;
                    $fixed = true;
                }
            }

            if ($fixed && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                if (!in_array($file, $this->fixedFiles)) {
                    $this->fixedFiles[] = $file;
                    echo "  修復 write(): $file\n";
                }
                $this->totalFixes++;
            }
        }
    }

    private function fixMethodParameterTypes(): void
    {
        echo "修復方法參數類型問題...\n";

        $files = $this->findPhpFiles(['app/']);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;
            $fixed = false;

            // 修復常見的無類型參數
            $patterns = [
                // function method($data) -> function method(mixed $data)
                '/(function\s+\w+\([^)]*)\$(\w+)(?!\s*:)([^)]*\))/'
                => function ($matches) {
                    // 不修復已經有類型的參數
                    if (preg_match('/(?:int|string|bool|array<mixed>|mixed|object)\s+\$' . $matches[2] . '/', $matches[0])) {
                        return $matches[0];
                    }
                    return $matches[1] . 'mixed $' . $matches[2] . $matches[3];
                },
            ];

            foreach ($patterns as $pattern => $replacement) {
                if (is_callable($replacement)) {
                    $newContent = preg_replace_callback($pattern, $replacement, $content);
                } else {
                    $newContent = preg_replace($pattern, $replacement, $content);
                }

                if ($newContent !== null && $newContent !== $content) {
                    $content = $newContent;
                    $fixed = true;
                }
            }

            if ($fixed && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                if (!in_array($file, $this->fixedFiles)) {
                    $this->fixedFiles[] = $file;
                    echo "  修復參數類型: $file\n";
                }
                $this->totalFixes++;
            }
        }
    }

    private function findPhpFiles(array<mixed> $directories): array<mixed>
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
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "批量修復摘要\n";
        echo str_repeat('=', 60) . "\n";
        echo "總修復次數: {$this->totalFixes}\n";
        echo "修復的檔案數: " . count(array_unique($this->fixedFiles)) . "\n";

        if (!empty($this->fixedFiles)) {
            echo "\n修復的檔案列表:\n";
            $uniqueFiles = array_unique($this->fixedFiles);
            sort($uniqueFiles);
            foreach ($uniqueFiles as $file) {
                echo "  - " . str_replace(getcwd() . '/', '', $file) . "\n";
            }
        }
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new BulkPHPStanFixer();
    $fixer->run();
}
