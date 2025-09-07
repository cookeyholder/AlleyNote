#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 自動語法錯誤修復腳本
 * 修復常見的語法問題：
 * 1. 重複的 public 關鍵字
 * 2. 不正確的註釋格式
 * 3. 換行符問題
 */

function findPhpFiles(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getRealPath();
        }
    }
    
    return $files;
}

function checkSyntax(string $file): bool
{
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnVar);
    return $returnVar === 0;
}

function fixSyntaxErrors(string $file): bool
{
    $content = file_get_contents($file);
    $originalContent = $content;
    $changed = false;

    // 修復重複的 public 關鍵字
    $patterns = [
        // 修復: public /** @var ... */\n public /** @var ... */ array $var
        '/public\s+\/\*\*\s*@var[^*]*\*\/\s*\\\\n\s*public\s+\/\*\*\s*@var[^*]*\*\/\s*array\s+(\$\w+)/m' => 'public array $1',
        
        // 修復: /** @var array<int, \n\n > */\n
        '/\/\*\*\s*@var\s+array<[^>]*,\s*\n\s*>\s*\*\/\s*\\\\n/m' => '',
        
        // 修復多餘的換行符和註釋
        '/\/\*\*\s*@var[^*]*\*\/\s*\\\\n\s*return/m' => 'return',
        
        // 修復格式錯誤的註釋
        '/\/\*\*\s*@var[^*]*\n\s*>\s*\*\/\s*\\\\n/m' => '',
    ];

    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $changed = true;
        }
    }

    if ($changed) {
        file_put_contents($file, $content);
        echo "修復檔案: $file\n";
        return true;
    }

    return false;
}

// 主程式
$appDirectory = '/var/www/html/app';
$files = findPhpFiles($appDirectory);

$syntaxErrors = [];
$fixedFiles = [];

echo "檢查語法錯誤...\n";

foreach ($files as $file) {
    if (!checkSyntax($file)) {
        $syntaxErrors[] = $file;
        echo "語法錯誤: $file\n";
        
        // 嘗試修復
        if (fixSyntaxErrors($file)) {
            $fixedFiles[] = $file;
            
            // 再次檢查是否修復成功
            if (checkSyntax($file)) {
                echo "成功修復: $file\n";
            } else {
                echo "修復失敗: $file\n";
            }
        }
    }
}

echo "\n=== 摘要 ===\n";
echo "檢查檔案數量: " . count($files) . "\n";
echo "發現語法錯誤: " . count($syntaxErrors) . "\n";
echo "成功修復檔案: " . count($fixedFiles) . "\n";

if (!empty($syntaxErrors)) {
    echo "\n仍有語法錯誤的檔案:\n";
    foreach ($syntaxErrors as $file) {
        if (!in_array($file, $fixedFiles, true)) {
            echo "- $file\n";
        }
    }
}
