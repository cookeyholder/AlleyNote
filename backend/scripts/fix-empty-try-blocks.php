<?php

declare(strict_types=1);

/**
 * 修復空 try 塊語法錯誤腳本
 *
 * 此腳本用於修復 try { 這類空 try 塊造成的語法錯誤
 */

class EmptyTryBlockFixer
{
    private int $fixedFiles = 0;
    private int $totalFixes = 0;
    private array $fixedFilesList = [];

    public function __construct()
    {
        echo "🔧 開始修復空 try 塊語法錯誤...\n\n";
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

        // 修復空 try 塊
        $content = $this->fixEmptyTryBlocks($content, $fixCount);

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
     * 修復空 try 塊
     */
    private function fixEmptyTryBlocks(string $content, int &$fixCount): string
    {
        $lines = explode("\n", $content);
        $modified = false;
        $i = 0;

        while ($i < count($lines)) {
            $line = trim($lines[$i]);

            // 檢查是否是空 try 塊模式
            if (preg_match('/try\s*\{\s*\/\*\s*empty\s*\*\/\s*\}/', $line)) {
                // 找到對應的程式碼塊
                $codeBlockStart = $i + 1;
                $codeBlockEnd = $this->findCodeBlockEnd($lines, $codeBlockStart);

                if ($codeBlockEnd !== -1) {
                    // 重構為正確的 try-catch 語法
                    $indent = str_repeat(' ', strlen($lines[$i]) - strlen(ltrim($lines[$i])));

                    // 替換 try 行
                    $lines[$i] = $indent . 'try {';

                    // 找到註解的 catch 塊位置並添加真正的 catch
                    $catchPosition = $this->findCatchCommentPosition($lines, $codeBlockEnd);

                    if ($catchPosition !== -1) {
                        // 移除註解的 catch 行
                        if (strpos($lines[$catchPosition], '// catch block commented out') !== false) {
                            unset($lines[$catchPosition]);
                        }

                        // 在正確位置插入 catch 塊
                        $catchBlock = [
                            $indent . '} catch (Exception $e) {',
                            $indent . '    return $this->json($response, [',
                            $indent . '        \'error\' => \'操作失敗\',',
                            $indent . '        \'message\' => $e->getMessage(),',
                            $indent . '    ], 500);',
                            $indent . '}'
                        ];

                        array_splice($lines, $catchPosition, 0, $catchBlock);
                    } else {
                        // 如果沒找到註解，在程式碼塊結束後添加 catch
                        $catchBlock = [
                            $indent . '} catch (Exception $e) {',
                            $indent . '    return $this->json($response, [',
                            $indent . '        \'error\' => \'操作失敗\',',
                            $indent . '        \'message\' => $e->getMessage(),',
                            $indent . '    ], 500);',
                            $indent . '}'
                        ];

                        array_splice($lines, $codeBlockEnd + 1, 0, $catchBlock);
                    }

                    $modified = true;
                    $fixCount++;
                }
            }

            $i++;
        }

        if ($modified) {
            $content = implode("\n", $lines);
            // 重新索引陣列鍵以移除 unset 造成的空隙
            $lines = array_values(explode("\n", $content));
            $content = implode("\n", $lines);
        }

        return $content;
    }

    /**
     * 找到程式碼塊的結束位置
     */
    private function findCodeBlockEnd(array $lines, int $start): int
    {
        $braceCount = 0;
        $inTryBlock = false;

        for ($i = $start; $i < count($lines); $i++) {
            $line = trim($lines[$i]);

            // 跳過空行和註解
            if (empty($line) || strpos($line, '//') === 0 || strpos($line, '/*') === 0) {
                continue;
            }

            // 檢查是否是 return 語句（通常是 try 塊的結束）
            if (strpos($line, 'return ') === 0) {
                $inTryBlock = true;
                // 繼續尋找可能的結束
            }

            // 檢查是否是 } 結尾（方法結束）
            if (preg_match('/^\s*}\s*(\/\/.*)?$/', $lines[$i]) && $inTryBlock) {
                return $i - 1; // 返回 return 語句的位置
            }

            // 檢查是否是下一個方法開始
            if (preg_match('/^\s*(public|private|protected)\s+function/', $line)) {
                return $i - 1;
            }
        }

        return -1;
    }

    /**
     * 找到註解的 catch 塊位置
     */
    private function findCatchCommentPosition(array $lines, int $searchStart): int
    {
        for ($i = $searchStart; $i < min($searchStart + 5, count($lines)); $i++) {
            if (strpos($lines[$i], '// catch block commented out') !== false ||
                strpos($lines[$i], '} // catch') !== false) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * 列印摘要報告
     */
    private function printSummary(): void
    {
        echo "\n📊 修復報告:\n";
        echo "總共修復了 {$this->fixedFiles} 個檔案中的 {$this->totalFixes} 個空 try 塊\n\n";

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
$fixer = new EmptyTryBlockFixer();
$fixer->run();
