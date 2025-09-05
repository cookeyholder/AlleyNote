<?php

declare(strict_types=1);

/**
 * 修復 PHPUnit assertContains 棄用警告的腳本
 *
 * 此腳本會掃描所有測試檔案，並將舊式的 assertContains 調用
 * 轉換為新式的 assertThat 或 assertContainsEquals 調用
 */

function fixAssertContains(string $filePath): void
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "無法讀取檔案: $filePath\n";
        return;
    }

    $originalContent = $content;

    // 模式1: assertContains(值, 陣列) -> assertThat(陣列, $this->contains(值))
    // 需要檢查是否需要添加 use PHPUnit\Framework\Constraint\IsIdentical;
    $pattern1 = '/\$this->assertContains\(([^,]+),\s*([^,\)]+)(?:,\s*([^)]*))?\)/';

    $content = preg_replace_callback($pattern1, function($matches) {
        $needle = trim($matches[1]);
        $haystack = trim($matches[2]);
        $message = isset($matches[3]) ? ', ' . trim($matches[3]) : '';

        return "\$this->assertThat({$haystack}, \$this->contains({$needle}){$message})";
    }, $content);

    // 檢查是否需要添加 PHPUnit constraint imports
    if ($content !== $originalContent && strpos($content, 'use PHPUnit\Framework\Constraint') === false) {
        // 找到其他 PHPUnit import 的位置
        $testCasePattern = '/use PHPUnit\\\\Framework\\\\TestCase;/';
        if (preg_match($testCasePattern, $content)) {
            $replacement = "use PHPUnit\\Framework\\TestCase;\nuse PHPUnit\\Framework\\Constraint\\IsIdentical;";
            $content = preg_replace($testCasePattern, $replacement, $content);
        }
    }

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "已修復 $filePath 中的 assertContains 調用\n";
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
            // 檢查檔案是否包含 assertContains
            $content = file_get_contents($filePath);
            if ($content && strpos($content, 'assertContains') !== false) {
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

echo "開始掃描包含 assertContains 的測試檔案...\n";
$testFiles = findTestFiles($testsDirectory);
echo "找到 " . count($testFiles) . " 個包含 assertContains 的檔案\n\n";

foreach ($testFiles as $testFile) {
    echo "處理檔案: " . basename($testFile) . "\n";
    fixAssertContains($testFile);
}

echo "\n修復完成！\n";
