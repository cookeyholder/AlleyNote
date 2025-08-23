#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Swagger UI 功能測試腳本
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== Swagger UI 功能測試 ===\n\n";

// 測試 1: 檢查 OpenApi 套件是否可用
echo "1. 檢查 OpenApi 套件...\n";
try {
    if (class_exists('OpenApi\Generator')) {
        echo "   ✓ OpenApi\Generator 類別可用\n";
    } else {
        echo "   ❌ OpenApi\Generator 類別不可用\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ 錯誤：{$e->getMessage()}\n";
    exit(1);
}

// 測試 2: 檢查掃描路徑
echo "\n2. 檢查掃描路徑...\n";
$scanPaths = [
    __DIR__ . '/../src/Controllers',
    __DIR__ . '/../src/Schemas'
];

foreach ($scanPaths as $path) {
    if (is_dir($path)) {
        $files = glob($path . '/*.php');
        echo "   ✓ {$path} ({" . count($files) . "} 個 PHP 檔案)\n";
    } else {
        echo "   ❌ 路徑不存在：{$path}\n";
    }
}

// 測試 3: 掃描 Swagger 註解
echo "\n3. 掃描 Swagger 註解...\n";
try {
    $openapi = \OpenApi\Generator::scan($scanPaths);
    echo "   ✓ Swagger 註解掃描成功\n";

    // 測試 4: 檢查產生的文件結構
    echo "\n4. 檢查文件結構...\n";
    $doc = json_decode($openapi->toJson(), true);

    if (isset($doc['info']['title'])) {
        echo "   ✓ API 標題：{$doc['info']['title']}\n";
    }

    if (isset($doc['paths']) && is_array($doc['paths'])) {
        $pathCount = count($doc['paths']);
        echo "   ✓ API 路徑數量：{$pathCount}\n";

        // 顯示所有路徑
        foreach ($doc['paths'] as $path => $methods) {
            foreach ($methods as $method => $details) {
                $summary = $details['summary'] ?? '無描述';
                echo "     - {$method} {$path}: {$summary}\n";
            }
        }
    }

    if (isset($doc['components']['schemas']) && is_array($doc['components']['schemas'])) {
        $schemaCount = count($doc['components']['schemas']);
        echo "   ✓ Schema 數量：{$schemaCount}\n";

        // 顯示所有 Schema
        foreach ($doc['components']['schemas'] as $schemaName => $schema) {
            $title = $schema['title'] ?? $schemaName;
            echo "     - {$schemaName}: {$title}\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ 掃描失敗：{$e->getMessage()}\n";
    echo "   檔案：{$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

// 測試 5: 測試 JSON 輸出
echo "\n5. 測試 JSON 輸出...\n";
try {
    $json = $openapi->toJson();
    $jsonData = json_decode($json, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        echo "   ✓ JSON 格式正確\n";
        echo "   ✓ JSON 大小：" . number_format(strlen($json)) . " 位元組\n";
    } else {
        echo "   ❌ JSON 格式錯誤：" . json_last_error_msg() . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ JSON 輸出失敗：{$e->getMessage()}\n";
}

// 測試 6: 檢查控制器檔案
echo "\n6. 檢查控制器檔案...\n";
$controllerFiles = [
    'PostController.php' => '貼文控制器',
    'SwaggerController.php' => 'Swagger 控制器'
];

foreach ($controllerFiles as $file => $description) {
    $filePath = __DIR__ . "/../src/Controllers/{$file}";
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if (strpos($content, '@OA\\') !== false) {
            echo "   ✓ {$description} 包含 Swagger 註解\n";
        } else {
            echo "   ⚠️  {$description} 沒有 Swagger 註解\n";
        }
    } else {
        echo "   ❌ {$description} 檔案不存在\n";
    }
}

// 測試 7: 檢查 Schema 檔案
echo "\n7. 檢查 Schema 檔案...\n";
$schemaFiles = [
    'PostSchema.php' => '貼文 Schema',
    'PostRequestSchema.php' => '貼文請求 Schema',
    'AuthSchema.php' => '授權 Schema'
];

foreach ($schemaFiles as $file => $description) {
    $filePath = __DIR__ . "/../src/Schemas/{$file}";
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if (strpos($content, '@OA\\Schema') !== false) {
            echo "   ✓ {$description} 包含 Schema 定義\n";
        } else {
            echo "   ⚠️  {$description} 沒有 Schema 定義\n";
        }
    } else {
        echo "   ❌ {$description} 檔案不存在\n";
    }
}

echo "\n=== 測試完成 ===\n";
echo "\n下一步：\n";
echo "1. 啟動 Docker 容器：docker-compose up -d\n";
echo "2. 進入容器：docker exec -it alleynote_web bash\n";
echo "3. 產生文件：php scripts/generate-swagger-docs.php\n";
echo "4. 訪問 Swagger UI：http://localhost/api/docs/ui\n";
echo "\n✅ Swagger UI 整合完成！\n";
