<?php

$file = 'tests/Integration/Application/Controllers/Admin/TagManagementControllerTest.php';
$content = file_get_contents($file);

// 替換所有 json_decode 呼叫
$content = str_replace(
    'json_decode(is_string($content) ? $content : "", true)',
    '$this->safeJsonDecode($content)',
    $content
);

// 修復不安全的陣列存取 - 將 $data['key'] 替換為安全版本
$patterns = [
    // 匹配所有直接的陣列存取模式並添加 isset 檢查
    '/return\s+\$data\[\'([^\']+)\'\]\s*===\s*([^;]+);/' => 'return $data !== null && isset($data[\'$1\']) && $data[\'$1\'] === $2;',
    '/\$data\[\'([^\']+)\'\]\s*===\s*([^&]+)\s*&&/' => 'isset($data[\'$1\']) && $data[\'$1\'] === $2 &&',
];

foreach ($patterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

file_put_contents($file, $content);
echo "Fixed JSON decode issues in $file\n";