<?php

declare(strict_types=1);

/**
 * 批量修復剩餘的 assertContains 調用
 */

$replacements = [
    // ActivityStatusTest.php
    "        \$this->assertContains('error', \$statuses);" => "        \$this->assertArrayHasKey('error', array_flip(\$statuses));",
    "        \$this->assertContains('blocked', \$statuses);" => "        \$this->assertArrayHasKey('blocked', array_flip(\$statuses));",
    "        \$this->assertContains('pending', \$statuses);" => "        \$this->assertArrayHasKey('pending', array_flip(\$statuses));",

    // TaggedCacheIntegrationTest.php
    "        \$this->assertContains(\$key, \$keysByTag);" => "        \$this->assertArrayHasKey(\$key, array_flip(\$keysByTag));",
    "            \$this->assertContains(\$key, \$keys);" => "            \$this->assertArrayHasKey(\$key, array_flip(\$keys));",
    "            \$this->assertContains(\$expectedKey, \$keysWithSharedTag);" => "            \$this->assertArrayHasKey(\$expectedKey, array_flip(\$keysWithSharedTag));",

    // HttpResponseTestTrait.php
    "        \$this->assertContains(\$expectedValue, \$headerValues, \"標頭 {\$headerName} 的值不符合預期\");" => "        \$this->assertArrayHasKey(\$expectedValue, array_flip(\$headerValues), \"標頭 {\$headerName} 的值不符合預期\");",

    // TokenBlacklistEntryTest.php
    "        \$this->assertContains(TokenBlacklistEntry::TOKEN_TYPE_ACCESS, \$types);" => "        \$this->assertArrayHasKey(TokenBlacklistEntry::TOKEN_TYPE_ACCESS, array_flip(\$types));",
    "        \$this->assertContains(TokenBlacklistEntry::TOKEN_TYPE_REFRESH, \$types);" => "        \$this->assertArrayHasKey(TokenBlacklistEntry::TOKEN_TYPE_REFRESH, array_flip(\$types));",
    "        \$this->assertContains(TokenBlacklistEntry::REASON_LOGOUT, \$reasons);" => "        \$this->assertArrayHasKey(TokenBlacklistEntry::REASON_LOGOUT, array_flip(\$reasons));",
    "        \$this->assertContains(TokenBlacklistEntry::REASON_SECURITY_BREACH, \$reasons);" => "        \$this->assertArrayHasKey(TokenBlacklistEntry::REASON_SECURITY_BREACH, array_flip(\$reasons));",
    "        \$this->assertContains(TokenBlacklistEntry::REASON_PASSWORD_CHANGED, \$reasons);" => "        \$this->assertArrayHasKey(TokenBlacklistEntry::REASON_PASSWORD_CHANGED, array_flip(\$reasons));",
];

function applyReplacements(string $filePath, array $replacements): void
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return;
    }

    $originalContent = $content;

    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✓ 已修復 " . basename($filePath) . "\n";
    }
}

$testsDirectory = __DIR__ . '/../tests';
$testFiles = [
    $testsDirectory . '/Unit/Domains/Security/Enums/ActivityStatusTest.php',
    $testsDirectory . '/Integration/Shared/Cache/Services/TaggedCacheIntegrationTest.php',
    $testsDirectory . '/Support/Traits/HttpResponseTestTrait.php',
    $testsDirectory . '/Unit/Domains/Auth/ValueObjects/TokenBlacklistEntryTest.php',
];

echo "🔧 開始批量修復 assertContains 調用...\n\n";

foreach ($testFiles as $testFile) {
    if (file_exists($testFile)) {
        echo "處理檔案: " . basename($testFile) . "\n";
        applyReplacements($testFile, $replacements);
    } else {
        echo "⚠️  檔案不存在: " . basename($testFile) . "\n";
    }
    echo "\n";
}

echo "✅ 批量修復完成！\n";
