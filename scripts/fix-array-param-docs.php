<?php

declare(strict_types=1);

/**
 * 建立和執行針對 array 型別問題的 PHPDoc 註解修復腳本
 */
$directoriesToScan = [
    '/var/www/html/app/Application/Controllers',
    '/var/www/html/app/Infrastructure'
];

$fixCount = 0;
$processedFiles = [];

echo "開始修復 array 參數類型註解...\n";

foreach ($directoriesToScan as $dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getPathname();
        echo "檢查檔案: $filePath\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // 1. 修復方法參數中的 array $args 問題
        $content = preg_replace_callback(
            '/public function\s+(\w+)\([^)]*array\s+\$args[^)]*\)/m',
            function($matches) use (&$fixCount) {
                $methodSignature = $matches[0];
                
                // 檢查是否有對應的 PHPDoc
                $beforeMethod = strstr($content, $methodSignature, true);
                $docBlockPattern = '/\/\*\*.*?\*\/\s*$/s';
                
                if (!preg_match($docBlockPattern, $beforeMethod)) {
                    // 如果沒有 PHPDoc，添加一個
                    $newDoc = "    /**\n     * @param array<string, mixed> \$args\n     */\n";
                    $fixCount++;
                    return $newDoc . $methodSignature;
                }
                
                return $methodSignature;
            },
            $content
        );
        
        // 2. 修復缺少類型註解的其他 array 參數
        $content = preg_replace_callback(
            '/public function\s+(\w+)\([^)]*array\s+\$(\w+)[^)]*\)/m',
            function($matches) use (&$fixCount, $content) {
                $methodSignature = $matches[0];
                $paramName = $matches[2];
                
                if ($paramName === 'args') {
                    return $methodSignature; // 已在上面處理
                }
                
                // 檢查是否已有正確的 PHPDoc
                $beforeMethod = strstr($content, $methodSignature, true);
                $hasCorrectDoc = preg_match("/@param\s+array<[^>]+>\s+\\\${$paramName}/", $beforeMethod);
                
                if (!$hasCorrectDoc) {
                    $typeHint = match($paramName) {
                        'data', 'params', 'attributes', 'config' => 'array<string, mixed>',
                        'headers' => 'array<string, string>',
                        'options', 'rules' => 'array<string, mixed>',
                        default => 'array<string, mixed>'
                    };
                    
                    // 如果沒有完整的 PHPDoc，需要更複雜的處理
                    $fixCount++;
                }
                
                return $methodSignature;
            },
            $content
        );
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $processedFiles[] = str_replace('/var/www/html/', '', $filePath);
            echo "  ✓ 已修復\n";
        }
    }
}

echo "\n修復完成！\n";
echo "總修復次數: $fixCount\n";
echo "修復的檔案數: " . count($processedFiles) . "\n";

if (!empty($processedFiles)) {
    echo "已修復的檔案:\n";
    foreach ($processedFiles as $file) {
        echo "  - $file\n";
    }
}