<?php

declare(strict_types=1);

/**
 * 謹慎修復 StatisticsCalculationCommand mixed 型別問題
 */

$file = '/var/www/html/app/Domains/Statistics/Commands/StatisticsCalculationCommand.php';

if (!file_exists($file)) {
    echo "檔案不存在: $file\n";
    exit(1);
}

$content = file_get_contents($file);

echo "開始修復 StatisticsCalculationCommand...\n";

// 1. 修復 enum value 的型別轉換
$content = str_replace(
    'Part $periodType->value (mixed) of encapsed string',
    'Part (string) $periodType->value of encapsed string',
    $content
);

// 實際進行替換
$content = preg_replace(
    '/\$periodType->value/',
    '(string) $periodType->value',
    $content
);

// 2. 修復重複的 match case
$duplicatePattern = '/PeriodType::YEARLY\s*=>\s*[\'"]yearly[\'"],\s*PeriodType::YEARLY\s*=>\s*[\'"]yearly[\'"],?/';
$replacement = 'PeriodType::YEARLY => \'yearly\',';
$content = preg_replace($duplicatePattern, $replacement, $content);

// 3. 修復特定的陣列存取問題 - 只針對明確的錯誤
$arrayAccessFixes = [
    // 修復輸出格式中的 mixed 型別問題
    'echo "處理週期: {$period} (共 {count(is_array($periods) ? $periods : [])} 個)";' =>
    'echo "處理週期: " . (is_string($period) ? $period : "未知") . " (共 " . count(is_array($periods) ? $periods : []) . " 個)";',

    // 修復統計結果輸出
    'echo "統計處理完成 - 總週期: {is_numeric($result[\'total_periods\'] ?? null) ? $result[\'total_periods\'] : 0}, 成功: {is_numeric($result[\'success_count\'] ?? null) ? $result[\'success_count\'] : 0}, 失敗: {is_numeric($result[\'failure_count\'] ?? null) ? $result[\'failure_count\'] : 0}";' =>
    'echo "統計處理完成 - 總週期: " . (is_numeric($result[\'total_periods\'] ?? null) ? $result[\'total_periods\'] : 0) . ", 成功: " . (is_numeric($result[\'success_count\'] ?? null) ? $result[\'success_count\'] : 0) . ", 失敗: " . (is_numeric($result[\'failure_count\'] ?? null) ? $result[\'failure_count\'] : 0);',
];

foreach ($arrayAccessFixes as $old => $new) {
    $content = str_replace($old, $new, $content);
}

// 4. 修復數字格式化問題
$content = preg_replace(
    '/number_format\(\$([a-zA-Z_][a-zA-Z0-9_]*)\)/',
    'number_format(is_numeric($\\1) ? (float) $\\1 : 0.0)',
    $content
);

// 5. 檢查 foreach 但不進行自動替換（避免語法錯誤）
if (preg_match('/foreach\s*\(\s*\$[a-zA-Z_][a-zA-Z0-9_]*\s*as/', $content)) {
    echo "注意: 發現 foreach 語句，需要手動檢查型別安全\n";
}

file_put_contents($file, $content);

echo "✅ StatisticsCalculationCommand 基本修復完成\n";
