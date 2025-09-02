<?php

declare(strict_types=1);

/**
 * 專門修復 PHPStan missingType.generics 錯誤的腳本
 * 主要針對測試檔案中的 ReflectionClass 等泛型類型
 */

class GenericsPhpstanFixer
{
    private int $fixCount = 0;
    private array<mixed> $processedFiles = [];

    public function run(): void
    {
        echo "開始 Generics PHPStan 修復...\n";

        $directories = [
            'tests/',
            'app/',
        ];

        foreach ($directories as $directory) {
            $this->processDirectory($directory);
        }

        echo "\n修復完成！\n";
        echo "總修復次數: {$this->fixCount}\n";
        echo "修復的檔案數: " . count($this->processedFiles) . "\n";

        if (!empty($this->processedFiles)) {
            echo "\n修復的檔案:\n";
            foreach ($this->processedFiles as $file) {
                echo "- $file\n";
            }
        }
    }

    private function processDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }

    private function processFile(string $filePath): void
    {
        echo "處理檔案: $filePath\n";

        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;

        // 修復各種 generics 類型問題
        $content = $this->fixReflectionClassGenerics($content);
        $content = $this->fixMockeryGenerics($content);
        $content = $this->fixCollectorGenerics($content);
        $content = $this->fixIteratorGenerics($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = $filePath;
        }
    }

    private function fixReflectionClassGenerics(string $content): string
    {
        // 修復 ReflectionClass 泛型問題
        $patterns = [
            // Property declarations with ReflectionClass
            '/(\s+\*\s+@var\s+)ReflectionClass(\s+\$\w+)/i' => function($matches) {
                $this->fixCount++;
                return $matches[1] . 'ReflectionClass' . $matches[2];
            },

            // Method parameters with ReflectionClass
            '/(\s+\*\s+@param\s+)ReflectionClass(\s+\$\w+)/i' => function($matches) {
                $this->fixCount++;
                return $matches[1] . 'ReflectionClass' . $matches[2];
            },

            // Method return types with ReflectionClass
            '/(\s+\*\s+@return\s+)ReflectionClass(\s)/i' => function($matches) {
                $this->fixCount++;
                return $matches[1] . 'ReflectionClass' . $matches[2];
            },

            // New ReflectionClass instantiation
            '/new\s+ReflectionClass\(\s*([^)]+)\s*\)/' => function($matches) {
                // 不修復這個，只修復 @var 註解
                return $matches[0];
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
                if ($newContent !== null) {
                    $content = $newContent;
                    $this->fixCount += $count;
                }
            }
        }

        return $content;
    }

    private function fixMockeryGenerics(string $content): string
    {
        // 修復 Mockery 相關的泛型問題
        $patterns = [
            // MockInterface 泛型
            '/(\s+\*\s+@var\s+)([a-zA-Z\\\\]+)\|Mockery\\\\MockInterface(\s+\$\w+)/i' => function($matches) {
                $this->fixCount++;
                return $matches[1] . $matches[2] . '|Mockery\\MockInterface' . $matches[3];
            },

            // @param with MockInterface
            '/(\s+\*\s+@param\s+)([a-zA-Z\\\\]+)\|Mockery\\\\MockInterface(\s+\$\w+)/i' => function($matches) {
                $this->fixCount++;
                return $matches[1] . $matches[2] . '|Mockery\\MockInterface' . $matches[3];
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            }
        }

        return $content;
    }

    private function fixCollectorGenerics(string $content): string
    {
        // 修復 Collector 介面的泛型問題
        $patterns = [
            // @implements Collector
            '/(\s+\*\s+@implements\s+Collector)<([^,>]+),\s*([^>]+)>/' => function($matches) {
                $this->fixCount++;
                return $matches[1] . '<' . $matches[2] . ', ' . $matches[3] . '>';
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            }
        }

        return $content;
    }

    private function fixIteratorGenerics(string $content): string
    {
        // 修復 Iterator 相關的泛型問題
        $patterns = [
            // @implements IteratorAggregate
            '/(\s+\*\s+@implements\s+IteratorAggregate)<([^>]+)>/' => function($matches) {
                // 檢查是否已經有兩個參數
                if (strpos($matches[2], ',') === false) {
                    $this->fixCount++;
                    return $matches[1] . '<int, ' . $matches[2] . '>';
                }
                return $matches[0];
            },

            // @var Iterator
            '/(\s+\*\s+@var\s+Iterator)<([^>]+)>/' => function($matches) {
                // 檢查是否已經有兩個參數
                if (strpos($matches[2], ',') === false) {
                    $this->fixCount++;
                    return $matches[1] . '<int, ' . $matches[2] . '>';
                }
                return $matches[0];
            },
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $content = preg_replace_callback($pattern, $replacement, $content);
            }
        }

        return $content;
    }
}

// 執行腳本
$fixer = new GenericsPhpstanFixer();
$fixer->run();
