<?php
/**
 * 修復孤立的 catch 區塊語法錯誤
 * 這些錯誤是由於 try 區塊被移除但 catch 區塊仍然存在導致的
 */

// 定義需要修復的檔案路徑
$projectRoot = __DIR__;
$filesToScan = [];

// 收集所有 PHP 檔案
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectRoot . '/app')
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filesToScan[] = $file->getPathname();
    }
}

$filesFixed = 0;
$issuesFixed = 0;

echo "開始修復孤立的 catch 區塊...\n";

foreach ($filesToScan as $filePath) {
    $originalContent = file_get_contents($filePath);
    $content = $originalContent;
    $fileFixed = false;

    // 尋找孤立的 catch 區塊（前面沒有對應的 try）
    $lines = explode("\n", $content);
    $inFunction = false;
    $braceDepth = 0;
    $tryDepth = [];
    $linesToRemove = [];

    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);

        // 計算大括號深度
        $braceDepth += substr_count($lines[$i], '{') - substr_count($lines[$i], '}');

        // 檢查是否進入函式
        if (preg_match('/^\s*(public|private|protected)?\s*function\s+/', $line)) {
            $inFunction = true;
            $tryDepth = [];
        }

        // 檢查 try 區塊開始
        if (preg_match('/^\s*try\s*{/', $line)) {
            $tryDepth[] = $braceDepth;
        }

        // 檢查 catch 區塊
        if (preg_match('/^\s*}\s*catch\s*\(/', $line) || preg_match('/^\s*catch\s*\(/', $line)) {
            // 檢查是否有對應的 try
            $hasTry = false;
            foreach ($tryDepth as $depth) {
                if ($depth <= $braceDepth) {
                    $hasTry = true;
                    break;
                }
            }

            if (!$hasTry) {
                // 找到孤立的 catch 區塊，標記為待移除
                $catchStart = $i;
                $catchBraceCount = 0;
                $catchEnd = $i;

                // 找到 catch 區塊的結束
                for ($j = $i; $j < count($lines); $j++) {
                    $catchBraceCount += substr_count($lines[$j], '{') - substr_count($lines[$j], '}');
                    if ($j > $i && $catchBraceCount <= 0) {
                        $catchEnd = $j;
                        break;
                    }
                }

                // 標記這些行待移除
                for ($k = $catchStart; $k <= $catchEnd; $k++) {
                    $linesToRemove[] = $k;
                }

                $issuesFixed++;
                $fileFixed = true;

                echo "  發現孤立的 catch 區塊在 " . basename($filePath) . " 行 " . ($i + 1) . "-" . ($catchEnd + 1) . "\n";
            }
        }

        // 檢查函式結束
        if ($inFunction && $braceDepth <= 0 && preg_match('/^\s*}\s*$/', $line)) {
            $inFunction = false;
            $tryDepth = [];
        }
    }

    // 移除標記的行
    if (!empty($linesToRemove)) {
        $linesToRemove = array_unique($linesToRemove);
        sort($linesToRemove);

        $newLines = [];
        for ($i = 0; $i < count($lines); $i++) {
            if (!in_array($i, $linesToRemove)) {
                $newLines[] = $lines[$i];
            }
        }

        $content = implode("\n", $newLines);
    }

    // 儲存修復後的檔案
    if ($fileFixed && $content !== $originalContent) {
        file_put_contents($filePath, $content);
        $filesFixed++;
        echo "  已修復: " . basename($filePath) . "\n";
    }
}

echo "\n修復完成！\n";
echo "修復的檔案數量: $filesFixed\n";
echo "修復的問題數量: $issuesFixed\n";
