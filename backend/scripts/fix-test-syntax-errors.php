<?php

declare(strict_types=1);

/**
 * 修復測試檔案語法錯誤腳本
 *
 * 此腳本用於修復測試檔案中的語法錯誤，特別是 sprintf 和字串插值的問題
 */

class TestSyntaxErrorFixer
{
    private int $fixedFiles = 0;
    private int $totalFixes = 0;
    private array $fixedFilesList = [];

    public function __construct()
    {
        echo "🔧 開始修復測試檔案語法錯誤...\n\n";
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

        // 修復各種語法錯誤
        $content = $this->fixMalformedSprintf($content, $fixCount);
        $content = $this->fixMalformedStringPatterns($content, $fixCount);
        $content = $this->fixBrokenQueries($content, $fixCount);
        $content = $this->fixPercentSPatterns($content, $fixCount);

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
        // 修復模式: $stmt = $this->db->prepare("SELECT ... ?sprintf(");
        $pattern = '/\$(\w+)\s*=\s*\$this->db->prepare\("([^"]+)\?sprintf\("\);/';
        $replacement = '$${1} = $this->db->prepare("${2}?");';

        $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($newContent !== null && $count > 0) {
            $content = $newContent;
            $fixCount += $count;
        }

        // 修復模式: ...sprintf("); 結尾
        $pattern = '/sprintf\("\);/';
        $replacement = '");';

        $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($newContent !== null && $count > 0) {
            $content = $newContent;
            $fixCount += $count;
        }

        return $content;
    }

    /**
     * 修復錯誤的字串模式
     */
    private function fixMalformedStringPatterns(string $content, int &$fixCount): string
    {
        // 修復模式: $this->assertNotEmpty(%s, ", is_string($exists) ? $exists : '')Required index...
        $pattern = '/\$this->assertNotEmpty\(%s,\s*",\s*is_string\([^)]+\)\s*\?\s*[^:]+:\s*\'\'?\)([^s]+sprintf\(")/';
        $replacement = '$this->assertNotEmpty($exists, "$1");';

        $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($newContent !== null && $count > 0) {
            $content = $newContent;
            $fixCount += $count;
        }

        // 修復模式: ", is_string($var) ? $var : '')message
        $pattern = '/",\s*is_string\([^)]+\)\s*\?\s*[^:]+:\s*\'\'?\)([^s]*)/';
        $replacement = '", "$1");';

        $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($newContent !== null && $count > 0) {
            $content = $newContent;
            $fixCount += $count;
        }

        return $content;
    }

    /**
     * 修復錯誤的查詢語法
     */
    private function fixBrokenQueries(string $content, int &$fixCount): string
    {
        $lines = explode("\n", $content);
        $modified = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 修復模式: ...WHERE type='index' AND name = ?sprintf(");
            if (strpos($line, '?sprintf("') !== false) {
                $line = str_replace('?sprintf(")', '?");', $line);
                $lines[$i] = $line;
                $modified = true;
                $fixCount++;
            }

            // 修復模式: $this->assertNotEmpty(%s, ", ...
            if (preg_match('/\$this->assertNotEmpty\(%s,\s*",/', $line)) {
                // 找到正確的變數名稱
                if (preg_match('/\$(\w+)\s*=\s*\$stmt->fetch/', $lines[$i-1] ?? '', $matches)) {
                        $varName = '$' . $matches[1];
                    $line = preg_replace('/\$this->assertNotEmpty\(%s,\s*",/', '$this->assertNotEmpty(' . $varName . ', "', $line);
                } else {
                    // 預設使用 $exists
                    $line = preg_replace('/\$this->assertNotEmpty\(%s,\s*",/', '$this->assertNotEmpty($exists, "', $line);
                }
                $lines[$i] = $line;
                $modified = true;
                $fixCount++;
            }

            // 修復錯誤的 sprintf 模式
            if (strpos($line, 'sprintf("') !== false && strpos($line, '"') === strrpos($line, '"')) {
                // 看起來像是未完成的 sprintf，移除它
                $line = str_replace('sprintf("', '"', $line);
                $lines[$i] = $line;
                $modified = true;
                $fixCount++;
            }
        }

        if ($modified) {
            $content = implode("\n", $lines);
        }

        return $content;
    }

    /**
     * 修復 %s 模式
     */
    private function fixPercentSPatterns(string $content, int &$fixCount): string
    {
        // 修復孤立的 %s
        $pattern = '/\b%s\b(?!\s*->)/';
        $replacement = '$this';

        $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
        if ($newContent !== null && $count > 0) {
            $content = $newContent;
            $fixCount += $count;
        }

        // 修復特定的錯誤模式
        $patterns = [
            // 修復 %s-> 模式
            '/%s->/' => '$this->',

            // 修復 array_key_exists('%s', 模式
            "/array_key_exists\\('%s',/" => "array_key_exists('key',",

            // 修復其他常見的 %s 錯誤
            "/\\('%s'\\)/" => "('value')",
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content, -1, $count);
            if ($newContent !== null && $count > 0) {
                $content = $newContent;
                $fixCount += $count;
            }
        }

        return $content;
    }

    /**
     * 列印摘要報告
     */
    private function printSummary(): void
    {
        echo "\n📊 修復報告:\n";
        echo "總共修復了 {$this->fixedFiles} 個檔案中的 {$this->totalFixes} 個語法錯誤\n\n";

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
$fixer = new TestSyntaxErrorFixer();
$fixer->run();
