#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 自動修復 PHPStan Level 10 錯誤 - Array 型別規格批量修復工具
 */

function main(): void
{
    $baseDir = '/var/www/html/app';
    
    if (!is_dir($baseDir)) {
        echo "錯誤：找不到 app 目錄\n";
        exit(1);
    }
    
    echo "開始批量修復 Array 型別規格...\n";
    
    // 定義需要修復的模式
    $patterns = [
        // 修復建構函式參數
        [
            'pattern' => '/(\s+)public array \$(\w+),/',
            'replacement' => '$1/** @var array<string, mixed> */\n$1public array $$2,',
            'description' => '修復建構函式陣列屬性'
        ],
        
        // 修復方法參數
        [
            'pattern' => '/(\s+array \$\w+)(\s*=\s*\[\])?([,\)])/',
            'replacement' => '$1/** @var array<string, mixed> */$2$3',
            'description' => '修復方法參數陣列型別'
        ],
        
        // 修復回傳型別
        [
            'pattern' => '/(\s+\*\s+@return\s+)array(\s*)/',
            'replacement' => '$1array<string, mixed>$2',
            'description' => '修復回傳型別註解'
        ],
    ];
    
    $totalFiles = 0;
    $totalChanges = 0;
    
    // 遞迴處理所有 PHP 檔案
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        foreach ($patterns as $pattern) {
            $content = preg_replace(
                $pattern['pattern'],
                $pattern['replacement'],
                $content
            );
        }
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $totalFiles++;
            $changes = substr_count($content, '/** @var array<string, mixed> */') - 
                      substr_count($originalContent, '/** @var array<string, mixed> */');
            $totalChanges += $changes;
            
            echo "修復檔案: " . str_replace($baseDir . '/', '', $filePath) . " ({$changes} 處修改)\n";
        }
    }
    
    echo "\n修復完成！\n";
    echo "修復檔案數: {$totalFiles}\n";
    echo "總修改數: {$totalChanges}\n";
}

if (php_sapi_name() === 'cli') {
    main();
}
