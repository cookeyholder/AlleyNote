<?php
declare(strict_types=1);

/**
 * Final Generic Annotations Fixer
 *
 * 修復所有剩餘的泛型標註語法錯誤
 */

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/vendor/autoload.php';

// 計數器
$totalFixes = 0;
$filesFixes = [];

/**
 * 修復所有剩餘的泛型標註
 */
function fixAllRemainingGenerics($content) {
    global $totalFixes;

    $lines = explode("\n", $content);
    $modified = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $originalLine = $line;

        // 修復所有方法回傳值中的泛型標註
        if (preg_match('/\):\s*array<mixed>]+>/', $line)) {
            $newLine = preg_replace('/\):\s*array<mixed>]+>/', '): array<mixed>', $line);
            $lines[$i] = $newLine;
            $modified = true;
            $totalFixes++;
        }

        // 修復所有參數中的泛型標註
        if (preg_match('/array<mixed>]+>\s+\$/', $line)) {
            $newLine = preg_replace('/array<mixed>]+>(\s+\$)/', 'array<mixed>$1', $line);
            $lines[$i] = $newLine;
            $modified = true;
            $totalFixes++;
        }

        // 修復所有屬性中的泛型標註
        if (preg_match('/^(\s*)(private|protected|public)\s+array<mixed>]+>\s+\$/', $line)) {
            $newLine = preg_replace('/^(\s*)(private|protected|public)\s+array<mixed>]+>(\s+\$)/', '$1$2 array<mixed>$3', $line);
            $lines[$i] = $newLine;
            $modified = true;
            $totalFixes++;
        }

        // 修復變數宣告中的泛型標註
        if (preg_match('/\$\w+\s*:\s*array<mixed>]+>/', $line)) {
            $newLine = preg_replace('/(\$\w+)\s*:\s*array<mixed>]+>/', '$1: array<mixed>', $line);
            $lines[$i] = $newLine;
            $modified = true;
            $totalFixes++;
        }
    }

    return $modified ? implode("\n", $lines) : $content;
}

/**
 * 處理單個檔案
 */
function processFile($filePath) {
    global $filesFixes;

    if (!file_exists($filePath)) {
        return;
    }

    $content = file_get_contents($filePath);
    $originalContent = $content;

    $newContent = fixAllRemainingGenerics($content);

    // 如果內容有變更，寫回檔案
    if ($newContent !== $originalContent) {
        file_put_contents($filePath, $newContent);
        $fixCount = substr_count($originalContent, 'arrayfiles()
    ->name('*.php')
    ->in($projectRoot . '/app')
    ->notPath('vendor')
    ->notPath('tests')
    ->notPath('storage');

echo "開始修復所有剩餘的泛型標註語法錯誤...\n";
echo "===========================================\n";

foreach ($finder as $file) {
    processFile($file->getRealPath());
}

echo "\n===========================================\n";
echo "所有剩餘泛型標註修復完成！\n";
echo "總共修復: $totalFixes 個問題\n";
echo "處理的檔案: " . count($filesFixes) . " 個\n";

if (!empty($filesFixes)) {
    echo "\n詳細修復統計:\n";
    foreach ($filesFixes as $file => $count) {
        echo "- " . str_replace($projectRoot . '/', '', $file) . ": $count 個修復\n";
    }
}
