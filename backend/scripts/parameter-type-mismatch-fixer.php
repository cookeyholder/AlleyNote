<?php
declare(strict_types=1);

/**
 * Parameter Type Mismatch Fixer
 *
 * 修復常見的參數類型不匹配錯誤，例如：
 * - expects string, string|null given
 * - expects array<mixed>, list<string> given
 * - expects int, int|null given
 */

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/vendor/autoload.php';

// 計數器
$totalFixes = 0;
$filesFixes = [];

/**
 * 在函數呼叫前加上 null 檢查或型別轉換
 */
function fixParameterTypeMismatch($content, $line, $error) {
    global $totalFixes, $filesFixes;

    $lines = explode("\n", $content);
    $lineIndex = $line - 1;

    if (!isset($lines[$lineIndex])) {
        return $content;
    }

    $currentLine = $lines[$lineIndex];
    $originalLine = $currentLine;

    // 修復 expects string, string|null given
    if (preg_match('/Parameter #\d+ \$\w+ of function (\w+) expects string, string\|null given/', $error)) {
        // 提取函數名稱
        if (preg_match('/function (\w+)/', $error, $matches)) {
            $functionName = $matches[1];

            // 尋找函數呼叫並加上 null 檢查
            if (preg_match("/($functionName\s*\(\s*)([^,)]+)/", $currentLine, $funcMatches)) {
                $beforeFunc = $funcMatches[1];
                $parameter = $funcMatches[2];

                // 如果參數不是已經有 null 檢查，加上 ?? ''
                if (!strpos($parameter, '??') && !strpos($parameter, '!== null')) {
                    $newParameter = "($parameter ?? '')";
                    $currentLine = str_replace($beforeFunc . $parameter, $beforeFunc . $newParameter, $currentLine);
                }
            }
        }
    }

    // 修復 expects int, int|null given
    if (preg_match('/Parameter #\d+ \$\w+ of function (\w+) expects int, int\|null given/', $error)) {
        if (preg_match('/function (\w+)/', $error, $matches)) {
            $functionName = $matches[1];

            if (preg_match("/($functionName\s*\(\s*)([^,)]+)/", $currentLine, $funcMatches)) {
                $beforeFunc = $funcMatches[1];
                $parameter = $funcMatches[2];

                if (!strpos($parameter, '??') && !strpos($parameter, '!== null')) {
                    $newParameter = "($parameter ?? 0)";
                    $currentLine = str_replace($beforeFunc . $parameter, $beforeFunc . $newParameter, $currentLine);
                }
            }
        }
    }

    // 修復 expects array<mixed>, list<string> given
    if (preg_match('/Parameter #\d+ \$\w+ of function (\w+) expects array<mixed>, list<string> given/', $error)) {
        if (preg_match('/function (\w+)/', $error, $matches)) {
            $functionName = $matches[1];

            if (preg_match("/($functionName\s*\(\s*)([^,)]+)/", $currentLine, $funcMatches)) {
                $beforeFunc = $funcMatches[1];
                $parameter = $funcMatches[2];

                // 將 list 轉換為 array<mixed>（通常不需要特殊處理，但加上型別轉換以確保）
                if (!strpos($parameter, '(array<mixed>)')) {
                    $newParameter = "(array<mixed>) $parameter";
                    $currentLine = str_replace($beforeFunc . $parameter, $beforeFunc . $newParameter, $currentLine);
                }
            }
        }
    }

    // 修復 expects bool, int|null given 等類似錯誤
    if (preg_match('/Parameter #\d+ \$\w+ of function (\w+) expects bool, /', $error)) {
        if (preg_match('/function (\w+)/', $error, $matches)) {
            $functionName = $matches[1];

            if (preg_match("/($functionName\s*\(\s*)([^,)]+)/", $currentLine, $funcMatches)) {
                $beforeFunc = $funcMatches[1];
                $parameter = $funcMatches[2];

                if (!strpos($parameter, '(bool)')) {
                    $newParameter = "(bool) ($parameter ?? false)";
                    $currentLine = str_replace($beforeFunc . $parameter, $beforeFunc . $newParameter, $currentLine);
                }
            }
        }
    }

    // 如果有變更，更新行數
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

    // 運行 PHPStan 只針對這個檔案
    $phpstanCmd = "cd /var/www/html && ./vendor/bin/phpstan analyse --memory-limit=256M --no-progress " . escapeshellarg($filePath) . " 2>&1";
    $phpstanOutput = shell_exec($phpstanCmd);

    if (!$phpstanOutput) {
        return;
    }

    // 解析 PHPStan 輸出中的參數類型錯誤
    $lines = explode("\n", $phpstanOutput);
    foreach ($lines as $outputLine) {
        if (preg_match('/^\s*(\d+)\s+Parameter.*expects\s+(string|int|bool|array<mixed>).*given/', $outputLine, $matches)) {
            $lineNumber = (int)$matches[1];
            $content = fixParameterTypeMismatch($content, $lineNumber, $outputLine);
        }
    }

    // 如果內容有變更，寫回檔案
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $filesFixes[$filePath] = ($filesFixes[$filePath] ?? 0) + substr_count($content, '??') - substr_count($originalContent, '??');
        echo "已處理: $filePath (修復 " . $filesFixes[$filePath] . " 個問題)\n";
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

echo "開始修復參數類型不匹配錯誤...\n";
echo "===========================================\n";

foreach ($finder as $file) {
    processFile($file->getRealPath());
}

echo "\n===========================================\n";
echo "參數類型不匹配修復完成！\n";
echo "總共修復: $totalFixes 個問題\n";
echo "處理的檔案: " . count($filesFixes) . " 個\n";

if (!empty($filesFixes)) {
    echo "\n詳細修復統計:\n";
    foreach ($filesFixes as $file => $count) {
        echo "- " . str_replace($projectRoot . '/', '', $file) . ": $count 個修復\n";
    }
}
