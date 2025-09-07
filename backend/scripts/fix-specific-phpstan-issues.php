<?php

declare(strict_types=1);

/**
 * 針對 PHPStan missingType.iterableValue 錯誤的修復腳本
 */

$fixes = [
    // PostController 方法
    [
        'file' => 'app/Application/Controllers/Api/V1/PostController.php',
        'method' => 'show',
        'line_pattern' => '/public function show\(Request \$request, Response \$response, array \$args\): Response/',
        'replacement' => 'public function show(Request $request, Response $response, array $args): Response'
    ],
    [
        'file' => 'app/Application/Controllers/Api/V1/PostController.php',
        'method' => 'update',
        'line_pattern' => '/public function update\(Request \$request, Response \$response, array \$args\): Response/',
        'replacement' => 'public function update(Request $request, Response $response, array $args): Response'
    ],
    [
        'file' => 'app/Application/Controllers/Api/V1/PostController.php',
        'method' => 'delete',
        'line_pattern' => '/public function delete\(Request \$request, Response \$response, array \$args\): Response/',
        'replacement' => 'public function delete(Request $request, Response $response, array $args): Response'
    ],
    [
        'file' => 'app/Application/Controllers/Api/V1/PostController.php',
        'method' => 'togglePin',
        'line_pattern' => '/public function togglePin\(Request \$request, Response \$response, array \$args\): Response/',
        'replacement' => 'public function togglePin(Request $request, Response $response, array $args): Response'
    ],
];

foreach ($fixes as $fix) {
    $file = $fix['file'];

    if (!file_exists($file)) {
        echo "檔案不存在: {$file}\n";
        continue;
    }

    $content = file_get_contents($file);
    if ($content === false) {
        echo "無法讀取檔案: {$file}\n";
        continue;
    }

    // 新增一個特殊的 PHPStan 抑制註解
    $methodDocComment = <<<'EOD'
    /**
     * @phpstan-param array<string, mixed> $args
     */
EOD;

    // 找到方法簽名並在前面添加 PHPStan 註解
    $pattern = '/(\s*)(public function ' . $fix['method'] . '\(Request \$request, Response \$response, array \$args\): Response)/';
    $replacement = '$1' . $methodDocComment . "\n" . '$1$2';

    $newContent = preg_replace($pattern, $replacement, $content);

    if ($newContent !== $content && $newContent !== null) {
        file_put_contents($file, $newContent);
        echo "已修復方法 {$fix['method']} 在檔案 {$file}\n";
    } else {
        echo "無法修復方法 {$fix['method']} 在檔案 {$file}\n";
    }
}

echo "修復完成！\n";
