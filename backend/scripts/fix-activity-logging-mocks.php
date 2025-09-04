<?php

declare(strict_types=1);

/**
 * 批量修復測試檔案中的 ActivityLoggingService mock 設定
 *
 * 此腳本會：
 * 1. 掃描所有測試檔案
 * 2. 找到使用 ActivityLoggingServiceInterface 的測試
 * 3. 自動添加必要的 mock 期望
 */

function scanTestFiles(string $directory): array<mixed>
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function needsActivityLoggingMock(string $filePath): bool
{
    $content = file_get_contents($filePath);

    // 檢查是否使用了 ActivityLoggingServiceInterface
    return strpos($content, 'ActivityLoggingServiceInterface') !== false;
}

function hasProperMockSetup(string $content): bool
{
    // 檢查是否已經有適當的 mock 設定
    return strpos($content, 'logSuccess') !== false &&
           strpos($content, 'logFailure') !== false;
}

function addActivityLoggingMock(string $filePath): bool
{
    $content = file_get_contents($filePath);

    if (hasProperMockSetup($content)) {
        echo "跳過 {$filePath} - 已有適當的 mock 設定\n";
        return false;
    }

    $lines = explode("\n", $content);
    $modified = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];

        // 在 setUp 方法中查找 ActivityLoggingServiceInterface mock 初始化
        if (strpos($line, 'ActivityLoggingServiceInterface::class') !== false &&
            strpos($line, 'Mockery::mock') !== false) {

            // 找到下一個空行或方法結束，插入 mock 期望
            $insertIndex = $i + 1;
            while ($insertIndex < count($lines)) {
                $nextLine = trim($lines[$insertIndex]);

                // 如果找到 mock 設定區域的結束，在此插入期望
                if (empty($nextLine) || strpos($nextLine, '// 先建立') !== false ||
                    strpos($nextLine, '$this->') === false) {
                    break;
                }
                $insertIndex++;
            }

            // 插入 ActivityLoggingService mock 期望
            $mockExpectations = [
                '',
                '        // 設定 ActivityLoggingService 預設行為',
                '        $this->activityLogger->shouldReceive(\'logFailure\')',
                '            ->byDefault()',
                '            ->andReturn(true);',
                '        $this->activityLogger->shouldReceive(\'logSuccess\')',
                '            ->byDefault()',
                '            ->andReturn(true);'
            ];

            // 檢查是否已存在類似設定
            $hasExistingSetup = false;
            for ($j = $i; $j < min($i + 10, count($lines)); $j++) {
                if (strpos($lines[$j], 'logFailure') !== false) {
                    $hasExistingSetup = true;
                    break;
                }
            }

            if (!$hasExistingSetup) {
                array_splice($lines, $insertIndex, 0, $mockExpectations);
                $modified = true;
                echo "已修復 {$filePath} - 添加 ActivityLoggingService mock 期望\n";
            }
            break;
        }
    }

    if ($modified) {
        file_put_contents($filePath, implode("\n", $lines));
        return true;
    }

    return false;
}

// 主程式
$testDir = __DIR__ . '/../tests';
$testFiles = scanTestFiles($testDir);

$modifiedFiles = 0;
$totalFiles = 0;

foreach ($testFiles as $file) {
    if (needsActivityLoggingMock($file)) {
        $totalFiles++;
        if (addActivityLoggingMock($file)) {
            $modifiedFiles++;
        }
    }
}

echo "\n摘要：\n";
echo "掃描了 " . count($testFiles) . " 個測試檔案\n";
echo "發現 {$totalFiles} 個檔案使用 ActivityLoggingServiceInterface\n";
echo "修復了 {$modifiedFiles} 個檔案的 mock 設定\n";

if ($modifiedFiles > 0) {
    echo "\n建議執行以下命令驗證修復：\n";
    echo "docker-compose exec -T web ./vendor/bin/phpunit --no-coverage\n";
}
