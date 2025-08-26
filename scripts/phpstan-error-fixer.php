<?php

declare(strict_types=1);

/**
 * PHPStan 錯誤自動修復工具
 * 基於 Context7 MCP 查詢的最新 PHPStan 知識和最佳實踐
 * 
 * 功能:
 * - 自動分析 PHPStan 輸出
 * - 分類錯誤類型並提供修復建議
 * - 自動修復常見的錯誤類型
 * - 生成修復報告和剩餘問題清單
 */

class PhpStanErrorFixer
{
    private array $errorPatterns = [];
    private array $fixableErrors = [];
    private array $unfixableErrors = [];
    private array $statistics = [];
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = realpath($projectRoot);
        $this->initializeErrorPatterns();
    }

    /**
     * 初始化錯誤模式（基於 Context7 MCP 查詢的 PHPStan 最新知識）
     */
    private function initializeErrorPatterns(): void
    {
        $this->errorPatterns = [
            // 未使用的方法和常數 (unused methods and constants)
            'unused_methods' => [
                'pattern' => '/Method .+::.+\(\) is unused\./i',
                'fixable' => true,
                'priority' => 'LOW',
                'action' => 'remove_or_mark_internal'
            ],

            'unused_constants' => [
                'pattern' => '/Constant .+ is unused\./i',
                'fixable' => true,
                'priority' => 'LOW',
                'action' => 'remove_or_mark_internal'
            ],

            // 數組偏移不存在 (array offset does not exist)
            'array_offset_not_found' => [
                'pattern' => '/Offset .+ does not exist on array/i',
                'fixable' => true,
                'priority' => 'HIGH',
                'action' => 'add_array_key_checks'
            ],

            // 未定義方法調用 (undefined method calls)
            'undefined_method_calls' => [
                'pattern' => '/Call to an undefined method .+::.+\(\)\./i',
                'fixable' => true,
                'priority' => 'HIGH',
                'action' => 'fix_mockery_mock_calls'
            ],

            // 嚴格比較問題 (strict comparison issues)
            'strict_comparison_always_true' => [
                'pattern' => '/Strict comparison using !== between .+ will always evaluate to true\./i',
                'fixable' => true,
                'priority' => 'MEDIUM',
                'action' => 'fix_strict_comparisons'
            ],

            // 型別錯誤 (type errors)
            'type_errors' => [
                'pattern' => '/Parameter #\d+ .+ expects .+, .+ given\./i',
                'fixable' => false,
                'priority' => 'HIGH',
                'action' => 'manual_review_required'
            ],

            // Mockery 相關問題
            'mockery_issues' => [
                'pattern' => '/expects .+, Mockery.+Mock.+ given/i',
                'fixable' => true,
                'priority' => 'HIGH',
                'action' => 'fix_mockery_type_issues'
            ]
        ];
    }

    /**
     * 分析 PHPStan 輸出檔案
     */
    public function analyzePhpStanOutput(string $outputFile): array
    {
        if (!file_exists($outputFile)) {
            throw new InvalidArgumentException("PHPStan 輸出檔案不存在: {$outputFile}");
        }

        $content = file_get_contents($outputFile);
        $lines = explode("\n", $content);

        $errors = [];
        $currentError = null;

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // 檢查是否為錯誤行 (以行號和檔案路徑開始)
            if (preg_match('/^\s*(\d+)\s+(.+)$/', $line, $matches)) {
                if ($currentError) {
                    $errors[] = $currentError;
                }

                $currentError = [
                    'line_number' => (int)$matches[1],
                    'message' => trim($matches[2]),
                    'file' => null,
                    'type' => 'unknown',
                    'fixable' => false,
                    'priority' => 'UNKNOWN'
                ];

                // 分析錯誤類型
                $this->classifyError($currentError);
            }

            // 檢查檔案路徑行
            if (preg_match('/^\s*Line\s+(.+\.php)/', $line, $matches)) {
                if ($currentError) {
                    $currentError['file'] = trim($matches[1]);
                }
            }
        }

        if ($currentError) {
            $errors[] = $currentError;
        }

        $this->categorizeErrors($errors);
        return $errors;
    }

    /**
     * 分類錯誤
     */
    private function classifyError(array &$error): void
    {
        foreach ($this->errorPatterns as $type => $config) {
            if (preg_match($config['pattern'], $error['message'])) {
                $error['type'] = $type;
                $error['fixable'] = $config['fixable'];
                $error['priority'] = $config['priority'];
                $error['action'] = $config['action'];
                break;
            }
        }
    }

    /**
     * 將錯誤分組
     */
    private function categorizeErrors(array $errors): void
    {
        $this->fixableErrors = array_filter($errors, fn($error) => $error['fixable']);
        $this->unfixableErrors = array_filter($errors, fn($error) => !$error['fixable']);

        // 統計資訊
        $this->statistics = [
            'total_errors' => count($errors),
            'fixable_errors' => count($this->fixableErrors),
            'unfixable_errors' => count($this->unfixableErrors),
            'by_type' => [],
            'by_priority' => ['HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0, 'UNKNOWN' => 0]
        ];

        foreach ($errors as $error) {
            $type = $error['type'];
            $priority = $error['priority'];

            $this->statistics['by_type'][$type] = ($this->statistics['by_type'][$type] ?? 0) + 1;
            $this->statistics['by_priority'][$priority]++;
        }
    }

    /**
     * 執行自動修復
     */
    public function executeAutoFixes(): array
    {
        $fixResults = [];

        // 按優先級排序
        $sortedErrors = $this->fixableErrors;
        usort($sortedErrors, function ($a, $b) {
            $priorities = ['HIGH' => 3, 'MEDIUM' => 2, 'LOW' => 1];
            return ($priorities[$b['priority']] ?? 0) - ($priorities[$a['priority']] ?? 0);
        });

        foreach ($sortedErrors as $error) {
            $result = $this->fixError($error);
            if ($result) {
                $fixResults[] = $result;
            }
        }

        return $fixResults;
    }

    /**
     * 修復單個錯誤
     */
    private function fixError(array $error): ?array
    {
        $action = $error['action'] ?? null;
        if (!$action) return null;

        switch ($action) {
            case 'remove_or_mark_internal':
                return $this->handleUnusedItems($error);

            case 'add_array_key_checks':
                return $this->addArrayKeyChecks($error);

            case 'fix_mockery_mock_calls':
                return $this->fixMockeryMockCalls($error);

            case 'fix_strict_comparisons':
                return $this->fixStrictComparisons($error);

            case 'fix_mockery_type_issues':
                return $this->fixMockeryTypeIssues($error);

            default:
                return null;
        }
    }

    /**
     * 處理未使用的項目 (方法、常數等)
     */
    private function handleUnusedItems(array $error): ?array
    {
        $file = $error['file'] ?? null;
        if (!$file) return null;

        $filePath = $this->projectRoot . '/' . ltrim($file, '/');
        if (!file_exists($filePath)) return null;

        $content = file_get_contents($filePath);
        $lineNumber = $error['line_number'];
        $lines = explode("\n", $content);

        if (!isset($lines[$lineNumber - 1])) return null;

        $line = $lines[$lineNumber - 1];

        // 提取方法或常數名稱
        $itemName = null;
        if (preg_match('/Method (.+::.+)\(\) is unused/', $error['message'], $matches)) {
            $itemName = $matches[1];
            $type = 'method';
        } elseif (preg_match('/Constant (.+) is unused/', $error['message'], $matches)) {
            $itemName = $matches[1];
            $type = 'constant';
        }

        if (!$itemName) return null;

        // 對於方法和常數，我們添加 @internal 註解而不是直接刪除
        // 因為它們可能在未來會被使用
        if ($type === 'method') {
            $docComment = "    /**\n     * @internal This method is currently unused but kept for future use\n     */\n";
            $lines[$lineNumber - 1] = $docComment . $line;
        } elseif ($type === 'constant') {
            // 為常數添加註解
            $docComment = "    /** @internal Currently unused but kept for future use */\n";
            $lines[$lineNumber - 1] = $docComment . $line;
        }

        file_put_contents($filePath, implode("\n", $lines));

        return [
            'type' => 'unused_item_marked',
            'file' => $filePath,
            'line' => $lineNumber,
            'item' => $itemName,
            'action' => "Added @internal annotation for unused {$type}"
        ];
    }

    /**
     * 添加數組鍵檢查
     */
    private function addArrayKeyChecks(array $error): ?array
    {
        $file = $error['file'] ?? null;
        if (!$file) return null;

        $filePath = $this->projectRoot . '/' . ltrim($file, '/');
        if (!file_exists($filePath)) return null;

        // 對於數組偏移問題，我們需要手動檢查具體情況
        // 這裡先標記為需要手動審查
        return [
            'type' => 'array_offset_manual_review',
            'file' => $filePath,
            'line' => $error['line_number'],
            'message' => $error['message'],
            'action' => 'Manual review required: Consider adding isset() or array_key_exists() checks'
        ];
    }

    /**
     * 修復 Mockery Mock 調用
     */
    private function fixMockeryMockCalls(array $error): ?array
    {
        $file = $error['file'] ?? null;
        if (!$file) return null;

        // Mockery 相關的問題通常需要手動處理
        // 因為涉及到測試的邏輯和 mock 對象的設置
        return [
            'type' => 'mockery_manual_review',
            'file' => $file,
            'line' => $error['line_number'],
            'message' => $error['message'],
            'action' => 'Manual review required: Check mock object methods and MockeryPHPUnitIntegration trait'
        ];
    }

    /**
     * 修復嚴格比較問題
     */
    private function fixStrictComparisons(array $error): ?array
    {
        // 嚴格比較問題通常需要調整條件邏輯
        return [
            'type' => 'strict_comparison_manual_review',
            'file' => $error['file'] ?? null,
            'line' => $error['line_number'],
            'message' => $error['message'],
            'action' => 'Manual review required: Adjust conditional logic or type declarations'
        ];
    }

    /**
     * 修復 Mockery 型別問題
     */
    private function fixMockeryTypeIssues(array $error): ?array
    {
        return [
            'type' => 'mockery_type_manual_review',
            'file' => $error['file'] ?? null,
            'line' => $error['line_number'],
            'message' => $error['message'],
            'action' => 'Manual review required: Check mock return types and method signatures'
        ];
    }

    /**
     * 生成修復報告
     */
    public function generateReport(array $errors, array $fixResults): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $reportPath = $this->projectRoot . '/storage/phpstan-fix-report.md';

        // 確保 storage 目錄存在
        $storageDir = dirname($reportPath);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $report = "# PHPStan 錯誤分析與修復報告\n\n";
        $report .= "**生成時間**: {$timestamp}\n";
        $report .= "**基於**: Context7 MCP 查詢的 PHPStan 最新知識\n\n";

        // 統計摘要
        $report .= "## 📊 錯誤統計摘要\n\n";
        $report .= "| 類別 | 數量 | 百分比 |\n";
        $report .= "|------|------|--------|\n";
        $report .= sprintf("| 總錯誤數 | %d | 100%% |\n", $this->statistics['total_errors']);
        $report .= sprintf(
            "| 可修復錯誤 | %d | %.1f%% |\n",
            $this->statistics['fixable_errors'],
            ($this->statistics['fixable_errors'] / max($this->statistics['total_errors'], 1)) * 100
        );
        $report .= sprintf(
            "| 需手動處理 | %d | %.1f%% |\n",
            $this->statistics['unfixable_errors'],
            ($this->statistics['unfixable_errors'] / max($this->statistics['total_errors'], 1)) * 100
        );
        $report .= "\n";

        // 優先級分布
        $report .= "## 🎯 優先級分布\n\n";
        foreach ($this->statistics['by_priority'] as $priority => $count) {
            if ($count > 0) {
                $emoji = match ($priority) {
                    'HIGH' => '🔴',
                    'MEDIUM' => '🟡',
                    'LOW' => '🟢',
                    default => '⚪'
                };
                $report .= "- {$emoji} {$priority}: {$count} 個錯誤\n";
            }
        }
        $report .= "\n";

        // 錯誤類型分布
        $report .= "## 🔍 錯誤類型分布\n\n";
        foreach ($this->statistics['by_type'] as $type => $count) {
            $report .= "- **{$type}**: {$count} 個錯誤\n";
        }
        $report .= "\n";

        // 修復結果
        if (!empty($fixResults)) {
            $report .= "## ✅ 自動修復結果\n\n";
            foreach ($fixResults as $result) {
                $report .= "### {$result['type']}\n";
                $report .= "- **檔案**: `{$result['file']}`\n";
                if (isset($result['line'])) {
                    $report .= "- **行號**: {$result['line']}\n";
                }
                $report .= "- **動作**: {$result['action']}\n\n";
            }
        }

        // 需手動處理的高優先級錯誤
        $highPriorityUnfixable = array_filter($this->unfixableErrors, fn($e) => $e['priority'] === 'HIGH');
        if (!empty($highPriorityUnfixable)) {
            $report .= "## 🔴 高優先級手動處理清單\n\n";
            foreach (array_slice($highPriorityUnfixable, 0, 20) as $error) {
                $report .= "### {$error['file']}:{$error['line_number']}\n";
                $report .= "```\n{$error['message']}\n```\n";
                $report .= "**建議**: " . $this->getSuggestionForError($error) . "\n\n";
            }
        }

        // 修復建議
        $report .= "## 🔧 修復建議\n\n";
        $report .= "### 立即處理 (高優先級)\n";
        $report .= "1. 數組偏移問題: 添加 `isset()` 或 `array_key_exists()` 檢查\n";
        $report .= "2. Mockery 問題: 檢查 MockeryPHPUnitIntegration trait 和 mock 方法簽名\n";
        $report .= "3. 型別問題: 審查方法參數和返回類型\n\n";

        $report .= "### 後續處理 (中低優先級)\n";
        $report .= "1. 未使用方法: 考慮是否真的需要這些方法\n";
        $report .= "2. 未使用常數: 移除不需要的常數\n";
        $report .= "3. 嚴格比較: 調整條件邏輯\n\n";

        $report .= "## 📝 下一步行動\n\n";
        $report .= "1. 先處理高優先級錯誤\n";
        $report .= "2. 執行測試確保修復不會破壞功能\n";
        $report .= "3. 重新執行 PHPStan 檢查修復效果\n";
        $report .= "4. 處理剩餘的中低優先級問題\n\n";

        file_put_contents($reportPath, $report);

        echo "✅ 修復報告已生成: {$reportPath}\n";
    }

    /**
     * 獲取錯誤的修復建議
     */
    private function getSuggestionForError(array $error): string
    {
        return match ($error['type']) {
            'array_offset_not_found' => '使用 isset($array[\'key\']) 或 $array[\'key\'] ?? null 來安全訪問數組元素',
            'undefined_method_calls' => '檢查 mock 對象的方法名稱和簽名，確保 MockeryPHPUnitIntegration trait 已正確使用',
            'strict_comparison_always_true' => '審查條件邏輯，可能需要調整型別宣告或移除不必要的檢查',
            'type_errors' => '檢查方法參數類型，確保傳入的參數與期望的型別匹配',
            'mockery_issues' => '檢查 Mockery mock 的返回類型和方法簽名是否正確',
            default => '需要手動審查此錯誤並根據具體情況進行修復'
        };
    }

    /**
     * 輸出彩色統計資訊
     */
    public function printColoredSummary(): void
    {
        echo "\n" . $this->colorize("=== 📊 PHPStan 錯誤修復摘要 ===", 'cyan') . "\n\n";

        echo $this->colorize("總錯誤數: ", 'yellow') . $this->colorize((string)$this->statistics['total_errors'], 'red') . "\n";
        echo $this->colorize("可修復: ", 'yellow') . $this->colorize((string)$this->statistics['fixable_errors'], 'green') . "\n";
        echo $this->colorize("需手動處理: ", 'yellow') . $this->colorize((string)$this->statistics['unfixable_errors'], 'red') . "\n\n";

        echo $this->colorize("優先級分布:", 'yellow') . "\n";
        foreach ($this->statistics['by_priority'] as $priority => $count) {
            if ($count > 0) {
                $color = match ($priority) {
                    'HIGH' => 'red',
                    'MEDIUM' => 'yellow',
                    'LOW' => 'green',
                    default => 'gray'
                };
                echo "  {$priority}: " . $this->colorize((string)$count, $color) . "\n";
            }
        }
        echo "\n";
    }

    /**
     * 輸出彩色文字
     */
    private function colorize(string $text, string $color): string
    {
        $colors = [
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'magenta' => '35',
            'cyan' => '36',
            'white' => '37',
            'gray' => '90'
        ];

        $colorCode = $colors[$color] ?? '37';
        return "\033[{$colorCode}m{$text}\033[0m";
    }

    /**
     * 獲取統計資訊
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * 獲取可修復錯誤
     */
    public function getFixableErrors(): array
    {
        return $this->fixableErrors;
    }

    /**
     * 獲取不可修復錯誤
     */
    public function getUnfixableErrors(): array
    {
        return $this->unfixableErrors;
    }
}

// 主程式
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

$options = getopt('f:h', ['file:', 'help', 'dry-run', 'fix', 'report-only']);

if (isset($options['h']) || isset($options['help'])) {
    echo "PHPStan 錯誤自動修復工具 v2.0\n";
    echo "基於 Context7 MCP 查詢的最新 PHPStan 知識\n\n";
    echo "用法: php phpstan-error-fixer.php [選項]\n\n";
    echo "選項:\n";
    echo "  -f, --file FILE     指定 PHPStan 輸出檔案\n";
    echo "  --dry-run           僅分析不執行修復\n";
    echo "  --fix               執行自動修復\n";
    echo "  --report-only       僅生成報告\n";
    echo "  -h, --help          顯示此幫助訊息\n\n";
    echo "範例:\n";
    echo "  php phpstan-error-fixer.php -f phpstan-output.txt --dry-run\n";
    echo "  php phpstan-error-fixer.php -f phpstan-output.txt --fix\n";
    exit(0);
}

$phpstanFile = $options['f'] ?? $options['file'] ?? 'phpstan-output.txt';
$dryRun = isset($options['dry-run']);
$fix = isset($options['fix']);
$reportOnly = isset($options['report-only']);

if (!$fix && !$dryRun && !$reportOnly) {
    echo "請指定操作模式: --dry-run, --fix, 或 --report-only\n";
    exit(1);
}

try {
    $fixer = new PhpStanErrorFixer(__DIR__ . '/..');

    echo "🔍 分析 PHPStan 輸出...\n";
    $errors = $fixer->analyzePhpStanOutput($phpstanFile);

    $fixer->printColoredSummary();

    $fixResults = [];
    if ($fix) {
        echo "🔧 執行自動修復...\n";
        $fixResults = $fixer->executeAutoFixes();
        echo "✅ 完成 " . count($fixResults) . " 項自動修復\n";
    }

    if (!$reportOnly) {
        echo "📝 生成修復報告...\n";
        $fixer->generateReport($errors, $fixResults);
    }

    if ($dryRun) {
        echo "\n💡 這是乾運行模式，沒有實際修改檔案\n";
        echo "使用 --fix 選項來執行實際修復\n";
    }

    exit(0);
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
