#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 清理無用的 PHPStan 忽略註解
 * 這個腳本會移除不再需要的 @phpstan-ignore-* 註解
 */

$filesToProcess = [
    'tests/Integration/FileSystemBackupTest.php' => [39],
    'tests/Integration/Http/PostControllerTest.php' => [131],
    'tests/Integration/JwtAuthenticationIntegrationTest.php' => [388, 416, 470],
    'tests/Integration/JwtAuthenticationIntegrationTest_simple.php' => [272],
    'tests/Integration/JwtTokenBlacklistIntegrationTest.php' => [339],
    'tests/Integration/PostControllerTest.php' => [525, 552],
    'tests/Integration/Repositories/PostRepositoryTest.php' => [91, 167],
    'tests/TestCase.php' => [229],
    'tests/Unit/DTOs/BaseDTOTest.php' => [46],
    'tests/Unit/Domains/Auth/Exceptions/JwtExceptionTest.php' => [23],
    'tests/Unit/Domains/Auth/Services/AuthenticationServiceTest.php' => [732, 750],
    'tests/Unit/Infrastructure/Auth/Repositories/TokenBlacklistRepositoryTest.php' => [1249],
    'tests/Unit/Services/AttachmentServiceTest.php' => [233],
    'tests/Unit/Services/Security/FileSecurityServiceTest.php' => [252],
];

foreach ($filesToProcess as $filePath => $lines) {
    $fullPath = __DIR__ . '/../' . $filePath;
    
    if (!file_exists($fullPath)) {
        echo "File not found: $fullPath\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $fileLines = explode("\n", $content);
    
    $modified = false;
    
    foreach ($lines as $lineNumber) {
        $arrayIndex = $lineNumber - 1; // Convert to 0-based index
        
        if (isset($fileLines[$arrayIndex])) {
            $line = $fileLines[$arrayIndex];
            
            // 移除包含 @phpstan-ignore 的註解行
            if (strpos($line, '@phpstan-ignore') !== false) {
                // 如果這是單獨的註解行，直接移除
                if (trim($line) === '' || preg_match('/^\s*\/\/.*@phpstan-ignore/', $line) || preg_match('/^\s*\*.*@phpstan-ignore/', $line)) {
                    unset($fileLines[$arrayIndex]);
                    $modified = true;
                    echo "Removed ignore comment at line $lineNumber in $filePath\n";
                }
                // 如果是行內註解，移除註解部分
                elseif (preg_match('/(.+?)\s*\/\/.*@phpstan-ignore.*/', $line, $matches)) {
                    $fileLines[$arrayIndex] = rtrim($matches[1]);
                    $modified = true;
                    echo "Cleaned inline ignore comment at line $lineNumber in $filePath\n";
                }
            }
        }
    }
    
    if ($modified) {
        // 重新組合內容，保持原有的行號結構
        $newContent = implode("\n", $fileLines);
        file_put_contents($fullPath, $newContent);
        echo "Updated $filePath\n";
    }
}

echo "PHPStan ignore cleanup completed.\n";