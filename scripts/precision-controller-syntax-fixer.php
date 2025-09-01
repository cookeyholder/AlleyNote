<?php
declare(strict_types=1);

/**
 * 精確的控制器語法錯誤修復器
 * 專門處理 OpenAPI 註解和 try-catch 結構錯誤
 */

class PrecisionControllerSyntaxFixer
{
    private array $stats = ['files' => 0, 'fixes' => 0];

    public function fixControllerSyntaxErrors(): void
    {
        echo "開始精確修復控制器語法錯誤...\n";

        $controllerFiles = [
            'app/Application/Controllers/Api/V1/ActivityLogController.php',
            'app/Application/Controllers/Api/V1/AttachmentController.php',
            'app/Application/Controllers/Api/V1/AuthController.php',
            'app/Application/Controllers/Api/V1/PostController.php',
            'app/Application/Controllers/Api/V1/IpController.php',
            'app/Application/Controllers/Health/HealthController.php',
        ];

        foreach ($controllerFiles as $file) {
            if (file_exists($file)) {
                $this->processControllerFile($file);
            }
        }

        $this->generateReport();
    }

    private function processControllerFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        echo "處理: $filePath\n";

        // 1. 修復 OpenAPI 註解中的 => 語法錯誤
        $content = $this->fixOpenApiAttributes($content);

        // 2. 修復 try-catch 結構錯誤
        $content = $this->fixTryCatchBlocks($content);

        // 3. 修復方法內的語法錯誤
        $content = $this->fixMethodSyntaxErrors($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->stats['files']++;
            echo "✓ 已修復: $filePath\n";
        }
    }

    private function fixOpenApiAttributes(string $content): string
    {
        $fixes = 0;

        // 修復 OpenAPI 屬性中的 => 語法
        $patterns = [
            // path => '/api/...' 改為 path: '/api/...'
            '/path\s*=>\s*/' => 'path: ',
            '/operationId\s*=>\s*/' => 'operationId: ',
            '/summary\s*=>\s*/' => 'summary: ',
            '/description\s*=>\s*/' => 'description: ',
            '/property\s*=>\s*/' => 'property: ',
            '/type\s*=>\s*/' => 'type: ',
            '/response\s*=>\s*/' => 'response: ',
            '/name\s*=>\s*/' => 'name: ',
            '/tags\s*=>\s*/' => 'tags: ',
            '/required\s*=>\s*/' => 'required: ',
            '/content\s*=>\s*/' => 'content: ',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        $this->stats['fixes'] += $fixes;
        return $content;
    }

    private function fixTryCatchBlocks(string $content): string
    {
        $fixes = 0;

        // 找到所有的 try 塊並確保它們有對應的 catch 或 finally
        $lines = explode("\n", $content);
        $modifiedLines = [];
        $i = 0;

        while ($i < count($lines)) {
            $line = $lines[$i];

            // 檢查是否是 try 行
            if (preg_match('/^\s*try\s*\{/', $line)) {
                $modifiedLines[] = $line;
                $i++;

                // 尋找對應的 catch 或 finally
                $tryBlockFound = true;
                $braceLevel = 1;
                $foundCatchOrFinally = false;

                // 讀取 try 塊的內容
                while ($i < count($lines) && $braceLevel > 0) {
                    $currentLine = $lines[$i];

                    // 計算大括號層級
                    $braceLevel += substr_count($currentLine, '{') - substr_count($currentLine, '}');

                    $modifiedLines[] = $currentLine;
                    $i++;

                    // 如果大括號層級歸零，檢查下一行是否有 catch 或 finally
                    if ($braceLevel === 0 && $i < count($lines)) {
                        $nextLine = trim($lines[$i]);
                        if (preg_match('/^(catch|finally)/', $nextLine)) {
                            $foundCatchOrFinally = true;
                        }
                        break;
                    }
                }

                // 如果沒有找到 catch 或 finally，添加一個
                if (!$foundCatchOrFinally) {
                    $modifiedLines[] = "        } catch (Exception \$e) {";
                    $modifiedLines[] = "            // 錯誤處理待實現";
                    $modifiedLines[] = "            throw \$e;";
                    $fixes++;
                }
            } else {
                $modifiedLines[] = $line;
                $i++;
            }
        }

        $this->stats['fixes'] += $fixes;
        return implode("\n", $modifiedLines);
    }

    private function fixMethodSyntaxErrors(string $content): string
    {
        $fixes = 0;

        // 修復孤立的變數賦值和返回語句
        $lines = explode("\n", $content);
        $modifiedLines = [];
        $inMethodBody = false;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // 檢查是否進入方法體
            if (preg_match('/public function|private function|protected function/', $line)) {
                $inMethodBody = true;
            }

            // 檢查是否離開方法體
            if ($inMethodBody && preg_match('/^\s*}\s*$/', $line) && !preg_match('/\{/', $line)) {
                $inMethodBody = false;
            }

            // 修復孤立的語句（在方法體外）
            if (!$inMethodBody) {
                // 如果是孤立的變數賦值或返回語句，註釋掉
                if (preg_match('/^\s*(\$[a-zA-Z_][a-zA-Z0-9_]*\s*=|return\s+)/', $trimmedLine) &&
                    !preg_match('/\/\*|\/\/|\*/', $trimmedLine)) {
                    $modifiedLines[] = '        // ' . $line . ' // 已註釋: 孤立語句';
                    $fixes++;
                    continue;
                }
            }

            $modifiedLines[] = $line;
        }

        $this->stats['fixes'] += $fixes;
        return implode("\n", $modifiedLines);
    }

    private function generateReport(): void
    {
        $report = "\n=== 精確控制器語法修復報告 ===\n";
        $report .= "處理檔案數: {$this->stats['files']}\n";
        $report .= "修復錯誤數: {$this->stats['fixes']}\n";
        $report .= "完成時間: " . date('Y-m-d H:i:s') . "\n";

        echo $report;
        file_put_contents(__DIR__ . '/../logs/precision-controller-fix-report.log', $report);
    }
}

// 執行修復
$fixer = new PrecisionControllerSyntaxFixer();
$fixer->fixControllerSyntaxErrors();
