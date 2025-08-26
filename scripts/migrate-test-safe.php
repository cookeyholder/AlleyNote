<?php

declare(strict_types=1);

function migrateTestAnnotationsSafely(string $projectRoot): array
{
    $stats = [
        'files_processed' => 0,
        'methods_migrated' => 0,
        'errors' => []
    ];

    // 查找所有測試檔案
    $testFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectRoot . '/tests'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($testFiles as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getRealPath();
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $migratedCount = 0;

        // 檢查是否有 @test 註解需要遷移
        if (strpos($content, '/** @test */') === false) {
            continue;
        }

        // 第一步：確保有 Test attribute 的 use 語句
        if (strpos($content, 'use PHPUnit\Framework\Attributes\Test;') === false) {
            // 找到其他 PHPUnit use 語句的位置，插入在附近
            if (preg_match('/^(.*use PHPUnit\\\\Framework.*?;)$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertAfter = $matches[0][1] + strlen($matches[0][0]);
                $content = substr($content, 0, $insertAfter) . "\nuse PHPUnit\\Framework\\Attributes\\Test;" . substr($content, $insertAfter);
            } else {
                // 備用：在最後一個 use 語句後插入
                if (preg_match_all('/^use [^;]+;$/m', $content, $allUses, PREG_OFFSET_CAPTURE)) {
                    $lastUse = end($allUses[0]);
                    $insertAfter = $lastUse[1] + strlen($lastUse[0]);
                    $content = substr($content, 0, $insertAfter) . "\nuse PHPUnit\\Framework\\Attributes\\Test;" . substr($content, $insertAfter);
                }
            }
        }

        // 第二步：替換 /** @test */ 為 #[Test]
        // 使用 preg_replace_callback 來精確控制替換
        $content = preg_replace_callback(
            '/(\s*)(\/\*\*\s*@test\s*\*\/)\s*(public\s+function\s+\w+)/m',
            function ($matches) use (&$migratedCount) {
                $migratedCount++;
                $indent = $matches[1];
                $methodDeclaration = $matches[3];
                return $indent . "#[Test]\n" . $indent . $methodDeclaration;
            },
            $content
        );

        // 如果有變更，寫入檔案
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content) === false) {
                $stats['errors'][] = "Failed to write file: $filePath";
            } else {
                $stats['files_processed']++;
                $stats['methods_migrated'] += $migratedCount;
                echo "✅ Migrated: " . basename($filePath) . " (+{$migratedCount} @test)\n";
            }
        }
    }

    return $stats;
}

// 主程式執行
if (php_sapi_name() === 'cli') {
    $projectRoot = __DIR__ . '/..';

    echo "🚀 安全的 PHPUnit @test 遷移工具\n";
    echo "=================================\n\n";
    echo "將 /** @test */ 註解安全地遷移到 #[Test] attributes\n";
    echo "保持檔案結構完整性\n\n";

    echo "掃描測試檔案...\n";
    $stats = migrateTestAnnotationsSafely($projectRoot);

    echo "\n📊 遷移完成統計:\n";
    echo "=====================================\n";
    echo "檔案處理數: {$stats['files_processed']}\n";
    echo "方法遷移數: {$stats['methods_migrated']}\n";

    if (!empty($stats['errors'])) {
        echo "\n❌ 發生的錯誤:\n";
        foreach ($stats['errors'] as $error) {
            echo "- $error\n";
        }
    }

    echo "\n✅ PHPUnit @test 遷移完成！\n";
    echo "現在執行測試檢查結果: sudo docker exec alleynote_web ./vendor/bin/phpunit --testdox\n";
}
