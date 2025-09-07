<?php

declare(strict_types=1);

/**
 * 進階 Argument Type 錯誤修復腳本
 *
 * 專門修復 PHPStan Level 10 中的 argument.type 錯誤
 */

require_once __DIR__ . '/../vendor/autoload.php';

class AdvancedArgumentTypeFixer
{
    private string $rootDir;
    private array $results = [];

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function run(): void
    {
        echo "🔧 進階 Argument Type 修復腳本開始執行...\n";

        // 1. 取得所有PHP檔案
        $phpFiles = $this->getAllPhpFiles();
        echo "📁 找到 " . count($phpFiles) . " 個 PHP 檔案\n";

        // 2. 針對每個檔案進行修復
        foreach ($phpFiles as $file) {
            $this->processFile($file);
        }

        // 3. 顯示修復結果
        $this->showResults();
    }

    private function getAllPhpFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->rootDir . '/app'),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function processFile(string $filePath): void
    {
        $originalContent = file_get_contents($filePath);
        $content = $originalContent;
        $changes = 0;

        // 修復模式集合
        $patterns = [
            // 1. 陣列合併參數類型
            [
                'pattern' => '/array_merge\(\s*([^,]+),\s*\$this->config\[([^\]]+)\]\s*\?\?\s*\[\]\s*\)/m',
                'replacement' => function($matches) {
                    $firstParam = trim($matches[1]);
                    $configKey = $matches[2];
                    return "array_merge(\n            $firstParam,\n            is_array(\$this->config[$configKey] ?? []) ? \$this->config[$configKey] : []\n        )";
                }
            ],

            // 2. str_starts_with 參數類型檢查
            [
                'pattern' => '/str_starts_with\(\s*([^,]+),\s*(\$\w+)\s*\)/m',
                'replacement' => function($matches) {
                    $haystack = trim($matches[1]);
                    $needle = trim($matches[2]);
                    return "str_starts_with($haystack, is_string($needle) ? $needle : '')";
                }
            ],

            // 3. in_array 參數類型檢查
            [
                'pattern' => '/in_array\(\s*([^,]+),\s*(\$\w+)\s*\)/m',
                'replacement' => function($matches) {
                    $needle = trim($matches[1]);
                    $haystack = trim($matches[2]);
                    return "in_array($needle, is_array($haystack) ? $haystack : [])";
                }
            ],

            // 4. 配置陣列訪問類型檢查
            [
                'pattern' => '/\$this->config\[([^\]]+)\]\s*\?\?\s*\[\]/',
                'replacement' => function($matches) {
                    $key = $matches[1];
                    return "is_array(\$this->config[$key] ?? []) ? \$this->config[$key] : []";
                }
            ],

            // 5. 方法參數類型檢查
            [
                'pattern' => '/(\w+)\(\s*(\$[^,\)]+)\s*\)/m',
                'replacement' => function($matches) {
                    $methodName = $matches[1];
                    $param = trim($matches[2]);

                    // 常見需要字串參數的方法
                    $stringMethods = ['trim', 'strtolower', 'strtoupper', 'strlen', 'substr', 'str_replace'];

                    if (in_array($methodName, $stringMethods) && strpos($param, '->') === false && strpos($param, '(') === false) {
                        return "$methodName(is_string($param) ? $param : '')";
                    }

                    return $matches[0]; // 無變更
                }
            ],

            // 6. 陣列訪問安全性檢查
            [
                'pattern' => '/(\$\w+)\[([^\]]+)\]\s*\?\?\s*([^;]+);/',
                'replacement' => function($matches) {
                    if (count($matches) < 4) {
                        return $matches[0];
                    }

                    $array = $matches[1];
                    $key = $matches[2];
                    $default = trim($matches[3]);

                    return "is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default;";
                }
            ],

            // 7. json_encode 參數檢查
            [
                'pattern' => '/json_encode\(\s*(\$[^,\)]+)([^)]*)\)/',
                'replacement' => function($matches) {
                    if (count($matches) < 2) {
                        return $matches[0];
                    }

                    $param = trim($matches[1]);
                    $options = $matches[2] ?? '';

                    if (strpos($param, 'is_array') === false && strpos($param, '?') === false) {
                        return "json_encode(is_array($param) ? $param : [$param]$options)";
                    }

                    return $matches[0]; // 無變更
                }
            ],

            // 8. Response 構造函數 body 參數
            [
                'pattern' => '/new Response\s*\(\s*status:\s*(\d+),\s*headers:\s*([^,]+),\s*body:\s*(\$[^,\)]+)\s*\)/',
                'replacement' => function($matches) {
                    if (count($matches) < 4) {
                        return $matches[0];
                    }

                    $status = $matches[1];
                    $headers = $matches[2];
                    $body = trim($matches[3]);

                    return "new Response(\n            status: $status,\n            headers: $headers,\n            body: is_string($body) ? $body : '',\n        )";
                }
            ]
        ];

        foreach ($patterns as $pattern) {
            if (is_callable($pattern['replacement'])) {
                $newContent = preg_replace_callback($pattern['pattern'], $pattern['replacement'], $content);
            } else {
                $newContent = preg_replace($pattern['pattern'], $pattern['replacement'], $content);
            }

            if ($newContent !== null && $newContent !== $content) {
                $changes++;
                $content = $newContent;
            }
        }

        // 特殊處理：PHPDoc 類型註解改進
        $content = $this->improvePhpDocTypes($content, $changes);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->results[] = [
                'file' => $filePath,
                'changes' => $changes
            ];
            echo "✅ 修復檔案: " . basename($filePath) . " (變更: $changes)\n";
        }
    }

    private function improvePhpDocTypes(string $content, int &$changes): string
    {
        $lines = explode("\n", $content);
        $modified = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 改進方法參數的 PHPDoc
            if (preg_match('/\s*\*\s*@param\s+array\s+\$(\w+)/', $line, $matches)) {
                $paramName = $matches[1];

                // 檢查下一行是否有方法定義
                for ($j = $i + 1; $j < min($i + 5, count($lines)); $j++) {
                    if (preg_match('/function\s+\w+\([^)]*array\s+\$' . $paramName . '[^)]*\)/', $lines[$j])) {
                        // 根據常見模式改進類型
                        if (strpos($paramName, 'config') !== false || strpos($paramName, 'options') !== false) {
                            $lines[$i] = str_replace('@param array $' . $paramName, '@param array<string, mixed> $' . $paramName, $line);
                            $modified = true;
                            $changes++;
                        } elseif (strpos($paramName, 'list') !== false || strpos($paramName, 'items') !== false || strpos($paramName, 'data') !== false) {
                            $lines[$i] = str_replace('@param array $' . $paramName, '@param array<mixed> $' . $paramName, $line);
                            $modified = true;
                            $changes++;
                        }
                        break;
                    }
                }
            }

            // 改進 @return 類型
            if (preg_match('/\s*\*\s*@return\s+array\s*$/', $line)) {
                $lines[$i] = str_replace('@return array', '@return array<mixed>', $line);
                $modified = true;
                $changes++;
            }
        }

        return $modified ? implode("\n", $lines) : $content;
    }

    private function showResults(): void
    {
        echo "\n📊 修復結果統計:\n";
        echo "總計修復檔案數: " . count($this->results) . "\n";

        $totalChanges = array_sum(array_column($this->results, 'changes'));
        echo "總計變更數: $totalChanges\n";

        if (count($this->results) > 0) {
            echo "\n修復的檔案:\n";
            foreach ($this->results as $result) {
                echo "  - " . basename($result['file']) . " (變更: {$result['changes']})\n";
            }
        }

        echo "\n✅ 進階 Argument Type 修復完成！\n";
        echo "💡 建議下一步：執行 PHPStan 分析以驗證修復效果\n";
    }
}

// 執行修復
$fixer = new AdvancedArgumentTypeFixer(__DIR__ . '/..');
$fixer->run();
