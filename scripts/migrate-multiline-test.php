<?php

declare(strict_types=1);

function migrateMultiLineTestAnnotations(string $projectRoot): array
{
    $stats = [
        'files_processed' => 0,
        'methods_migrated' => 0,
        'errors' => []
    ];

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

        // 檢查是否有多行 @test 註解
        if (!preg_match('/\/\*\*[\s\S]*?\*\s+@test[\s\S]*?\*\//m', $content)) {
            continue;
        }

        // 添加 Test attribute 的 use 語句（如果還沒有）
        if (strpos($content, 'use PHPUnit\Framework\Attributes\Test;') === false) {
            if (preg_match('/^(.*use PHPUnit\\\\Framework.*?;)$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $insertAfter = $matches[0][1] + strlen($matches[0][0]);
                $content = substr($content, 0, $insertAfter) . "\nuse PHPUnit\\Framework\\Attributes\\Test;" . substr($content, $insertAfter);
            } else if (preg_match_all('/^use [^;]+;$/m', $content, $allUses, PREG_OFFSET_CAPTURE)) {
                $lastUse = end($allUses[0]);
                $insertAfter = $lastUse[1] + strlen($lastUse[0]);
                $content = substr($content, 0, $insertAfter) . "\nuse PHPUnit\\Framework\\Attributes\\Test;" . substr($content, $insertAfter);
            }
        }

        // 處理多行 @test 註解
        $content = preg_replace_callback(
            '/(\s*)(\/\*\*[\s\S]*?\*\s+@test[\s\S]*?\*\/)\s*(public\s+function\s+\w+)/m',
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
                echo "✅ Migrated: " . basename($filePath) . " (+{$migratedCount} multi-line @test)\n";
            }
        }
    }

    return $stats;
}

if (php_sapi_name() === 'cli') {
    $projectRoot = __DIR__ . '/..';

    echo "🚀 多行 @test 註解遷移工具\n";
    echo "============================\n\n";
    echo "遷移多行格式的 /** ... @test ... */ 註解\n\n";

    echo "掃描測試檔案...\n";
    $stats = migrateMultiLineTestAnnotations($projectRoot);

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

    echo "\n✅ 多行 @test 遷移完成！\n";
}
