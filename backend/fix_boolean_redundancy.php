<?php

$file = $argv[1] ?? '';
if (!$file || !file_exists($file)) {
    echo "請提供有效的檔案路徑\n";
    exit(1);
}

$content = file_get_contents($file);

// 修復冗餘的布林運算
// 模式: return true && expression && true;
$pattern = '/return true\s*&&\s*(\$metadata\[[^\]]+\][^;]+)\s*&&\s*true;/';
$replacement = 'return $1;';
$content = preg_replace($pattern, $replacement, $content);

// 清理多行的情況
$pattern2 = '/return true\s*\n\s*&&\s*(\$metadata\[[^\]]+\][^&]+)\s*\n\s*&&\s*true;/';
$content = preg_replace($pattern2, 'return $1;', $content);

file_put_contents($file, $content);
echo "修復完成: $file\n";
