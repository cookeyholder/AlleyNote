<?php
/**
 * 修復語法錯誤 - 清理多餘的括號和運算符
 */

// 掃描所有有語法錯誤的檔案
$directories = ['/var/www/html/app'];
$allFiles = [];

foreach ($directories as $dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $allFiles[] = $file->getPathname();
        }
    }
}

$fixedFiles = 0;

echo "修復語法錯誤 - 清理多餘的括號...\n";

foreach ($allFiles as $filePath) {
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // 修復模式1: 多餘的括號和 ?? 運算符
    $content = preg_replace('/\$data \? [^:]+: null\)\) \?\? ([^)]+)\)/', '$1', $content);

    // 修復模式2: 複雜的三元運算符
    $content = preg_replace('/\(\$data \? \$[^>]+>[^:]+: null\)\) \?\? ([^)]+)\)/', '$1', $content);

    // 修復模式3: 簡化陣列取值
    $content = preg_replace('/\$data \? \$params->([^:]+): null\)\) \?\? ([^)]+)\)/', '$params[\'$1\'] ?? $2', $content);

    // 修復模式4: 移除多餘的大括號結構
    $lines = explode("\n", $content);
    $newLines = [];

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        // 修復錯誤的語法結構
        if (preg_match('/\$data \? \$([^>]+)->([^:]+): null\)\) \?\? ([^)]+)\)/', $line, $matches)) {
            $line = str_replace($matches[0], '$' . $matches[1] . '[\'' . $matches[2] . '\'] ?? ' . $matches[3], $line);
        }

        $newLines[] = $line;
    }

    $content = implode("\n", $newLines);

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $fixedFiles++;
        echo "修復: " . str_replace('/var/www/html/', '', $filePath) . "\n";
    }
}

echo "\n修復完成！修復的檔案: $fixedFiles\n";
