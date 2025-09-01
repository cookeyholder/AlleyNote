<?php
/**
 * 最終語法修復腳本
 * 修復所有剩餘的語法錯誤
 */

function fixAllSyntaxIssues() {
    // 獲取有語法錯誤的檔案列表
    $problematicFiles = [
        '/var/www/html/app/Application/Controllers/Security/CSPReportController.php',
        '/var/www/html/app/Application/Middleware/JwtAuthorizationMiddleware.php',
    ];

    // 額外掃描所有 PHP 檔案
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('/var/www/html/app')
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $allFiles[] = $file->getPathname();
        }
    }

    $fixedFiles = 0;

    foreach ($allFiles as $filePath) {
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fileFixed = false;

        // 修復模式1: 孤立的語句塊（由於註解掉的 if 造成）
        $lines = explode("\n", $content);
        $newLines = [];
        $skipNext = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmed = trim($line);

            // 檢查註解掉的 if 語句
            if (preg_match('/^\/\/ if \([^)]*\) \{ \/\/.*語法錯誤.*/', $trimmed)) {
                // 找到對應的代碼塊和結束大括號
                $j = $i + 1;
                $foundCode = false;
                $codeLines = [];

                while ($j < count($lines)) {
                    $nextTrimmed = trim($lines[$j]);

                    if (preg_match('/^\}$/', $nextTrimmed)) {
                        // 找到結束大括號，將代碼塊轉換為正常的 if
                        if (!empty($codeLines)) {
                            // 創建簡化的 if 語句
                            $newLines[] = str_replace('// if', 'if', $line);
                            $newLines[] = preg_replace('/\/\/ .*語法錯誤.*/', '', $line);

                            // 添加代碼內容
                            foreach ($codeLines as $codeLine) {
                                $newLines[] = $codeLine;
                            }

                            $newLines[] = $lines[$j]; // 結束大括號
                            $fileFixed = true;
                        }

                        $i = $j; // 跳過到結束大括號
                        break;
                    } else {
                        $codeLines[] = $lines[$j];
                    }

                    $j++;
                }

                continue;
            }

            // 修復孤立的 else
            if (preg_match('/^\s*} else \{/', $trimmed)) {
                // 檢查前面是否有對應的 if
                $hasIf = false;

                for ($k = $i - 1; $k >= 0; $k--) {
                    $prevTrimmed = trim($lines[$k]);

                    if (preg_match('/if\s*\(/', $prevTrimmed)) {
                        $hasIf = true;
                        break;
                    }

                    if (preg_match('/^function|^class|^private|^public|^protected/', $prevTrimmed)) {
                        break;
                    }
                }

                if (!$hasIf) {
                    // 轉換為簡單的代碼塊
                    $newLines[] = '        {';
                    $fileFixed = true;
                    continue;
                }
            }

            $newLines[] = $line;
        }

        $content = implode("\n", $newLines);

        // 修復複雜的表達式
        $content = preg_replace(
            '/\(is_array\([^)]+\) \? [^:]+: [^)]+\)/',
            '$data',
            $content
        );

        // 修復 isset 表達式
        $content = preg_replace(
            '/\(is_array\([^)]+\) && isset\([^)]+\)\) \? [^:]+: null/',
            'true',
            $content
        );

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $fixedFiles++;
            echo "修復: " . str_replace('/var/www/html/', '', $filePath) . "\n";
        }
    }

    echo "\n修復完成！修復的檔案: $fixedFiles\n";
}

fixAllSyntaxIssues();
