<?php

declare(strict_types=1);

/**
 * 修復被批量處理破壞的檔案格式
 */

$projectRoot = dirname(__DIR__);

// 取得所有被修改的 PHP 檔案
$output = shell_exec('cd ' . escapeshellarg($projectRoot) . ' && git diff --name-only HEAD~3 --name-status | grep "\.php$"');
$modifiedFiles = array_filter(explode("\n", trim($output ?? '')));

$fixedCount = 0;
$skippedCount = 0;

foreach ($modifiedFiles as $fileInfo) {
    if (empty(trim($fileInfo))) {
        continue;
    }
    
    // 解析 git 狀態格式 (例如 "M\tpath/to/file.php")
    $parts = explode("\t", $fileInfo);
    if (count($parts) < 2) {
        continue;
    }
    
    $status = $parts[0];
    $filePath = $parts[1];
    $fullPath = $projectRoot . '/' . $filePath;
    
    // 只處理修改的檔案 (M)，跳過新增 (A) 或刪除 (D) 的檔案
    if ($status !== 'M') {
        continue;
    }
    
    if (!file_exists($fullPath)) {
        echo "檔案不存在: $fullPath\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    if ($content === false) {
        echo "無法讀取檔案: $fullPath\n";
        continue;
    }
    
    // 檢查是否是格式被破壞的檔案（第一行包含多個語句）
    $lines = explode("\n", $content);
    $firstLine = $lines[0] ?? '';
    
    // 檢查第一行是否包含過多內容（可能是格式問題）
    if (strpos($firstLine, 'declare(strict_types=1);') !== false && 
        strpos($firstLine, 'namespace') !== false) {
        
        echo "發現格式問題檔案: $filePath\n";
        
        // 嘗試從 git 歷史恢復
        $gitCommand = "cd " . escapeshellarg(dirname($projectRoot)) . " && git show HEAD~3:" . escapeshellarg($filePath) . " 2>/dev/null";
        $originalContent = shell_exec($gitCommand);
        
        if ($originalContent !== null && !empty(trim($originalContent))) {
            // 驗證原始內容是否看起來正常
            $originalLines = explode("\n", $originalContent);
            $originalFirstLine = $originalLines[0] ?? '';
            
            if (strpos($originalFirstLine, '<?php') === 0 && 
                strpos($originalFirstLine, 'declare') === false) {
                
                // 看起來是正常的格式，恢復它
                if (file_put_contents($fullPath, $originalContent) !== false) {
                    echo "✅ 已恢復: $filePath\n";
                    $fixedCount++;
                } else {
                    echo "❌ 恢復失敗: $filePath\n";
                }
            } else {
                echo "⚠️  原始版本格式也有問題，跳過: $filePath\n";
                $skippedCount++;
            }
        } else {
            echo "⚠️  無法從 git 歷史取得檔案，跳過: $filePath\n";
            $skippedCount++;
        }
    }
}

echo "\n=== 修復完成 ===\n";
echo "修復檔案數: $fixedCount\n";
echo "跳過檔案數: $skippedCount\n";

// 執行語法檢查
echo "\n正在檢查修復後的檔案語法...\n";
$syntaxErrors = [];

foreach ($modifiedFiles as $fileInfo) {
    if (empty(trim($fileInfo))) {
        continue;
    }
    
    $parts = explode("\t", $fileInfo);
    if (count($parts) < 2) {
        continue;
    }
    
    $filePath = $parts[1];
    $fullPath = $projectRoot . '/' . $filePath;
    
    if (!file_exists($fullPath) || !str_ends_with($filePath, '.php')) {
        continue;
    }
    
    $syntaxCheck = shell_exec("php -l " . escapeshellarg($fullPath) . " 2>&1");
    if (strpos($syntaxCheck, 'No syntax errors') === false) {
        $syntaxErrors[] = $filePath;
        echo "❌ 語法錯誤: $filePath\n";
        echo "   " . trim($syntaxCheck) . "\n";
    }
}

if (empty($syntaxErrors)) {
    echo "✅ 所有檔案語法檢查通過\n";
} else {
    echo "\n❌ 發現 " . count($syntaxErrors) . " 個檔案有語法錯誤\n";
}
