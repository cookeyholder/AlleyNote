<?php

declare(strict_types=1);

/**
 * 修復 StatisticsCalculationCommand 的 PHPStan 錯誤
 * 專門處理 match 語句重複案例和 mixed 類型問題
 */

$file = 'app/Domains/Statistics/Commands/StatisticsCalculationCommand.php';

if (!file_exists($file)) {
    echo "檔案不存在: $file\n";
    exit(1);
}

$content = file_get_contents($file);

// 檢查是否真的有重複的 YEARLY 案例
if (substr_count($content, 'PeriodType::YEARLY =>') > 1) {
    echo "發現重複的 PeriodType::YEARLY 案例，將移除重複項目\n";
    
    // 找到第一個 match 語句並確保只有一個 YEARLY 案例
    $pattern = '/(return match.*?\{)(.*?)(default.*?\}.*?;)/s';
    if (preg_match($pattern, $content, $matches)) {
        $matchCases = $matches[2];
        
        // 移除重複的 YEARLY 行
        $lines = explode("\n", $matchCases);
        $uniqueLines = [];
        $yearlyFound = false;
        
        foreach ($lines as $line) {
            if (strpos($line, 'PeriodType::YEARLY =>') !== false) {
                if (!$yearlyFound) {
                    $uniqueLines[] = $line;
                    $yearlyFound = true;
                }
                // 跳過重複的 YEARLY 行
            } else {
                $uniqueLines[] = $line;
            }
        }
        
        $newMatchCases = implode("\n", $uniqueLines);
        $newContent = str_replace($matchCases, $newMatchCases, $content);
        
        file_put_contents($file, $newContent);
        echo "已移除重複的 YEARLY 案例\n";
    }
}

// 修復 enum name/value 混合類型問題
$content = file_get_contents($file);

// 確保 enum 錯誤訊息使用正確的屬性
$patterns = [
    // 將 $periodType->value 改為 $periodType->name
    '/(\$periodType->)value/' => '${1}name',
    
    // 確保字串連接正確
    '/"不支援的週期類型: " \. \$periodType->name/' => '"不支援的週期類型: {$periodType->name}"',
];

foreach ($patterns as $pattern => $replacement) {
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== $content) {
        $content = $newContent;
        echo "已應用模式修復: $pattern\n";
    }
}

file_put_contents($file, $content);

echo "StatisticsCalculationCommand 修復完成\n";
