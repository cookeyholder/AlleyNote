#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 解析錯誤修復腳本
 *
 * 修復所有剩餘的 PHP 語法解析錯誤
 */

class ParseErrorFixer
{
    private string $baseDir;
    private array $fixedFiles = [];

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🔧 修復 PHP 語法解析錯誤...\n\n";

        // 獲取有解析錯誤的檔案
        $errorFiles = $this->getParseErrorFiles();

        foreach ($errorFiles as $file) {
            $this->fixParseErrors($file);
        }

        echo "\n✅ 解析錯誤修復完成！\n";
        echo "修復檔案數: " . count($this->fixedFiles) . "\n";
    }

    private function getParseErrorFiles(): array
    {
        $command = './vendor/bin/phpstan analyse --memory-limit=1G 2>&1';
        $output = shell_exec($command);

        if (!$output) {
            return [];
        }

        $files = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (preg_match('/^\s*Line\s+(.+\.php)/', $line, $matches)) {
                $file = trim($matches[1]);
                if (!in_array($file, $files)) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    private function fixParseErrors(string $file): void
    {
        $filePath = $this->baseDir . '/' . $file;
        if (!file_exists($filePath)) {
            return;
        }

        echo "🔧 修復: $file\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復各種解析錯誤
        $content = $this->fixEscapedCharacters($content);
        $content = $this->fixMalformedComments($content);
        $content = $this->fixDocBlockIssues($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = $file;
            echo "  ✅ 已修復\n";
        } else {
            echo "  ℹ️  無需修復\n";
        }
    }

    private function fixEscapedCharacters(string $content): string
    {
        // 修復錯誤的轉義序列
        $content = str_replace('\\n     *', "\n     *", $content);
        $content = str_replace('\\n    *', "\n    *", $content);
        $content = str_replace('\\n      */', "\n      */", $content);
        $content = str_replace('\\n    */', "\n    */", $content);
        $content = str_replace('\\n     */', "\n     */", $content);
        $content = str_replace('\\n    public', "\n    public", $content);
        $content = str_replace('\\n    private', "\n    private", $content);
        $content = str_replace('\\n    protected', "\n    protected", $content);

        return $content;
    }

    private function fixMalformedComments(string $content): string
    {
        // 修復格式錯誤的註解
        $lines = explode("\n", $content);
        $fixedLines = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 修復包含 @return 但格式錯誤的行
            if (preg_match('/^\s*\*\s*@return\s+/', $line)) {
                // 確保前面有正確的文檔塊開始
                if ($i > 0 && !preg_match('/^\s*\/\*\*/', $lines[$i-1]) && !preg_match('/^\s*\*/', $lines[$i-1])) {
                    $indent = $this->getLineIndentation($line);
                    array_splice($fixedLines, -1, 0, [$indent . '/**']);
                }

                // 確保後面有正確的文檔塊結束
                if ($i < count($lines) - 1) {
                    $nextLine = $lines[$i + 1];
                    if (!preg_match('/^\s*\*/', $nextLine) && !preg_match('/^\s*\*\//', $nextLine)) {
                        $fixedLines[] = $line;
                        $indent = $this->getLineIndentation($line);
                        $fixedLines[] = $indent . ' */';
                        continue;
                    }
                }
            }

            $fixedLines[] = $line;
        }

        return implode("\n", $fixedLines);
    }

    private function fixDocBlockIssues(string $content): string
    {
        // 修復重複或格式錯誤的文檔塊
        $content = preg_replace('/(\s*)\/\*\*\s*\n(\s*)\/\*\*\s*\n/m', '$1/**\n', $content);
        $content = preg_replace('/(\s*)\*\/\s*\n(\s*)\*\/\s*\n/m', '$1 */\n', $content);

        // 修復孤立的 @return 行
        $content = preg_replace('/^(\s*)\*\s*@return\s+([^\n]+)\n(\s*)(public|private|protected)/m', '$1/**\n$1 * @return $2\n$1 */\n$3$4', $content);

        return $content;
    }

    private function getLineIndentation(string $line): string
    {
        preg_match('/^(\s*)/', $line, $matches);
        return $matches[1] ?? '    ';
    }
}

// 執行修復
$fixer = new ParseErrorFixer();
$fixer->run();
