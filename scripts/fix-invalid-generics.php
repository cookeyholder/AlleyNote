<?php
declare(strict_types=1);

/**
 * Fix Invalid Generic Annotations Fixer
 *
 * 修復無效的泛型標注語法錯誤
 */

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/vendor/autoload.php';

// 計數器
$totalFixes = 0;
$filesFixes = [];

/**
 * 修復無效的泛型標注
 */
function fixInvalidGenerics($content) {
    global $totalFixes;

    $lines = explode("\n", $content);
    $modified = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $originalLine = $line;

        // 修復方法簽名中錯誤的泛型標注
        // 例如：private function method(): array<mixed>
        // 應該是：private function method(): array<mixed>
        if (preg_match('/^(\s*)(private|protected|public)\s+(static\s+)?function\s+\w+\([^)]*\):\s*array<mixed>]+>\s*$/', $line, $matches)) {
            $indent = $matches[1];
            $visibility = $matches[2];
            $static = $matches[3] ?? '';

            // 提取函數定義部分
            if (preg_match('/^(\s*)(private|protected|public)\s+(static\s+)?function\s+(\w+)\(([^)]*)\):\s*array<mixed>]+>\s*$/', $line, $funcMatches)) {
                $functionName = $funcMatches[4];
                $parameters = $funcMatches[5];

                // 替換為正確的語法
                $newLine = $indent . $visibility . ' ' . $static . 'function ' . $functionName . '(' . $parameters . '): array<mixed>';
                $lines[$i] = $newLine;
                $modified = true;
                $totalFixes++;
            }
        }

        // 修復參數中錯誤的泛型標注
        // 例如：public function method(array<mixed> $param)
        // 應該是：public function method(array<mixed> $param)
        if (preg_match('/array<mixed>]+>\s+\$/', $line)) {
            $newLine = preg_replace('/array<mixed>]+>(\s+\$)/', 'array<mixed>$1', $line);
            if ($newLine !== $line) {
                $lines[$i] = $newLine;
                $modified = true;
                $totalFixes++;
            }
        }

        // 修復屬性中錯誤的泛型標注
        // 例如：private array<mixed> $property
        // 應該是：private array<mixed> $property
        if (preg_match('/^(\s*)(private|protected|public)\s+array<mixed>]+>\s+\$/', $line)) {
            $newLine = preg_replace('/^(\s*)(private|protected|public)\s+array<mixed>]+>(\s+\$)/', '$1$2 array<mixed>$3', $line);
            if ($newLine !== $line) {
                $lines[$i] = $newLine;
                $modified = true;
                $totalFixes++;
            }
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

    $newContent = fixInvalidGenerics($content);

    // 如果內容有變更，寫回檔案
    if ($newContent !== $originalContent) {
        file_put_contents($filePath, $newContent);
        $fixCount = substr_count($originalContent, 'arrayfiles()
    ->name('*.php')
    ->in($projectRoot . '/app')
    ->notPath('vendor')
    ->notPath('tests')
    ->notPath('storage');

echo "開始修復無效的泛型標注語法錯誤...\n";
echo "===========================================\n";

foreach ($finder as $file) {
    processFile($file->getRealPath());
}

echo "\n===========================================\n";
echo "無效泛型標注修復完成！\n";
echo "總共修復: $totalFixes 個問題\n";
echo "處理的檔案: " . count($filesFixes) . " 個\n";

if (!empty($filesFixes)) {
    echo "\n詳細修復統計:\n";
    foreach ($filesFixes as $file => $count) {
        echo "- " . str_replace($projectRoot . '/', '', $file) . ": $count 個修復\n";
    }
}
