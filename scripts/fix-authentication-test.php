<?php

declare(strict_types=1);

function fixAuthenticationServiceTestFile(string $filePath): int
{
    $content = file_get_contents($filePath);
    $originalContent = $content;

    // 替換所有的多行 @test 註解
    $content = preg_replace(
        '/(\s+)\/\*\*\s*\*\s+@test\s*\*\/\s*(public\s+function\s+\w+)/m',
        '$1#[Test]' . "\n" . '$1$2',
        $content
    );

    $migratedCount = 0;
    if ($content !== $originalContent) {
        // 統計替換的數量
        $migratedCount = preg_match_all('/#\[Test\]/', $content);

        if (file_put_contents($filePath, $content)) {
            echo "✅ 修復 AuthenticationServiceTest.php (+{$migratedCount} methods)\n";
        } else {
            echo "❌ 無法寫入檔案: $filePath\n";
        }
    }

    return $migratedCount;
}

if (php_sapi_name() === 'cli') {
    $projectRoot = __DIR__ . '/..';
    $filePath = $projectRoot . '/tests/Unit/Domains/Auth/Services/AuthenticationServiceTest.php';

    echo "🛠️  修復 AuthenticationServiceTest.php 檔案\n";
    echo "========================================\n\n";

    if (file_exists($filePath)) {
        $migratedCount = fixAuthenticationServiceTestFile($filePath);
        echo "\n📊 修復統計: $migratedCount 個方法\n";
        echo "✅ 修復完成！\n";
    } else {
        echo "❌ 檔案不存在: $filePath\n";
    }
}
