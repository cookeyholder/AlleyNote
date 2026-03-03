<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Core;

/**
 * 增強版 API 文件產生腳本
 *
 * 使用方法：
 * php scripts/generate-swagger-docs.php [選項]
 *
 * 選項：
 *   --output=DIR       指定輸出目錄 (預設: public)
 *   --format=FORMAT    指定格式 json|yaml|both (預設: both)
 *   --env=ENV          指定環境 development|staging|production (預設: development)
 *   --validate         驗證生成的文件
 *   --verbose          顯示詳細訊息
 *   --quiet            靜默模式
 *   --help             顯示幫助訊息
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/lib/ConsoleOutput.php';

use OpenApi\Generator;

/**
 * 解析命令列參數
 */
function parseArguments(array $argv): array
{
    $options = [
        'output' => __DIR__ . '/../public',
        'format' => 'both',
        'env' => 'development',
        'validate' => false,
        'verbose' => false,
        'quiet' => false,
        'help' => false
    ];

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];

        if (str_starts_with($arg, '--output=')) {
            $options['output'] = substr($arg, 9);
        } elseif (str_starts_with($arg, '--format=')) {
            $format = substr($arg, 9);
            if (in_array($format, ['json', 'yaml', 'both'])) {
                $options['format'] = $format;
            }
        } elseif (str_starts_with($arg, '--env=')) {
            $env = substr($arg, 6);
            if (in_array($env, ['development', 'staging', 'production'])) {
                $options['env'] = $env;
            }
        } elseif ($arg === '--validate') {
            $options['validate'] = true;
        } elseif ($arg === '--verbose' || $arg === '-v') {
            $options['verbose'] = true;
        } elseif ($arg === '--quiet' || $arg === '-q') {
            $options['quiet'] = true;
        } elseif ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        }
    }

    return $options;
}

/**
 * 顯示幫助訊息
 */
function showHelp(): void
{
    echo <<<'EOF'
AlleyNote API 文件產生工具

用途:
  自動掃描控制器和 Schema 檔案，生成 OpenAPI 3.0 格式的 API 文件

使用方法:
  php scripts/generate-swagger-docs.php [選項]

選項:
  --output=DIR       指定輸出目錄
                     預設: public
  --format=FORMAT    指定輸出格式 json|yaml|both
                     預設: both
  --env=ENV          指定環境 development|staging|production
                     預設: development
  --validate         驗證生成的 API 文件
  --verbose, -v      顯示詳細訊息
  --quiet, -q        靜默模式（只顯示錯誤）
  --help, -h         顯示此幫助訊息

範例:
  php scripts/generate-swagger-docs.php
  php scripts/generate-swagger-docs.php --format=json --validate
  php scripts/generate-swagger-docs.php --output=/tmp/docs --env=production

生成的檔案:
  - api-docs.json    JSON 格式的 OpenAPI 規格
  - api-docs.yaml    YAML 格式的 OpenAPI 規格

EOF;
}

/**
 * 驗證 API 文件
 */
function validateApiDoc(array $apiDoc, ConsoleOutput $output): bool
{
    $errors = [];

    // 檢查必要的頂層欄位
    $requiredFields = ['openapi', 'info', 'paths'];
    foreach ($requiredFields as $field) {
        if (!isset($apiDoc[$field])) {
            $errors[] = "缺少必要欄位: {$field}";
        }
    }

    // 檢查 OpenAPI 版本
    if (isset((is_array($apiDoc) ? $apiDoc['openapi'] : (is_object($apiDoc) ? $apiDoc->openapi : null)))) {
        if (!preg_match('/^3\.\d+\.\d+$/', (is_array($apiDoc) ? $apiDoc['openapi'] : (is_object($apiDoc) ? $apiDoc->openapi : null)))) {
            $errors[] = "OpenAPI 版本格式不正確: " . (is_array($apiDoc) ? $apiDoc['openapi'] : (is_object($apiDoc) ? $apiDoc->openapi : null));
        }
    }

    // 檢查 info 欄位
    if (isset((is_array($apiDoc) ? $apiDoc['info'] : (is_object($apiDoc) ? $apiDoc->info : null)))) {
        $requiredInfoFields = ['title', 'version'];
        foreach ($requiredInfoFields as $field) {
            if (!isset((is_array($apiDoc) ? $apiDoc['info'] : (is_object($apiDoc) ? $apiDoc->info : null))[$field]) || empty((is_array($apiDoc) ? $apiDoc['info'] : (is_object($apiDoc) ? $apiDoc->info : null))[$field])) {
                $errors[] = "info.{$field} 欄位缺失或為空";
            }
        }
    }

    // 檢查路徑數量
    $pathCount = count((is_array($apiDoc) ? $apiDoc['paths'] : (is_object($apiDoc) ? $apiDoc->paths : null)) ?? []);
    if ($pathCount === 0) {
        $errors[] = "沒有找到任何 API 路徑";
    }

    // 檢查每個路徑的操作
    foreach ((is_array($apiDoc) ? $apiDoc['paths'] : (is_object($apiDoc) ? $apiDoc->paths : null)) ?? [] as $path => $methods) {
        foreach ($methods as $method => $operation) {
            if (!in_array($method, ['get', 'post', 'put', 'delete', 'patch', 'head', 'options', 'trace'])) {
                continue;
            }

            // 檢查操作是否有 summary
            if (!isset((is_array($operation) ? $operation['summary'] : (is_object($operation) ? $operation->summary : null))) || empty((is_array($operation) ? $operation['summary'] : (is_object($operation) ? $operation->summary : null)))) {
                $errors[] = "{$method} {$path} 缺少 summary";
            }

            // 檢查操作是否有 responses
            if (!isset((is_array($operation) ? $operation['responses'] : (is_object($operation) ? $operation->responses : null))) || empty((is_array($operation) ? $operation['responses'] : (is_object($operation) ? $operation->responses : null)))) {
                $errors[] = "{$method} {$path} 缺少 responses";
            }
        }
    }

    // 顯示驗證結果
    if (empty($errors)) {
        $output->success("API 文件驗證通過");
        return true;
    } else {
        $output->error("API 文件驗證失敗，發現 " . count($errors) . " 個問題：");
        foreach ($errors as $error) {
            $output->error("  - {$error}");
        }
        return false;
    }
}

/**
 * 取得檔案統計資訊
 */
function getFileStats(string $file): array
{
    if (!file_exists($file)) {
        return ['size' => 0, 'lines' => 0];
    }

    $size = filesize($file);
    $lines = count(file($file));

    return ['size' => $size, 'lines' => $lines];
}

/**
 * 格式化檔案大小
 */
function formatFileSize(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    } elseif ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    } else {
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}

/**
 * 主程式
 */
function main(array $argv): void
{
    // 解析參數
    $options = parseArguments($argv);

    // 設定輸出物件
    $verbosity = (is_array($options) ? $options['quiet'] : (is_object($options) ? $options->quiet : null)) ? ConsoleOutput::VERBOSITY_QUIET : ((is_array($options) ? $options['verbose'] : (is_object($options) ? $options->verbose : null)) ? ConsoleOutput::VERBOSITY_VERBOSE : ConsoleOutput::VERBOSITY_NORMAL);
    $output = new ConsoleOutput($verbosity);

    try {
        // 顯示幫助
        if ((is_array($options) ? $options['help'] : (is_object($options) ? $options->help : null))) {
            showHelp();
            return;
        }

        $output->title("AlleyNote API 文件產生器");

        // 設定環境變數
        (is_array($_ENV) ? $_ENV['APP_ENV'] : (is_object($_ENV) ? $_ENV->APP_ENV : null)) = (is_array($options) ? $options['env'] : (is_object($options) ? $options->env : null));

        // 設定要掃描的目錄
        // 掃描路徑
        $scanPaths = [
            dirname(__DIR__) . '/app/Application/Controllers',
            dirname(__DIR__) . '/app/Shared/DTOs',
            dirname(__DIR__) . '/app/Shared/Schemas',
            dirname(__DIR__) . '/app/Shared/OpenApi',
        ];
        $output->info("正在掃描控制器和 Schema 檔案...");
        $totalFiles = 0;
        foreach ($scanPaths as $path) {
            if (!is_dir($path)) {
                $output->warning("目錄不存在 - {$path}");
            } else {
                $phpFiles = glob($path . '/*.php');
                $fileCount = count($phpFiles);
                $totalFiles += $fileCount;
                if ((is_array($options) ? $options['verbose'] : (is_object($options) ? $options->verbose : null))) {
                    $output->info("  - {$path} ({$fileCount} 個 PHP 檔案)");
                }
            }
        }

        $output->info("總共掃描 {$totalFiles} 個 PHP 檔案");
        $output->newLine();

        // 生成 OpenAPI 文件
        $output->info("產生 OpenAPI 規格...");
        $openapi = Generator::scan($scanPaths);

        // 如果沒有 Info，添加預設 Info
        if (!$openapi->info || !$openapi->info->title) {
            $openapi->info = new \OpenApi\Annotations\Info([
                'title' => 'AlleyNote API',
                'version' => '1.0.0',
                'description' => 'AlleyNote 公布欄系統 API 文件'
            ]);
        }

        // 確保輸出目錄存在
        $outputDir = realpath((is_array($options) ? $options['output'] : (is_object($options) ? $options->output : null))) ?: (is_array($options) ? $options['output'] : (is_object($options) ? $options->output : null));
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                throw new RuntimeException("無法建立輸出目錄：{$outputDir}");
            }
            $output->info("建立輸出目錄：{$outputDir}");
        }

        // 獲取 API 文件資料
        $apiDoc = json_decode($openapi->toJson(), true);

        // 驗證文件
        if ((is_array($options) ? $options['validate'] : (is_object($options) ? $options->validate : null))) {
            $output->info("驗證 API 文件...");
            if (!validateApiDoc($apiDoc, $output)) {
                exit(1);
            }
            $output->newLine();
        }

        // 根據格式選項產生檔案
        $generatedFiles = [];

        if ((is_array($options) ? $options['format'] : (is_object($options) ? $options->format : null)) === 'json' || (is_array($options) ? $options['format'] : (is_object($options) ? $options->format : null)) === 'both') {
            $jsonFile = $outputDir . '/api-docs.json';
            $jsonContent = json_encode($apiDoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (file_put_contents($jsonFile, $jsonContent) === false) {
                throw new RuntimeException("無法寫入 JSON 檔案：{$jsonFile}");
            }

            $output->success("JSON 格式 API 文件已產生：{$jsonFile}");
            $generatedFiles[] = $jsonFile;
        }

        if ((is_array($options) ? $options['format'] : (is_object($options) ? $options->format : null)) === 'yaml' || (is_array($options) ? $options['format'] : (is_object($options) ? $options->format : null)) === 'both') {
            $yamlFile = $outputDir . '/api-docs.yaml';
            $yamlContent = $openapi->toYaml();

            if (file_put_contents($yamlFile, $yamlContent) === false) {
                throw new RuntimeException("無法寫入 YAML 檔案：{$yamlFile}");
            }

            $output->success("YAML 格式 API 文件已產生：{$yamlFile}");
            $generatedFiles[] = $yamlFile;
        }

        // 統計資訊
        $pathCount = count((is_array($apiDoc) ? $apiDoc['paths'] : (is_object($apiDoc) ? $apiDoc->paths : null)) ?? []);
        $schemaCount = count((is_array($apiDoc) ? $apiDoc['components'] : (is_object($apiDoc) ? $apiDoc->components : null))['schemas'] ?? []);
        $serverCount = count((is_array($apiDoc) ? $apiDoc['servers'] : (is_object($apiDoc) ? $apiDoc->servers : null)) ?? []);
        $tagCount = count((is_array($apiDoc) ? $apiDoc['tags'] : (is_object($apiDoc) ? $apiDoc->tags : null)) ?? []);

        $output->newLine();
        $output->subtitle("產生結果");
        $output->info("API 路徑數量：{$pathCount}");
        $output->info("Schema 數量：{$schemaCount}");
        $output->info("伺服器設定：{$serverCount}");
        $output->info("標籤數量：{$tagCount}");
        $output->info("OpenAPI 版本：" . ((is_array($apiDoc) ? $apiDoc['openapi'] : (is_object($apiDoc) ? $apiDoc->openapi : null)) ?? '未知'));
        $output->info("API 標題：" . ((is_array($apiDoc) ? $apiDoc['info'] : (is_object($apiDoc) ? $apiDoc->info : null))['title'] ?? '未知'));
        $output->info("API 版本：" . ((is_array($apiDoc) ? $apiDoc['info'] : (is_object($apiDoc) ? $apiDoc->info : null))['version'] ?? '未知'));

        // 詳細統計 (verbose 模式)
        if ((is_array($options) ? $options['verbose'] : (is_object($options) ? $options->verbose : null))) {
            $output->newLine();
            $output->subtitle("詳細統計");

            // 按 HTTP 方法統計
            $methodStats = [];
            foreach ((is_array($apiDoc) ? $apiDoc['paths'] : (is_object($apiDoc) ? $apiDoc->paths : null)) ?? [] as $path => $methods) {
                foreach ($methods as $method => $operation) {
                    if (in_array($method, ['get', 'post', 'put', 'delete', 'patch', 'head', 'options', 'trace'])) {
                        $methodStats[$method] = ($methodStats[$method] ?? 0) + 1;
                    }
                }
            }

            $output->info("HTTP 方法分布：");
            foreach ($methodStats as $method => $count) {
                $output->info("  " . strtoupper($method) . ": {$count}");
            }

            // 按標籤統計
            if (!empty((is_array($apiDoc) ? $apiDoc['tags'] : (is_object($apiDoc) ? $apiDoc->tags : null)))) {
                $output->newLine();
                $output->info("標籤列表：");
                foreach ((is_array($apiDoc) ? $apiDoc['tags'] : (is_object($apiDoc) ? $apiDoc->tags : null)) as $tag) {
                    $output->info("  - " . ((is_array($tag) ? $tag['name'] : (is_object($tag) ? $tag->name : null)) ?? '未命名') . ": " . ((is_array($tag) ? $tag['description'] : (is_object($tag) ? $tag->description : null)) ?? '無描述'));
                }
            }

            // 檔案大小統計
            $output->newLine();
            $output->info("檔案大小：");
            foreach ($generatedFiles as $file) {
                $stats = getFileStats($file);
                $formattedSize = formatFileSize((is_array($stats) ? $stats['size'] : (is_object($stats) ? $stats->size : null)));
                $output->info("  " . basename($file) . ": {$formattedSize} ({(is_array($stats) ? $stats['lines'] : (is_object($stats) ? $stats->lines : null))} 行)");
            }
        }

        $output->newLine();
        $output->subtitle("使用說明");
        $output->line("1. 啟動伺服器後，訪問以下網址查看 API 文件：");
        $output->listItem("🌐 Swagger UI：http://localhost/api/docs/ui");
        $output->listItem("📄 JSON 文件：http://localhost/api/docs");

        if (in_array((is_array($options) ? $options['format'] : (is_object($options) ? $options->format : null)), ['json', 'both'])) {
            $output->listItem("📁 本地 JSON：{$outputDir}/api-docs.json");
        }
        if (in_array((is_array($options) ? $options['format'] : (is_object($options) ? $options->format : null)), ['yaml', 'both'])) {
            $output->listItem("📁 本地 YAML：{$outputDir}/api-docs.yaml");
        }

        $output->newLine();
        $output->line("2. 整合到其他工具：");
        $output->listItem("🔧 Postman: 匯入 JSON 檔案以建立 API 集合");
        $output->listItem("🔧 Insomnia: 匯入 OpenAPI 規格檔案");
        $output->listItem("🔧 前端生成: 使用 swagger-codegen 或類似工具");

        $output->newLine();
        $output->success("API 文件產生完成！");
    } catch (Exception $e) {
        $output->error("無法產生 API 文件");
        $output->error("錯誤訊息：{$e->getMessage()}");
        $output->error("檔案：{$e->getFile()}:{$e->getLine()}");

        if ((is_array($options) ? $options['verbose'] : (is_object($options) ? $options->verbose : null))) {
            $output->newLine();
            $output->info("詳細錯誤堆疊：");
            $output->line($e->getTraceAsString());
        } else {
            $output->info("提示：使用 --verbose 參數查看詳細錯誤訊息");
        }

        exit(1);
    }
}

// 如果直接執行此腳本，則執行主函數
if (realpath($argv[0]) === __FILE__) {
    main($argv);
}
