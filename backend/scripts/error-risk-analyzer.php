<?php

declare(strict_types=1);

/**
 * 錯誤風險分析腳本 - 根據風險驅動原則分析和排序錯誤.
 */
echo "=== 錯誤風險分析工具 ===\n\n";

// 讀取 PHPStan 分析結果
$phpstanFile = '/var/www/html/clean-phpstan-errors.json';
echo "嘗試讀取檔案：{$phpstanFile}\n";
if (!file_exists($phpstanFile)) {
    echo "❌ 找不到 PHPStan 分析結果檔案：{$phpstanFile}\n";
    exit(1);
}

$content = file_get_contents($phpstanFile);
// 提取 JSON 部分（跳過進度條和警告訊息）
$jsonStart = strpos($content, '{"totals"');
if ($jsonStart === false) {
    echo "❌ 找不到有效的 JSON 資料\n";
    exit(1);
}
$jsonContent = substr($content, $jsonStart);

$phpstanData = json_decode($jsonContent, true);
if (!$phpstanData) {
    echo "❌ 無法解析 PHPStan JSON 資料\n";
    exit(1);
}

// 定義風險權重
$riskWeights = [
    // 語法錯誤 - 最高風險
    'phpstan.parse' => 100,
    'syntax_error' => 100,
    'try_without_catch' => 90,

    // 核心系統檔案 - 高風險
    'core_files' => [
        'Cache' => 85,
        'Monitoring' => 80,
        'Validation' => 75,
        'Config' => 70,
        'DTOs' => 65,
        'Schemas' => 60,
    ],

    // 測試檔案 - 中等風險
    'test_files' => [
        'manual' => 50,
        'Performance' => 45,
        'Support' => 40,
        'Functional' => 35,
        'E2E' => 30,
    ],
];

$fileRiskScores = [];
$errorClassification = [
    'syntax_errors' => [],
    'try_catch_errors' => [],
    'array_errors' => [],
    'class_errors' => [],
    'other_errors' => [],
];

echo "📊 分析 PHPStan 錯誤...\n";
echo "總計檔案錯誤數：{$phpstanData['totals']['file_errors']}\n\n";

foreach ($phpstanData['files'] as $filePath => $fileData) {
    $relativeFile = str_replace('/var/www/html/', '', $filePath);
    $errorCount = $fileData['errors'];

    // 計算基礎風險分數
    $baseRisk = $errorCount * 10; // 每個錯誤基礎分數 10

    // 根據檔案類型和位置調整風險分數
    $fileTypeMultiplier = 1.0;

    if (str_contains($relativeFile, 'app/Shared/Cache/')) {
        $fileTypeMultiplier = 2.5; // 快取系統非常重要
    } elseif (str_contains($relativeFile, 'app/Shared/Monitoring/')) {
        $fileTypeMultiplier = 2.2;
    } elseif (str_contains($relativeFile, 'app/Shared/Validation/')) {
        $fileTypeMultiplier = 2.0;
    } elseif (str_contains($relativeFile, 'app/Shared/Config/')) {
        $fileTypeMultiplier = 1.8;
    } elseif (str_contains($relativeFile, 'app/Shared/DTOs/')) {
        $fileTypeMultiplier = 1.6;
    } elseif (str_contains($relativeFile, 'app/Shared/Schemas/')) {
        $fileTypeMultiplier = 1.4;
    } elseif (str_contains($relativeFile, 'tests/manual/')) {
        $fileTypeMultiplier = 1.0;
    } elseif (str_contains($relativeFile, 'tests/Performance/')) {
        $fileTypeMultiplier = 0.9;
    } elseif (str_contains($relativeFile, 'tests/Support/')) {
        $fileTypeMultiplier = 0.8;
    } elseif (str_contains($relativeFile, 'tests/')) {
        $fileTypeMultiplier = 0.7;
    }

    // 根據錯誤類型調整風險分數
    $errorTypeMultiplier = 1.0;
    $syntaxErrorCount = 0;
    $tryCatchErrorCount = 0;

    foreach ($fileData['messages'] as $message) {
        if ($message['identifier'] === 'phpstan.parse' ||
            str_contains($message['message'], 'Syntax error')) {
            $syntaxErrorCount++;
            if (str_contains($message['message'], 'try without catch')) {
                $tryCatchErrorCount++;
                $errorClassification['try_catch_errors'][] = [
                    'file' => $relativeFile,
                    'line' => $message['line'],
                    'message' => $message['message'],
                ];
            } elseif (str_contains($message['message'], 'array')) {
                $errorClassification['array_errors'][] = [
                    'file' => $relativeFile,
                    'line' => $message['line'],
                    'message' => $message['message'],
                ];
            } elseif (str_contains($message['message'], 'class') || str_contains($message['message'], 'T_CLASS')) {
                $errorClassification['class_errors'][] = [
                    'file' => $relativeFile,
                    'line' => $message['line'],
                    'message' => $message['message'],
                ];
            } else {
                $errorClassification['syntax_errors'][] = [
                    'file' => $relativeFile,
                    'line' => $message['line'],
                    'message' => $message['message'],
                ];
            }
        } else {
            $errorClassification['other_errors'][] = [
                'file' => $relativeFile,
                'line' => $message['line'],
                'message' => $message['message'],
            ];
        }
    }

    if ($syntaxErrorCount > 0) {
        $errorTypeMultiplier = 3.0; // 語法錯誤高風險
    }
    if ($tryCatchErrorCount > 0) {
        $errorTypeMultiplier = max($errorTypeMultiplier, 2.5); // try-catch 錯誤高風險
    }

    $finalRiskScore = $baseRisk * $fileTypeMultiplier * $errorTypeMultiplier;

    $fileRiskScores[] = [
        'file' => $relativeFile,
        'full_path' => $filePath,
        'error_count' => $errorCount,
        'base_risk' => $baseRisk,
        'file_type_multiplier' => $fileTypeMultiplier,
        'error_type_multiplier' => $errorTypeMultiplier,
        'final_risk_score' => $finalRiskScore,
        'syntax_errors' => $syntaxErrorCount,
        'try_catch_errors' => $tryCatchErrorCount,
    ];
}

// 按風險分數排序
usort($fileRiskScores, function ($a, $b) {
    return $b['final_risk_score'] <=> $a['final_risk_score'];
});

echo "🎯 風險驅動排序結果（前20個最高風險檔案）：\n";
echo str_repeat("=", 80) . "\n";
printf("%-4s %-50s %6s %8s %6s\n", "排名", "檔案路徑", "錯誤數", "風險分數", "語法錯誤");
echo str_repeat("-", 80) . "\n";

for ($i = 0; $i < min(20, count($fileRiskScores)); $i++) {
    $file = $fileRiskScores[$i];
    printf("%-4d %-50s %6d %8.1f %6d\n",
        $i + 1,
        strlen($file['file']) > 50 ? '...' . substr($file['file'], -47) : $file['file'],
        $file['error_count'],
        $file['final_risk_score'],
        $file['syntax_errors']
    );
}

echo "\n📋 錯誤類型分析：\n";
echo "語法錯誤: " . count($errorClassification['syntax_errors']) . " 個\n";
echo "Try-Catch 錯誤: " . count($errorClassification['try_catch_errors']) . " 個\n";
echo "陣列錯誤: " . count($errorClassification['array_errors']) . " 個\n";
echo "類別錯誤: " . count($errorClassification['class_errors']) . " 個\n";
echo "其他錯誤: " . count($errorClassification['other_errors']) . " 個\n";

echo "\n🚀 建議的批次處理策略：\n";
echo "第一批（最高風險）：處理前5個檔案，主要是核心系統檔案的語法錯誤\n";
echo "第二批（高風險）：處理接下來的5個檔案，包含重要的服務檔案\n";
echo "第三批（中等風險）：處理測試檔案和支援檔案\n";

// 輸出第一批檔案清單
echo "\n📝 第一批處理檔案（前5個最高風險）：\n";
for ($i = 0; $i < min(5, count($fileRiskScores)); $i++) {
    $file = $fileRiskScores[$i];
    echo ($i + 1) . ". {$file['file']} ({$file['error_count']} 個錯誤)\n";
}

// 將結果寫入檔案
$reportData = [
    'analysis_time' => date('Y-m-d H:i:s'),
    'total_files_with_errors' => $phpstanData['totals']['file_errors'],
    'risk_sorted_files' => $fileRiskScores,
    'error_classification' => $errorClassification,
    'top_5_files' => array_slice($fileRiskScores, 0, 5),
];

file_put_contents(__DIR__ . '/../../error-risk-analysis-report.json', json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n✅ 分析完成！報告已儲存到 error-risk-analysis-report.json\n";
