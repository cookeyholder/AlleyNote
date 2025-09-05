<?php

declare(strict_types=1);

/**
 * 修復 PHPUnit @test 標註的腳本
 *
 * 此腳本會掃描所有測試檔案，並將舊式的 @test 標註
 * 轉換為新式的 #[Test] 屬性標註
 */

function addTestImportIfNeeded(string $filePath): void
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "無法讀取檔案: $filePath\n";
        return;
    }

    // 檢查是否已經有 Test 屬性的 import
    if (strpos($content, 'use PHPUnit\Framework\Attributes\Test;') !== false) {
        return; // 已經有了，跳過
    }

    // 檢查是否有 @test 標註
    if (strpos($content, '@test') === false) {
        return; // 沒有 @test 標註，跳過
    }

    // 找到其他 PHPUnit import 的位置
    $pattern = '/use PHPUnit\\\\Framework\\\\TestCase;/';
    if (preg_match($pattern, $content)) {
        $replacement = "use PHPUnit\\Framework\\TestCase;\nuse PHPUnit\\Framework\\Attributes\\Test;";
        $content = preg_replace($pattern, $replacement, $content);
        file_put_contents($filePath, $content);
        echo "已為 $filePath 添加 Test 屬性 import\n";
    }
}

function convertTestAnnotations(string $filePath): void
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "無法讀取檔案: $filePath\n";
        return;
    }

    $originalContent = $content;

    // 模式1: 處理有描述的 @test
    // /**
    //  * 描述
    //  *
    //  * @test
    //  */
    $pattern1 = '/(\s+\/\*\*\s*\n(?:\s+\*[^\n]*\n)*\s+\*\s*\n\s+\*\s+)@test(\s*\n\s+\*\/\s*\n\s+)((?:public|private|protected)\s+function)/';
    $content = preg_replace($pattern1, '$1$2#[Test]$3', $content);

    // 模式2: 處理簡單的 @test
    // /**
    //  * @test
    //  */
    $pattern2 = '/(\s+\/\*\*\s*\n\s+\*\s+)@test(\s*\n\s+\*\/\s*\n\s+)((?:public|private|protected)\s+function)/';
    $content = preg_replace($pattern2, '$1$2#[Test]$3', $content);

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "已轉換 $filePath 中的 @test 標註\n";
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
            // 檢查檔案名稱是否包含 Test
            if (strpos($file->getFilename(), 'Test.php') !== false) {
                $testFiles[] = $filePath;
            }
        }
    }

    return $testFiles;
}

// 主執行邏輯
$testsDirectory = __DIR__ . '/../tests';

if (!is_dir($testsDirectory)) {
    echo "測試目錄不存在: $testsDirectory\n";
    exit(1);
}

echo "開始掃描測試檔案...\n";
$testFiles = findTestFiles($testsDirectory);
echo "找到 " . count($testFiles) . " 個測試檔案\n\n";

foreach ($testFiles as $testFile) {
    echo "處理檔案: " . basename($testFile) . "\n";

    // 先添加必要的 import
    addTestImportIfNeeded($testFile);

    // 然後轉換 @test 標註
    convertTestAnnotations($testFile);
}

echo "\n修復完成！\n";
