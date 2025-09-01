#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * AlleyNote 統一腳本管理工具
 * 
 * 基於零錯誤修復經驗和現代 PHP 最佳實務
 * 
 * 使用方式:
 *   php unified-scripts.php <command> [options]
 * 
 * 可用命令:
 *   fix [--type=TYPE]           - 執行錯誤修復 (type: all|type-hints|undefined-variables|...)
 *   test [--action=ACTION]      - 測試管理 (action: run|coverage|migrate|clean)
 *   analyze [--type=TYPE]       - 專案分析 (type: full|architecture|modern-php|ddd)
 *   deploy [--env=ENV]          - 部署 (env: production|staging|development)
 *   maintain [--task=TASK]      - 維護 (task: all|cache|logs|database|cleanup)
 *   status                      - 顯示專案狀態
 *   list                        - 列出所有可用命令
 * 
 * 範例:
 *   php unified-scripts.php fix --type=type-hints
 *   php unified-scripts.php test --action=coverage
 *   php unified-scripts.php analyze --type=architecture
 *   php unified-scripts.php status
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AlleyNote\Scripts\Consolidated\ScriptManager;
use AlleyNote\Scripts\Consolidated\DefaultScriptConfiguration;
use AlleyNote\Scripts\Consolidated\DefaultScriptExecutor;
use AlleyNote\Scripts\Consolidated\DefaultScriptAnalyzer;

function main(array<mixed> $argv): int
{
    $projectRoot = dirname(__DIR__);

    try {
        // 初始化腳本管理器
        $config = new DefaultScriptConfiguration($projectRoot);
        $executor = new DefaultScriptExecutor($projectRoot);
        $analyzer = new DefaultScriptAnalyzer($projectRoot);

        $scriptManager = new ScriptManager($projectRoot, $config, $executor, $analyzer);

        // 解析命令列參數
        $command = $argv[1] ?? 'status';
        $options = parseOptions(array_slice($argv, 2));

        // 顯示歡迎資訊
        displayWelcome();

        // 執行命令
        $result = match ($command) {
            'list' => handleListCommand($scriptManager),
            'status' => handleStatusCommand($scriptManager),
            default => $scriptManager->execute($command, $options)
        };

        // 顯示結果
        displayResult($result);

        return $result->exitCode;
    } catch (\Throwable $e) {
        displayError("執行錯誤: {$e->getMessage()}");
        displayUsage();
        return 1;
    }
}

function parseOptions(array<mixed> $args): array<mixed>
{
    $options = [];

    foreach ($args as $arg) {
        if (str_starts_with($arg, '--')) {
            $parts = explode('=', substr($arg, 2), 2);
            $key = $parts[0];
            $value = $parts[1] ?? true;
            $options[$key] = $value;
        }
    }

    return $options;
}

function handleListCommand(ScriptManager $manager): AlleyNote\Scripts\Consolidated\ScriptResult
{
    $commands = $manager->listCommands();

    echo "\n📋 可用的腳本類別:\n";
    foreach ((is_array($commands) ? $commands['categories'] : (is_object($commands) ? $commands->categories : null)) as $category => $description) {
        echo "  • {$category}: {$description}\n";
    }

    echo "\n🔗 命令別名:\n";
    foreach ((is_array($commands) ? $commands['aliases'] : (is_object($commands) ? $commands->aliases : null)) as $alias => $category) {
        echo "  • {$alias} → {$category}\n";
    }

    echo "\n📁 發現的腳本檔案: " . count((is_array($commands) ? $commands['available_scripts'] : (is_object($commands) ? $commands->available_scripts : null))) . " 個\n";

    return new AlleyNote\Scripts\Consolidated\ScriptResult(
        success: true,
        message: '命令列表顯示完成',
        details: $commands
    );
}

function handleStatusCommand(ScriptManager $manager): AlleyNote\Scripts\Consolidated\ScriptResult
{
    echo "\n🔍 正在檢查專案狀態...\n";

    $status = $manager->getProjectStatus();

    echo "\n📊 專案健康狀況報告:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    // PHPStan 錯誤
    $errorIcon = $status->phpstanErrors === 0 ? '✅' : '❌';
    echo "  {$errorIcon} PHPStan 錯誤: {$status->phpstanErrors}\n";

    // 測試狀態
    $testIcon = $status->testStatus->allPassing() ? '✅' : '❌';
    echo "  {$testIcon} 測試狀態: {$status->testStatus->passingTests}/{$status->testStatus->totalTests} 通過\n";

    // 架構指標
    echo "  📐 架構指標:\n";
    echo "    • 總類別數: {$status->architectureMetrics->totalClasses}\n";
    echo "    • 介面數: {$status->architectureMetrics->totalInterfaces}\n";
    echo "    • DDD 限界上下文: {$status->architectureMetrics->dddContexts}\n";
    echo "    • PSR-4 合規性: " . number_format($status->architectureMetrics->psrCompliance, 2) . "%\n";

    // 現代 PHP 採用程度
    $modernIcon = $status->modernPhpAdoption->isGood() ? '✅' : '⚠️';
    $adoptionPercent = number_format($status->modernPhpAdoption->adoptionRate * 100, 2);
    echo "  {$modernIcon} 現代 PHP 採用率: {$adoptionPercent}%\n";

    // 整體健康狀況
    $overallIcon = $status->isHealthy() ? '🎉' : '⚠️';
    $overallStatus = $status->isHealthy() ? '優秀' : '需要改進';
    echo "\n  {$overallIcon} 整體狀況: {$overallStatus}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    return new AlleyNote\Scripts\Consolidated\ScriptResult(
        success: true,
        message: '專案狀態檢查完成',
        details: ['status' => $status]
    );
}

function displayWelcome(): void
{
    echo "\n🚀 AlleyNote 統一腳本管理工具 v2.0.0\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "基於零錯誤修復經驗和現代 PHP 最佳實務\n\n";
}

function displayResult(AlleyNote\Scripts\Consolidated\ScriptResult $result): void
{
    $icon = $result->isSuccess() ? '✅' : '❌';
    echo "\n{$icon} {$result->message}\n";

    if (!empty($result->details)) {
        echo "\n📋 詳細資訊:\n";
        foreach ($result->details as $key => $value) {
            if (is_array($value) && !empty($value)) {
                echo "  • {$key}: " . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "  • {$key}: {$value}\n";
            }
        }
    }

    $time = number_format($result->executionTime, 3);
    echo "\n⏱️ 執行時間: {$time} 秒\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}

function displayError(string $message): void
{
    echo "\n❌ 錯誤: {$message}\n";
}

function displayUsage(): void
{
    echo "\n📖 使用方式:\n";
    echo "  php unified-scripts.php <command> [options]\n\n";
    echo "可用命令:\n";
    echo "  fix [--type=TYPE]      - 錯誤修復\n";
    echo "  test [--action=ACTION] - 測試管理\n";
    echo "  analyze [--type=TYPE]  - 專案分析\n";
    echo "  deploy [--env=ENV]     - 部署\n";
    echo "  maintain [--task=TASK] - 維護\n";
    echo "  status                 - 專案狀態\n";
    echo "  list                   - 列出命令\n";
}

// 執行主程式
exit(main($argv));
