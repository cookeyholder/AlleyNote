<?php

declare(strict_types=1);

/**
 * PHPStan Level 10 智能回退工具
 * 移除有問題的返回型別註解，保留有效的修復
 */
class PHPStanSmartRollback
{
    private int $rolledBackFiles = 0;
    private int $keptFixes = 0;
    private int $removedBadFixes = 0;

    public function __construct()
    {
        echo "🔄 啟動 PHPStan Level 10 智能回退工具...\n";
        echo "📋 分析並移除有問題的型別註解\n\n";
    }

    public function rollbackSmartly(): void
    {
        echo "🔍 掃描已修復的 PHP 檔案...\n";
        $files = $this->getPhpFiles();
        echo "發現 " . count($files) . " 個 PHP 檔案\n\n";

        foreach ($files as $file) {
            $this->analyzeAndFixFile($file);
        }

        $this->printSummary();
    }

    private function analyzeAndFixFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 移除可能有問題的 @return 註解
        $content = $this->removeProblematicReturnAnnotations($content);

        // 只保留安全的修復
        $content = $this->keepSafeFixes($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->rolledBackFiles++;
            echo "  🔄 回退: " . basename($filePath) . "\n";
        }
    }

    private function removeProblematicReturnAnnotations(string $content): string
    {
        // 移除可能有問題的簡單 @return 註解
        $problematicPatterns = [
            // 移除只是重複 PHP 型別宣告的 @return
            '/\*\s*@return\s+(void|bool|int|string|float|array)\s*\n\s*\*\/\s*\n\s*(public|private|protected)\s+function\s+[^:]+:\s*\1\s*{/' => function($matches) {
                // 如果 @return 和實際型別宣告完全一樣，移除 @return
                $this->removedBadFixes++;
                return str_replace($matches[0],
                    str_replace("     * @return {$matches[1]}\n", '', $matches[0]),
                    $matches[0]
                );
            },

            // 移除可能不正確的複雜型別註解
            '/\*\s*@return\s+\w+\|\w+.*\n/' => function($matches) {
                // 移除複雜的聯合型別，因為可能不正確
                $this->removedBadFixes++;
                return '';
            },
        ];

        foreach ($problematicPatterns as $pattern => $action) {
            if (is_callable($action)) {
                $content = preg_replace_callback($pattern, $action, $content);
            } else {
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $action, $content);
                    $this->removedBadFixes++;
                }
            }
        }

        return $content;
    }

    private function keepSafeFixes(string $content): string
    {
        // 保留明顯安全的修復
        $safePatternsToKeep = [
            // 保留 array<string, mixed> 這種明確的泛型註解
            '/@return\s+array<[^>]+>/',
            // 保留 self 返回型別
            '/@return\s+self/',
            // 保留 static 返回型別
            '/@return\s+static/',
        ];

        foreach ($safePatternsToKeep as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->keptFixes++;
            }
        }

        return $content;
    }

    private function getPhpFiles(): array
    {
        $files = [];

        $directories = [
            'app/Application',
            'app/Domains',
            'app/Infrastructure',
            'app/Shared',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir)
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

    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🔄 智能回退完成報告\n";
        echo str_repeat("=", 60) . "\n";
        echo "回退的檔案數：{$this->rolledBackFiles}\n";
        echo "移除有問題的修復：{$this->removedBadFixes} 個\n";
        echo "保留安全的修復：{$this->keptFixes} 個\n\n";

        echo "🧪 建議執行測試：\n";
        echo "docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Validation/ValidationResultTest.php\n";
        echo "\n📊 檢查改善情況：\n";
        echo "docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G --error-format=table 2>&1 | tail -5\n";
        echo str_repeat("=", 60) . "\n";
    }
}

// 執行智能回退
try {
    $rollback = new PHPStanSmartRollback();
    $rollback->rollbackSmartly();
} catch (Exception $e) {
    echo "❌ 回退過程中發生錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
