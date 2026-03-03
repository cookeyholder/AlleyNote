<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use OpenApi\Generator;

try {
    echo "正在生成 API 文檔...\n";
    
    // 設定要掃描的目錄
    $scanPaths = [
        __DIR__ . '/../app/Application/Controllers',
        __DIR__ . '/../app/Shared/DTOs',
        __DIR__ . '/../app/Shared/Schemas',
        __DIR__ . '/../app/Shared/OpenApi',
    ];
    
    echo "掃描目錄:\n";
    foreach ($scanPaths as $path) {
        echo "  - $path\n";
    }
    
    // 生成 OpenAPI 文件
    $openapi = Generator::scan($scanPaths);
    
    // 輸出目錄
    $outputDir = __DIR__ . '/../public';
    
    // 生成 JSON
    $jsonFile = $outputDir . '/api-docs.json';
    $jsonContent = $openapi->toJson();
    file_put_contents($jsonFile, $jsonContent);
    echo "\n✓ JSON 文件已生成: $jsonFile\n";
    
    // 生成 YAML
    $yamlFile = $outputDir . '/api-docs.yaml';
    $yamlContent = $openapi->toYaml();
    file_put_contents($yamlFile, $yamlContent);
    echo "✓ YAML 文件已生成: $yamlFile\n";
    
    // 統計資訊
    $apiDoc = json_decode($jsonContent, true);
    $pathCount = count($apiDoc['paths'] ?? []);
    echo "\n統計資訊:\n";
    echo "  - API 路徑數量: $pathCount\n";
    echo "  - OpenAPI 版本: " . ($apiDoc['openapi'] ?? '未知') . "\n";
    echo "  - API 標題: " . ($apiDoc['info']['title'] ?? '未知') . "\n";
    echo "  - API 版本: " . ($apiDoc['info']['version'] ?? '未知') . "\n";
    
    echo "\n✓ API 文檔生成完成！\n";
} catch (Exception $e) {
    echo "✗ 錯誤: " . $e->getMessage() . "\n";
    echo "  檔案: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
