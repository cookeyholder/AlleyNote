<?php
declare(strict_types=1);

/**
 * 終極 missingType.iterableValue 修復工具
 * 
 * 專門修復 PHPStan missingType.iterableValue 錯誤，
 * 使用更強大的模式匹配和上下文分析
 */

function scanDirectory(string $dir): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

function fixIterableValueTypes(string $content): array
{
    $fixes = [];
    $lines = explode("\n", $content);
    $fixed = false;
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $originalLine = $line;
        
        // 1. 方法參數修復 - 更精確的模式
        if (preg_match('/^(\s*)(public|private|protected)?\s*(static)?\s*function\s+(\w+)\s*\(/', $line)) {
            // 檢查參數中的 array 型別
            $pattern = '/(\$\w+)\s*:\s*array(?!\<)/';
            if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $paramName = $matches[1][array_search($match, $matches[0])][0];
                    
                    // 根據參數名稱推測類型
                    $arrayType = guessArrayTypeFromParamName($paramName);
                    $line = str_replace($match[0], $paramName . ': ' . $arrayType, $line);
                }
            }
        }
        
        // 2. 方法返回類型修復
        if (preg_match('/:\s*array\s*$/', $line) || preg_match('/:\s*array\s*(?=\s*{)/', $line)) {
            // 檢查方法名稱來推測返回類型
            if (preg_match('/function\s+(\w+)/', $line, $matches)) {
                $methodName = $matches[1];
                $arrayType = guessArrayTypeFromMethodName($methodName);
                $line = preg_replace('/:\s*array(\s*)$/', ': ' . $arrayType . '$1', $line);
                $line = preg_replace('/:\s*array(\s*(?=\s*{))/', ': ' . $arrayType . '$1', $line);
            }
        }
        
        // 3. 屬性宣告修復
        if (preg_match('/^(\s*)(public|private|protected)\s+(\w+\s+)?\$\w+\s*:\s*array/', $line)) {
            if (preg_match('/\$(\w+)\s*:\s*array(?!\<)/', $line, $matches)) {
                $propName = $matches[1];
                $arrayType = guessArrayTypeFromPropertyName($propName);
                $line = preg_replace('/:\s*array(?!\<)/', ': ' . $arrayType, $line);
            }
        }
        
        // 4. 介面方法修復
        if (preg_match('/^(\s*)public\s+function\s+(\w+)/', $line) && 
            strpos($content, 'interface') !== false) {
            // 介面方法參數
            $pattern = '/(\$\w+)\s*:\s*array(?!\<)/';
            if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $paramName = $matches[1][array_search($match, $matches[0])][0];
                    $arrayType = guessArrayTypeFromParamName($paramName);
                    $line = str_replace($match[0], $paramName . ': ' . $arrayType, $line);
                }
            }
            
            // 介面方法返回類型
            if (preg_match('/:\s*array\s*;/', $line)) {
                if (preg_match('/function\s+(\w+)/', $line, $matches)) {
                    $methodName = $matches[1];
                    $arrayType = guessArrayTypeFromMethodName($methodName);
                    $line = preg_replace('/:\s*array(\s*;)/', ': ' . $arrayType . '$1', $line);
                }
            }
        }
        
        // 5. 建構函數參數特別處理
        if (preg_match('/function\s+__construct/', $line)) {
            $pattern = '/(\$\w+)\s*:\s*array(?!\<)/';
            if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $paramName = $matches[1][array_search($match, $matches[0])][0];
                    $arrayType = guessArrayTypeFromConstructorParam($paramName);
                    $line = str_replace($match[0], $paramName . ': ' . $arrayType, $line);
                }
            }
        }
        
        // 6. 靜態方法修復
        if (preg_match('/static\s+function/', $line)) {
            $pattern = '/(\$\w+)\s*:\s*array(?!\<)/';
            if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $paramName = $matches[1][array_search($match, $matches[0])][0];
                    $arrayType = guessArrayTypeFromParamName($paramName);
                    $line = str_replace($match[0], $paramName . ': ' . $arrayType, $line);
                }
            }
        }
        
        if ($line !== $originalLine) {
            $fixes[] = "Line " . ($i + 1) . ": $originalLine -> $line";
            $lines[$i] = $line;
            $fixed = true;
        }
    }
    
    return [$fixed ? implode("\n", $lines) : $content, $fixes];
}

function guessArrayTypeFromParamName(string $paramName): string
{
    $paramName = strtolower(ltrim($paramName, '$'));
    
    $patterns = [
        // 數據結構相關
        'data' => 'array<string, mixed>',
        'attributes' => 'array<string, mixed>',
        'config' => 'array<string, mixed>',
        'options' => 'array<string, mixed>',
        'settings' => 'array<string, mixed>',
        'params' => 'array<string, mixed>',
        'parameters' => 'array<string, mixed>',
        'fields' => 'array<string, mixed>',
        'values' => 'array<string, mixed>',
        
        // ID 和列表相關
        'ids' => 'array<int, int>',
        'tagids' => 'array<int, int>',
        'userids' => 'array<int, int>',
        'postids' => 'array<int, int>',
        
        // 字串列表
        'tags' => 'array<int, string>',
        'roles' => 'array<int, string>',
        'permissions' => 'array<int, string>',
        'scopes' => 'array<int, string>',
        'items' => 'array<int, string>',
        'names' => 'array<int, string>',
        'keys' => 'array<int, string>',
        
        // 錯誤和驗證
        'errors' => 'array<string, string>',
        'rules' => 'array<string, string>',
        'messages' => 'array<string, string>',
        
        // HTTP 相關
        'headers' => 'array<string, string>',
        'cookies' => 'array<string, string>',
        'request' => 'array<string, mixed>',
        'response' => 'array<string, mixed>',
        'args' => 'array<string, mixed>',
        
        // 條件和搜尋
        'conditions' => 'array<string, mixed>',
        'criteria' => 'array<string, mixed>',
        'filters' => 'array<string, mixed>',
        'search' => 'array<string, mixed>',
        
        // DTO 相關
        'dtos' => 'array<int, mixed>',
        
        // 結果集
        'results' => 'array<int, mixed>',
        'records' => 'array<int, mixed>',
        'rows' => 'array<int, mixed>',
    ];
    
    foreach ($patterns as $pattern => $type) {
        if (strpos($paramName, $pattern) !== false) {
            return $type;
        }
    }
    
    // 複數形式通常是索引陣列
    if (substr($paramName, -1) === 's' && !in_array($paramName, ['class', 'address', 'process'])) {
        return 'array<int, mixed>';
    }
    
    return 'array<string, mixed>';
}

function guessArrayTypeFromMethodName(string $methodName): string
{
    $methodName = strtolower($methodName);
    
    $patterns = [
        // Getter 方法
        'getroles' => 'array<int, string>',
        'getpermissions' => 'array<int, string>',
        'gettags' => 'array<int, string>',
        'getids' => 'array<int, int>',
        'getstats' => 'array<string, mixed>',
        'getconfig' => 'array<string, mixed>',
        'getoptions' => 'array<string, mixed>',
        'getdata' => 'array<string, mixed>',
        'getattributes' => 'array<string, mixed>',
        'getfields' => 'array<string, mixed>',
        'getvalidationrules' => 'array<string, string>',
        'geterrors' => 'array<string, string>',
        
        // 搜尋和查詢方法
        'search' => 'array<int, mixed>',
        'find' => 'array<int, mixed>',
        'filter' => 'array<int, mixed>',
        'paginate' => 'array<string, mixed>',
        'getall' => 'array<int, mixed>',
        'list' => 'array<int, mixed>',
        
        // 轉換方法
        'toarray' => 'array<string, mixed>',
        'serialize' => 'array<string, mixed>',
        'jsonserialize' => 'array<string, mixed>',
        
        // 驗證方法
        'validate' => 'array<string, string>',
        'check' => 'array<string, mixed>',
        'verify' => 'array<string, mixed>',
        
        // 建立方法
        'create' => 'array<string, mixed>',
        'make' => 'array<string, mixed>',
        'build' => 'array<string, mixed>',
    ];
    
    foreach ($patterns as $pattern => $type) {
        if (strpos($methodName, $pattern) !== false) {
            return $type;
        }
    }
    
    // 預設為混合類型
    return 'array<string, mixed>';
}

function guessArrayTypeFromPropertyName(string $propName): string
{
    $propName = strtolower($propName);
    
    $patterns = [
        'config' => 'array<string, mixed>',
        'options' => 'array<string, mixed>',
        'settings' => 'array<string, mixed>',
        'data' => 'array<string, mixed>',
        'attributes' => 'array<string, mixed>',
        'cache' => 'array<string, mixed>',
        'errors' => 'array<string, string>',
        'rules' => 'array<string, string>',
        'permissions' => 'array<int, string>',
        'roles' => 'array<int, string>',
    ];
    
    foreach ($patterns as $pattern => $type) {
        if (strpos($propName, $pattern) !== false) {
            return $type;
        }
    }
    
    return 'array<string, mixed>';
}

function guessArrayTypeFromConstructorParam(string $paramName): string
{
    $paramName = strtolower(ltrim($paramName, '$'));
    
    // 建構函數參數通常是配置相關
    $patterns = [
        'config' => 'array<string, mixed>',
        'options' => 'array<string, mixed>',
        'settings' => 'array<string, mixed>',
        'data' => 'array<string, mixed>',
        'attributes' => 'array<string, mixed>',
        'params' => 'array<string, mixed>',
        'dependencies' => 'array<string, mixed>',
    ];
    
    foreach ($patterns as $pattern => $type) {
        if (strpos($paramName, $pattern) !== false) {
            return $type;
        }
    }
    
    return 'array<string, mixed>';
}

// 主執行邏輯
$directories = [
    __DIR__ . '/../app',
    __DIR__ . '/../tests',
];

$totalFixes = 0;
$fixedFiles = 0;

echo "開始終極 missingType.iterableValue 修復...\n";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    
    $files = scanDirectory($dir);
    
    foreach ($files as $file) {
        echo "處理檔案: " . str_replace(__DIR__ . '/../', '', $file) . "\n";
        
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }
        
        [$fixedContent, $fixes] = fixIterableValueTypes($content);
        
        if (!empty($fixes)) {
            file_put_contents($file, $fixedContent);
            $totalFixes += count($fixes);
            $fixedFiles++;
            
            echo "  修復了 " . count($fixes) . " 個問題\n";
            foreach ($fixes as $fix) {
                echo "    $fix\n";
            }
        }
    }
}

echo "\n修復完成！\n";
echo "總修復次數: $totalFixes\n";
echo "修復的檔案數: $fixedFiles\n";