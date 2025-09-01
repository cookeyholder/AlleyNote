<?php
/**
 * 修復控制器語法錯誤 - 特別針對複雜表達式
 */

$controllers = [
    '/var/www/html/app/Application/Controllers/Api/V1/AuthController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/AttachmentController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/PostController.php',
];

foreach ($controllers as $filePath) {
    $content = file_get_contents($filePath);
    $originalContent = $content;

    echo "修復: " . basename($filePath) . "\n";

    // 修復模式1: 多餘的括號和三元運算符
    $content = preg_replace('/\$data \? \$([^>]+)->([^:]+): null\)\),/', '$1->$2,', $content);

    // 修復模式2: 複雜的表達式簡化
    $content = preg_replace('/\$data \? ([^:]+): null\)\),/', '$1,', $content);

    // 修復模式3: 陣列取值語法
    $content = preg_replace('/\$data \? \$([^[]+)\[([^]]+)\]: null\)\),/', '$1[$2],', $content);

    // 修復模式4: 直接取值語法
    $content = preg_replace('/\(\$data \? ([^:]+): null\)\);/', '$1;', $content);

    // 修復模式5: 移除多餘的括號結構
    $lines = explode("\n", $content);
    $newLines = [];

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        // 修復特定的錯誤模式
        $line = preg_replace('/\$data \? ([^:]+): null\)\)/', '$1', $line);
        $line = preg_replace('/\)\),$/', '),', $line);
        $line = preg_replace('/\)\);$/', ');', $line);

        $newLines[] = $line;
    }

    $content = implode("\n", $newLines);

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "  已修復並儲存\n";
    } else {
        echo "  無需修復\n";
    }
}

echo "\n控制器語法修復完成!\n";
