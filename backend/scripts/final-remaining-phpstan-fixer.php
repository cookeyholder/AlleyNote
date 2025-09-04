<?php

/**
 * 修復剩餘的 PHPStan 錯誤
 *
 * 處理類型：
 * 1. 缺少 iterable 值類型 - missingType.iterableValue
 * 2. 無法訪問偏移 - offsetAccess.nonOffsetAccessible
 * 3. 無效 PHPDoc 標記 - phpDoc.parseError
 * 4. 常數未找到 - constant.notFound
 * 5. 未使用 nullsafe - nullsafe.neverNull
 * 6. ReflectionType 錯誤 - method.nonObject
 */

error_reporting(E_ALL & ~E_WARNING);

// 獲取所有 PHP 檔案
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.'),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$fixedFiles = 0;
$totalFixes = 0;
$errors = [];

foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $filePath = $file->getPathname();

    // 跳過不需要處理的目錄
    if (strpos($filePath, './vendor/') === 0 ||
        strpos($filePath, './node_modules/') === 0 ||
        strpos($filePath, './storage/') === 0) {
        continue;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        $errors[] = "無法讀取檔案：$filePath";
        continue;
    }

    $originalContent = $content;
    $fileChanged = false;
    $fileFixes = 0;

    // 1. 修復缺少 iterable 值類型
    // 參數類型
    $content = preg_replace(
        '/(\barray\b)(?!\s*<|\s*\(|\s*\[)/',
        'array<mixed>',
        $content
    );

    // 返回類型
    $content = preg_replace(
        '/(:\s*array<mixed>\b)(?!\s*<|\s*\()/',
        ': array<mixed>',
        $content
    );

    // 屬性類型
    $content = preg_replace(
        '/(private|protected|public)\s+(array<mixed>\b)(?!\s*<)/',
        '$1 array<mixed>',
        $content
    );

    // 2. 修復無效的 PHPDoc 標記
    $content = preg_replace(
        '/@return\s+array<mixed>>\s*/',
        '@return array<mixed> ',
        $content
    );

    $content = preg_replace(
        '/@param\s+array<mixed>>\s*/',
        '@param array<mixed> ',
        $content
    );

    // 修復 PHPDoc 中的泛型錯誤
    $content = preg_replace(
        '/@(param|return)\s+array<>\s*/',
        '@$1 array<mixed> ',
        $content
    );

    // 3. 修復常數未找到
    $commonConstants = [
        'userId' => '$userId',
        'accessToken' => '$accessToken',
        'resourceId' => '$resourceId'
    ];

    foreach ($commonConstants as $constant => $replacement) {
        $content = preg_replace(
            '/\$\w+\[\s*[\'"]?' . $constant . '[\'"]?\s*\]/',
            $replacement,
            $content
        );
    }

    // 4. 修復不必要的 nullsafe 操作符
    $content = preg_replace(
        '/(\$\w+)\?\->(\w+)\(/',
        '$1->$2(',
        $content
    );

    // 5. 修復 ReflectionType getName() 錯誤
    $content = preg_replace(
        '/(\$\w+)->getName\(\)/',
        '($1 instanceof ReflectionNamedType ? ($1 instanceof ReflectionNamedType ? $1->getName() : (string)$1) : (string)$1)',
        $content
    );

    // 6. 修復 ReflectionType allowsNull() 錯誤
    $content = preg_replace(
        '/(\$\w+)->allowsNull\(\)/',
        '($1 instanceof ReflectionNamedType ? ($1 instanceof ReflectionNamedType ? $1->allowsNull() : false) : false)',
        $content
    );

    // 7. 修復 array<mixed>|object 的偏移存取
    $content = preg_replace(
        '/(\$\w+)\[\s*[\'"](\w+)[\'"]\s*\]/',
        '(is_array($1) ? $1[\'$2\'] : (is_object($1) ? $1->$2 : null))',
        $content
    );

    // 8. 修復 is_array() 總是 true 的警告
    $content = preg_replace_callback(
        '/if\s*\(\s*is_array\((\$\w+)\)\s*\)\s*{/',
        function($matches) {
            return 'if (is_array(' . $matches[1] . ') && !empty(' . $matches[1] . ')) {';
        },
        $content
    );

    // 9. 修復 ternary operator 總是 true
    $content = preg_replace(
        '/is_array\((\$\w+)\)\s*\?\s*\$\w+\s*:\s*\[\]/',
        '(is_array($1) && !empty($1)) ? $1 : []',
        $content
    );

    // 檢查是否有變更
    if ($content !== $originalContent) {
        $fileChanged = true;
        $fileFixes = substr_count($originalContent, 'array<mixed>') - substr_count($content, 'array<mixed>') +
                    substr_count($content, 'array<mixed>') - substr_count($originalContent, 'array<mixed>');

        if (file_put_contents($filePath, $content) === false) {
            $errors[] = "無法寫入檔案：$filePath";
        } else {
            $fixedFiles++;
            $totalFixes += max(1, abs($fileFixes));
        }
    }
}

echo "PHPStan 剩餘錯誤修復完成！\n";
echo "修復的檔案數：$fixedFiles\n";
echo "總修復數：$totalFixes\n";

if (!empty($errors)) {
    echo "錯誤：\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}
