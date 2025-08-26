<?php

declare(strict_types=1);

/**
 * PHPUnit 11.5+ Attributes 遷移工具
 * 
 * 將 PHPUnit doc-comment metadata 遷移到 PHP 8+ attributes
 * 基於 PHPUnit 官方文件的最佳實踐
 */

function migratePhpunitAttributes(string $projectRoot): array
{
    $stats = [
        'files_processed' => 0,
        'methods_migrated' => 0,
        'classes_migrated' => 0,
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

        // 遷移測試類別的 metadata
        $content = migrateClassAttributes($content);
        if ($content !== $originalContent) {
            $stats['classes_migrated']++;
        }

        // 遷移測試方法的 metadata
        $methodResult = migrateMethodAttributes($content);
        $content = $methodResult['content'];
        $stats['methods_migrated'] += $methodResult['migrated_count'];

        // 只在有變更時寫入檔案
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content) === false) {
                $stats['errors'][] = "Failed to write file: $filePath";
            } else {
                $stats['files_processed']++;
                echo "✅ Migrated: " . basename($filePath) . " (+" . $methodResult['migrated_count'] . " methods)\n";
            }
        }
    }

    return $stats;
}

function migrateClassAttributes(string $content): string
{
    // 遷移類別級別的 @group 標記
    $content = preg_replace(
        '/\/\*\*[\s\*]*@group\s+(\w+)[\s\*]*\*\/\s*(abstract\s+|final\s+)?class/m',
        "use PHPUnit\\Framework\\Attributes\\Group;\n\n#[Group('$1')]\n$2class",
        $content
    );

    return $content;
}

function migrateMethodAttributes(string $content): array
{
    $migratedCount = 0;

    // 確保有必要的 use 語句
    $content = ensureAttributeUseStatements($content);

    // 1. 遷移 @test 標記
    $pattern = '/\/\*\*[\s\S]*?@test[\s\S]*?\*\/\s*public\s+function\s+(\w+)/';
    if (preg_match_all($pattern, $content, $matches)) {
        $migratedCount += count($matches[0]);
        $content = preg_replace(
            $pattern,
            "#[Test]\n    public function $1",
            $content
        );
    }

    // 2. 遷移 @dataProvider 標記
    $pattern = '/\/\*\*[\s\S]*?@dataProvider\s+(\w+)[\s\S]*?\*\/\s*public\s+function\s+(\w+)/';
    if (preg_match_all($pattern, $content, $matches)) {
        $content = preg_replace(
            $pattern,
            "#[DataProvider('$1')]\n    public function $2",
            $content
        );
    }

    // 3. 遷移 @group 標記
    $pattern = '/\/\*\*[\s\S]*?@group\s+(\w+)[\s\S]*?\*\/\s*public\s+function\s+(\w+)/';
    if (preg_match_all($pattern, $content, $matches)) {
        $content = preg_replace(
            $pattern,
            "#[Group('$1')]\n    public function $2",
            $content
        );
    }

    // 4. 遷移 @depends 標記
    $pattern = '/\/\*\*[\s\S]*?@depends\s+(\w+)[\s\S]*?\*\/\s*public\s+function\s+(\w+)/';
    if (preg_match_all($pattern, $content, $matches)) {
        $content = preg_replace(
            $pattern,
            "#[Depends('$1')]\n    public function $2",
            $content
        );
    }

    // 5. 遷移 @expectedException 標記
    $pattern = '/\/\*\*[\s\S]*?@expectedException\s+([\w\\\\]+)[\s\S]*?\*\/\s*public\s+function\s+(\w+)/';
    if (preg_match_all($pattern, $content, $matches)) {
        $content = preg_replace(
            $pattern,
            "#[ExpectedException($1::class)]\n    public function $2",
            $content
        );
    }

    // 6. 清理只包含 @test 的空 doc-comments
    $content = preg_replace(
        '/\/\*\*\s*\*\s*@test\s*\*\s*\*\/\s*\n/',
        '',
        $content
    );

    // 7. 清理其他簡單的測試相關 doc-comments
    $content = preg_replace(
        '/\/\*\*\s*\*\s*測試.*?\s*\*\s*\*\/\s*\n\s*#\[Test\]/m',
        '#[Test]',
        $content
    );

    return [
        'content' => $content,
        'migrated_count' => $migratedCount
    ];
}

function ensureAttributeUseStatements(string $content): string
{
    $useStatements = [
        'Test' => 'use PHPUnit\\Framework\\Attributes\\Test;',
        'DataProvider' => 'use PHPUnit\\Framework\\Attributes\\DataProvider;',
        'Group' => 'use PHPUnit\\Framework\\Attributes\\Group;',
        'Depends' => 'use PHPUnit\\Framework\\Attributes\\Depends;',
        'ExpectedException' => 'use PHPUnit\\Framework\\Attributes\\ExpectedException;',
    ];

    // 檢查是否需要添加 use 語句
    $needsUse = [];

    if (preg_match('/#\[Test\]/', $content)) {
        $needsUse[] = $useStatements['Test'];
    }
    if (preg_match('/#\[DataProvider/', $content)) {
        $needsUse[] = $useStatements['DataProvider'];
    }
    if (preg_match('/#\[Group/', $content)) {
        $needsUse[] = $useStatements['Group'];
    }
    if (preg_match('/#\[Depends/', $content)) {
        $needsUse[] = $useStatements['Depends'];
    }
    if (preg_match('/#\[ExpectedException/', $content)) {
        $needsUse[] = $useStatements['ExpectedException'];
    }

    if (!empty($needsUse)) {
        // 找到最後一個 use 語句的位置
        $pattern = '/(use\s+[^;]+;)(?=\s*$|\s*\/\*|\s*class|\s*abstract|\s*final)/m';
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $lastUsePosition = end($matches[0])[1] + strlen(end($matches[0])[0]);
            $newUseStatements = "\n" . implode("\n", $needsUse);
            $content = substr_replace($content, $newUseStatements, $lastUsePosition, 0);
        } else {
            // 如果找不到 use 語句，在 namespace 後面添加
            $pattern = '/(namespace\s+[^;]+;)/';
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $namespaceEnd = $matches[0][1] + strlen($matches[0][0]);
                $newUseStatements = "\n\n" . implode("\n", $needsUse);
                $content = substr_replace($content, $newUseStatements, $namespaceEnd, 0);
            }
        }
    }

    return $content;
}

// 主執行邏輯
if (PHP_SAPI !== 'cli') {
    die('This script must be run from the command line.');
}

$projectRoot = dirname(__DIR__);

echo "🚀 PHPUnit Attributes 遷移工具\n";
echo "=====================================\n\n";
echo "基於 PHPUnit 11.5+ 官方文件進行 metadata 遷移\n";
echo "將 doc-comment 標記遷移到 PHP 8+ attributes\n\n";

echo "掃描測試檔案...\n";
$stats = migratePhpunitAttributes($projectRoot);

echo "\n📊 遷移完成統計:\n";
echo "=====================================\n";
echo "檔案處理數: {$stats['files_processed']}\n";
echo "方法遷移數: {$stats['methods_migrated']}\n";
echo "類別遷移數: {$stats['classes_migrated']}\n";

if (!empty($stats['errors'])) {
    echo "\n❌ 錯誤:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - $error\n";
    }
}

echo "\n✅ PHPUnit Attributes 遷移完成！\n";
echo "建議下一步：執行測試確認遷移成功\n";
echo "指令: bash scripts/test-analysis-workflow.sh --quick\n";
