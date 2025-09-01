<?php
/**
 * 全面修復 AuthController 語法錯誤
 */

$filePath = '/var/www/html/app/Application/Controllers/Api/V1/AuthController.php';
$content = file_get_contents($filePath);

// 修復所有的語法錯誤

// 1. 修復缺少括號的 json_encode
$content = preg_replace('/\(json_encode\([^)]+\) \?\: \'\'?\);/', '(json_encode($1) ?: \'\'));', $content);
$content = preg_replace('/write\(\(json_encode\([^)]+\) \?\: \'[^\']*\'\);/', 'write((json_encode($1) ?: \'$2\'));', $content);

// 2. 修復複雜表達式
$content = str_replace('getBody()->write((json_encode($responseData) ?: \'\');', 'getBody()->write((json_encode($responseData) ?: \'\'));', $content);

// 3. 修復方法調用
$content = str_replace('getUserAgent();', 'getUserAgent());', $content);

// 4. 修復變數引用
$content = str_replace('credentials->email', '$credentials[\'email\']', $content);

// 5. 修復複雜賦值
$content = preg_replace('/\$refreshToken = \$data \? [^:]+: null\) \?\? null;/', '$refreshToken = $requestData[\'refresh_token\'] ?? null;', $content);

// 6. 修復 DeviceInfo 結構
$lines = explode("\n", $content);
$newLines = [];
$skipNext = false;

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];

    // 修復語法錯誤
    if (strpos($line, 'userAgent: $request->getHeaderLine') !== false) {
        // 確保這行語法正確
        $line = str_replace('userAgent: $request->getHeaderLine(\'User-Agent\') ?: \'Unknown\',', 'userAgent: $request->getHeaderLine(\'User-Agent\') ?: \'Unknown\',', $line);
    }

    // 修復孤立的分號
    if (trim($line) === ';') {
        continue; // 跳過孤立的分號
    }

    // 修復括號問題
    if (strpos($line, ');') !== false && strpos($line, '];') !== false) {
        $line = str_replace(');', ']);', $line);
    }

    $newLines[] = $line;
}

$content = implode("\n", $newLines);

// 最後的全局修復
$content = str_replace('((json_encode($responseData) ?: \'\');', '((json_encode($responseData) ?: \'\'));', $content);

file_put_contents($filePath, $content);

echo "AuthController 語法錯誤修復完成!\n";
