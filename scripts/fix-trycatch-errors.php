<?php

declare(strict_types=1);

/**
 * 修復 try-catch 結構錯誤的腳本
 */

class TryCatchFixer
{
    private int $totalFixed = 0;
    private int $filesProcessed = 0;

    public function __construct(
        private string $projectRoot
    ) {}

    public function fixAllTryCatchErrors(): void
    {
        echo "開始修復 try-catch 結構錯誤...\n";

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot . '/app')
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $this->fixFile($file->getPathname());
        }

        echo "修復完成！處理了 {$this->filesProcessed} 個檔案，修復了 {$this->totalFixed} 個語法錯誤。\n";
    }

    private function fixFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $originalContent = $content;
        $fixCount = 0;

        $lines = explode("\n", $content);
        $cleanedLines = [];
        $inTryBlock = false;
        $tryStartLine = -1;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 檢測 try 開始
            if (preg_match('/^\s*try\s*\{/', $line)) {
                $inTryBlock = true;
                $tryStartLine = $i;
                $cleanedLines[] = $line;
                continue;
            }

            // 如果我們在 try 塊中，檢查是否有對應的 catch 或 finally
            if ($inTryBlock) {
                // 檢查當前行是否是 catch 或 finally
                if (preg_match('/^\s*(catch|finally)\s*[\(\{]/', $line)) {
                    $inTryBlock = false;
                    $cleanedLines[] = $line;
                    continue;
                }

                // 檢查是否是 try 塊的結束（找到對應的右大括號）
                if (preg_match('/^\s*\}\s*$/', $line)) {
                    // 檢查下一行是否是 catch 或 finally
                    $nextLineIsCatch = false;
                    if ($i + 1 < count($lines)) {
                        $nextLine = trim($lines[$i + 1]);
                        if (preg_match('/^(catch|finally)/', $nextLine)) {
                            $nextLineIsCatch = true;
                        }
                    }

                    if (!$nextLineIsCatch) {
                        // 沒有 catch 或 finally，添加一個默認的 catch
                        $indent = str_repeat(' ', strlen($line) - strlen(ltrim($line)));
                        $cleanedLines[] = $line; // 原本的 }
                        $cleanedLines[] = $indent . 'catch (\\Exception $e) {';
                        $cleanedLines[] = $indent . '    // 預設錯誤處理';
                        $cleanedLines[] = $indent . '    throw $e;';
                        $cleanedLines[] = $indent . '}';
                        $inTryBlock = false;
                        $fixCount++;
                        continue;
                    }
                }
            }

            $cleanedLines[] = $line;
        }

        $content = implode("\n", $cleanedLines);

        // 只有在內容真的有改變時才寫入檔案
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->totalFixed += $fixCount;
            $this->filesProcessed++;

            $relativePath = str_replace($this->projectRoot . '/', '', $filePath);
            echo "修復了 {$relativePath} 中的 {$fixCount} 個 try-catch 語法錯誤\n";
        }
    }
}

// 執行修復
$fixer = new TryCatchFixer('/var/www/html');
$fixer->fixAllTryCatchErrors();
