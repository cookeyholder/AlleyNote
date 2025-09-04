<?php
/**
 * 修復 "unexpected '<', expecting T_VARIABLE" 語法錯誤
 */

$rootDir = dirname(__DIR__);
$fixCount = 0;
$fileCount = 0;

// 搜尋所有 PHP 檔案
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$phpFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' &&
        !strpos($file->getPathname(), 'vendor/') &&
        !strpos($file->getPathname(), 'node_modules/') &&
        !strpos($file->getPathname(), 'storage/phpstan/cache/')) {
        $phpFiles[] = $file->getPathname();
    }
}

foreach ($phpFiles as $filePath) {
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $fileFixCount = 0;

    // 模式 1: 修復陣列泛型語法錯誤 (array<mixed> 改為 array<mixed>)
    if (preg_match_all('/array<mixed>]+>/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach (array_reverse($matches[0]) as $match) {
            $content = substr_replace($content, 'array<mixed>', $match[1], strlen($match[0]));
            $fileFixCount++;
            $fixCount++;
        }
    }

    // 模式 2: 修復變數後的泛型語法錯誤 (\$var 改為 \$var)
    if (preg_match_all('/\$[a-zA-Z_][a-zA-Z0-9_]*<[^>]+>/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach (array_reverse($matches[0]) as $match) {
            $varName = preg_replace('/<[^>]+>/', '', $match[0]);
            $content = substr_replace($content, $varName, $match[1], strlen($match[0]));
            $fileFixCount++;
            $fixCount++;
        }
    }

    // 模式 3: 修復類別實例化的泛型語法錯誤 (new Class 改為 new Class)
    if (preg_match_all('/new\s+[a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*<[^>]+>/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach (array_reverse($matches[0]) as $match) {
            $className = preg_replace('/<[^>]+>/', '', $match[0]);
            $content = substr_replace($content, $className, $match[1], strlen($match[0]));
            $fileFixCount++;
            $fixCount++;
        }
    }

    // 模式 4: 修復類型聲明中的泛型語法 (Type)
    if (preg_match_all('/\b[A-Z][a-zA-Z0-9_\\\\]*<[^>]+>/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach (array_reverse($matches[0]) as $match) {
            // 跳過已經處理過的 new 和 array<mixed> 情況
            if (strpos($match[0], 'array<mixed>') === 0) {
                continue;
            }

            $beforeMatch = substr($content, max(0, $match[1] - 4), 4);
            if (strpos($beforeMatch, 'new') !== false) {
                continue;
            }

            $typeName = preg_replace('/<[^>]+>/', '', $match[0]);
            $content = substr_replace($content, $typeName, $match[1], strlen($match[0]));
            $fileFixCount++;
            $fixCount++;
        }
    }

    // 模式 5: 修復參數類型提示中的泛型語法
    $content = preg_replace_callback(
        '/function\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\([^)]*\)/s',
        function($matches) use (&$fileFixCount, &$fixCount) {
            $funcDecl = $matches[0];
            $originalFuncDecl = $funcDecl;

            // 移除參數類型中的泛型語法
            $funcDecl = preg_replace('/([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)<[^>]+>(\s+\$)/', '$1$2', $funcDecl);

            if ($funcDecl !== $originalFuncDecl) {
                $changes = substr_count($originalFuncDecl, '<') - substr_count($funcDecl, '<');
                $fileFixCount += $changes;
                $fixCount += $changes;
            }

            return $funcDecl;
        },
        $content
    );

    // 模式 6: 修復屬性聲明中的泛型語法
    $content = preg_replace_callback(
        '/(private|protected|public)\s+([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)<[^>]+>(\s+\$[a-zA-Z_][a-zA-Z0-9_]*)/s',
        function($matches) use (&$fileFixCount, &$fixCount) {
            $fileFixCount++;
            $fixCount++;
            return $matches[1] . ' ' . $matches[2] . $matches[3];
        },
        $content
    );

    // 模式 7: 修復返回類型中的泛型語法
    $content = preg_replace_callback(
        '/:\s*([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)<[^>]+>/',
        function($matches) use (&$fileFixCount, &$fixCount) {
            $fileFixCount++;
            $fixCount++;
            return ': ' . $matches[1];
        },
        $content
    );

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $fileCount++;
        $relativePath = str_replace($rootDir . '/', '', $filePath);
        echo "修復 {$relativePath}: {$fileFixCount} 個語法錯誤\n";
    }
}

echo "✅ 總共修復了 {$fixCount} 個語法錯誤，處理了 {$fileCount} 個檔案\n";
