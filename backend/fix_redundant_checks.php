<?php

$file = $argv[1] ?? '';
if (!$file || !file_exists($file)) {
    echo "請提供有效的檔案路徑\n";
    exit(1);
}

$content = file_get_contents($file);

// 修復模式1: array_flip(is_array($var) ? array_filter($var, fn($v) => is_string($v) || is_int($v)) : [])
$pattern1 = '/array_flip\(is_array\((\$\w+)\) \? array_filter\(\1, fn\(\$v\) => is_string\(\$v\) \|\| is_int\(\$v\)\) : \[\]\)/';
$replacement1 = 'array_flip($1)';
$content = preg_replace($pattern1, $replacement1, $content);

// 修復模式2: array_filter($var, fn($v) => is_string($v) || is_int($v)) 對已知陣列
$pattern2 = '/array_filter\((\$\w+), fn\(\$v\) => is_string\(\$v\) \|\| is_int\(\$v\)\)/';
$replacement2 = '$1';
$content = preg_replace($pattern2, $replacement2, $content);

file_put_contents($file, $content);
echo "修復完成: $file\n";
