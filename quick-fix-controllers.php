<?php
/**
 * 快速修復剩餘的控制器語法錯誤
 */

$files = [
    '/var/www/html/app/Application/Controllers/Api/V1/PostController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/AttachmentController.php',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // 修復括號錯誤
    $content = str_replace('($responseData ?: \'\');', '($responseData ?: \'\'));', $content);
    $content = str_replace('($errorResponse ?: \'\');', '($errorResponse ?: \'\'));', $content);
    $content = str_replace('($successResponse ?: \'\');', '($successResponse ?: \'\'));', $content);
    
    // 修復方法調用錯誤
    $content = str_replace('$e->getErrors();', '$e->getErrors());', $content);
    $content = str_replace('$e->getErrors(,', '$e->getErrors(),', $content);
    
    // 修復陣列語法
    $content = preg_replace('/\],\s*\]/m', ']', $content);
    
    file_put_contents($file, $content);
    echo "修復: " . basename($file) . "\n";
}

echo "控制器語法快速修復完成!\n";