<?php

declare(strict_types=1);

/**
 * 修復檔案中的重複內容腳本
 * 
 * 這個腳本會掃描檔案並移除重複的行或段落，
 * 特別針對 ActivityLogControllerTest.php 中的重複問題
 */

$filepath = '/var/www/html/tests/Unit/Application/Controllers/Api/V1/ActivityLogControllerTest.php';

if (!file_exists($filepath)) {
    echo "⚠️  檔案不存在: {$filepath}\n";
    exit(1);
}

echo "🔍 讀取檔案內容...\n";
$content = file_get_contents($filepath);

// 分析問題：看起來整個檔案被重複了
$lines = explode("\n", $content);
$totalLines = count($lines);

echo "📊 檔案統計: {$totalLines} 行\n";

// 檢查開頭是否有重複的 <?php
$phpTagCount = 0;
foreach ($lines as $line) {
    if (trim($line) === '<?php') {
        $phpTagCount++;
    }
}

echo "📊 找到 {$phpTagCount} 個 <?php 標籤\n";

// 檢查是否每行都被重複了
$hasInlineRepetition = false;
if (count($lines) > 0) {
    $firstLine = $lines[0];
    if (strpos($firstLine, '<?php<?php') !== false) {
        $hasInlineRepetition = true;
        echo "🔧 發現行內重複內容，修復中...\n";
    }
}

if ($phpTagCount > 1 || $hasInlineRepetition) {
    echo "🔧 修復重複的 PHP 標籤和內容...\n";
    
    if ($hasInlineRepetition) {
        // 處理行內重複的情況
        $fixedContent = '';
        
        foreach ($lines as $line) {
            // 檢查行內是否有重複的模式
            if (preg_match('/^(.+)\1$/', $line, $matches)) {
                // 如果整行被重複，只保留前半部分
                $fixedContent .= $matches[1] . "\n";
            } elseif (strpos($line, '<?php<?php') !== false) {
                // 特殊處理 <?php<?php 的情況
                $fixedContent .= "<?php\n";
            } elseif (strpos($line, 'declare(strict_types=1);declare(strict_types=1);') !== false) {
                // 特殊處理 declare 的重複
                $fixedContent .= "declare(strict_types=1);\n";
            } else {
                // 處理其他可能的重複模式
                $parts = explode(';', $line);
                $uniqueParts = [];
                $lastPart = '';
                
                foreach ($parts as $part) {
                    if ($part !== $lastPart || trim($part) === '') {
                        $uniqueParts[] = $part;
                        $lastPart = $part;
                    }
                }
                
                $cleanedLine = implode(';', $uniqueParts);
                
                // 處理 namespace 重複
                if (preg_match('/^namespace ([^;]+);\1;?/', $cleanedLine, $matches)) {
                    $cleanedLine = 'namespace ' . $matches[1] . ';';
                }
                
                $fixedContent .= $cleanedLine . "\n";
            }
        }
    } else {
        // 原本的邏輯處理多個 <?php 標籤的情況
        $fixedContent = '';
        $inClass = false;
        $classFound = false;
        $braceCount = 0;
        $skipDuplicates = false;
        
        foreach ($lines as $i => $line) {
            $trimmedLine = trim($line);
            
            // 跳過重複的 <?php 和 declare
            if ($trimmedLine === '<?php' && $classFound) {
                $skipDuplicates = true;
                continue;
            }
            
            if ($skipDuplicates && $trimmedLine === 'declare(strict_types=1);') {
                continue;
            }
            
            if ($skipDuplicates && strpos($trimmedLine, 'namespace ') === 0) {
                continue;
            }
            
            if ($skipDuplicates && strpos($trimmedLine, 'use ') === 0) {
                continue;
            }
            
            // 檢查是否找到類別定義
            if (!$classFound && strpos($trimmedLine, 'class ') !== false && strpos($trimmedLine, 'extends TestCase') !== false) {
                $classFound = true;
                $inClass = true;
                $skipDuplicates = false;
            }
            
            // 如果已經找到類別且遇到重複的類別定義，跳過
            if ($classFound && $skipDuplicates && strpos($trimmedLine, 'class ') !== false) {
                break;
            }
            
            if (!$skipDuplicates) {
                $fixedContent .= $line . "\n";
            }
            
            // 計算大括號
            if ($inClass) {
                $braceCount += substr_count($line, '{') - substr_count($line, '}');
                
                // 如果大括號平衡，類別結束
                if ($braceCount <= 0 && strpos($line, '}') !== false) {
                    break;
                }
            }
        }
    }
    
    // 移除末尾多餘的換行
    $fixedContent = rtrim($fixedContent) . "\n";
    
    echo "✅ 修復後內容長度: " . strlen($fixedContent) . " 字元\n";
    echo "✅ 修復後行數: " . count(explode("\n", $fixedContent)) . " 行\n";
    
    // 備份原檔案
    $backupFile = $filepath . '.backup.' . date('Y-m-d_H-i-s');
    copy($filepath, $backupFile);
    echo "💾 備份檔案: {$backupFile}\n";
    
    // 寫入修復後的內容
    file_put_contents($filepath, $fixedContent);
    echo "✅ 修復完成！\n";
    
} else {
    echo "ℹ️  檔案看起來沒有重複內容問題\n";
}

// 驗證修復結果
echo "\n🔍 驗證修復結果...\n";
$newContent = file_get_contents($filepath);
$newLines = explode("\n", $newContent);

echo "📊 修復後統計:\n";
echo "   - 總行數: " . count($newLines) . "\n";
echo "   - 內容長度: " . strlen($newContent) . " 字元\n";

// 檢查語法
echo "\n🔍 檢查 PHP 語法...\n";
$syntaxCheck = shell_exec("php -l {$filepath} 2>&1");
if (strpos($syntaxCheck, 'No syntax errors detected') !== false) {
    echo "✅ 語法檢查通過\n";
} else {
    echo "❌ 語法錯誤:\n{$syntaxCheck}\n";
}

echo "\n🎉 修復腳本執行完成！\n";