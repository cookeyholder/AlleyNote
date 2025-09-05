<?php

declare(strict_types=1);

/**
 * 簡單修復 PHPUnit @test 標註的腳本
 *
 * 此腳本會掃描所有測試檔案，並將舊式的 @test 標註
 * 轉換為新式的 #[Test] 屬性標註，不處理 assertContains
 */

function addTestImportIfNeeded(string $filePath): void
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return;
    }

    // 檢查是否已經有 Test 屬性的 import
    if (strpos($content, 'use PHPUnit\Framework\Attributes\Test;') !== false) {
        return;
    }

    // 檢查是否有 @test 標註
    if (strpos($content, '@test') === false) {
        return;
    }

    // 找到其他 PHPUnit import 的位置，在 TestCase 之後添加
    if (preg_match('/use PHPUnit\\\\Framework\\\\TestCase;/', $content)) {
        $content = preg_replace(
            '/use PHPUnit\\\\Framework\\\\TestCase;/',
            "use PHPUnit\\Framework\\TestCase;\nuse PHPUnit\\Framework\\Attributes\\Test;",
            $content
        );
        file_put_contents($filePath, $content);
        echo "  ✓ 已添加 Test 屬性 import\n";
    }
}

function convertTestAnnotations(string $filePath): void
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return;
    }

    $originalContent = $content;
    $replacements = 0;

    // 處理不同的 @test 註解模式

    // 模式1: 單獨的 @test 行
    $content = preg_replace_callback(
        '/(\s+\/\*\*[\s\S]*?)\*\s+@test\s*\n(\s+\*\/\s*\n\s*)((?:public|private|protected)\s+function)/m',
        function($matches) use (&$replacements) {
            $replacements++;
            return $matches[1] . $matches[2] . '#[Test]' . "\n    " . $matches[3];
        },
        $content
    );

    // 模式2: 有描述的 @test
    $content = preg_replace_callback(
        '/(\s+\/\*\*[\s\S]*?)\*\s*\n\s+\*\s+@test\s*\n(\s+\*\/\s*\n\s*)((?:public|private|protected)\s+function)/m',
        function($matches) use (&$replacements) {
            $replacements++;
            return $matches[1] . $matches[2] . '#[Test]' . "\n    " . $matches[3];
        },
        $content
    );

    if ($content !== $originalContent && $replacements > 0) {
        file_put_contents($filePath, $content);
        echo "  ✓ 已轉換 {$replacements} 個 @test 標註\n";
    }
}

function findTestFiles(string $directory): array
{
    $testFiles = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            if (strpos($file->getFilename(), 'Test.php') !== false) {
                $content = file_get_contents($filePath);
                if ($content && strpos($content, '@test') !== false) {
                    $testFiles[] = $filePath;
                }
            }
        }
    }

    return $testFiles;
}

// 主執行邏輯
$testsDirectory = __DIR__ . '/../tests';

if (!is_dir($testsDirectory)) {
    echo "❌ 測試目錄不存在: $testsDirectory\n";
    exit(1);
}

echo "🔍 開始掃描包含 @test 標註的測試檔案...\n";
$testFiles = findTestFiles($testsDirectory);
echo "📝 找到 " . count($testFiles) . " 個包含 @test 標註的檔案\n\n";

foreach ($testFiles as $testFile) {
    echo "🔧 處理檔案: " . basename($testFile) . "\n";
    addTestImportIfNeeded($testFile);
    convertTestAnnotations($testFile);
    echo "\n";
}

echo "✅ 修復完成！\n";
