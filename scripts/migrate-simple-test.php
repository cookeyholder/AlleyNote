<?php

declare(strict_types=1);

/**
 * 簡單的 PHPUnit @test 遷移工具
 */

function migrateSimpleTestAnnotations(string $projectRoot): array
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

        // 統計 @test 出現次數
        $testMatches = preg_match_all('/\/\*\*\s*@test\s*\*\//', $content);
        if ($testMatches > 0) {
            // 確保有必要的 use 語句
            $content = ensureTestAttributeUse($content);

            // 將 /** @test */ 替換為 #[Test]
            $content = preg_replace(
                '/(\s*)(\/\*\*\s*@test\s*\*\/\s*)(public\s+function\s+\w+)/',
                '$1#[Test]' . "\n" . '$1$3',
                $content
            );

            $stats['methods_migrated'] += $testMatches;
        }

        // 處理其他格式的 @dataProvider, @group 等
        $otherMatches = preg_match_all('/\/\*\*[^*]*@(?:dataProvider|group|depends)/', $content);

        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content) === false) {
                $stats['errors'][] = "Failed to write file: $filePath";
            } else {
                $stats['files_processed']++;
                echo "✅ Migrated: " . basename($filePath) . " (+{$testMatches} test methods)\n";
            }
        }
    }

    return $stats;
}

function ensureTestAttributeUse(string $content): string
{
    // 檢查是否已經有 Test attribute 的 use 語句
    if (strpos($content, 'use PHPUnit\Framework\Attributes\Test;') !== false) {
        return $content;
    }

    // 找到最後一個 use 語句的位置
    if (preg_match_all('/^use\s+[^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
        $lastUseMatch = end($matches[0]);
        $insertPos = $lastUseMatch[1] + strlen($lastUseMatch[0]);

        // 在最後一個 use 語句後插入
        $content = substr($content, 0, $insertPos) . "\nuse PHPUnit\\Framework\\Attributes\\Test;" . substr($content, $insertPos);
    } else {
        // 如果沒有 use 語句，在 namespace 後面或開頭添加
        if (preg_match('/^namespace\s+[^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $content = substr($content, 0, $insertPos) . "\n\nuse PHPUnit\\Framework\\Attributes\\Test;" . substr($content, $insertPos);
        } else {
            // 在 <?php 後面添加
            $content = str_replace("<?php\n", "<?php\n\nuse PHPUnit\\Framework\\Attributes\\Test;\n", $content);
        }
    }

    return $content;
}

// 主程式執行
if (php_sapi_name() === 'cli') {
    $projectRoot = __DIR__ . '/..';

    echo "🚀 簡單的 PHPUnit @test 遷移工具\n";
    echo "===================================\n\n";
    echo "專門處理 /** @test */ 格式的註解\n\n";

    echo "掃描測試檔案...\n";
    $stats = migrateSimpleTestAnnotations($projectRoot);

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
}
