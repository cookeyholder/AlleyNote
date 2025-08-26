<?php

declare(strict_types=1);

/**
 * PHPUnit 11.5+ Attributes 遷移工具 (最終版)
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

        // 遷移測試方法的 metadata
        $methodResult = migrateMethodAttributes($content);
        $content = $methodResult['content'];
        $stats['methods_migrated'] += $methodResult['migrated_count'];

        // 如果有遷移，確保有必要的 use 語句
        if ($methodResult['migrated_count'] > 0) {
            $content = ensureAttributeUseStatements($content);
        }

        // 遷移測試類別的 metadata
        $content = migrateClassAttributes($content);

        // 只在有變更時寫入檔案
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content) === false) {
                $stats['errors'][] = "Failed to write file: $filePath";
            } else {
                $stats['files_processed']++;
                if ($methodResult['migrated_count'] > 0) {
                    $stats['classes_migrated']++;
                }
                $relativePath = str_replace($projectRoot . '/', '', $filePath);
                echo "✅ Migrated: " . basename($filePath) . " (+{$methodResult['migrated_count']} methods)\n";
            }
        }
    }

    return $stats;
}

function migrateClassAttributes(string $content): string
{
    // 遷移類別級別的 @group 標記
    $content = preg_replace(
        '/\/\*\*[\s\S]*?@group\s+(\w+)[\s\S]*?\*\/\s*((abstract\s+|final\s+)?class)/m',
        "#[Group('$1')]\n$2",
        $content
    );

    return $content;
}

function migrateMethodAttributes(string $content): array
{
    $migratedCount = 0;

    // 1. 遷移 @test 標記 - 更精確的正則表達式
    $pattern = '/(\s*)(\/\*\*[^*]*?\*(?:[^\/][^*]*?\*)*?\s*@test\s*(?:[^*]*?\*(?:[^\/][^*]*?\*)*?)?\*\/)\s*(public\s+function\s+\w+)/';
    $replacement = function ($matches) {
        $indent = $matches[1];
        $methodDeclaration = $matches[3];
        return $indent . "#[Test]\n" . $indent . $methodDeclaration;
    };

    if (preg_match_all($pattern, $content)) {
        $migratedCount += preg_match_all($pattern, $content, $matches);
        $content = preg_replace_callback($pattern, $replacement, $content);
    }

    // 2. 遷移 @dataProvider 標記
    $pattern = '/(\s*)(\/\*\*[^*]*?\*(?:[^\/][^*]*?\*)*?\s*@dataProvider\s+(\w+)\s*(?:[^*]*?\*(?:[^\/][^*]*?\*)*?)?\*\/)\s*(public\s+function\s+\w+)/';
    $replacement = function ($matches) {
        $indent = $matches[1];
        $dataProviderName = $matches[3];
        $methodDeclaration = $matches[4];
        return $indent . "#[DataProvider('$dataProviderName')]\n" . $indent . $methodDeclaration;
    };

    if (preg_match_all($pattern, $content)) {
        $content = preg_replace_callback($pattern, $replacement, $content);
    }

    // 3. 遷移 @group 標記
    $pattern = '/(\s*)(\/\*\*[^*]*?\*(?:[^\/][^*]*?\*)*?\s*@group\s+(\w+)\s*(?:[^*]*?\*(?:[^\/][^*]*?\*)*?)?\*\/)\s*(public\s+function\s+\w+)/';
    $replacement = function ($matches) {
        $indent = $matches[1];
        $groupName = $matches[3];
        $methodDeclaration = $matches[4];
        return $indent . "#[Group('$groupName')]\n" . $indent . $methodDeclaration;
    };

    if (preg_match_all($pattern, $content)) {
        $content = preg_replace_callback($pattern, $replacement, $content);
    }

    // 4. 遷移 @depends 標記
    $pattern = '/(\s*)(\/\*\*[^*]*?\*(?:[^\/][^*]*?\*)*?\s*@depends\s+(\w+)\s*(?:[^*]*?\*(?:[^\/][^*]*?\*)*?)?\*\/)\s*(public\s+function\s+\w+)/';
    $replacement = function ($matches) {
        $indent = $matches[1];
        $dependsMethod = $matches[3];
        $methodDeclaration = $matches[4];
        return $indent . "#[Depends('$dependsMethod')]\n" . $indent . $methodDeclaration;
    };

    if (preg_match_all($pattern, $content)) {
        $content = preg_replace_callback($pattern, $replacement, $content);
    }

    return [
        'content' => $content,
        'migrated_count' => $migratedCount
    ];
}

function ensureAttributeUseStatements(string $content): string
{
    // 檢查是否已經有 PHPUnit attributes 的 use 語句
    $hasTestAttribute = strpos($content, 'use PHPUnit\Framework\Attributes\Test;') !== false;
    $hasDataProviderAttribute = strpos($content, 'use PHPUnit\Framework\Attributes\DataProvider;') !== false;
    $hasGroupAttribute = strpos($content, 'use PHPUnit\Framework\Attributes\Group;') !== false;
    $hasDependsAttribute = strpos($content, 'use PHPUnit\Framework\Attributes\Depends;') !== false;

    // 找到 class 定義前的位置，在最後一個 use 語句後面插入
    if (preg_match('/^((?:(?:use\s+[^;]+;[\r\n]*)+))(.*?)^((?:abstract\s+|final\s+)?class\s+)/ms', $content, $matches)) {
        $beforeUses = $matches[1];
        $afterUses = $matches[2];
        $classDeclaration = $matches[3];

        $useStatements = [];
        if (!$hasTestAttribute && strpos($content, '#[Test]') !== false) {
            $useStatements[] = 'use PHPUnit\Framework\Attributes\Test;';
        }
        if (!$hasDataProviderAttribute && strpos($content, '#[DataProvider(') !== false) {
            $useStatements[] = 'use PHPUnit\Framework\Attributes\DataProvider;';
        }
        if (!$hasGroupAttribute && strpos($content, '#[Group(') !== false) {
            $useStatements[] = 'use PHPUnit\Framework\Attributes\Group;';
        }
        if (!$hasDependsAttribute && strpos($content, '#[Depends(') !== false) {
            $useStatements[] = 'use PHPUnit\Framework\Attributes\Depends;';
        }

        if (!empty($useStatements)) {
            $useStatementsStr = implode("\n", $useStatements) . "\n";
            $content = $beforeUses . $useStatementsStr . $afterUses . $classDeclaration . substr($content, strpos($content, $classDeclaration) + strlen($classDeclaration));
        }
    } else {
        // 備用方案：在第一個類別定義前插入
        if (preg_match('/(.*?)((?:abstract\s+|final\s+)?class\s+)/s', $content, $matches)) {
            $beforeClass = $matches[1];
            $classDeclaration = $matches[2];

            $useStatements = [];
            if (!$hasTestAttribute && strpos($content, '#[Test]') !== false) {
                $useStatements[] = 'use PHPUnit\Framework\Attributes\Test;';
            }
            if (!$hasDataProviderAttribute && strpos($content, '#[DataProvider(') !== false) {
                $useStatements[] = 'use PHPUnit\Framework\Attributes\DataProvider;';
            }
            if (!$hasGroupAttribute && strpos($content, '#[Group(') !== false) {
                $useStatements[] = 'use PHPUnit\Framework\Attributes\Group;';
            }
            if (!$hasDependsAttribute && strpos($content, '#[Depends(') !== false) {
                $useStatements[] = 'use PHPUnit\Framework\Attributes\Depends;';
            }

            if (!empty($useStatements)) {
                $useStatementsStr = implode("\n", $useStatements) . "\n\n";
                $content = $beforeClass . $useStatementsStr . $classDeclaration . substr($content, strlen($beforeClass) + strlen($classDeclaration));
            }
        }
    }

    return $content;
}

// 主程式執行
if (php_sapi_name() === 'cli') {
    $projectRoot = __DIR__ . '/..';

    echo "🚀 PHPUnit Attributes 遷移工具 (最終版)\n";
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
        echo "\n❌ 發生的錯誤:\n";
        foreach ($stats['errors'] as $error) {
            echo "- $error\n";
        }
    }

    echo "\n✅ PHPUnit Attributes 遷移完成！\n";
    echo "建議下一步：執行測試確認遷移成功\n";
    echo "指令: bash scripts/test-analysis-workflow.sh --quick\n";
}
