<?php

declare(strict_types=1);

/**
 * 修復不完整 try-catch 塊的腳本
 *
 * 主要修復以下問題：
 * - "Cannot use try without catch or finally" 錯誤
 * - 空的 try 塊
 * - 不完整的 try-catch 結構
 */

class IncompleteTryCatchFixer
{
    private int $filesProcessed = 0;
    private int $issuesFixed = 0;
    private array $fixedPatterns = [];

    public function run(): void
    {
        echo "🔧 修復不完整的 try-catch 塊...\n";

        $this->processAllPhpFiles();

        echo "\n✅ try-catch 修復完成！\n";
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

        // 修復各種不完整的 try-catch 問題
        $content = $this->fixIncompleteTryCatch($content, $hasChanges, $filePath);

        if ($hasChanges) {
            file_put_contents($filePath, $content);
            echo "修復檔案: " . str_replace(__DIR__ . '/../', '', $filePath) . "\n";
        }

        $this->filesProcessed++;
    }

    private function fixIncompleteTryCatch(string $content, bool &$hasChanges, string $filePath): string
    {
        $patterns = [
            // 修復空的 try 塊後面直接跟著另一個方法或類結束
            // try { } public function -> try { } catch (\Exception $e) { /* handle */ } public function
            '/try\s*\{\s*\}\s*(public|private|protected|static|\})/s' => function($matches) {
                $nextToken = $matches[1];
                return "try {\n            // TODO: Add implementation\n        } catch (\\Exception \$e) {\n            // TODO: Handle exception\n            throw \$e;\n        }\n        {$nextToken}";
            },

            // 修復只有 try 沒有 catch 或 finally 的情況
            // try { some code } public function -> try { some code } catch (\Exception $e) { /* handle */ } public function
            '/try\s*\{([^}]*(?:\{[^}]*\}[^}]*)*)\}\s*(public|private|protected|static|\})/s' => function($matches) {
                $tryContent = trim($matches[1]);
                $nextToken = $matches[2];

                if (empty($tryContent)) {
                    return "try {\n            // TODO: Add implementation\n        } catch (\\Exception \$e) {\n            // TODO: Handle exception\n            throw \$e;\n        }\n        {$nextToken}";
                } else {
                    return "try {\n{$matches[1]}        } catch (\\Exception \$e) {\n            // TODO: Handle exception\n            throw \$e;\n        }\n        {$nextToken}";
                }
            },

            // 修復 try 塊後面直接是 EOF 的情況
            '/try\s*\{([^}]*(?:\{[^}]*\}[^}]*)*)\}\s*$/s' => function($matches) {
                $tryContent = trim($matches[1]);

                if (empty($tryContent)) {
                    return "try {\n            // TODO: Add implementation\n        } catch (\\Exception \$e) {\n            // TODO: Handle exception\n            throw \$e;\n        }";
                } else {
                    return "try {\n{$matches[1]}        } catch (\\Exception \$e) {\n            // TODO: Handle exception\n            throw \$e;\n        }";
                }
            },

            // 修復註解掉的 catch 塊
            '/try\s*\{([^}]*(?:\{[^}]*\}[^}]*)*)\}\s*\/\*\s*catch.*?\*\//s' => function($matches) {
                return "try {\n{$matches[1]}        } catch (\\Exception \$e) {\n            // TODO: Handle exception\n            throw \$e;\n        }";
            },

            // 修復帶有註解說明的不完整 try
            '/try\s*\{([^}]*(?:\{[^}]*\}[^}]*)*)\}\s*\/\/.*catch.*(?:\n|$)/s' => function($matches) {
                return "try {\n{$matches[1]}        } catch (\\Exception \$e) {\n            // TODO: Handle exception\n            throw \$e;\n        }\n";
            },

            // 修復簡單的空 try 塊
            '/try\s*\{\s*\}\s*(?=\n|$)/s' => function($matches) {
                return "try {\n            // TODO: Add implementation\n        } catch (\\Exception \$e) {\n            // TODO: Handle exception\n            throw \$e;\n        }";
            },
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

                    echo "  修復 {$count} 個 try-catch 問題在 " . basename($filePath) . "\n";
                }
            }
        }

        // 特殊處理控制器檔案
        if (strpos($filePath, 'Controller.php') !== false) {
            $content = $this->fixControllerSpecificTryCatch($content, $hasChanges, $filePath);
        }

        return $content;
    }

    private function fixControllerSpecificTryCatch(string $content, bool &$hasChanges, string $filePath): string
    {
        // 控制器中常見的模式：try 後面直接跟著 return 語句但沒有 catch
        $controllerPatterns = [
            // 修復控制器中的 API 響應 try 塊
            '/try\s*\{\s*(.*?return\s+\$response.*?;)\s*\}\s*(public|private|protected|\})/s' => function($matches) {
                $tryContent = trim($matches[1]);
                $nextToken = $matches[2];

                return "try {\n            {$tryContent}\n        } catch (\\Exception \$e) {\n            error_log('Controller error: ' . \$e->getMessage());\n            \$errorResponse = json_encode([\n                'success' => false,\n                'message' => 'Internal server error',\n                'error' => \$e->getMessage(),\n            ]);\n            \$response->getBody()->write(\$errorResponse ?: '{\"error\": \"JSON encoding failed\"}');\n            return \$response->withHeader('Content-Type', 'application/json')->withStatus(500);\n        }\n\n        {$nextToken}";
            },

            // 修復帶有日誌記錄的 try 塊
            '/try\s*\{\s*(.*?error_log.*?;)\s*\}\s*(public|private|protected|\})/s' => function($matches) {
                $tryContent = trim($matches[1]);
                $nextToken = $matches[2];

                return "try {\n            {$tryContent}\n        } catch (\\Exception \$e) {\n            error_log('Operation failed: ' . \$e->getMessage());\n            throw \$e;\n        }\n\n        {$nextToken}";
            },
        ];

        foreach ($controllerPatterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $newContent = preg_replace_callback($pattern, $replacement, $content);
                if ($newContent !== null && $newContent !== $content) {
                    $content = $newContent;
                    $hasChanges = true;
                    $this->issuesFixed++;
                    echo "  修復控制器 try-catch 結構在 " . basename($filePath) . "\n";
                }
            }
        }

        return $content;
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new IncompleteTryCatchFixer();
    $fixer->run();
} else {
    echo "此腳本只能在命令列執行\n";
}
