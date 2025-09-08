<?php

declare(strict_types=1);

/**
 * 強化版批量語法修復腳本
 *
 * 這個腳本整合了所有語法修復功能，提供系統性的語法錯誤清理。
 * 專為第八輪 PHPStan 語法錯誤修復設計。
 */

echo "🚀 開始強化版批量語法修復...\n";

$projectRoot = __DIR__ . '/..';
$totalFiles = 0;
$totalFixes = 0;
$fixedFiles = [];

/**
 * 遞迴掃描目錄獲取所有 PHP 檔案
 */
function scanPhpFiles(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

/**
 * 強化版語法修復函數
 */
function comprehensiveSyntaxFix(string $filePath): array
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return ['fixes' => 0, 'types' => []];
    }

    $originalContent = $content;
    $fixes = 0;
    $fixTypes = [];

    // 1. 修復不完整的 try-catch 結構
    $pattern = '/try\s*\{\s*\}\s*$/m';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, "try {\n            // TODO: 實作邏輯\n        } catch (Exception \$e) {\n            // TODO: 處理例外\n            throw \$e;\n        }", $content);
        $fixes += substr_count($originalContent, 'try {') - substr_count($content, 'try {') + 1;
        $fixTypes[] = 'incomplete-try-catch';
    }

    // 2. 修復重複的 catch 塊
    $duplicateCatchPattern = '/(\s*}\s*catch\s*\(\s*\\\\?Exception\s+\$e\s*\)\s*\{\s*\/\/\s*TODO:\s*Handle\s+exception\s*throw\s+\$e;\s*)+/';
    $count = 0;
    $content = preg_replace($duplicateCatchPattern, '', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'duplicate-catch';
    }

    // 3. 修復錯誤的 catch 塊位置
    $misplacedCatchPattern = '/(\s*return\s+[^;]+;\s*)\s*}\s*catch\s*\(\s*\\\\?Exception\s+\$e\s*\)\s*\{\s*\/\/\s*TODO:\s*Handle\s+exception\s*throw\s+\$e;\s*}/';
    $count = 0;
    $content = preg_replace($misplacedCatchPattern, '$1', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'misplaced-catch';
    }

    // 4. 修復未閉合的方括號
    $unclosedBracketPattern = '/\$[a-zA-Z_][a-zA-Z0-9_]*\[\'[^\']*\'\s*(?!\])/';
    $count = 0;
    $content = preg_replace($unclosedBracketPattern, '$0]', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'unclosed-brackets';
    }

    // 5. 修復變數存取中的方括號問題
    $bracketAccessPattern = '/\$([a-zA-Z_][a-zA-Z0-9_]*)\[([\'"][^\'"]*)(?!\])/';
    $count = 0;
    $content = preg_replace($bracketAccessPattern, '$$$1[$2\']', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'bracket-access';
    }

    // 6. 修復 if 語句語法錯誤
    $ifStatementPattern = '/if\s*\(\s*([^)]+)\s*\)\s*\{\s*\}\s*else\s*if\s*\(/';
    $count = 0;
    $content = preg_replace($ifStatementPattern, 'if ($1) {
        // TODO: 實作邏輯
    } else if (', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'if-statement';
    }

    // 7. 修復缺少的方法結尾大括號
    $methodPattern = '/public\s+function\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\([^)]*\)\s*:\s*[a-zA-Z\\\\|_][a-zA-Z0-9\\\\|_]*\s*\{[^}]*$/m';
    if (preg_match($methodPattern, $content)) {
        $content .= "\n    }\n";
        $fixes++;
        $fixTypes[] = 'missing-method-brace';
    }

    // 8. 修復檔案結尾缺少的類別大括號
    if (!preg_match('/}\s*$/', trim($content)) && preg_match('/class\s+\w+/', $content)) {
        $content = rtrim($content) . "\n}\n";
        $fixes++;
        $fixTypes[] = 'missing-class-brace';
    }

    // 9. 修復 OpenAPI 屬性語法錯誤
    $openApiPattern = '/(\w+)\s*=>\s*([\'"][^\'"]*[\'"])/';
    $count = 0;
    $content = preg_replace($openApiPattern, '$1: $2', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'openapi-attributes';
    }

    // 10. 修復字串插值錯誤
    $stringInterpolationPattern = '/sprintf\s*\(\s*[\'"]([^\'"]*){\s*%s\s*}[\'"],\s*[\'"][^\'"]*[\'"]\s*\)/';
    $count = 0;
    $content = preg_replace($stringInterpolationPattern, "sprintf('$1%s', \$variable)", $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'string-interpolation';
    }

    // 11. 修復變數名截斷問題
    $truncatedVarPattern = '/\$([a-zA-Z_][a-zA-Z0-9_]*)[\'"][;\)]/';
    $count = 0;
    $content = preg_replace($truncatedVarPattern, '$$$1', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'truncated-variables';
    }

    // 12. 修復意外的 EOF 錯誤
    if (!preg_match('/\?>\s*$/', $content) && !preg_match('/}\s*$/', trim($content))) {
        $lines = explode("\n", $content);
        $lastLine = trim(end($lines));
        if (!empty($lastLine) && !preg_match('/[;}]\s*$/', $lastLine)) {
            $content = rtrim($content) . ";\n";
            $fixes++;
            $fixTypes[] = 'unexpected-eof';
        }
    }

    // 13. 修復多重存取修飾符
    $multipleModifiersPattern = '/(public|private|protected)\s+(public|private|protected)/';
    $count = 0;
    $content = preg_replace($multipleModifiersPattern, '$1', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'multiple-modifiers';
    }

    // 14. 修復不完整的陣列語法
    $incompleteArrayPattern = '/\[\s*,\s*\]/';
    $count = 0;
    $content = preg_replace($incompleteArrayPattern, '[]', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'incomplete-array';
    }

    // 15. 修復錯誤的雙箭頭語法
    $doubleArrowPattern = '/=>\s*:/';
    $count = 0;
    $content = preg_replace($doubleArrowPattern, ':', $content, -1, $count);
    if ($count > 0) {
        $fixes += $count;
        $fixTypes[] = 'double-arrow';
    }

    // 如果有修改，寫回檔案
    if ($content !== $originalContent && $fixes > 0) {
        file_put_contents($filePath, $content);
    }

    return ['fixes' => $fixes, 'types' => $fixTypes];
}

/**
 * 檢查檔案語法
 */
function checkSyntax(string $filePath): bool
{
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);
    return $returnCode === 0;
}

// 掃描所有 PHP 檔案
$directories = [
    $projectRoot . '/app',
    $projectRoot . '/tests',
];

$allFiles = [];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $allFiles = array_merge($allFiles, scanPhpFiles($dir));
    }
}

$totalFiles = count($allFiles);
$progressCounter = 0;

echo "📁 發現 {$totalFiles} 個 PHP 檔案\n";
echo "🔧 開始修復...\n\n";

// 按優先級處理檔案（先處理控制器和重要檔案）
$priorityFiles = [];
$regularFiles = [];

foreach ($allFiles as $file) {
    $fileName = basename($file);
    if (strpos($file, 'Controller') !== false ||
        strpos($fileName, 'Service') !== false ||
        strpos($fileName, 'Repository') !== false ||
        strpos($fileName, 'Application.php') !== false) {
        $priorityFiles[] = $file;
    } else {
        $regularFiles[] = $file;
    }
}

$processFiles = array_merge($priorityFiles, $regularFiles);

// 處理每個檔案
foreach ($processFiles as $file) {
    $progressCounter++;
    $fileName = basename($file);

    // 顯示進度
    if ($progressCounter % 50 === 0 || in_array($file, $priorityFiles)) {
        echo "🔍 處理中... ({$progressCounter}/{$totalFiles}) {$fileName}\n";
    }

    $result = comprehensiveSyntaxFix($file);

    if ($result['fixes'] > 0) {
        $totalFixes += $result['fixes'];
        $fixedFiles[] = [
            'file' => $fileName,
            'path' => $file,
            'fixes' => $result['fixes'],
            'types' => $result['types']
        ];

        echo "  ✅ 修復 {$result['fixes']} 個問題在 {$fileName}\n";
        echo "  📝 修復類型: " . implode(', ', $result['types']) . "\n";

        // 檢查語法
        if (!checkSyntax($file)) {
            echo "  ⚠️  語法檢查失敗: {$fileName}\n";
        }
    }
}

echo "\n📊 強化版語法修復摘要:\n";
echo "==================================================\n";
echo "掃描的檔案數: {$totalFiles}\n";
echo "修復的檔案數: " . count($fixedFiles) . "\n";
echo "總修復數量: {$totalFixes}\n";

if ($totalFixes > 0) {
    echo "\n📁 修復的檔案詳情:\n";
    echo "==================================================\n";

    $typeStats = [];
    foreach ($fixedFiles as $fileInfo) {
        echo "  📄 {$fileInfo['file']}: {$fileInfo['fixes']} 個修復\n";
        echo "     類型: " . implode(', ', $fileInfo['types']) . "\n";

        foreach ($fileInfo['types'] as $type) {
            $typeStats[$type] = ($typeStats[$type] ?? 0) + 1;
        }
    }

    echo "\n🎯 修復類型統計:\n";
    echo "==================================================\n";
    arsort($typeStats);
    foreach ($typeStats as $type => $count) {
        echo "  - {$type}: {$count} 次\n";
    }

    echo "\n💡 修復完成後建議:\n";
    echo "  1. 運行 PHPStan 檢查修復效果\n";
    echo "  2. 運行測試確保功能正常\n";
    echo "  3. 檢查是否還有其他語法錯誤\n";
    echo "  4. 提交修復變更\n";

    echo "\n📈 預期改善:\n";
    echo "  - 大幅減少語法錯誤總數\n";
    echo "  - 恢復 PHPStan 完整分析能力\n";
    echo "  - 改善程式碼結構和可讀性\n";
    echo "  - 為類型錯誤修復做好準備\n";

    echo "\n🔍 建議下一步檢查:\n";
    echo "  php scripts/simple-syntax-analyzer.php\n";
    echo "  docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G\n";
}

echo "\n✅ 強化版語法修復完成！\n";
