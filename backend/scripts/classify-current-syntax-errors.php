<?php

declare(strict_types=1);

/**
 * 語法錯誤分類腳本
 *
 * 此腳本用於分析當前專案中的 PHPStan 語法錯誤，並按檔案和錯誤類型進行分類
 * 幫助制定修復優先級和策略
 */

echo "🔍 開始分析當前語法錯誤...\n\n";

// 執行 PHPStan 並捕獲輸出
$command = './vendor/bin/phpstan analyse --memory-limit=1G --no-progress --error-format=json 2>/dev/null';
$output = shell_exec($command);

if (!$output) {
    echo "❌ 無法執行 PHPStan，請確認環境設定\n";
    exit(1);
}

$result = json_decode($output, true);
if (!$result) {
    echo "❌ 無法解析 PHPStan 輸出\n";
    exit(1);
}

// 語法錯誤關鍵字模式
$syntaxErrorPatterns = [
    'Syntax error' => 'syntax_general',
    'unexpected' => 'syntax_unexpected',
    'expecting' => 'syntax_expecting',
    'Cannot use try without catch' => 'try_without_catch',
    'Cannot use empty array elements' => 'empty_array_elements',
    'Syntax error, unexpected EOF' => 'unexpected_eof',
    'Syntax error, unexpected T_CATCH' => 'multiple_catch',
    'Syntax error, unexpected T_DOUBLE_ARROW' => 'double_arrow',
    'Syntax error, unexpected T_IF' => 'unexpected_if',
    'Syntax error, unexpected \'{\'' => 'unexpected_brace',
    'Syntax error, unexpected \')\'' => 'unexpected_paren',
    'Syntax error, unexpected \']\'' => 'unexpected_bracket',
];

$syntaxErrors = [];
$totalSyntaxErrors = 0;
$fileErrorCounts = [];
$errorTypeStats = [];

// 分析錯誤
if (isset($result['files'])) {
    foreach ($result['files'] as $file => $fileData) {
        if (isset($fileData['messages'])) {
            $fileSyntaxErrors = [];

            foreach ($fileData['messages'] as $message) {
                $errorMessage = $message['message'];
                $line = $message['line'];

                // 檢查是否為語法錯誤
                $isSyntaxError = false;
                $errorType = 'unknown';

                foreach ($syntaxErrorPatterns as $pattern => $type) {
                    if (strpos($errorMessage, $pattern) !== false) {
                        $isSyntaxError = true;
                        $errorType = $type;
                        break;
                    }
                }

                if ($isSyntaxError) {
                    $fileSyntaxErrors[] = [
                        'line' => $line,
                        'message' => $errorMessage,
                        'type' => $errorType
                    ];

                    $totalSyntaxErrors++;

                    // 統計錯誤類型
                    if (!isset($errorTypeStats[$errorType])) {
                        $errorTypeStats[$errorType] = 0;
                    }
                    $errorTypeStats[$errorType]++;
                }
            }

            if (!empty($fileSyntaxErrors)) {
                $syntaxErrors[$file] = $fileSyntaxErrors;
                $fileErrorCounts[$file] = count($fileSyntaxErrors);
            }
        }
    }
}

// 按錯誤數量排序檔案
arsort($fileErrorCounts);

echo "📊 語法錯誤分析結果\n";
echo "==================\n\n";

echo "🎯 總覽\n";
echo "------\n";
echo sprintf("總語法錯誤數: %d 個\n", $totalSyntaxErrors);
echo sprintf("受影響檔案數: %d 個\n", count($syntaxErrors));
echo "\n";

echo "🔥 錯誤類型分布\n";
echo "--------------\n";
arsort($errorTypeStats);
foreach ($errorTypeStats as $type => $count) {
    $percentage = round(($count / $totalSyntaxErrors) * 100, 1);
    echo sprintf("%-25s: %3d 個 (%s%%)\n", $type, $count, $percentage);
}
echo "\n";

echo "📁 檔案錯誤排行榜 (Top 20)\n";
echo "-------------------------\n";
$rank = 1;
foreach (array_slice($fileErrorCounts, 0, 20) as $file => $count) {
    $relativeFile = str_replace('/var/www/html/', '', $file);
    echo sprintf("%2d. %-60s: %2d 個錯誤\n", $rank, $relativeFile, $count);
    $rank++;
}
echo "\n";

// 推薦修復策略
echo "🎯 修復策略建議\n";
echo "--------------\n";

// 快速修復目標 (1-3個錯誤)
$quickFixes = array_filter($fileErrorCounts, function($count) { return $count <= 3; });
if (!empty($quickFixes)) {
    echo sprintf("🟢 快速修復目標 (%d 個檔案, 1-3個錯誤/檔案):\n", count($quickFixes));
    foreach (array_slice($quickFixes, 0, 10) as $file => $count) {
        $relativeFile = str_replace('/var/www/html/', '', $file);
        echo sprintf("   - %s (%d 個錯誤)\n", $relativeFile, $count);
    }
    echo "\n";
}

// 中等修復目標 (4-10個錯誤)
$mediumFixes = array_filter($fileErrorCounts, function($count) { return $count >= 4 && $count <= 10; });
if (!empty($mediumFixes)) {
    echo sprintf("🟡 中等修復目標 (%d 個檔案, 4-10個錯誤/檔案):\n", count($mediumFixes));
    foreach (array_slice($mediumFixes, 0, 10) as $file => $count) {
        $relativeFile = str_replace('/var/www/html/', '', $file);
        echo sprintf("   - %s (%d 個錯誤)\n", $relativeFile, $count);
    }
    echo "\n";
}

// 複雜修復目標 (10+個錯誤)
$complexFixes = array_filter($fileErrorCounts, function($count) { return $count > 10; });
if (!empty($complexFixes)) {
    echo sprintf("🔴 複雜修復目標 (%d 個檔案, 10+個錯誤/檔案):\n", count($complexFixes));
    foreach (array_slice($complexFixes, 0, 10) as $file => $count) {
        $relativeFile = str_replace('/var/www/html/', '', $file);
        echo sprintf("   - %s (%d 個錯誤)\n", $relativeFile, $count);
    }
    echo "\n";
}

// 生成詳細報告檔案
$reportData = [
    'timestamp' => date('c'),
    'total_syntax_errors' => $totalSyntaxErrors,
    'affected_files' => count($syntaxErrors),
    'error_type_stats' => $errorTypeStats,
    'file_error_counts' => $fileErrorCounts,
    'syntax_errors' => $syntaxErrors,
    'quick_fixes' => $quickFixes,
    'medium_fixes' => $mediumFixes,
    'complex_fixes' => $complexFixes
];

file_put_contents('syntax-errors-classification-report.json', json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "💾 詳細報告已生成: syntax-errors-classification-report.json\n";

// 推薦下一步行動
echo "\n🚀 推薦行動項目\n";
echo "-------------\n";

if (!empty($quickFixes)) {
    $quickCount = array_sum($quickFixes);
    echo sprintf("1. 優先處理 %d 個快速修復檔案，可減少 %d 個語法錯誤\n", count($quickFixes), $quickCount);
}

if (!empty($errorTypeStats)) {
    $topErrorType = array_key_first($errorTypeStats);
    $topErrorCount = $errorTypeStats[$topErrorType];
    echo sprintf("2. 建立 '%s' 批量修復腳本，可處理 %d 個類似錯誤\n", $topErrorType, $topErrorCount);
}

if (!empty($complexFixes)) {
    $complexFile = array_key_first($complexFixes);
    $relativeComplexFile = str_replace('/var/www/html/', '', $complexFile);
    echo sprintf("3. 手動重構複雜檔案: %s (%d 個錯誤)\n", $relativeComplexFile, $complexFixes[$complexFile]);
}

echo "\n✅ 語法錯誤分類完成！\n";
