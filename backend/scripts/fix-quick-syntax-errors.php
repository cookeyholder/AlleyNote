<?php

declare(strict_types=1);

/**
 * 快速語法錯誤批量修復腳本
 *
 * 此腳本用於批量修復常見的語法錯誤模式，專注於快速修復目標檔案
 * 包括：括號不匹配、箭頭語法、try-catch結構等問題
 */

echo "🔧 開始批量修復快速語法錯誤...\n\n";

// 定義常見的語法錯誤修復模式
$fixPatterns = [
    // 1. 缺少右括號的陣列存取修復
    [
        'pattern' => '/\$result\[\'([^\']+)\'\s*$/',
        'replacement' => '$result[\'$1\']',
        'description' => '修復缺少右括號的陣列存取'
    ],

    // 2. 條件判斷中缺少右括號
    [
        'pattern' => '/if\s*\(\s*isset\(\$([^)]+)\s*\{\s*$/',
        'replacement' => 'if (isset($1)) {',
        'description' => '修復 isset 條件判斷缺少右括號'
    ],

    // 3. 字串插值中的錯誤括號
    [
        'pattern' => '/strpos\(\$([^,]+),\s*\'([^\']+)\'\]\s*(!==\s*false)/',
        'replacement' => 'strpos($1, \'$2\') $3',
        'description' => '修復 strpos 函數的括號錯誤'
    ],

    // 4. 陣列元素中的語法錯誤
    [
        'pattern' => '/\(string\]\s*\$value/',
        'replacement' => '(string) $value',
        'description' => '修復類型轉換語法錯誤'
    ],

    // 5. 條件判斷中的語法錯誤
    [
        'pattern' => '/isset\(\$([^)]+)\s*&&\s*is_array\(\$([^)]+)\s*\{/',
        'replacement' => 'isset($1) && is_array($2)) {',
        'description' => '修復複合條件判斷的括號錯誤'
    ],

    // 6. 空的 try 塊修復
    [
        'pattern' => '/try\s*\{\s*\/\*\s*empty\s*\*\/\s*\}/',
        'replacement' => 'try {',
        'description' => '修復空的 try 塊'
    ],

    // 7. 不完整的 catch 結構
    [
        'pattern' => '/\}\s*$\n\s*\/\//',
        'replacement' => "} catch (Exception \$e) {\n            error_log('Operation failed: ' . \$e->getMessage());\n            throw \$e;\n        }\n\n        //",
        'description' => '添加缺失的 catch 塊'
    ],

    // 8. EOF 錯誤修復 - 多餘的右括號
    [
        'pattern' => '/\}\s*\}\s*$/m',
        'replacement' => '}',
        'description' => '移除多餘的右括號'
    ]
];

// 獲取需要修復的快速目標檔案列表
$quickFixFiles = [
    'app/Application/Middleware/RateLimitMiddleware.php',
    'app/Domains/Auth/DTOs/LoginRequestDTO.php',
    'app/Domains/Auth/DTOs/RefreshRequestDTO.php',
    'app/Domains/Auth/Services/Advanced/PwnedPasswordService.php',
    'app/Domains/Post/Services/RichTextProcessorService.php',
    'app/Domains/Security/Services/Advanced/SecurityTestService.php',
    'app/Domains/Security/Services/Core/XssProtectionService.php',
    'app/Domains/Security/Services/Error/ErrorHandlerService.php',
    'app/Domains/Security/Services/IpService.php',
    'app/Infrastructure/Auth/Repositories/UserRepository.php',
    'app/Domains/Auth/Services/AuthService.php',
    'app/Domains/Auth/Services/AuthorizationService.php',
];

$totalFilesProcessed = 0;
$totalFixesApplied = 0;
$fileResults = [];

foreach ($quickFixFiles as $relativeFile) {
    $filePath = $relativeFile;

    if (!file_exists($filePath)) {
        echo "⚠️  檔案不存在: {$relativeFile}\n";
        continue;
    }

    echo "📄 處理檔案: {$relativeFile}\n";

    $originalContent = file_get_contents($filePath);
    if ($originalContent === false) {
        echo "❌ 無法讀取檔案: {$relativeFile}\n";
        continue;
    }

    $modifiedContent = $originalContent;
    $fixesInFile = 0;
    $appliedFixes = [];

    // 應用修復模式
    foreach ($fixPatterns as $pattern) {
        $newContent = preg_replace($pattern['pattern'], $pattern['replacement'], $modifiedContent);

        if ($newContent !== null && $newContent !== $modifiedContent) {
            $fixesInFile++;
            $totalFixesApplied++;
            $appliedFixes[] = $pattern['description'];
            $modifiedContent = $newContent;
        }
    }

    // 特殊修復：手動處理常見的語法錯誤

    // 修復：if (!$result['allowed') 缺少右括號
    if (strpos($modifiedContent, "if (!$") !== false) {
        $modifiedContent = preg_replace(
            '/if\s*\(\s*!\s*\$result\[\'([^\']+)\'\s*\)\s*\{/',
            'if (!$result[\'$1\']) {',
            $modifiedContent
        );
    }

    // 修復：strpos 函數的括號錯誤
    $modifiedContent = preg_replace(
        '/strpos\(\$([^,]+),\s*\'([^\']+)\'\]\s*(.*?)\s*===\s*0/',
        'strpos($1, \'$2\') $3 === 0',
        $modifiedContent
    );

    // 修復：array_map 回調函數的語法錯誤
    $modifiedContent = preg_replace(
        '/\(string\]\s*\$/',
        '(string) $',
        $modifiedContent
    );

    // 修復：複合條件判斷
    $modifiedContent = preg_replace(
        '/if\s*\(\s*isset\(\$([^)]+)\)\s*&&\s*is_array\(\$([^)]+)\)\s*\{/',
        'if (isset($1) && is_array($2)) {',
        $modifiedContent
    );

    // 如果有修改，寫回檔案
    if ($modifiedContent !== $originalContent) {
        if (file_put_contents($filePath, $modifiedContent) !== false) {
            echo "✅ 修復完成，應用了 {$fixesInFile} 項修復\n";
            foreach ($appliedFixes as $fix) {
                echo "   - {$fix}\n";
            }
            $fileResults[$relativeFile] = [
                'fixes' => $fixesInFile,
                'applied_fixes' => $appliedFixes,
                'status' => 'success'
            ];
        } else {
            echo "❌ 無法寫入檔案: {$relativeFile}\n";
            $fileResults[$relativeFile] = [
                'fixes' => 0,
                'status' => 'write_error'
            ];
        }
    } else {
        echo "ℹ️  無需修復\n";
        $fileResults[$relativeFile] = [
            'fixes' => 0,
            'status' => 'no_changes'
        ];
    }

    $totalFilesProcessed++;
    echo "\n";
}

// 生成修復報告
echo "📊 批量修復完成報告\n";
echo "==================\n\n";
echo sprintf("處理檔案數: %d\n", $totalFilesProcessed);
echo sprintf("總修復數量: %d\n", $totalFixesApplied);
echo "\n";

echo "📋 詳細結果:\n";
echo "----------\n";
foreach ($fileResults as $file => $result) {
    $status = match($result['status']) {
        'success' => '✅ 成功',
        'no_changes' => 'ℹ️  無變更',
        'write_error' => '❌ 寫入錯誤',
        default => '❓ 未知'
    };

    echo sprintf("%-60s: %s (%d 項修復)\n", $file, $status, $result['fixes']);
}

// 儲存詳細報告
$reportData = [
    'timestamp' => date('c'),
    'total_files_processed' => $totalFilesProcessed,
    'total_fixes_applied' => $totalFixesApplied,
    'file_results' => $fileResults,
    'fix_patterns_used' => $fixPatterns
];

file_put_contents('quick-syntax-fixes-report.json', json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n💾 詳細報告已生成: quick-syntax-fixes-report.json\n";

// 建議後續步驟
echo "\n🎯 建議後續步驟:\n";
echo "-------------\n";
echo "1. 執行 PHPStan 驗證修復效果\n";
echo "2. 運行測試確保功能正常\n";
echo "3. 檢查剩餘的語法錯誤\n";
echo "4. 繼續處理中等複雜度的修復目標\n";

if ($totalFixesApplied > 0) {
    echo "\n🚀 執行驗證指令:\n";
    echo "docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress\n";
}

echo "\n✅ 快速語法錯誤批量修復完成！\n";
