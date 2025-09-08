<?php

declare(strict_types=1);

/**
 * 修復字串插值語法錯誤腳本
 *
 * 此腳本用於修復錯誤的字串插值語法，特別是 sprintf 和字串插值混合的問題
 */

class StringInterpolationSyntaxFixer
{
    private int $fixedFiles = 0;
    private int $totalFixes = 0;
    private array $fixedFilesList = [];

    public function __construct()
    {
        echo "🔧 開始修復字串插值語法錯誤...\n\n";
    }

    /**
     * 執行修復
     */
    public function run(): void
    {
        $this->findAndFixFiles();
        $this->printSummary();
    }

    /**
     * 尋找並修復檔案
     */
    private function findAndFixFiles(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('tests', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }

        // 也檢查 app 目錄
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('app', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }

    /**
     * 處理單個檔案
     */
    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixCount = 0;

        // 修復錯誤的 sprintf 語法
        $content = $this->fixMalformedSprintf($content, $fixCount);

        // 修復錯誤的字串插值
        $content = $this->fixMalformedStringInterpolation($content, $fixCount);

        // 修復錯誤的 %s 模式
        $content = $this->fixMalformedPercentS($content, $fixCount);

        if ($fixCount > 0) {
            file_put_contents($filePath, $content);
            $this->fixedFiles++;
            $this->totalFixes += $fixCount;
            $this->fixedFilesList[] = [
                'file' => $filePath,
                'fixes' => $fixCount
            ];
            echo "✅ 修復 {$filePath}: {$fixCount} 個修復\n";
        }
    }

    /**
     * 修復錯誤的 sprintf 語法
     */
    private function fixMalformedSprintf(string $content, int &$fixCount): string
    {
        $lines = explode("\n", $content);
        $modified = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 修復模式: $logs = %s->pdo->query(", is_string($this) ? $this : '')
            if (preg_match('/\$\w+\s*=\s*%s->/', $line)) {
                // 找到對應的 sprintf 結尾
                $j = $i;
                $sprintfFound = false;
                while ($j < count($lines) && $j < $i + 10) {
                    if (strpos($lines[$j], 'sprintf(') !== false) {
                        $sprintfFound = true;
                        break;
                    }
                    $j++;
                }

                if ($sprintfFound) {
                    // 提取變數名和方法調用
                    if (preg_match('/(\$\w+)\s*=\s*%s->(.+)$/', $line, $matches)) {
                        $varName = $matches[1];
                        $methodCall = $matches[2];

                        // 重構為正確的語法
                        $lines[$i] = $varName . ' = $this->' . $methodCall;
                        $modified = true;
                        $fixCount++;
                    }
                }
            }

            // 修復其他錯誤的 %s 模式
            if (strpos($line, '%s->') !== false && strpos($line, 'sprintf') === false) {
                $line = str_replace('%s->', '$this->', $line);
                if ($line !== $lines[$i]) {
                    $lines[$i] = $line;
                    $modified = true;
                    $fixCount++;
                }
            }
        }

        if ($modified) {
            $content = implode("\n", $lines);
        }

        return $content;
    }

    /**
     * 修復錯誤的字串插值
     */
    private function fixMalformedStringInterpolation(string $content, int &$fixCount): string
    {
        // 修復模式: ", is_string($this) ? $this : '') 在字串中間
        $pattern = '/",\s*is_string\(\$this\)\s*\?\s*\$this\s*:\s*\'\'\)\s*[\r\n]/';
        $replacement = "\"\n";

        $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($newContent !== null && $count > 0) {
            $content = $newContent;
            $fixCount += $count;
        }

        // 修復 sprintf(" 開頭的錯誤模式
        $pattern = '/sprintf\("(\s*[\r\n])/';
        $replacement = "\"$1";

        $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($newContent !== null && $count > 0) {
            $content = $newContent;
            $fixCount += $count;
        }

        return $content;
    }

    /**
     * 修復錯誤的 %s 模式
     */
    private function fixMalformedPercentS(string $content, int &$fixCount): string
    {
        $lines = explode("\n", $content);
        $modified = false;
        $i = 0;

        while ($i < count($lines)) {
            $line = $lines[$i];

            // 檢查是否有 %s-> 開頭的行
            if (preg_match('/^\s*%s->/', $line)) {
                // 這可能是一個錯誤的字串插值，應該是 $this->
                $line = preg_replace('/^\s*%s->/', '$this->', $line);
                $lines[$i] = $line;
                $modified = true;
                $fixCount++;
            }

            // 檢查多行的錯誤模式
            if (strpos($line, '", is_string($this) ? $this : \'\')') !== false) {
                // 移除這個錯誤的插值
                $line = str_replace('", is_string($this) ? $this : \'\')', '"', $line);
                $lines[$i] = $line;
                $modified = true;
                $fixCount++;

                // 檢查下一行是否是相關的 SQL 或其他內容
                if ($i + 1 < count($lines)) {
                    $nextLine = trim($lines[$i + 1]);
                    if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|WHERE|ORDER|LIMIT)/i', $nextLine)) {
                        // 這看起來像是 SQL，可能需要連接
                        $lines[$i] = rtrim($line, '"') . ' . "';
                    }
                }
            }

            // 檢查 sprintf(" 後面跟著換行的模式
            if (strpos($line, 'sprintf("') !== false && preg_match('/sprintf\("\s*$/', $line)) {
                // 移除 sprintf(" 只保留引號
                $line = preg_replace('/sprintf\("/', '"', $line);
                $lines[$i] = $line;
                $modified = true;
                $fixCount++;
            }

            $i++;
        }

        if ($modified) {
            $content = implode("\n", $lines);
        }

        return $content;
    }

    /**
     * 列印摘要報告
     */
    private function printSummary(): void
    {
        echo "\n📊 修復報告:\n";
        echo "總共修復了 {$this->fixedFiles} 個檔案中的 {$this->totalFixes} 個字串插值語法錯誤\n\n";

        if (!empty($this->fixedFilesList)) {
            echo "修復詳情:\n";
            foreach ($this->fixedFilesList as $fileInfo) {
                echo "  {$fileInfo['file']}: {$fileInfo['fixes']} 個修復\n";
            }
        }

        echo "\n✅ 修復完成！建議執行 PHPStan 和測試檢查結果。\n";
    }
}

// 執行修復
$fixer = new StringInterpolationSyntaxFixer();
$fixer->run();
