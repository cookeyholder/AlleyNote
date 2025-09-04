#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 環境配置驗證工具
 *
 * 此工具用於驗證指定環境的配置是否正確且完整
 *
 * 使用方式:
 *   php scripts/validate-config.php [--env=環境名稱] [--verbose] [--help]
 *
 * 範例:
 *   php scripts/validate-config.php --env=production
 *   php scripts/validate-config.php --env=development --verbose
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Shared\Config\EnvironmentConfig;

/**
 * 命令列選項解析
 */
function parseOptions(array $argv): array
{
    $options = [
        'env' => 'development',
        'verbose' => false,
        'help' => false,
    ];

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--env=')) {
            $options['env'] = substr($arg, 6);
        } elseif ($arg === '--verbose' || $arg === '-v') {
            $options['verbose'] = true;
        } elseif ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        }
    }

    return $options;
}

/**
 * 顯示幫助資訊
 */
function showHelp(): void
{
    echo "AlleyNote 環境配置驗證工具\n";
    echo "\n";
    echo "使用方式:\n";
    echo "  php scripts/validate-config.php [選項]\n";
    echo "\n";
    echo "選項:\n";
    echo "  --env=環境名稱    指定要驗證的環境 (development, testing, production)\n";
    echo "  --verbose, -v     詳細輸出模式\n";
    echo "  --help, -h        顯示此幫助資訊\n";
    echo "\n";
    echo "範例:\n";
    echo "  php scripts/validate-config.php --env=production\n";
    echo "  php scripts/validate-config.php --env=development --verbose\n";
    echo "\n";
}

/**
 * 輸出訊息
 */
function output(string $message, bool $verbose = false, bool $forceOutput = false): void
{
    global $options;

    if ($forceOutput || !$verbose || $options['verbose']) {
        echo $message . "\n";
    }
}

/**
 * 輸出成功訊息
 */
function success(string $message): void
{
    output("✅ " . $message, false, true);
}

/**
 * 輸出錯誤訊息
 */
function error(string $message): void
{
    output("❌ " . $message, false, true);
}

/**
 * 輸出警告訊息
 */
function warning(string $message): void
{
    output("⚠️  " . $message, false, true);
}

/**
 * 輸出資訊訊息
 */
function info(string $message, bool $verbose = false): void
{
    output("ℹ️  " . $message, $verbose);
}

/**
 * 驗證環境配置檔案是否存在
 */
function validateConfigFileExists(string $env): bool
{
    $configFile = __DIR__ . "/../.env.{$env}";

    if (!file_exists($configFile)) {
        error("環境配置檔案不存在: .env.{$env}");
        info("請確認檔案路徑: {$configFile}");
        return false;
    }

    success("找到環境配置檔案: .env.{$env}");
    return true;
}

/**
 * 驗證檔案權限
 */
function validateFilePermissions(string $env): bool
{
    $configFile = __DIR__ . "/../.env.{$env}";

    if (!is_readable($configFile)) {
        error("無法讀取環境配置檔案: .env.{$env}");
        return false;
    }

    $permissions = fileperms($configFile) & 0777;

    if ($permissions & 0044) {
        warning("環境配置檔案權限過於寬鬆 (其他人可讀取)");
        info("建議執行: chmod 600 .env.{$env}", true);
    } else {
        success("環境配置檔案權限設定正確");
    }

    return true;
}

/**
 * 驗證環境配置內容
 */
function validateConfigContent(string $env): array
{
    try {
        $config = new EnvironmentConfig($env);
        $errors = $config->validate();

        if (empty($errors)) {
            success("環境配置內容驗證通過");
            return [];
        }

        error("環境配置內容驗證失敗");
        foreach ($errors as $configError) {
            error("  - {$configError}");
        }

        return $errors;
    } catch (Exception $e) {
        error("載入環境配置時發生錯誤: " . $e->getMessage());
        return [$e->getMessage()];
    }
}

/**
 * 顯示環境配置摘要
 */
function showConfigSummary(string $env): void
{
    global $options;

    if (!$options['verbose']) {
        return;
    }

    try {
        $config = new EnvironmentConfig($env);
        $allConfig = $config->all();

        info("環境配置摘要:", true);
        info("================", true);

        $sensitiveKeys = ['PASSWORD', 'SECRET', 'KEY', 'TOKEN'];

        foreach ($allConfig as $key => $value) {
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains(strtoupper($key), $sensitiveKey)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $displayValue = str_repeat('*', min(8, strlen((string)$value)));
            } else {
                $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;
                $displayValue = substr($displayValue, 0, 50);
            }

            info("  {$key} = {$displayValue}", true);
        }

    } catch (Exception $e) {
        error("無法載入配置摘要: " . $e->getMessage());
    }
}

/**
 * 檢查環境特定的建議設定
 */
function checkEnvironmentSpecificRecommendations(string $env): void
{
    global $options;

    if (!$options['verbose']) {
        return;
    }

    try {
        $config = new EnvironmentConfig($env);

        info("環境特定建議:", true);
        info("================", true);

        switch ($env) {
            case 'production':
                checkProductionRecommendations($config);
                break;
            case 'testing':
                checkTestingRecommendations($config);
                break;
            case 'development':
                checkDevelopmentRecommendations($config);
                break;
        }

    } catch (Exception $e) {
        error("檢查環境建議時發生錯誤: " . $e->getMessage());
    }
}

/**
 * 檢查生產環境建議
 */
function checkProductionRecommendations(EnvironmentConfig $config): void
{
    if ($config->get('CACHE_DRIVER') !== 'redis') {
        warning("生產環境建議使用 Redis 作為快取驅動程式");
    } else {
        success("快取驅動程式設定正確 (Redis)");
    }

    if (!$config->get('ACTIVITY_LOG_MONITOR_PERFORMANCE', false)) {
        warning("生產環境建議啟用活動記錄效能監控");
    } else {
        success("活動記錄效能監控已啟用");
    }

    if ($config->get('LOG_LEVEL') === 'debug') {
        warning("生產環境不建議使用 debug 日誌等級");
    } else {
        success("日誌等級設定適合生產環境");
    }
}

/**
 * 檢查測試環境建議
 */
function checkTestingRecommendations(EnvironmentConfig $config): void
{
    if ($config->get('DB_DATABASE') !== ':memory:') {
        info("建議測試環境使用記憶體資料庫以提高測試速度", true);
    } else {
        success("資料庫設定適合測試環境 (記憶體資料庫)");
    }

    if ($config->get('CACHE_DRIVER') !== 'array') {
        info("建議測試環境使用陣列快取", true);
    } else {
        success("快取驅動程式適合測試環境 (陣列)");
    }
}

/**
 * 檢查開發環境建議
 */
function checkDevelopmentRecommendations(EnvironmentConfig $config): void
{
    if (!$config->get('APP_DEBUG', false)) {
        warning("開發環境建議啟用偵錯模式");
    } else {
        success("偵錯模式已啟用");
    }

    if ($config->get('ACTIVITY_LOG_LEVEL') !== 'debug') {
        info("開發環境建議使用 debug 活動記錄等級", true);
    } else {
        success("活動記錄等級適合開發環境");
    }
}

/**
 * 主要驗證流程
 */
function main(array $argv): int
{
    global $options;
    $options = parseOptions($argv);

    if ($options['help']) {
        showHelp();
        return 0;
    }

    $env = $options['env'];

    output("=================================");
    output("AlleyNote 環境配置驗證工具");
    output("=================================");
    output("正在驗證環境: {$env}");
    output("=================================");

    $hasErrors = false;

    // 1. 檢查配置檔案是否存在
    if (!validateConfigFileExists($env)) {
        return 1;
    }

    // 2. 檢查檔案權限
    if (!validateFilePermissions($env)) {
        $hasErrors = true;
    }

    // 3. 驗證配置內容
    $configErrors = validateConfigContent($env);
    if (!empty($configErrors)) {
        $hasErrors = true;
    }

    // 4. 顯示配置摘要（詳細模式）
    showConfigSummary($env);

    // 5. 檢查環境特定建議（詳細模式）
    checkEnvironmentSpecificRecommendations($env);

    // 最終結果
    output("=================================");
    if ($hasErrors) {
        error("環境配置驗證失敗，發現 " . count($configErrors) . " 個錯誤");
        output("=================================");
        return 1;
    } else {
        success("環境配置驗證通過");
        output("=================================");
        return 0;
    }
}

// 執行主程式
exit(main($argv));
