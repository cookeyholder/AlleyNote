<?php
/**
 * 全面修復控制器語法錯誤
 */

function fixController($filePath) {
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $newLines = [];

    echo "修復 " . basename($filePath) . "...\n";

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $trimmed = trim($line);

        // 跳過註解掉的 if 語句和其內容
        if (preg_match('/\/\/ if \(!?isset/', $trimmed)) {
            echo "  跳過註解的 if 語句 (行 " . ($i + 1) . ")\n";

            // 找到對應的結束大括號
            $braceCount = 0;
            $j = $i + 1;

            while ($j < count($lines)) {
                $testLine = trim($lines[$j]);

                if (preg_match('/^\}$/', $testLine) && $braceCount == 0) {
                    $i = $j; // 跳過到結束大括號
                    echo "    跳過到行 " . ($i + 1) . "\n";
                    break;
                }

                $braceCount += substr_count($lines[$j], '{') - substr_count($lines[$j], '}');
                $j++;
            }
            continue;
        }

        // 修復簡單的 isset 問題
        if (preg_match('/isset\([^)]+\? [^)]+: [^)]+\)/', $line)) {
            // 簡化複雜的 isset 表達式
            $line = preg_replace('/isset\(\(is_array\([^)]+\)\s*\?\s*[^:]+:\s*[^)]+\)\)/', 'isset($credentials["email"])', $line);
            echo "  簡化 isset 表達式 (行 " . ($i + 1) . ")\n";
        }

        $newLines[] = $line;
    }

    $newContent = implode("\n", $newLines);

    if ($newContent !== $content) {
        file_put_contents($filePath, $newContent);
        echo "  已儲存修復\n";
    } else {
        echo "  無需修復\n";
    }
}

$controllers = [
    '/var/www/html/app/Application/Controllers/Api/V1/AuthController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/PostController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/IpController.php',
];

foreach ($controllers as $controller) {
    fixController($controller);
}

echo "\n修復完成!\n";
