<?php

declare(strict_types=1);

/**
 * 語法錯誤分析腳本
 *
 * 用於分析專案中的語法錯誤，並按風險等級分類
 */

class SyntaxErrorAnalyzer
{
    private array $errorPatterns = [
        'critical' => [
            'Cannot use try without catch or finally',
            'Syntax error, unexpected T_PUBLIC',
            'Syntax error, unexpected T_PRIVATE',
            'Syntax error, unexpected T_PROTECTED',
            'Syntax error, unexpected EOF',
            'Syntax error, unexpected \'}\'',
        ],
        'high' => [
            'Syntax error, unexpected T_RETURN',
            'Syntax error, unexpected T_IS_EQUAL',
            'Syntax error, unexpected \',\'',
            'Cannot use empty array elements',
        ],
        'medium' => [
            'Syntax error, unexpected \'*\'',
            'Syntax error, unexpected \':\'',
            'Syntax error, unexpected \'=\'',
            'Syntax error, unexpected T_DOUBLE_ARROW',
        ],
        'low' => [
            'unused import',
            'missing return type',
            'missing parameter type',
        ]
    ];

    private array $fileCategories = [
        'core' => [
            'app/Domains/Auth/Services/',
            'app/Domains/Security/Services/',
            'app/Infrastructure/Database/',
            'app/Infrastructure/Auth/',
        ],
        'application' => [
            'app/Application/Controllers/',
            'app/Application/Services/',
            'app/Application/Middleware/',
        ],
        'domain' => [
            'app/Domains/',
        ],
        'tests' => [
            'tests/',
        ]
    ];

    public function analyzeFromPhpstan(): array
    {
        $command = 'docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=256M --error-format=raw';
        $output = shell_exec($command);

        if (!$output) {
            return [];
        }

        return $this->parsePhpstanOutput($output);
    }

    private function parsePhpstanOutput(string $output): array
    {
        $lines = explode("\n", $output);
        $errors = [];

        foreach ($lines as $line) {
            if (preg_match('/^(.+?):(\d+):(.+)$/', trim($line), $matches)) {
                $file = $matches[1];
                $lineNumber = (int)$matches[2];
                $message = trim($matches[3]);

                if (strpos($file, '/var/www/html/') === 0) {
                    $file = substr($file, strlen('/var/www/html/'));
                }

                $errors[] = [
                    'file' => $file,
                    'line' => $lineNumber,
                    'message' => $message,
                    'risk' => $this->classifyRisk($message),
                    'category' => $this->classifyFileCategory($file),
                ];
            }
        }

        return $errors;
    }

    private function classifyRisk(string $message): string
    {
        foreach ($this->errorPatterns as $risk => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($message, $pattern) !== false) {
                    return $risk;
                }
            }
        }
        return 'unknown';
    }

    private function classifyFileCategory(string $file): string
    {
        foreach ($this->fileCategories as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($file, $pattern) === 0) {
                    return $category;
                }
            }
        }
        return 'other';
    }

    public function generateReport(array $errors): string
    {
        $report = "# 語法錯誤分析報告\n\n";
        $report .= "產生時間: " . date('Y-m-d H:i:s') . "\n\n";

        // 統計資訊
        $stats = $this->generateStats($errors);
        $report .= "## 統計資訊\n\n";
        $report .= "總錯誤數: {$stats['total']}\n";
        $report .= "檔案數: {$stats['files']}\n\n";

        // 按風險等級分組
        $report .= "### 按風險等級分類\n\n";
        foreach ($stats['by_risk'] as $risk => $count) {
            $report .= "- {$risk}: {$count}\n";
        }
        $report .= "\n";

        // 按檔案類別分組
        $report .= "### 按檔案類別分類\n\n";
        foreach ($stats['by_category'] as $category => $count) {
            $report .= "- {$category}: {$count}\n";
        }
        $report .= "\n";

        // 建議的修復批次
        $batches = $this->suggestBatches($errors);
        $report .= "## 建議修復批次\n\n";

        foreach ($batches as $batchNumber => $batch) {
            $report .= "### 批次 {$batchNumber}\n\n";
            $report .= "風險等級: {$batch['risk']}\n";
            $report .= "檔案類別: {$batch['category']}\n";
            $report .= "檔案數: " . count($batch['files']) . "\n\n";

            foreach ($batch['files'] as $file) {
                $report .= "- {$file}\n";
            }
            $report .= "\n";
        }

        return $report;
    }

    private function generateStats(array $errors): array
    {
        $stats = [
            'total' => count($errors),
            'files' => count(array_unique(array_column($errors, 'file'))),
            'by_risk' => [],
            'by_category' => [],
        ];

        foreach ($errors as $error) {
            $stats['by_risk'][$error['risk']] = ($stats['by_risk'][$error['risk']] ?? 0) + 1;
            $stats['by_category'][$error['category']] = ($stats['by_category'][$error['category']] ?? 0) + 1;
        }

        return $stats;
    }

    private function suggestBatches(array $errors): array
    {
        $fileErrors = [];

        // 按檔案分組錯誤
        foreach ($errors as $error) {
            $file = $error['file'];
            if (!isset($fileErrors[$file])) {
                $fileErrors[$file] = [
                    'errors' => [],
                    'max_risk' => 'low',
                    'category' => $error['category'],
                ];
            }

            $fileErrors[$file]['errors'][] = $error;

            // 更新最高風險等級
            $riskLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
            $currentLevel = $riskLevels[$fileErrors[$file]['max_risk']] ?? 1;
            $newLevel = $riskLevels[$error['risk']] ?? 1;

            if ($newLevel > $currentLevel) {
                $fileErrors[$file]['max_risk'] = $error['risk'];
            }
        }

        // 按優先級排序檔案
        uasort($fileErrors, function ($a, $b) {
            $riskLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
            $categoryLevels = ['core' => 4, 'application' => 3, 'domain' => 2, 'tests' => 1, 'other' => 0];

            $riskDiff = ($riskLevels[$b['max_risk']] ?? 1) - ($riskLevels[$a['max_risk']] ?? 1);
            if ($riskDiff !== 0) {
                return $riskDiff;
            }

            return ($categoryLevels[$b['category']] ?? 0) - ($categoryLevels[$a['category']] ?? 0);
        });

        // 建立批次（每批最多5個檔案）
        $batches = [];
        $batchNumber = 1;
        $currentBatch = [];
        $currentRisk = null;
        $currentCategory = null;

        foreach ($fileErrors as $file => $data) {
            // 如果批次已滿或風險/類別改變，開始新批次
            if (count($currentBatch) >= 5 ||
                ($currentRisk && $currentRisk !== $data['max_risk']) ||
                ($currentCategory && $currentCategory !== $data['category'])) {

                if (!empty($currentBatch)) {
                    $batches[$batchNumber] = [
                        'risk' => $currentRisk,
                        'category' => $currentCategory,
                        'files' => $currentBatch,
                    ];
                    $batchNumber++;
                }

                $currentBatch = [];
            }

            $currentBatch[] = $file;
            $currentRisk = $data['max_risk'];
            $currentCategory = $data['category'];
        }

        // 加入最後一批
        if (!empty($currentBatch)) {
            $batches[$batchNumber] = [
                'risk' => $currentRisk,
                'category' => $currentCategory,
                'files' => $currentBatch,
            ];
        }

        return $batches;
    }
}

// 主程式
if (php_sapi_name() === 'cli') {
    $analyzer = new SyntaxErrorAnalyzer();

    echo "正在分析語法錯誤...\n";
    $errors = $analyzer->analyzeFromPhpstan();

    if (empty($errors)) {
        echo "未找到語法錯誤！\n";
        exit(0);
    }

    echo "找到 " . count($errors) . " 個錯誤\n";

    $report = $analyzer->generateReport($errors);
    $reportFile = __DIR__ . '/syntax_errors_report.md';

    file_put_contents($reportFile, $report);

    echo "報告已儲存至: {$reportFile}\n";
    echo "\n" . $report;
}
