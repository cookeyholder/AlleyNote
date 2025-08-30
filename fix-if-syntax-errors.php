<?php
/**
 * 修復控制器中的語法錯誤
 * 特別針對註解掉的 if 語句和孤立的 catch 區塊
 */

$files = [
    '/var/www/html/app/Application/Controllers/Api/V1/AuthController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/PostController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/IpController.php',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;

    echo "修復檔案: " . basename($file) . "\n";

    // 修復模式 1: 移除註解掉的 if 語句後的孤立語句塊
    $content = preg_replace_callback(
        '/\/\/ if \([^)]*\) \{ \/\/ isset[^\n]*\n(\s+)([^\n]+)\n(\s+)\}/',
        function ($matches) {
            // 保留語句但移除 if 結構
            return $matches[1] . $matches[2];
        },
        $content
    );

    // 修復模式 2: 修復破損的 if 條件
    $content = preg_replace(
        '/\/\/ if \(!isset\([^)]+\)\) \{ \/\/ isset[^\n]*\n\s+([^\n]+)\n\s+return[^\n]+\n\s+\}\n\s+\}/',
        '',
        $content
    );

    // 修復模式 3: 移除孤立的 catch 區塊中的註解語句
    $lines = explode("\n", $content);
    $newLines = [];
    $skipNext = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        // 檢查是否為註解掉的 if isset 語句
        if (preg_match('/\/\/ if \(!?isset/', $line)) {
            // 找到對應的結束大括號
            $j = $i + 1;
            $found = false;

            while ($j < count($lines) && !$found) {
                if (preg_match('/^\s+\}$/', $lines[$j])) {
                    $found = true;
                    // 跳過 if 語句但保留其中的內容
                    $i++; // 跳過 if 行

                    while ($i < $j) {
                        if (!preg_match('/^\s+return/', $lines[$i])) {
                            $newLines[] = $lines[$i];
                        }
                        $i++;
                    }

                    $i = $j; // 跳過結束大括號
                    break;
                }
                $j++;
            }

            if (!$found) {
                $newLines[] = $line;
            }
        } else {
            $newLines[] = $line;
        }
    }

    $content = implode("\n", $newLines);

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "  已修復並儲存\n";
    } else {
        echo "  無需修復\n";
    }
}

echo "\n修復完成!\n";
