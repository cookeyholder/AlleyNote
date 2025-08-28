<?php

/**
 * SQL 時間函式修正腳本 - 統一使用 PHP DateTime 方法.
 *
 * 此腳本用於將 Repository 檔案中的 SQL 時間函式（如 NOW()、DATE_SUB()）
 * 修正為跨資料庫相容的 PHP DateTime 方法。
 *
 * 修正原理：
 * - 將 SQL 中的 NOW() 替換為參數化的 :current_time
 * - 將 DATE_SUB() 替換為參數化的 :cutoff_date
 * - 在 PHP 程式碼中使用 new \DateTime() 計算時間
 * - 使用 ->format('Y-m-d H:i:s') 格式化為 SQL 相容格式
 *
 * 優勢：
 * - 跨資料庫相容（MySQL, SQLite, PostgreSQL 等）
 * - 時間邏輯可測試、可控制
 * - 符合 DDD 設計原則
 * - 參數化查詢，安全防 SQL 注入
 */

declare(strict_types=1);

function fixSqlTimeFunction(string $filePath): void
{
    if (!file_exists($filePath)) {
        echo "❌ 錯誤：找不到檔案 {$filePath}\n";

        return;
    }

    echo "🔍 正在分析檔案：{$filePath}\n";

    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "❌ 錯誤：無法讀取檔案 {$filePath}\n";

        return;
    }

    // 檢查是否已經使用 PHP DateTime 方法
    if (strpos($content, 'new \DateTime()') !== false || strpos($content, 'new DateTime()') !== false) {
        echo "✅ 檔案已經使用 PHP DateTime 方法，無需修正\n";

        return;
    }

    // 定義需要替換的 SQL 時間函式模式
    $sqlTimePatterns = [
        // MySQL NOW() 函式
        '/\bNOW\(\)/' => ':current_time',

        // MySQL DATE_SUB 函式
        '/DATE_SUB\(\s*NOW\(\)\s*,\s*INTERVAL\s+:?\w+\s+DAY\s*\)/' => ':cutoff_date',

        // SQLite datetime('now') 函式
        '/datetime\(\'now\'\)/' => ':current_time',
    ];

    $totalReplacements = 0;
    foreach ($sqlTimePatterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== null && $newContent !== $content) {
            $count = preg_match_all($pattern, $content);
            if ($count !== false) {
                $totalReplacements += $count;
            }
            $content = $newContent;
            echo "  ├─ 替換了 {$count} 個 '{$pattern}' → '{$replacement}'\n";
        }
    }

    if ($totalReplacements === 0) {
        echo "ℹ️  未發現需要替換的 SQL 時間函式\n";

        return;
    }

    // 在使用時間參數的方法中加入 PHP DateTime 處理邏輯
    $content = addPhpDateTimeHandling($content);

    // 寫回檔案
    file_put_contents($filePath, $content);

    echo "✅ 修正完成！\n";
    echo "  ├─ 總共替換了 {$totalReplacements} 個 SQL 時間函式\n";
    echo "  ├─ 已加入對應的 PHP DateTime 處理邏輯\n";
    echo "  └─ 檔案已更新為跨資料庫相容格式\n";
}

/**
 * 在使用時間參數的方法中加入 PHP DateTime 處理邏輯.
 */
function addPhpDateTimeHandling(string $content): string
{
    // 尋找使用 :current_time 或 :cutoff_date 參數的方法
    $pattern = '/(public\s+function\s+\w+[^{]*\{)((?:[^{}]++|\{(?:[^{}]++|\{[^{}]*+\})*+\})*+)(\})/s';

    $result = preg_replace_callback($pattern, function ($matches) {
        $methodStart = $matches[1];
        $methodBody = $matches[2];
        $methodEnd = $matches[3];

        $needsCurrentTime = strpos($methodBody, ':current_time') !== false;
        $needsCutoffDate = strpos($methodBody, ':cutoff_date') !== false;

        if (!$needsCurrentTime && !$needsCutoffDate) {
            return $matches[0]; // 不需要修改
        }

        // 檢查是否已經有 DateTime 處理
        if (strpos($methodBody, 'new \DateTime()') !== false || strpos($methodBody, 'new DateTime()') !== false) {
            return $matches[0]; // 已經有處理邏輯
        }

        $dateTimeCode = '';

        if ($needsCurrentTime) {
            $dateTimeCode .= "\n        \$currentTime = new \\DateTime();\n";
        }

        if ($needsCutoffDate) {
            $dateTimeCode .= "        \$cutoffDate = new \\DateTime();\n";
            $dateTimeCode .= "        \$cutoffDate->modify('-30 days'); // 請根據業務需求調整天數\n";
        }

        // 在 try 塊之後插入，如果沒有 try 塊就在方法開始插入
        if (preg_match('/(\s*)(try\s*\{)/', $methodBody, $tryMatches)) {
            $methodBody = str_replace($tryMatches[0], $tryMatches[1] . $tryMatches[2] . $dateTimeCode, $methodBody);
        } else {
            $methodBody = $dateTimeCode . $methodBody;
        }

        return $methodStart . $methodBody . $methodEnd;
    }, $content);

    return $result ?? $content;
}

// 主程式執行
$repositoryFile = '/var/www/html/app/Infrastructure/Auth/Repositories/TokenBlacklistRepository.php';

echo "🚀 開始執行 SQL 時間函式修正腳本\n";
echo "═══════════════════════════════════════\n";

fixSqlTimeFunction($repositoryFile);

echo "\n🎯 修正程序完成！\n";
echo "\n💡 提醒：\n";
echo "  • 請檢查修正結果是否符合預期\n";
echo "  • 請根據業務需求調整 cutoff_date 的天數計算\n";
echo "  • 請執行測試確認功能正常\n";
echo "  • 建議使用 PHP CS Fixer 格式化程式碼\n";
