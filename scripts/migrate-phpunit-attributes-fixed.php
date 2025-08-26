<?php

declare(strict_types=1);

/**
 * PHPUnit 11.5+ Attributes 遷移工具 (修復版)
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

        // 確保有必要的 use 語句
        $content = ensureAttributeUseStatements($content);

        // 遷移測試方法的 metadata
        $methodResult = migrateMethodAttributes($content);
        $content = $methodResult['content'];
        $stats['methods_migrated'] += $methodResult['migrated_count'];

        // 遷移測試類別的 metadata
        $content = migrateClassAttributes($content);
        if ($content !== $originalContent) {
            $stats['classes_migrated']++;
        }

        // 只在有變更時寫入檔案
        if ($content !== $originalContent) {
            if (file_put_contents($filePath, $content) === false) {
                $stats['errors'][] = "Failed to write file: $filePath";
            } else {
                $stats['files_processed']++;
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

    // 1. 遷移 @test 標記 - 改進的正則表達式
    $pattern = '/(\s*)(\/\*\*(?:[^*]|\*(?!\/))*@test(?:[^*]|\*(?!\/))*\*\/\s*)(public\s+function\s+\w+)/';
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
    $pattern = '/(\s*)(\/\*\*(?:[^*]|\*(?!\/))*@dataProvider\s+(\w+)(?:[^*]|\*(?!\/))*\*\/\s*)(public\s+function\s+\w+)/';
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
    $pattern = '/(\s*)(\/\*\*(?:[^*]|\*(?!\/))*@group\s+(\w+)(?:[^*]|\*(?!\/))*\*\/\s*)(public\s+function\s+\w+)/';
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
    $pattern = '/(\s*)(\/\*\*(?:[^*]|\*(?!\/))*@depends\s+(\w+)(?:[^*]|\*(?!\/))*\*\/\s*)(public\s+function\s+\w+)/';
    $replacement = function ($matches) {
        $indent = $matches[1];
        $dependsMethod = $matches[3];
        $methodDeclaration = $matches[4];
        return $indent . "#[Depends('$dependsMethod')]\n" . $indent . $methodDeclaration;
    };

    if (preg_match_all($pattern, $content)) {
        $content = preg_replace_callback($pattern, $replacement, $content);
    }

    // 5. 遷移 @expectedException 標記
    $pattern = '/(\s*)(\/\*\*(?:[^*]|\*(?!\/))*@expectedException\s+([\w\\\\]+)(?:[^*]|\*(?!\/))*\*\/\s*)(public\s+function\s+\w+)/';
    $replacement = function ($matches) {
        $indent = $matches[1];
        $exceptionClass = $matches[3];
        $methodDeclaration = $matches[4];
        return $indent . "#[ExpectedException($exceptionClass::class)]\n" . $indent . $methodDeclaration;
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

    // 找到最後一個 use 語句的位置
    $lastUsePos = strrpos($content, 'use ');
    if ($lastUsePos === false) {
        // 如果沒有 use 語句，在 namespace 後面添加
        $namespacePos = strpos($content, 'namespace ');
        if ($namespacePos !== false) {
            $lineEndPos = strpos($content, "\n", $namespacePos);
            $insertPos = $lineEndPos !== false ? $lineEndPos + 1 : strlen($content);
        } else {
            // 如果沒有 namespace，在 <?php 後面添加
            $insertPos = strpos($content, "<?php\n") + 6;
        }
    } else {
        // 找到最後一個 use 語句的行尾
        $lineEndPos = strpos($content, "\n", $lastUsePos);
        $insertPos = $lineEndPos !== false ? $lineEndPos + 1 : strlen($content);
    }

    $useStatements = [];
    if (!$hasTestAttribute && (strpos($content, '@test') !== false || strpos($content, '#[Test]') !== false)) {
        $useStatements[] = 'use PHPUnit\Framework\Attributes\Test;';
    }
    if (!$hasDataProviderAttribute && strpos($content, '@dataProvider') !== false) {
        $useStatements[] = 'use PHPUnit\Framework\Attributes\DataProvider;';
    }
    if (!$hasGroupAttribute && strpos($content, '@group') !== false) {
        $useStatements[] = 'use PHPUnit\Framework\Attributes\Group;';
    }
    if (!$hasDependsAttribute && strpos($content, '@depends') !== false) {
        $useStatements[] = 'use PHPUnit\Framework\Attributes\Depends;';
    }

    if (!empty($useStatements)) {
        $useStatementsStr = implode("\n", $useStatements) . "\n";
        $content = substr($content, 0, $insertPos) . $useStatementsStr . substr($content, $insertPos);
    }

    return $content;
}

// 主程式執行
if (php_sapi_name() === 'cli') {
    $projectRoot = __DIR__ . '/..';

    echo "🚀 PHPUnit Attributes 遷移工具 (修復版)\n";
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
