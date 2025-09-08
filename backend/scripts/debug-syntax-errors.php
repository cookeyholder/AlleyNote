<?php

declare(strict_types=1);

/**
 * 調試語法錯誤檢測腳本
 *
 * 簡化版本用來測試 PHPStan 輸出解析
 */

echo "🔍 開始調試 PHPStan 語法錯誤解析...\n";

// 執行 PHPStan 並獲取輸出
$command = './vendor/bin/phpstan analyse --memory-limit=1G 2>&1';
echo "執行命令: {$command}\n";

$output = shell_exec($command);

if (!$output) {
    echo "❌ 無法執行 PHPStan 分析\n";
    exit(1);
}

echo "✅ PHPStan 輸出長度: " . strlen($output) . " 字符\n";

$lines = explode("\n", $output);
echo "📝 總行數: " . count($lines) . "\n\n";

$syntaxErrorCount = 0;
$totalErrorCount = 0;
$currentFile = null;
$syntaxErrors = [];

echo "🔍 逐行分析 PHPStan 輸出:\n";
echo str_repeat("=", 80) . "\n";

foreach ($lines as $index => $line) {
    $trimmed = trim($line);

    // 跳過空行和進度條
    if (empty($trimmed) || preg_match('/^\d+\/\d+\s*\[/', $trimmed)) {
        continue;
    }

    // 檢查檔案標題行
    if (preg_match('/^\s*Line\s+(.+\.php)\s*$/', $trimmed, $matches)) {
        $currentFile = $matches[1];
        echo "📁 檔案: {$currentFile}\n";
        continue;
    }

    // 檢查錯誤行
    if (preg_match('/^\s*(\d+)\s+(.+)$/', $trimmed, $matches)) {
        $lineNumber = (int) $matches[1];
        $errorMessage = trim($matches[2]);

        // 檢查是否為語法錯誤
        $isSyntax = false;
        $syntaxKeywords = [
            'Syntax error',
            'unexpected T_',
            'unexpected \'',
            'unexpected "',
            'Cannot use try without',
            'Parse error'
        ];

        foreach ($syntaxKeywords as $keyword) {
            if (stripos($errorMessage, $keyword) !== false) {
                $isSyntax = true;
                break;
            }
        }

        if ($isSyntax && $currentFile) {
            $syntaxErrorCount++;
            $syntaxErrors[] = [
                'file' => $currentFile,
                'line' => $lineNumber,
                'message' => $errorMessage
            ];
            echo "  ⚠️ 語法錯誤 #{$syntaxErrorCount} - 第 {$lineNumber} 行: " . substr($errorMessage, 0, 50) . "...\n";
        }
    }

    // 檢查總錯誤數
    if (preg_match('/\[ERROR\]\s*Found (\d+) errors?/', $trimmed, $matches)) {
        $totalErrorCount = (int) $matches[1];
        echo "📊 總錯誤數: {$totalErrorCount}\n";
    }
}

echo str_repeat("=", 80) . "\n";
echo "📊 分析結果:\n";
echo "- 語法錯誤數: {$syntaxErrorCount}\n";
echo "- PHPStan 總錯誤數: {$totalErrorCount}\n";

if ($syntaxErrorCount > 0) {
    echo "\n🔥 語法錯誤分布:\n";

    $fileGroups = [];
    foreach ($syntaxErrors as $error) {
        $fileGroups[$error['file']][] = $error;
    }

    foreach ($fileGroups as $file => $errors) {
        echo "📄 {$file}: " . count($errors) . " 個錯誤\n";

        // 顯示前 3 個錯誤作為範例
        $shown = 0;
        foreach ($errors as $error) {
            if ($shown >= 3) {
                echo "   ... 還有 " . (count($errors) - $shown) . " 個錯誤\n";
                break;
            }
            echo "   第 {$error['line']} 行: {$error['message']}\n";
            $shown++;
        }
        echo "\n";
    }

    // 生成簡化報告
    $reportContent = "# 語法錯誤調試報告\n\n";
    $reportContent .= "生成時間: " . date('Y-m-d H:i:s') . "\n";
    $reportContent .= "語法錯誤數: {$syntaxErrorCount}\n";
    $reportContent .= "總錯誤數: {$totalErrorCount}\n\n";

    $reportContent .= "## 錯誤分布\n\n";
    foreach ($fileGroups as $file => $errors) {
        $reportContent .= "### {$file} (" . count($errors) . " 個錯誤)\n\n";
        foreach ($errors as $error) {
            $reportContent .= "- 第 {$error['line']} 行: {$error['message']}\n";
        }
        $reportContent .= "\n";
    }

    file_put_contents(__DIR__ . '/../debug-syntax-errors-report.md', $reportContent);
    echo "📄 詳細報告已保存到: debug-syntax-errors-report.md\n";
} else {
    echo "✅ 沒有檢測到語法錯誤，或解析有問題\n";

    // 保存原始輸出用於調試
    file_put_contents(__DIR__ . '/../phpstan-raw-output.txt', $output);
    echo "📄 原始 PHPStan 輸出已保存到: phpstan-raw-output.txt\n";

    // 顯示前幾行輸出來調試
    echo "\n🔍 PHPStan 輸出前 20 行:\n";
    $firstLines = array_slice($lines, 0, 20);
    foreach ($firstLines as $i => $line) {
        echo sprintf("%2d: %s\n", $i + 1, $line);
    }
}

echo "\n✅ 調試完成！\n";
