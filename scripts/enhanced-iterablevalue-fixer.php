<?php
declare(strict_types=1);

/**
 * Enhanced Missing Iterable Value Type Fixer
 *
 * 專門修復 missingType.iterableValue 錯誤
 */

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/vendor/autoload.php';

// 計數器
$totalFixes = 0;
$filesFixes = [];

/**
 * 修復遺失的可迭代值類型
 */
function fixMissingIterableValueType($content, $line, $context) {
    global $totalFixes;

    $lines = explode("\n", $content);
    $lineIndex = $line - 1;

    if (!isset($lines[$lineIndex])) {
        return $content;
    }

    $currentLine = $lines[$lineIndex];
    $originalLine = $currentLine;

    // 修復方法參數中的 array<mixed> 類型
    if (strpos($context, 'parameter') !== false && strpos($context, 'iterable type array<mixed>') !== false) {
        // 替換 array<mixed> $param 為 array<mixed> $param
        $currentLine = preg_replace(
            '/\barray\s+(\$\w+)/i',
            'array<mixed> $1',
            $currentLine
        );

        // 或者根據上下文推測更具體的類型
        if (strpos($currentLine, '$request') !== false) {
            $currentLine = preg_replace(
                '/array<mixed>\s+(\$request)/i',
                'array<mixed> $1',
                $currentLine
            );
        }
    }

    // 修復回傳類型中的 array<mixed>
    if (strpos($context, 'return type') !== false && strpos($context, 'iterable type array<mixed>') !== false) {
        // 替換 ): array<mixed> 為 ): array<mixed>
        $currentLine = preg_replace(
            '/\):\s*array<mixed>\s*$/i',
            '): array<mixed>',
            $currentLine
        );

        // 也處理單行函式宣告
        $currentLine = preg_replace(
            '/\):\s*array<mixed>\s*\{/i',
            '): array<mixed> {',
            $currentLine
        );
    }

    // 修復屬性中的 array<mixed> 類型
    if (strpos($context, 'property') !== false && strpos($context, 'iterable type array<mixed>') !== false) {
        // 處理 private array<mixed> $property
        $currentLine = preg_replace(
            '/\b(private|protected|public)\s+array<mixed>\s+(\$\w+)/i',
            '$1 array<mixed> $2',
            $currentLine
        );
    }

    // 修復變數宣告中的 array<mixed> 類型標註
    if (preg_match('/@(var|param|return)\s+array<mixed>\s/', $currentLine)) {
        $currentLine = preg_replace(
            '/@(var|param|return)\s+array<mixed>\s+/',
            '@$1 array<mixed> ',
            $currentLine
        );
    }

    // 如果有變更，更新
    if ($currentLine !== $originalLine) {
        $lines[$lineIndex] = $currentLine;
        $totalFixes++;
        return implode("\n", $lines);
    }

    return $content;
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
    $fixCount = 0;

    // 運行 PHPStan 只針對這個檔案
    $relativeFilePath = str_replace('/var/www/html/', '', $filePath);
    $phpstanCmd = "cd /var/www/html && ./vendor/bin/phpstan analyse --memory-limit=256M --no-progress " . escapeshellarg($relativeFilePath) . " 2>&1";
    $phpstanOutput = shell_exec($phpstanCmd);

    if (!$phpstanOutput) {
        return;
    }

    // 解析 PHPStan 輸出中的 missingType.iterableValue 錯誤
    $lines = explode("\n", $phpstanOutput);
    $currentLine = 0;
    $currentContext = '';

    foreach ($lines as $outputLine) {
        // 捕獲行號
        if (preg_match('/^\s*(\d+)\s+/', $outputLine, $matches)) {
            $currentLine = (int)$matches[1];
        }

        // 捕獲錯誤上下文
        if (strpos($outputLine, 'iterable type array<mixed>') !== false) {
            $currentContext = $outputLine;
        }

        // 處理 missingType.iterableValue 錯誤
        if (strpos($outputLine, 'missingType.iterableValue') !== false && $currentLine > 0) {
            $newContent = fixMissingIterableValueType($content, $currentLine, $currentContext);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixCount++;
            }
        }
    }

    // 如果內容有變更，寫回檔案
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $filesFixes[$filePath] = $fixCount;
        echo "已處理: " . str_replace('/var/www/html/', '', $filePath) . " (修復 $fixCount 個問題)\n";
    }
}

// 取得所有 PHP 檔案
$finder = new Symfony\Component\Finder\Finder();
$finder->files()
    ->name('*.php')
    ->in($projectRoot . '/app')
    ->notPath('vendor')
    ->notPath('tests')
    ->notPath('storage');

echo "開始修復遺失的可迭代值類型錯誤...\n";
echo "===========================================\n";

foreach ($finder as $file) {
    processFile($file->getRealPath());
}

echo "\n===========================================\n";
echo "遺失的可迭代值類型修復完成！\n";
echo "總共修復: $totalFixes 個問題\n";
echo "處理的檔案: " . count($filesFixes) . " 個\n";

if (!empty($filesFixes)) {
    echo "\n詳細修復統計:\n";
    foreach ($filesFixes as $file => $count) {
        echo "- " . str_replace($projectRoot . '/', '', $file) . ": $count 個修復\n";
    }
}
