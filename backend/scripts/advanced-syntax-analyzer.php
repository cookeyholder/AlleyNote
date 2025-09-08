<?php

declare(strict_types=1);

/**
 * 高級語法錯誤分析腳本
 *
 * 深度分析 PHPStan 輸出中的語法錯誤，提供詳細的錯誤分類、
 * 優先級排序和修復建議，為第五輪修復提供精確指導
 */

class AdvancedSyntaxAnalyzer
{
    private array $errorCategories = [];
    private array $fileErrorCounts = [];
    private array $errorsByType = [];
    private array $priorityQueue = [];
    private int $totalErrors = 0;
    private array $complexityScores = [];
    private array $fixabilityScores = [];

    public function __construct()
    {
        $this->initializeErrorPatterns();
    }

    public function run(): void
    {
        echo "🔍 開始高級語法錯誤分析...\n";

        $this->analyzePHPStanOutput();
        $this->categorizeErrors();
        $this->calculatePriorities();
        $this->generateDetailedReport();
        $this->generateFixingStrategies();

        echo "\n✅ 高級語法錯誤分析完成！\n";
    }

    private function initializeErrorPatterns(): void
    {
        $this->errorCategories = [
            'syntax_unexpected_token' => [
                'patterns' => [
                    '/Syntax error, unexpected T_PUBLIC/',
                    '/Syntax error, unexpected T_PRIVATE/',
                    '/Syntax error, unexpected T_PROTECTED/',
                    '/Syntax error, unexpected T_FUNCTION/',
                    '/Syntax error, unexpected T_CLASS/',
                    '/Syntax error, unexpected T_IF/',
                    '/Syntax error, unexpected T_ELSE/',
                    '/Syntax error, unexpected T_FOREACH/',
                    '/Syntax error, unexpected T_WHILE/',
                ],
                'description' => '意外的語言關鍵字',
                'priority' => 'high',
                'complexity' => 8,
                'fixability' => 6,
                'auto_fixable' => false,
                'estimated_time' => '5-15分鐘/檔案'
            ],
            'syntax_bracket_mismatch' => [
                'patterns' => [
                    '/Syntax error, unexpected \'}\' on line/',
                    '/Syntax error, unexpected \'{\' on line/',
                    '/Syntax error, unexpected \')\' on line/',
                    '/Syntax error, unexpected \'(\' on line/',
                    '/Syntax error, unexpected \']\' on line/',
                    '/Syntax error, unexpected \'[\' on line/',
                    '/Unclosed \'[\' does not match \')\'/s',
                ],
                'description' => '括號不匹配',
                'priority' => 'high',
                'complexity' => 7,
                'fixability' => 8,
                'auto_fixable' => true,
                'estimated_time' => '2-5分鐘/檔案'
            ],
            'syntax_try_catch_incomplete' => [
                'patterns' => [
                    '/Cannot use try without catch or finally/',
                    '/Syntax error, unexpected T_CATCH/',
                    '/Syntax error, unexpected T_FINALLY/',
                ],
                'description' => '不完整的 try-catch 結構',
                'priority' => 'high',
                'complexity' => 6,
                'fixability' => 9,
                'auto_fixable' => true,
                'estimated_time' => '3-8分鐘/檔案'
            ],
            'syntax_array_errors' => [
                'patterns' => [
                    '/Cannot use empty array elements/',
                    '/Syntax error, unexpected T_DOUBLE_ARROW, expecting/',
                    '/Syntax error, unexpected \',\' on line/',
                    '/Syntax error, unexpected \']\', expecting \')\'/s',
                ],
                'description' => '陣列語法錯誤',
                'priority' => 'medium',
                'complexity' => 5,
                'fixability' => 9,
                'auto_fixable' => true,
                'estimated_time' => '1-3分鐘/檔案'
            ],
            'syntax_string_interpolation' => [
                'patterns' => [
                    '/Syntax error, unexpected \'"\' on line/',
                    '/Syntax error, unexpected \'\\$\' on line/',
                    '/Syntax error, unexpected T_ENCAPSED_AND_WHITESPACE/',
                ],
                'description' => '字串插值語法錯誤',
                'priority' => 'medium',
                'complexity' => 4,
                'fixability' => 7,
                'auto_fixable' => true,
                'estimated_time' => '2-5分鐘/檔案'
            ],
            'syntax_operator_errors' => [
                'patterns' => [
                    '/Syntax error, unexpected T_IS_EQUAL/',
                    '/Syntax error, unexpected T_IS_NOT_EQUAL/',
                    '/Syntax error, unexpected T_IS_IDENTICAL/',
                    '/Syntax error, unexpected T_IS_NOT_IDENTICAL/',
                    '/Syntax error, unexpected T_BOOLEAN_AND/',
                    '/Syntax error, unexpected T_BOOLEAN_OR/',
                ],
                'description' => '運算符語法錯誤',
                'priority' => 'medium',
                'complexity' => 3,
                'fixability' => 8,
                'auto_fixable' => true,
                'estimated_time' => '1-2分鐘/檔案'
            ],
            'syntax_eof_errors' => [
                'patterns' => [
                    '/Syntax error, unexpected EOF/',
                    '/expecting EOF/',
                    '/Syntax error, unexpected end of file/',
                ],
                'description' => '檔案結尾語法錯誤',
                'priority' => 'high',
                'complexity' => 9,
                'fixability' => 5,
                'auto_fixable' => false,
                'estimated_time' => '10-30分鐘/檔案'
            ],
            'syntax_openapi_attributes' => [
                'patterns' => [
                    '/Syntax error.*#\[OA\\\\/',
                    '/Syntax error.*OpenApi/',
                    '/Syntax error.*@OA/',
                ],
                'description' => 'OpenAPI 屬性語法錯誤',
                'priority' => 'low',
                'complexity' => 6,
                'fixability' => 4,
                'auto_fixable' => false,
                'estimated_time' => '5-15分鐘/檔案'
            ],
            'multiple_access_modifiers' => [
                'patterns' => [
                    '/Multiple access type modifiers are not allowed/',
                ],
                'description' => '多重存取修飾符',
                'priority' => 'medium',
                'complexity' => 3,
                'fixability' => 9,
                'auto_fixable' => true,
                'estimated_time' => '1-2分鐘/檔案'
            ],
            'syntax_other' => [
                'patterns' => [
                    '/Syntax error/',
                ],
                'description' => '其他語法錯誤',
                'priority' => 'medium',
                'complexity' => 5,
                'fixability' => 5,
                'auto_fixable' => false,
                'estimated_time' => '5-20分鐘/檔案'
            ]
        ];
    }

    private function analyzePHPStanOutput(): void
    {
        echo "📊 執行 PHPStan 分析...\n";

        // 檢查是否有現有的輸出檔案
        if (file_exists('phpstan-output.txt')) {
            echo "📄 使用現有的 phpstan-output.txt 檔案\n";
            $output = file_get_contents('phpstan-output.txt');
        } else {
            $command = './vendor/bin/phpstan analyse --memory-limit=1G 2>&1';
            $output = shell_exec($command);

            if ($output !== null) {
                file_put_contents('phpstan-output.txt', $output);
            }
        }

        if ($output === null) {
            echo "❌ 無法執行 PHPStan 分析\n";
            return;
        }

        $this->parseOutput($output);
    }

    private function parseOutput(string $output): void
    {
        $lines = explode("\n", $output);
        $currentFile = null;
        $currentFileErrors = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // 檢查檔案標題行 (格式: "  Line   app/SomeFile.php")
            if (preg_match('/^\s*Line\s+(.+\.php)\s*$/', $line, $matches)) {
                // 儲存前一個檔案的錯誤
                if ($currentFile && !empty($currentFileErrors)) {
                    $this->fileErrorCounts[$currentFile] = count($currentFileErrors);
                    foreach ($currentFileErrors as $error) {
                        $this->totalErrors++;
                        $this->categorizeError($error, $currentFile);
                    }
                }

                $currentFile = $matches[1];
                $currentFileErrors = [];
                continue;
            }

            // 檢查分隔線，表示檔案開始
            if (preg_match('/^\s*-+\s*$/', $line)) {
                continue;
            }

            // 檢查錯誤行，包含語法錯誤 (格式: "  123    Error message here")
            if (preg_match('/^\s*\d+\s+(.+)$/', $line, $matches)) {
                if ($currentFile && (
                    strpos($matches[1], 'Syntax error') !== false ||
                    strpos($matches[1], 'Multiple access') !== false ||
                    strpos($matches[1], 'Cannot use') !== false ||
                    strpos($matches[1], 'Parse error') !== false
                )) {
                    $currentFileErrors[] = $matches[1];
                }
            }
        }

        // 處理最後一個檔案
        if ($currentFile && !empty($currentFileErrors)) {
            $this->fileErrorCounts[$currentFile] = count($currentFileErrors);
            foreach ($currentFileErrors as $error) {
                $this->totalErrors++;
                $this->categorizeError($error, $currentFile);
            }
        }

        echo "📝 共發現 {$this->totalErrors} 個語法錯誤\n";
    }

    private function categorizeError(string $error, string $file): void
    {
        $categorized = false;

        foreach ($this->errorCategories as $category => $config) {
            // 跳過 syntax_other，這是最後的回退選項
            if ($category === 'syntax_other') {
                continue;
            }

            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $error)) {
                    if (!isset($this->errorsByType[$category])) {
                        $this->errorsByType[$category] = [];
                    }
                    $this->errorsByType[$category][] = [
                        'file' => $file,
                        'error' => $error,
                        'pattern' => $pattern
                    ];
                    $categorized = true;
                    return;
                }
            }
        }

        // 未分類的錯誤歸入 other
        if (!$categorized) {
            if (!isset($this->errorsByType['syntax_other'])) {
                $this->errorsByType['syntax_other'] = [];
            }
            $this->errorsByType['syntax_other'][] = [
                'file' => $file,
                'error' => $error,
                'pattern' => 'unclassified'
            ];
        }
    }

    private function calculatePriorities(): void
    {
        foreach ($this->errorsByType as $category => $errors) {
            $config = $this->errorCategories[$category] ?? $this->errorCategories['syntax_other'];
            $count = count($errors);

            $priorityScore = $this->calculatePriorityScore($config, $count);

            $this->priorityQueue[] = [
                'category' => $category,
                'count' => $count,
                'config' => $config,
                'priority_score' => $priorityScore,
                'errors' => $errors
            ];
        }

        // 按優先級排序
        usort($this->priorityQueue, function ($a, $b) {
            return $b['priority_score'] <=> $a['priority_score'];
        });
    }

    private function calculatePriorityScore(array $config, int $count): float
    {
        $priorityWeight = match($config['priority']) {
            'high' => 3.0,
            'medium' => 2.0,
            'low' => 1.0,
            default => 1.5
        };

        $complexityWeight = 1.0 - ($config['complexity'] / 10);
        $fixabilityWeight = $config['fixability'] / 10;
        $countWeight = min($count / 50, 2.0); // 錯誤數量權重

        return ($priorityWeight * 0.4) +
               ($complexityWeight * 0.2) +
               ($fixabilityWeight * 0.3) +
               ($countWeight * 0.1);
    }

    private function generateDetailedReport(): void
    {
        $reportPath = 'advanced-syntax-analysis-report.md';
        $report = $this->buildMarkdownReport();
        file_put_contents($reportPath, $report);
        echo "📄 詳細報告已生成：{$reportPath}\n";
    }

    private function buildMarkdownReport(): string
    {
        $report = "# 高級語法錯誤分析報告\n\n";
        $report .= "> **生成時間**: " . date('Y-m-d H:i:s') . "\n";
        $report .= "> **總錯誤數**: {$this->totalErrors}\n";
        $report .= "> **分析檔案數**: " . count($this->fileErrorCounts) . "\n\n";

        $report .= "## 📊 錯誤分類統計\n\n";
        $report .= "| 分類 | 錯誤數 | 優先級 | 可自動修復 | 預估修復時間 |\n";
        $report .= "|------|--------|--------|------------|------------|\n";

        foreach ($this->priorityQueue as $item) {
            $category = $item['category'];
            $config = $item['config'];
            $count = $item['count'];
            $autoFixable = $config['auto_fixable'] ? '✅' : '❌';

            $report .= "| {$config['description']} | {$count} | {$config['priority']} | {$autoFixable} | {$config['estimated_time']} |\n";
        }

        $report .= "\n## 🎯 優先修復順序\n\n";
        foreach (array_slice($this->priorityQueue, 0, 5) as $index => $item) {
            $rank = $index + 1;
            $config = $item['config'];
            $count = $item['count'];

            $report .= "### {$rank}. {$config['description']} ({$count} 個錯誤)\n";
            $report .= "- **優先級**: {$config['priority']}\n";
            $report .= "- **複雜度**: {$config['complexity']}/10\n";
            $report .= "- **可修復性**: {$config['fixability']}/10\n";
            $report .= "- **自動修復**: " . ($config['auto_fixable'] ? '可以' : '需要手動') . "\n";
            $report .= "- **預估時間**: {$config['estimated_time']}\n\n";
        }

        $report .= "## 📁 最需要關注的檔案\n\n";
        arsort($this->fileErrorCounts);
        $topFiles = array_slice($this->fileErrorCounts, 0, 15, true);

        $report .= "| 檔案 | 錯誤數 | 建議處理方式 |\n";
        $report .= "|------|--------|-------------|\n";

        foreach ($topFiles as $file => $count) {
            $suggestion = $this->getFileSuggestion($count);
            $report .= "| `" . basename($file) . "` | {$count} | {$suggestion} |\n";
        }

        $report .= "\n## 🛠️ 修復策略建議\n\n";
        $report .= $this->generateFixingStrategiesText();

        $report .= "\n## 📈 預期效果\n\n";
        $autoFixableCount = 0;
        $manualFixCount = 0;

        foreach ($this->priorityQueue as $item) {
            if ($item['config']['auto_fixable']) {
                $autoFixableCount += $item['count'];
            } else {
                $manualFixCount += $item['count'];
            }
        }

        if ($this->totalErrors > 0) {
            $report .= "- **可自動修復**: {$autoFixableCount} 個錯誤 (" . round($autoFixableCount / $this->totalErrors * 100, 1) . "%)\n";
            $report .= "- **需要手動修復**: {$manualFixCount} 個錯誤 (" . round($manualFixCount / $this->totalErrors * 100, 1) . "%)\n";
        } else {
            $report .= "- **可自動修復**: {$autoFixableCount} 個錯誤\n";
            $report .= "- **需要手動修復**: {$manualFixCount} 個錯誤\n";
        }
        $report .= "- **預計總修復時間**: " . $this->estimateTotalTime() . "\n";

        return $report;
    }

    private function getFileSuggestion(int $errorCount): string
    {
        if ($errorCount > 20) {
            return "🔴 緊急手動修復";
        } elseif ($errorCount > 10) {
            return "🟡 優先自動+手動";
        } elseif ($errorCount > 5) {
            return "🟢 自動修復為主";
        } else {
            return "⚪ 批量處理";
        }
    }

    private function generateFixingStrategies(): void
    {
        echo "\n💡 修復策略建議:\n";
        echo "==================================================\n";

        foreach (array_slice($this->priorityQueue, 0, 3) as $index => $item) {
            $rank = $index + 1;
            $config = $item['config'];
            $count = $item['count'];

            echo "\n{$rank}. {$config['description']} ({$count} 個)\n";
            echo "   優先級: {$config['priority']} | 可自動修復: " . ($config['auto_fixable'] ? '是' : '否') . "\n";
            echo "   建議: " . $this->getStrategyAdvice($config) . "\n";
        }
    }

    private function generateFixingStrategiesText(): string
    {
        $strategies = "";

        foreach (array_slice($this->priorityQueue, 0, 5) as $index => $item) {
            $rank = $index + 1;
            $config = $item['config'];
            $count = $item['count'];

            $strategies .= "### 策略 {$rank}: {$config['description']}\n";
            $strategies .= "**錯誤數**: {$count} 個\n\n";
            $strategies .= $this->getDetailedStrategy($config) . "\n\n";
        }

        return $strategies;
    }

    private function getStrategyAdvice(array $config): string
    {
        if ($config['auto_fixable']) {
            return "使用自動化腳本批量修復";
        } else {
            return "需要手動逐檔案檢查修復";
        }
    }

    private function getDetailedStrategy(array $config): string
    {
        $category = array_search($config, $this->errorCategories);

        return match($category) {
            'syntax_unexpected_token' => "1. 手動檢查類和方法結構\n2. 確認存取修飾符正確性\n3. 檢查方法簽名完整性",
            'syntax_bracket_mismatch' => "1. 使用 fix-missing-braces.php 腳本\n2. 檢查括號配對\n3. 驗證語法正確性",
            'syntax_try_catch_incomplete' => "1. 使用 fix-incomplete-try-catch.php 腳本\n2. 補充 catch 或 finally 塊\n3. 確保錯誤處理完整",
            'syntax_array_errors' => "1. 使用 fix-array-and-function-syntax.php 腳本\n2. 清理多餘逗號\n3. 修復陣列語法結構",
            'multiple_access_modifiers' => "1. 移除重複的存取修飾符\n2. 保留最後一個修飾符\n3. 確認方法可見性",
            default => "1. 手動分析具體錯誤\n2. 根據錯誤訊息修復\n3. 測試修復效果"
        };
    }

    private function estimateTotalTime(): string
    {
        $totalMinutes = 0;

        foreach ($this->priorityQueue as $item) {
            $config = $item['config'];
            $count = $item['count'];

            // 從時間字串中提取平均值
            if (preg_match('/(\d+)-(\d+)分鐘/', $config['estimated_time'], $matches)) {
                $avgMinutes = ($matches[1] + $matches[2]) / 2;
                $totalMinutes += $avgMinutes * $count;
            }
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf("%d小時%d分鐘", $hours, $minutes);
    }

    private function categorizeErrors(): void
    {
        echo "\n📋 錯誤分類結果:\n";
        foreach ($this->errorsByType as $category => $errors) {
            $config = $this->errorCategories[$category] ?? $this->errorCategories['syntax_other'];
            $count = count($errors);
            echo "  - {$config['description']}: {$count} 個\n";
        }
    }
}

// 執行分析
if (php_sapi_name() === 'cli') {
    try {
        $analyzer = new AdvancedSyntaxAnalyzer();
        $analyzer->run();
    } catch (Exception $e) {
        echo "❌ 分析過程中發生錯誤: " . $e->getMessage() . "\n";
        echo "📊 請檢查 PHPStan 輸出格式是否正確\n";
    }
}
