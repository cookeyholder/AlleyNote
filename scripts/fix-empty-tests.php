#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 修復空白測試類別的工具
 * 
 * 為空白的測試類別添加基本的測試方法，確保 PHPUnit 不會報告警告
 */

// 定義需要修復的空白測試檔案
$emptyTestFiles = [
    'tests/Unit/Database/DatabaseConnectionTest.php',
    'tests/Unit/Domains/Auth/Services/AuthenticationServiceTest.php',
    'tests/Unit/Models/PostTest.php',
    'tests/Unit/Repositories/AttachmentRepositoryTest.php',
    'tests/Unit/Repository/UserRepositoryTest.php',
    'tests/Unit/Services/AttachmentServiceTest.php',
    'tests/Unit/Services/AuthServiceTest.php',
    'tests/Unit/Services/CacheServiceTest.php',
    'tests/Unit/Services/RateLimitServiceTest.php',
    'tests/Unit/Services/Security/CsrfProtectionServiceTest.php',
    'tests/Unit/Services/Security/LoggingSecurityServiceTest.php',
    'tests/Unit/Services/Security/SessionSecurityServiceTest.php',
    'tests/Unit/Services/Security/XssProtectionServiceTest.php',
    'tests/Integration/AttachmentControllerTest.php',
    'tests/Integration/AttachmentUploadTest.php',
    'tests/Integration/AuthControllerTest.php',
    'tests/Integration/DatabaseBackupTest.php',
    'tests/Integration/FileSystemBackupTest.php',
    'tests/Integration/JwtAuthenticationIntegrationTest.php',
    'tests/Integration/PostControllerTest.php',
    'tests/Integration/RateLimitTest.php',
    'tests/Security/CsrfProtectionTest.php',
    'tests/Security/FileUploadSecurityTest.php',
    'tests/Security/PasswordHashingTest.php',
    'tests/Security/SqlInjectionTest.php',
    'tests/Security/XssPreventionTest.php',
];

$projectRoot = dirname(__DIR__);

foreach ($emptyTestFiles as $testFile) {
    $filePath = $projectRoot . '/' . $testFile;
    
    if (!file_exists($filePath)) {
        echo "檔案不存在: $filePath\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // 檢查是否已經有 test 方法
    if (preg_match('/public function test\w+/', $content)) {
        echo "檔案已經有測試方法: $testFile\n";
        continue;
    }
    
    // 查找類別名稱
    if (!preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
        echo "無法找到類別名稱: $testFile\n";
        continue;
    }
    
    $className = $matches[1];
    
    // 添加基本的 placeholder 測試
    $testMethod = "\n    /**\n     * TODO: 實作實際的測試案例\n     */\n    public function testPlaceholder(): void\n    {\n        \$this->markTestIncomplete('此測試尚未實作');\n    }\n";
    
    // 找到類別結束的位置並插入測試方法
    $pattern = '/(class\s+' . preg_quote($className) . '\s+extends[^{]*{[^}]*)(}(?:\s*\/\*.*?\*\/\s*)?$)/s';
    
    if (preg_match($pattern, $content)) {
        $newContent = preg_replace($pattern, '$1' . $testMethod . '$2', $content);
        
        if (file_put_contents($filePath, $newContent)) {
            echo "已修復: $testFile\n";
        } else {
            echo "修復失敗: $testFile\n";
        }
    } else {
        echo "無法匹配類別結構: $testFile\n";
    }
}

echo "修復完成！\n";