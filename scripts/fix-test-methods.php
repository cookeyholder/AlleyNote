#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 批量修復測試檔案的工具
 * 
 * 自動修復測試方法命名和添加基本測試到空白類別
 */

$projectRoot = dirname(__DIR__);

// 遞歸查找所有測試檔案
function findTestFiles($directory) {
    $testFiles = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && 
            (strpos($file->getFilename(), 'Test.php') !== false)) {
            $testFiles[] = $file->getPathname();
        }
    }
    
    return $testFiles;
}

// 修復測試方法命名
function fixTestMethodNames($content) {
    // 將沒有 test 前綴的 public function 加上 test 前綴
    $pattern = '/public function ([a-z][a-zA-Z0-9_]*)\(\): void/';
    
    $content = preg_replace_callback($pattern, function($matches) {
        $methodName = $matches[1];
        
        // 跳過已經有 test 前綴的方法
        if (strpos($methodName, 'test') === 0) {
            return $matches[0];
        }
        
        // 跳過 setUp, tearDown 等特殊方法
        $specialMethods = ['setUp', 'tearDown', 'setUpBeforeClass', 'tearDownAfterClass'];
        if (in_array($methodName, $specialMethods)) {
            return $matches[0];
        }
        
        // 添加 test 前綴
        $newMethodName = 'test' . ucfirst($methodName);
        return str_replace($methodName, $newMethodName, $matches[0]);
    }, $content);
    
    return $content;
}

// 檢查類別是否有任何測試方法
function hasTestMethods($content) {
    return preg_match('/public function test\w+\(/', $content) === 1;
}

// 為空白測試類別添加基本測試
function addPlaceholderTest($content) {
    // 找到類別結束的位置
    $pattern = '/(class\s+\w+Test\s+extends[^{]*\{[^}]*?)(}\s*$)/s';
    
    if (preg_match($pattern, $content)) {
        $testMethod = "\n    /**\n     * TODO: 實作實際的測試案例\n     */\n    public function testPlaceholder(): void\n    {\n        \$this->markTestIncomplete('此測試尚未實作');\n    }\n";
        
        $content = preg_replace($pattern, '$1' . $testMethod . '$2', $content);
    }
    
    return $content;
}

$testFiles = findTestFiles($projectRoot . '/tests');
$fixedFiles = [];
$errorFiles = [];

foreach ($testFiles as $filePath) {
    try {
        $originalContent = file_get_contents($filePath);
        $modifiedContent = $originalContent;
        
        // 修復方法命名
        $modifiedContent = fixTestMethodNames($modifiedContent);
        
        // 如果沒有測試方法，添加 placeholder
        if (!hasTestMethods($modifiedContent)) {
            $modifiedContent = addPlaceholderTest($modifiedContent);
        }
        
        // 如果有變更，寫入檔案
        if ($modifiedContent !== $originalContent) {
            if (file_put_contents($filePath, $modifiedContent)) {
                $fixedFiles[] = str_replace($projectRoot . '/', '', $filePath);
            } else {
                $errorFiles[] = str_replace($projectRoot . '/', '', $filePath);
            }
        }
        
    } catch (Exception $e) {
        $errorFiles[] = str_replace($projectRoot . '/', '', $filePath) . ' (錯誤: ' . $e->getMessage() . ')';
    }
}

echo "測試檔案修復完成！\n";
echo "已修復檔案數: " . count($fixedFiles) . "\n";

if (!empty($fixedFiles)) {
    echo "\n已修復的檔案:\n";
    foreach ($fixedFiles as $file) {
        echo "  - $file\n";
    }
}

if (!empty($errorFiles)) {
    echo "\n修復失敗的檔案:\n";
    foreach ($errorFiles as $file) {
        echo "  - $file\n";
    }
}