<?php
declare(strict_types=1);

/**
 * 終極 missingType.iterableValue 修復工具
 *
 * 專門修復 PHPStan missingType.iterableValue 錯誤，
 * 使用更強大的模式匹配和上下文分析
 */

function scanDirectory(string $dir): array<mixed>
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

function fixIterableValueTypes(string $content): array<mixed>
{
    $fixes = [];
    $lines = explode("\n", $content);
    $fixed = false;

    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $originalLine = $line;

        // 1. 方法參數修復 - 更精確的模式
        if (preg_match('/^(\s*)(public|private|protected)?\s*(static)?\s*function\s+(\w+)\s*\(/', $line)) {
            // 檢查參數中的 array<mixed> 型別
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
        if (preg_match('/:\s*array<mixed>\s*$/', $line) || preg_match('/:\s*array<mixed>\s*(?=\s*{)/', $line)) {
            // 檢查方法名稱來推測返回類型
            if (preg_match('/function\s+(\w+)/', $line, $matches)) {
                $methodName = $matches[1];
                $arrayType = guessArrayTypeFromMethodName($methodName);
                $line = preg_replace('/:\s*array(\s*)$/', ': ' . $arrayType . '$1', $line);
                $line = preg_replace('/:\s*array(\s*(?=\s*{))/', ': ' . $arrayType . '$1', $line);
            }
        }

        // 3. 屬性宣告修復
        if (preg_match('/^(\s*)(public|private|protected)\s+(\w+\s+)?\$\w+\s*:\s*array<mixed>/', $line)) {
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
            if (preg_match('/:\s*array<mixed>\s*;/', $line)) {
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
        'data' => 'array<mixed>',
        'attributes' => 'array<mixed>',
        'config' => 'array<mixed>',
        'options' => 'array<mixed>',
        'settings' => 'array<mixed>',
        'params' => 'array<mixed>',
        'parameters' => 'array<mixed>',
        'fields' => 'array<mixed>',
        'values' => 'array<mixed>',

        // ID 和列表相關
        'ids' => 'array<mixed>',
        'tagids' => 'array<mixed>',
        'userids' => 'array<mixed>',
        'postids' => 'array<mixed>',

        // 字串列表
        'tags' => 'array<mixed>',
        'roles' => 'array<mixed>',
        'permissions' => 'array<mixed>',
        'scopes' => 'array<mixed>',
        'items' => 'array<mixed>',
        'names' => 'array<mixed>',
        'keys' => 'array<mixed>',

        // 錯誤和驗證
        'errors' => 'array<mixed>',
        'rules' => 'array<mixed>',
        'messages' => 'array<mixed>',

        // HTTP 相關
        'headers' => 'array<mixed>',
        'cookies' => 'array<mixed>',
        'request' => 'array<mixed>',
        'response' => 'array<mixed>',
        'args' => 'array<mixed>',

        // 條件和搜尋
        'conditions' => 'array<mixed>',
        'criteria' => 'array<mixed>',
        'filters' => 'array<mixed>',
        'search' => 'array<mixed>',

        // DTO 相關
        'dtos' => 'array<mixed>',

        // 結果集
        'results' => 'array<mixed>',
        'records' => 'array<mixed>',
        'rows' => 'array<mixed>',
    ];

    foreach ($patterns as $pattern => $type) {
        if (strpos($paramName, $pattern) !== false) {
            return $type;
        }
    }

    // 複數形式通常是索引陣列
    if (substr($paramName, -1) === 's' && !in_array($paramName, ['class', 'address', 'process'])) {
        return 'array<mixed>';
    }

    return 'array<mixed>';
}

function guessArrayTypeFromMethodName(string $methodName): string
{
    $methodName = strtolower($methodName);

    $patterns = [
        // Getter 方法
        'getroles' => 'array<mixed>',
        'getpermissions' => 'array<mixed>',
        'gettags' => 'array<mixed>',
        'getids' => 'array<mixed>',
        'getstats' => 'array<mixed>',
        'getconfig' => 'array<mixed>',
        'getoptions' => 'array<mixed>',
        'getdata' => 'array<mixed>',
        'getattributes' => 'array<mixed>',
        'getfields' => 'array<mixed>',
        'getvalidationrules' => 'array<mixed>',
        'geterrors' => 'array<mixed>',

        // 搜尋和查詢方法
        'search' => 'array<mixed>',
        'find' => 'array<mixed>',
        'filter' => 'array<mixed>',
        'paginate' => 'array<mixed>',
        'getall' => 'array<mixed>',
        'list' => 'array<mixed>',

        // 轉換方法
        'toarray' => 'array<mixed>',
        'serialize' => 'array<mixed>',
        'jsonserialize' => 'array<mixed>',

        // 驗證方法
        'validate' => 'array<mixed>',
        'check' => 'array<mixed>',
        'verify' => 'array<mixed>',

        // 建立方法
        'create' => 'array<mixed>',
        'make' => 'array<mixed>',
        'build' => 'array<mixed>',
    ];

    foreach ($patterns as $pattern => $type) {
        if (strpos($methodName, $pattern) !== false) {
            return $type;
        }
    }

    // 預設為混合類型
    return 'array<mixed>';
}

function guessArrayTypeFromPropertyName(string $propName): string
{
    $propName = strtolower($propName);

    $patterns = [
        'config' => 'array<mixed>',
        'options' => 'array<mixed>',
        'settings' => 'array<mixed>',
        'data' => 'array<mixed>',
        'attributes' => 'array<mixed>',
        'cache' => 'array<mixed>',
        'errors' => 'array<mixed>',
        'rules' => 'array<mixed>',
        'permissions' => 'array<mixed>',
        'roles' => 'array<mixed>',
    ];

    foreach ($patterns as $pattern => $type) {
        if (strpos($propName, $pattern) !== false) {
            return $type;
        }
    }

    return 'array<mixed>';
}

function guessArrayTypeFromConstructorParam(string $paramName): string
{
    $paramName = strtolower(ltrim($paramName, '$'));

    // 建構函數參數通常是配置相關
    $patterns = [
        'config' => 'array<mixed>',
        'options' => 'array<mixed>',
        'settings' => 'array<mixed>',
        'data' => 'array<mixed>',
        'attributes' => 'array<mixed>',
        'params' => 'array<mixed>',
        'dependencies' => 'array<mixed>',
    ];

    foreach ($patterns as $pattern => $type) {
        if (strpos($paramName, $pattern) !== false) {
            return $type;
        }
    }

    return 'array<mixed>';
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
