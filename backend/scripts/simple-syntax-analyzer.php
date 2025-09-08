<?php

declare(strict_types=1);

/**
 * 簡化的語法錯誤統計腳本
 *
 * 分析 PHPStan 輸出中的語法錯誤，提供基本統計和修復建議
 */

class SimpleSyntaxAnalyzer
{
    private array $errorTypes = [];
    private array $fileErrorCounts = [];
    private int $totalErrors = 0;

    public function run(): void
    {
        echo "🔍 開始語法錯誤統計分析...\n";

        $this->analyzePHPStanOutput();
        $this->generateReport();

        echo "\n✅ 語法錯誤分析完成！\n";
    }

    private function analyzePHPStanOutput(): void
    {
        echo "📊 執行 PHPStan 分析...\n";

        $command = './vendor/bin/phpstan analyse --memory-limit=1G 2>&1';
        $output = shell_exec($command);

        if ($output === null) {
            echo "❌ 無法執行 PHPStan 分析\n";
            return;
        }

        $this->parseOutput($output);
        echo "📝 共發現 {$this->totalErrors} 個語法錯誤\n";
    }

    private function parseOutput(string $output): void
    {
        $lines = explode("\n", $output);
        $currentFile = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // 檢查檔案標題行
            if (preg_match('/^\s*Line\s+(.+\.php)\s*$/', $line, $matches)) {
                $currentFile = $matches[1];
                if (!isset($this->fileErrorCounts[$currentFile])) {
                    $this->fileErrorCounts[$currentFile] = 0;
                }
                continue;
            }

            // 檢查錯誤行並分類
            if (preg_match('/^\s*\d+\s+(.+)$/', $line, $matches) && $currentFile) {
                $errorMessage = $matches[1];

                if ($this->isSyntaxError($errorMessage)) {
                    $this->totalErrors++;
                    $this->fileErrorCounts[$currentFile]++;
                    $this->categorizeError($errorMessage);
                }
            }
        }
    }

    private function isSyntaxError(string $message): bool
    {
        $syntaxKeywords = [
            'Syntax error',
            'Multiple access type modifiers',
            'Cannot use try without catch',
            'Cannot use empty array elements',
            'Parse error',
            'Unclosed'
        ];

        foreach ($syntaxKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function categorizeError(string $errorMessage): void
    {
        $categories = [
            'unexpected_token' => [
                'keywords' => ['unexpected T_PUBLIC', 'unexpected T_PRIVATE', 'unexpected T_PROTECTED', 'unexpected T_FUNCTION'],
                'description' => '意外的語言關鍵字'
            ],
            'try_catch_errors' => [
                'keywords' => ['Cannot use try without catch', 'unexpected T_CATCH'],
                'description' => '不完整的 try-catch 結構'
            ],
            'bracket_errors' => [
                'keywords' => ['unexpected \'}\'', 'unexpected \'{\'', 'unexpected \')\'', 'unexpected \'(\'', 'Unclosed'],
                'description' => '括號不匹配'
            ],
            'array_errors' => [
                'keywords' => ['Cannot use empty array elements', 'unexpected T_DOUBLE_ARROW', 'unexpected \']\''],
                'description' => '陣列語法錯誤'
            ],
            'operator_errors' => [
                'keywords' => ['unexpected T_IS_EQUAL', 'unexpected T_IS_NOT_EQUAL'],
                'description' => '運算符語法錯誤'
            ],
            'access_modifiers' => [
                'keywords' => ['Multiple access type modifiers'],
                'description' => '多重存取修飾符'
            ],
            'string_errors' => [
                'keywords' => ['unexpected \'"\'', 'T_ENCAPSED_AND_WHITESPACE'],
                'description' => '字串語法錯誤'
            ],
            'eof_errors' => [
                'keywords' => ['unexpected EOF', 'expecting EOF'],
                'description' => '檔案結尾錯誤'
            ]
        ];

        foreach ($categories as $type => $config) {
            foreach ($config['keywords'] as $keyword) {
                if (strpos($errorMessage, $keyword) !== false) {
                    if (!isset($this->errorTypes[$type])) {
                        $this->errorTypes[$type] = [
                            'count' => 0,
                            'description' => $config['description'],
                            'examples' => []
                        ];
                    }
                    $this->errorTypes[$type]['count']++;

                    // 只保存前5個例子
                    if (count($this->errorTypes[$type]['examples']) < 5) {
                        $this->errorTypes[$type]['examples'][] = $errorMessage;
                    }
                    return;
                }
            }
        }

        // 其他語法錯誤
        if (!isset($this->errorTypes['other'])) {
            $this->errorTypes['other'] = [
                'count' => 0,
                'description' => '其他語法錯誤',
                'examples' => []
            ];
        }
        $this->errorTypes['other']['count']++;
        if (count($this->errorTypes['other']['examples']) < 5) {
            $this->errorTypes['other']['examples'][] = $errorMessage;
        }
    }

    private function generateReport(): void
    {
        echo "\n📊 語法錯誤統計報告:\n";
        echo "==================================================\n";
        echo "總語法錯誤數: {$this->totalErrors}\n";
        echo "受影響檔案數: " . count($this->fileErrorCounts) . "\n\n";

        echo "🎯 錯誤類型分布:\n";
        echo "==================================================\n";

        // 按錯誤數量排序
        uasort($this->errorTypes, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        foreach ($this->errorTypes as $type => $data) {
            $percentage = round(($data['count'] / $this->totalErrors) * 100, 1);
            echo "{$data['description']}: {$data['count']} 個 ({$percentage}%)\n";
        }

        echo "\n📁 錯誤最多的檔案:\n";
        echo "==================================================\n";

        arsort($this->fileErrorCounts);
        $topFiles = array_slice($this->fileErrorCounts, 0, 15, true);

        foreach ($topFiles as $file => $count) {
            $shortFile = basename($file);
            echo "{$shortFile}: {$count} 個錯誤\n";
        }

        echo "\n💡 修復建議:\n";
        echo "==================================================\n";

        foreach ($this->errorTypes as $type => $data) {
            if ($data['count'] > 0) {
                echo "\n{$data['description']} ({$data['count']} 個):\n";
                echo $this->getFixSuggestion($type) . "\n";
            }
        }

        echo "\n🔧 推薦修復順序:\n";
        echo "==================================================\n";
        echo "1. 多重存取修飾符 - 最容易修復\n";
        echo "2. 不完整的 try-catch 結構 - 使用現有腳本\n";
        echo "3. 括號不匹配 - 使用現有腳本\n";
        echo "4. 陣列語法錯誤 - 使用現有腳本\n";
        echo "5. 意外的語言關鍵字 - 需要手動修復\n";
        echo "6. 檔案結尾錯誤 - 需要仔細檢查\n";

        $this->generateDetailedReport();
    }

    private function getFixSuggestion(string $type): string
    {
        return match($type) {
            'unexpected_token' => "  建議: 手動檢查類和方法結構，確認語法完整性",
            'try_catch_errors' => "  建議: 使用 fix-incomplete-try-catch.php 腳本自動修復",
            'bracket_errors' => "  建議: 使用 fix-missing-braces.php 和 fix-unclosed-brackets.php 腳本",
            'array_errors' => "  建議: 使用 fix-array-and-function-syntax.php 腳本",
            'operator_errors' => "  建議: 使用 fix-duplicate-operators.php 腳本",
            'access_modifiers' => "  建議: 手動移除重複的 public/private/protected 修飾符",
            'string_errors' => "  建議: 手動檢查字串語法和引號匹配",
            'eof_errors' => "  建議: 手動檢查檔案結構，確保所有括號正確閉合",
            default => "  建議: 根據具體錯誤訊息進行手動修復"
        };
    }

    private function generateDetailedReport(): void
    {
        $reportPath = 'syntax-errors-summary.md';
        $report = "# 語法錯誤統計報告\n\n";
        $report .= "> **生成時間**: " . date('Y-m-d H:i:s') . "\n";
        $report .= "> **總錯誤數**: {$this->totalErrors}\n";
        $report .= "> **受影響檔案**: " . count($this->fileErrorCounts) . "\n\n";

        $report .= "## 📊 錯誤類型統計\n\n";
        $report .= "| 錯誤類型 | 數量 | 百分比 | 修復建議 |\n";
        $report .= "|----------|------|--------|----------|\n";

        foreach ($this->errorTypes as $type => $data) {
            $percentage = round(($data['count'] / $this->totalErrors) * 100, 1);
            $suggestion = $this->getShortSuggestion($type);
            $report .= "| {$data['description']} | {$data['count']} | {$percentage}% | {$suggestion} |\n";
        }

        $report .= "\n## 📁 問題檔案清單\n\n";
        $report .= "| 檔案 | 錯誤數 |\n";
        $report .= "|------|--------|\n";

        arsort($this->fileErrorCounts);
        foreach ($this->fileErrorCounts as $file => $count) {
            $shortFile = basename($file);
            $report .= "| `{$shortFile}` | {$count} |\n";
        }

        $report .= "\n## 🔧 修復策略\n\n";
        $report .= "### 自動修復（推薦優先）\n";
        $report .= "1. **多重存取修飾符**: 手動快速修復\n";
        $report .= "2. **try-catch 結構**: `php scripts/fix-incomplete-try-catch.php`\n";
        $report .= "3. **括號問題**: `php scripts/fix-missing-braces.php`\n";
        $report .= "4. **陣列語法**: `php scripts/fix-array-and-function-syntax.php`\n\n";

        $report .= "### 手動修復（需要仔細檢查）\n";
        $report .= "1. **意外關鍵字**: 檢查類和方法結構\n";
        $report .= "2. **檔案結尾錯誤**: 檢查整體語法結構\n";
        $report .= "3. **字串錯誤**: 檢查引號和字串插值\n\n";

        file_put_contents($reportPath, $report);
        echo "\n📄 詳細報告已生成：{$reportPath}\n";
    }

    private function getShortSuggestion(string $type): string
    {
        return match($type) {
            'access_modifiers' => '手動修復',
            'try_catch_errors' => '腳本修復',
            'bracket_errors' => '腳本修復',
            'array_errors' => '腳本修復',
            'operator_errors' => '腳本修復',
            default => '手動檢查'
        };
    }
}

// 執行分析
if (php_sapi_name() === 'cli') {
    try {
        $analyzer = new SimpleSyntaxAnalyzer();
        $analyzer->run();
    } catch (Exception $e) {
        echo "❌ 分析過程中發生錯誤: " . $e->getMessage() . "\n";
    }
}
