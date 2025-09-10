<?php

declare(strict_types=1);

/**
 * 風險驅動錯誤分析工具
 *
 * 實現「風險驅動優先級 → 批次處理 → 立即驗證 → 持續改進」工作流程
 * 分析 PHPStan 錯誤並按風險等級分類，產生修復建議
 */

final class RiskDrivenErrorAnalyzer
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

    public function __construct(string $projectRoot = '/var/www/html')
    {
        $this->projectRoot = $projectRoot;
    }

    public function analyze(): void
    {
        echo "🔍 開始風險驅動錯誤分析...\n";

        $phpstanOutput = $this->runPhpStan();
        $errors = $this->parsePhpStanOutput($phpstanOutput);

        $this->categorizeErrors($errors);
        $this->generateReport();
        $this->generateActionPlan();
    }

    private function runPhpStan(): string
    {
        $command = "cd {$this->projectRoot} && ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress --error-format=json 2>/dev/null";
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

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || !str_contains($line, '🪪')) {
                continue;
            }

            // 解析錯誤格式: Line | File | Message | Error Code
            if (preg_match('/🪪\s+([a-zA-Z.]+)/', $line, $matches)) {
                $errorCode = $matches[1];
                $filePath = $this->extractFilePath($line);
                $message = $this->extractMessage($line);
                $lineNumber = $this->extractLineNumber($line);

                $errors[] = [
                    'file' => $filePath,
                    'line' => $lineNumber,
                    'message' => $message,
                    'code' => $errorCode,
                    'risk_score' => $this->calculateRiskScore($errorCode),
                ];
            }
        }

        return $errors;
    }

    private function extractFilePath(string $line): string
    {
        if (preg_match('/^[^|]*\|\s*([^|]+?)\s*\|/', $line, $matches)) {
            return trim($matches[1]);
        }
        return 'unknown';
    }

    private function extractMessage(string $line): string
    {
        $parts = explode('🪪', $line);
        if (count($parts) > 1) {
            return trim($parts[0]);
        }
        return trim($line);
    }

    private function extractLineNumber(string $line): int
    {
        if (preg_match('/^\s*(\d+)/', $line, $matches)) {
            return (int)$matches[1];
        }
        return 0;
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
            $this->errorSummary[$code]['files'][] = $error['file'];
        }
    }

    private function generateReport(): void
    {
        echo "\n📊 錯誤風險分析報告\n";
        echo str_repeat("=", 50) . "\n";

        foreach (['critical', 'high', 'medium', 'low'] as $level) {
            $count = count($this->riskGroups[$level]);
            $emoji = $this->getRiskEmoji($level);
            echo sprintf("%s %s 風險: %d 個錯誤\n", $emoji, strtoupper($level), $count);
        }

        echo "\n🔍 錯誤類型統計 (前 10 名):\n";
        arsort($this->errorSummary);
        $top10 = array_slice($this->errorSummary, 0, 10, true);

        foreach ($top10 as $code => $info) {
            echo sprintf("  • %s: %d 個 (風險分數: %d)\n",
                $code, $info['count'], $info['risk_score']);
        }
    }

    private function generateActionPlan(): void
    {
        echo "\n🎯 修復行動計劃\n";
        echo str_repeat("=", 50) . "\n";

        $criticalCount = count($this->riskGroups['critical']);
        $highCount = count($this->riskGroups['high']);

        if ($criticalCount > 0) {
            echo "🚨 第一階段: 修復 {$criticalCount} 個關鍵錯誤\n";
            $this->suggestBatchActions('critical');
        }

        if ($highCount > 0) {
            echo "\n⚠️  第二階段: 修復 {$highCount} 個高風險錯誤\n";
            $this->suggestBatchActions('high');
        }

        echo "\n💡 建議批次大小: 每次處理 3-5 個檔案\n";
        echo "🔄 驗證步驟: 每批修復後立即執行 PHPStan 檢查\n";
    }

    private function suggestBatchActions(string $riskLevel): void
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

        $batchCount = 1;
        $currentBatch = [];

        foreach ($fileGroups as $file => $fileErrors) {
            $currentBatch[] = $file;

            if (count($currentBatch) >= 3) {
                echo "  批次 {$batchCount}: " . implode(', ', array_map('basename', $currentBatch)) . "\n";
                $currentBatch = [];
                $batchCount++;
            }
        }

        if (!empty($currentBatch)) {
            echo "  批次 {$batchCount}: " . implode(', ', array_map('basename', $currentBatch)) . "\n";
        }
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

    public function exportToJson(string $filename = 'error-analysis.json'): void
    {
        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_errors' => array_sum(array_map('count', $this->riskGroups)),
            'risk_groups' => array_map('count', $this->riskGroups),
            'error_summary' => $this->errorSummary,
            'detailed_errors' => $this->riskGroups,
        ];

        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "\n💾 分析結果已儲存至: {$filename}\n";
    }
}

// 執行分析
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $analyzer = new RiskDrivenErrorAnalyzer();
        $analyzer->analyze();
        $analyzer->exportToJson('/tmp/error-analysis.json');
    } catch (Exception $e) {
        echo "❌ 分析失敗: " . $e->getMessage() . "\n";
        exit(1);
    }
}
