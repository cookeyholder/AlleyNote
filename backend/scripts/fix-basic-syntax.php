<?php

declare(strict_types=1);

/**
 * 簡單的語法錯誤修復腳本 - 只執行基本的 PHP 語法檢查和修復
 */

function fixBasicSyntax(): void
{
    echo "開始修復基本語法錯誤...\n";

    $problemFiles = [
        'app/Application/Controllers/Api/V1/ActivityLogController.php',
        'app/Application/Controllers/Api/V1/AttachmentController.php',
        'app/Application/Controllers/Api/V1/AuthController.php',
        'app/Application/Controllers/Api/V1/IpController.php',
        'app/Application/Controllers/Api/V1/PostController.php'
    ];

    foreach ($problemFiles as $file) {
        $fullPath = "/var/www/html/{$file}";
        if (file_exists($fullPath)) {
            echo "修復檔案: {$file}\n";

            $content = file_get_contents($fullPath);

            // 移除孤立的 catch 語句（沒有對應 try 的）
            $lines = explode("\n", $content);
            $cleanedLines = [];
            $skip = false;

            for ($i = 0; $i < count($lines); $i++) {
                $line = $lines[$i];

                // 檢查是否是孤立的 catch
                if (preg_match('/^\s*catch\s*\(/', $line)) {
                    // 檢查前一行是否是 try 的結尾
                    $prevLine = $i > 0 ? trim($lines[$i-1]) : '';
                    if ($prevLine !== '}' && !preg_match('/try\s*\{/', $prevLine)) {
                        // 這是一個孤立的 catch，跳過它和它的塊
                        echo "  移除孤立的 catch 在第 " . ($i + 1) . " 行\n";
                        $skip = true;
                        continue;
                    }
                }

                if ($skip) {
                    // 如果我們在跳過 catch 塊，查找塊的結束
                    if (preg_match('/^\s*\}\s*$/', $line)) {
                        $skip = false;
                    }
                    continue;
                }

                $cleanedLines[] = $line;
            }

            $newContent = implode("\n", $cleanedLines);

            // 只有在內容有變化時才寫入
            if ($newContent !== $content) {
                file_put_contents($fullPath, $newContent);
                echo "  已修復 {$file}\n";
            }
        }
    }

    echo "基本語法修復完成！\n";
}

fixBasicSyntax();
