<?php

declare(strict_types=1);

/**
 * 改進的風險驅動錯誤分析工具
 *
 * 正確解析 PHPStan 輸出格式並按風險等級分類
 */

final class ImprovedErrorAnalyzer
{
    private const HIGH_RISK_PATTERNS = [
        'method.notFound' => 90,
        'class.notFound' => 90,
        'property.notFound' => 85,
        'variable.undefined' => 80,
        'argument.missing' => 80,
        'method.nonObject' => 75,
        'staticMethod.notFound' => 75,
        'return.type' => 70,
        'argument.type' => 65,
    ];

    private const MEDIUM_RISK_PATTERNS = [
        'catch.alreadyCaught' => 60,
        'argument.unknown' => 55,
        'arguments.count' => 55,
        'function.alreadyNarrowedType' => 50,
        'arrayValues.list' => 45,
    ];

    private const LOW_RISK_PATTERNS = [
        'property.onlyWritten' => 30,
        'variable.unused' => 25,
        'class.deprecated' => 20,
    ];

    private string $projectRoot;
    private array $errorSummary = [];
    private array $riskGroups = [];
    private array $fileErrorCounts = [];

    public function __construct(string $projectRoot = '/var/www/html')
    {
        $this->projectRoot = $projectRoot;
    }

    public function analyze(): void
    {
        echo "🔍 開始改進的風險驅動錯誤分析...\n";

        $phpstanOutput = $this->runPhpStan();
        $errors = $this->parsePhpStanOutput($phpstanOutput);

        echo "📝 解析到 " . count($errors) . " 個錯誤\n";

        $this->categorizeErrors($errors);
        $this->generateReport();
        $this->generateActionPlan();
    }

    private function runPhpStan(): string
    {
        $command = "cd {$this->projectRoot} && ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress 2>&1";
        $output = shell_exec($command);

        if ($output === null) {
            throw new RuntimeException('無法執行 PHPStan 分析');
        }

        return $output;
    }

    private function parsePhpStanOutput(string $output): array
    {
        $lines = explode("\n", $output);
        $errors = [];
        $currentFile = '';
        $inErrorSection = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // 跳過配置和總結行
            if (str_contains($line, 'Using configuration') ||
                str_contains($line, 'Found') ||
                empty($line) ||
                str_contains($line, '------')) {
                continue;
            }

            // 檢測檔案標題行
            if (preg_match('/^\s*Line\s+(.+\.php)$/', $line, $matches)) {
                $currentFile = $matches[1];
                $inErrorSection = true;
                if (!isset($this->fileErrorCounts[$currentFile])) {
                    $this->fileErrorCounts[$currentFile] = 0;
                }
                continue;
            }

            // 解析錯誤行
            if ($inErrorSection && preg_match('/^\s*(\d+)\s+(.+?)🪪\s+([a-zA-Z.]+)/', $line, $matches)) {
                $lineNumber = (int)$matches[1];
                $message = trim($matches[2]);
                $errorCode = $matches[3];

                $errors[] = [
                    'file' => $currentFile,
                    'line' => $lineNumber,
                    'message' => $message,
                    'code' => $errorCode,
                    'risk_score' => $this->calculateRiskScore($errorCode),
                ];

                $this->fileErrorCounts[$currentFile]++;
            }
        }

        return $errors;
    }

    private function calculateRiskScore(string $errorCode): int
    {
        if (isset(self::HIGH_RISK_PATTERNS[$errorCode])) {
            return self::HIGH_RISK_PATTERNS[$errorCode];
        }

        if (isset(self::MEDIUM_RISK_PATTERNS[$errorCode])) {
            return self::MEDIUM_RISK_PATTERNS[$errorCode];
        }

        if (isset(self::LOW_RISK_PATTERNS[$errorCode])) {
            return self::LOW_RISK_PATTERNS[$errorCode];
        }

        return 40; // 預設中等風險
    }

    private function categorizeErrors(array $errors): void
    {
        $this->riskGroups = [
            'critical' => [], // 90+
            'high' => [],     // 70-89
            'medium' => [],   // 40-69
            'low' => [],      // <40
        ];

        foreach ($errors as $error) {
            $score = $error['risk_score'];

            if ($score >= 90) {
                $this->riskGroups['critical'][] = $error;
            } elseif ($score >= 70) {
                $this->riskGroups['high'][] = $error;
            } elseif ($score >= 40) {
                $this->riskGroups['medium'][] = $error;
            } else {
                $this->riskGroups['low'][] = $error;
            }
        }

        // 統計錯誤類型
        foreach ($errors as $error) {
            $code = $error['code'];
            if (!isset($this->errorSummary[$code])) {
                $this->errorSummary[$code] = [
                    'count' => 0,
                    'risk_score' => $error['risk_score'],
                    'files' => [],
                ];
            }
            $this->errorSummary[$code]['count']++;

            if (!in_array($error['file'], $this->errorSummary[$code]['files'])) {
                $this->errorSummary[$code]['files'][] = $error['file'];
            }
        }
    }

    private function generateReport(): void
    {
        echo "\n📊 錯誤風險分析報告\n";
        echo str_repeat("=", 60) . "\n";

        $totalErrors = array_sum(array_map('count', $this->riskGroups));
        echo "📈 總錯誤數: {$totalErrors}\n\n";

        foreach (['critical', 'high', 'medium', 'low'] as $level) {
            $count = count($this->riskGroups[$level]);
            $percentage = $totalErrors > 0 ? round(($count / $totalErrors) * 100, 1) : 0;
            $emoji = $this->getRiskEmoji($level);
            echo sprintf("%s %s 風險: %d 個錯誤 (%s%%)\n",
                $emoji, strtoupper($level), $count, $percentage);
        }

        echo "\n🔍 錯誤類型統計 (前 15 名):\n";
        uasort($this->errorSummary, fn($a, $b) => $b['count'] - $a['count']);
        $top15 = array_slice($this->errorSummary, 0, 15, true);

        foreach ($top15 as $code => $info) {
            $riskLevel = $this->getRiskLevelFromScore($info['risk_score']);
            echo sprintf("  • %s: %d 個 (風險: %s, 影響檔案: %d)\n",
                $code, $info['count'], $riskLevel, count($info['files']));
        }

        echo "\n📁 錯誤最多的檔案 (前 10 名):\n";
        arsort($this->fileErrorCounts);
        $topFiles = array_slice($this->fileErrorCounts, 0, 10, true);

        foreach ($topFiles as $file => $count) {
            echo sprintf("  • %s: %d 個錯誤\n", basename($file), $count);
        }
    }

    private function generateActionPlan(): void
    {
        echo "\n🎯 修復行動計劃\n";
        echo str_repeat("=", 60) . "\n";

        $criticalCount = count($this->riskGroups['critical']);
        $highCount = count($this->riskGroups['high']);
        $mediumCount = count($this->riskGroups['medium']);

        if ($criticalCount > 0) {
            echo "🚨 第一階段: 修復 {$criticalCount} 個關鍵錯誤\n";
            $this->suggestBatchActions('critical', 1);
        }

        if ($highCount > 0) {
            echo "\n⚠️  第二階段: 修復 {$highCount} 個高風險錯誤\n";
            $this->suggestBatchActions('high', $criticalCount > 0 ? 2 : 1);
        }

        if ($mediumCount > 0) {
            echo "\n🟡 第三階段: 修復 {$mediumCount} 個中風險錯誤\n";
            echo "  建議: 批次處理相同錯誤類型\n";
        }

        echo "\n💡 批次處理建議:\n";
        echo "  • 每次處理 3-5 個檔案或同類型錯誤\n";
        echo "  • 每批修復後立即執行: docker compose exec -T web ./vendor/bin/phpstan analyse\n";
        echo "  • 驗證無新錯誤後再進行下一批\n";
        echo "  • 優先處理錯誤數量最多的檔案\n";

        $this->suggestSpecificFixes();
    }

    private function suggestBatchActions(string $riskLevel, int $startPhase): void
    {
        $errors = $this->riskGroups[$riskLevel];
        $fileGroups = [];

        foreach ($errors as $error) {
            $file = $error['file'];
            if (!isset($fileGroups[$file])) {
                $fileGroups[$file] = [];
            }
            $fileGroups[$file][] = $error;
        }

        // 按錯誤數量排序文件
        uasort($fileGroups, fn($a, $b) => count($b) - count($a));

        $batchCount = $startPhase;
        $currentBatch = [];
        $maxBatchSize = $riskLevel === 'critical' ? 3 : 5;

        foreach ($fileGroups as $file => $fileErrors) {
            $currentBatch[] = basename($file);

            if (count($currentBatch) >= $maxBatchSize) {
                echo "  批次 {$batchCount}: " . implode(', ', $currentBatch) . "\n";
                $currentBatch = [];
                $batchCount++;
            }
        }

        if (!empty($currentBatch)) {
            echo "  批次 {$batchCount}: " . implode(', ', $currentBatch) . "\n";
        }
    }

    private function suggestSpecificFixes(): void
    {
        echo "\n🔧 具體修復建議:\n";

        $topErrorTypes = array_slice($this->errorSummary, 0, 5, true);

        foreach ($topErrorTypes as $errorCode => $info) {
            $suggestion = $this->getFixSuggestion($errorCode);
            if ($suggestion) {
                echo "  • {$errorCode} ({$info['count']} 個): {$suggestion}\n";
            }
        }
    }

    private function getFixSuggestion(string $errorCode): ?string
    {
        return match ($errorCode) {
            'method.notFound' => '檢查方法名稱拼寫，或新增缺少的方法定義',
            'class.notFound' => '檢查類別名稱或新增 use 語句',
            'property.notFound' => '檢查屬性名稱或新增屬性定義',
            'variable.undefined' => '初始化變數或檢查變數作用域',
            'argument.type' => '檢查參數類型並新增適當的類型轉換',
            'return.type' => '修正回傳類型註解或回傳值',
            'catch.alreadyCaught' => '移除重複的 catch 區塊',
            'method.nonObject' => '新增 null 檢查或確保物件類型',
            default => null,
        };
    }

    private function getRiskEmoji(string $level): string
    {
        return match ($level) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '🟡',
            'low' => '🟢',
            default => '❓',
        };
    }

    private function getRiskLevelFromScore(int $score): string
    {
        if ($score >= 90) return 'CRITICAL';
        if ($score >= 70) return 'HIGH';
        if ($score >= 40) return 'MEDIUM';
        return 'LOW';
    }

    public function exportToJson(string $filename = '/tmp/error-analysis.json'): void
    {
        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_errors' => array_sum(array_map('count', $this->riskGroups)),
            'risk_groups' => array_map('count', $this->riskGroups),
            'error_summary' => $this->errorSummary,
            'file_error_counts' => $this->fileErrorCounts,
            'top_error_files' => array_slice($this->fileErrorCounts, 0, 20, true),
        ];

        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "\n💾 分析結果已儲存至: {$filename}\n";
    }

    public function getNextBatchFiles(string $riskLevel = 'critical', int $batchSize = 3): array
    {
        $errors = $this->riskGroups[$riskLevel] ?? [];
        if (empty($errors)) {
            $riskLevel = 'high';
            $errors = $this->riskGroups[$riskLevel] ?? [];
        }

        $fileGroups = [];
        foreach ($errors as $error) {
            $file = $error['file'];
            if (!isset($fileGroups[$file])) {
                $fileGroups[$file] = 0;
            }
            $fileGroups[$file]++;
        }

        arsort($fileGroups);
        return array_slice(array_keys($fileGroups), 0, $batchSize);
    }
}

// 執行分析
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $analyzer = new ImprovedErrorAnalyzer();
        $analyzer->analyze();
        $analyzer->exportToJson();

        echo "\n🚀 建議立即開始修復的檔案:\n";
        $nextBatch = $analyzer->getNextBatchFiles('critical', 3);
        if (empty($nextBatch)) {
            $nextBatch = $analyzer->getNextBatchFiles('high', 3);
        }

        foreach ($nextBatch as $file) {
            echo "  • " . basename($file) . "\n";
        }

    } catch (Exception $e) {
        echo "❌ 分析失敗: " . $e->getMessage() . "\n";
        exit(1);
    }
}
