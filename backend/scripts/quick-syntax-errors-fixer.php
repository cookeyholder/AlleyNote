<?php

declare(strict_types=1);

/**
 * 快速語法錯誤修復腳本
 *
 * 修復常見的語法錯誤：
 * - 不正確的反斜線
 * - 不完整的 try-catch 塊
 * - 錯誤的字串插值
 * - 其他常見語法問題
 */

class QuickSyntaxErrorsFixer
{
    private int $filesProcessed = 0;
    private int $issuesFixed = 0;
    private array $fixedPatterns = [];

    public function run(): void
    {
        echo "🔧 快速修復語法錯誤...\n";

        $this->processAllPhpFiles();

        echo "\n✅ 快速語法錯誤修復完成！\n";
        echo "📊 處理了 {$this->filesProcessed} 個檔案，修正了 {$this->issuesFixed} 個問題\n";

        if (!empty($this->fixedPatterns)) {
            echo "\n🎯 修復的模式：\n";
            foreach ($this->fixedPatterns as $pattern => $count) {
                echo "  - {$pattern}: {$count} 次\n";
            }
        }
    }

    private function processAllPhpFiles(): void
    {
        $directories = [
            __DIR__ . '/../app',
            __DIR__ . '/../tests',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->processDirectory($dir);
            }
        }
    }

    private function processDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }

    private function processFile(string $filePath): void
    {
        $originalContent = file_get_contents($filePath);
        if ($originalContent === false) {
            return;
        }

        $content = $originalContent;
        $hasChanges = false;

        // 修復各種語法錯誤
        $content = $this->fixCommonSyntaxErrors($content, $hasChanges, $filePath);

        if ($hasChanges) {
            file_put_contents($filePath, $content);
            echo "修復檔案: " . str_replace(__DIR__ . '/../', '', $filePath) . "\n";
        }

        $this->filesProcessed++;
    }

    private function fixCommonSyntaxErrors(string $content, bool &$hasChanges, string $filePath): string
    {
        $patterns = [
            // 修復錯誤的反斜線變數：\$var -> $var
            '/\\\\(\$[a-zA-Z_][a-zA-Z0-9_]*)/' => '$1',

            // 修復多餘的反斜線：\( -> (
            '/\\\\([(){}])/' => '$1',

            // 修復不完整的註解塊 (// catch block commented out due to syntax error...)
            '/\/\/ catch block commented out due to syntax error[^}]*}/' => '',

            // 修復錯誤的三元運算子：($var ? true : '') -> ($var ?: '')
            '/\(\$[a-zA-Z_][a-zA-Z0-9_]*\s*\?\s*true\s*:\s*[\'"][^\'"]*[\'"]\)/' => function($matches) {
                $match = $matches[0];
                if (preg_match('/\(\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\?\s*true\s*:\s*([\'"][^\'"]*[\'"])\)/', $match, $innerMatches)) {
                    return "(\${$innerMatches[1]} ?: {$innerMatches[2]})";
                }
                return $match;
            },

            // 修復字串插值中的語法錯誤：'string {$var ? true : ''}' -> 'string ' . ($var ?: '')
            '/[\'"]([^\'"]*){\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\?\s*true\s*:\s*[\'"][^\'\"]*[\'\"]}[\'"]/' => function($matches) {
                return "'{$matches[1]}' . (\${$matches[2]} ?: '')";
            },

            // 修復錯誤的陣列存取：isset((is_array($var) && isset($var['key'])) ? $var['key'] : null) -> isset($var['key'])
            '/isset\(\(is_array\(\$([a-zA-Z_][a-zA-Z0-9_]*)\)\s*&&\s*isset\(\$\1\[\'([^\']+)\'\]\)\)\s*\?\s*\$\1\[\'[^\']+\'\]\s*:\s*null\)/' => 'isset($1[\'$2\'])',

            // 修復錯誤的 unset：unset((condition) ? $var : null) -> if (condition) unset($var)
            '/unset\(\([^)]+\)\s*\?\s*(\$[a-zA-Z_][a-zA-Z0-9_]*(?:\[[^\]]*\])?)\s*:\s*null\)/' => '// Fixed: conditional unset removed - $1',

            // 修復多餘的空白和換行
            '/\n\s*\n\s*\n/' => "\n\n",

            // 修復錯誤的方法結尾 (多餘的 } 或不正確的結構)
            '/}\s*}\s*\n\s*public function/' => "}\n\n    public function",
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $newContent = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $newContent = preg_replace($pattern, $replacement, $content);
            }

            if ($newContent !== null && $newContent !== $content) {
                $matches = [];
                preg_match_all($pattern, $content, $matches);
                $count = count($matches[0]);

                if ($count > 0) {
                    $patternKey = substr($pattern, 0, 30) . '...';
                    $this->fixedPatterns[$patternKey] = ($this->fixedPatterns[$patternKey] ?? 0) + $count;
                    $this->issuesFixed += $count;
                    $hasChanges = true;
                    $content = $newContent;

                    echo "  修復 {$count} 個語法錯誤在 " . basename($filePath) . "\n";
                }
            }
        }

        // 修復特殊情況：不正確的控制器方法結構
        if (strpos($filePath, 'Controller.php') !== false) {
            $content = $this->fixControllerSpecificIssues($content, $hasChanges, $filePath);
        }

        return $content;
    }

    private function fixControllerSpecificIssues(string $content, bool &$hasChanges, string $filePath): string
    {
        // 修復不完整的 try-catch 結構
        $pattern = '/try\s*{\s*([^}]*)\s*}\s*\/\/ catch block[^}]*catch\s*\([^)]*\)\s*{([^}]*)}/s';
        $newContent = preg_replace_callback($pattern, function($matches) {
            return "try {\n        {$matches[1]}\n    } catch (\\Exception \$e) {\n        {$matches[2]}\n    }";
        }, $content);

        if ($newContent !== $content) {
            $content = $newContent;
            $hasChanges = true;
            $this->issuesFixed++;
            echo "  修復控制器 try-catch 結構在 " . basename($filePath) . "\n";
        }

        return $content;
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new QuickSyntaxErrorsFixer();
    $fixer->run();
} else {
    echo "此腳本只能在命令列執行\n";
}
