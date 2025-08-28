<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Consolidated;

use InvalidArgumentException;
use RuntimeException;

/**
 * 統一腳本管理器 - 基於零錯誤修復經驗和 PHP 8.4 最佳實務
 * 
 * 整合所有開發工具腳本，採用現代 PHP 語法和 DDD 原則
 * 
 * @author GitHub Copilot
 * @version 2.0.0
 * @since 2024-12-19
 */
final readonly class ScriptManager
{
    private const array SCRIPT_CATEGORIES = [
        'error-fixing' => 'PHPStan 錯誤修復工具',
        'testing' => '測試遷移和管理工具',
        'analysis' => '專案架構分析工具',
        'deployment' => '部署和備份工具',
        'maintenance' => '維護和清理工具',
    ];

    private const array COMMAND_ALIASES = [
        'fix' => 'error-fixing',
        'test' => 'testing',
        'scan' => 'analysis',
        'deploy' => 'deployment',
        'clean' => 'maintenance',
    ];

    public function __construct(
        private string $projectRoot,
        private ScriptConfigurationInterface $config,
        private ScriptExecutorInterface $executor,
        private ScriptAnalyzerInterface $analyzer
    ) {
        if (!is_dir($this->projectRoot)) {
            throw new InvalidArgumentException("專案根目錄不存在: {$this->projectRoot}");
        }
    }

    /**
     * 執行指定的腳本類別或具體腳本
     * 
     * @param string $command 命令或類別名稱
     * @param array<string, mixed> $options 執行選項
     * @return ScriptResult 執行結果
     */
    public function execute(string $command, array $options = []): ScriptResult
    {
        $resolvedCategory = $this->resolveCommand($command);

        return match ($resolvedCategory) {
            'error-fixing' => $this->executeErrorFixing($options),
            'testing' => $this->executeTesting($options),
            'analysis' => $this->executeAnalysis($options),
            'deployment' => $this->executeDeployment($options),
            'maintenance' => $this->executeMaintenance($options),
            default => throw new InvalidArgumentException("未知的命令: {$command}")
        };
    }

    /**
     * 列出所有可用的腳本類別和命令
     */
    public function listCommands(): array
    {
        return [
            'categories' => self::SCRIPT_CATEGORIES,
            'aliases' => self::COMMAND_ALIASES,
            'available_scripts' => $this->analyzer->scanAvailableScripts(),
        ];
    }

    /**
     * 獲取專案當前狀態報告
     */
    public function getProjectStatus(): ProjectStatus
    {
        return new ProjectStatus(
            phpstanErrors: $this->analyzer->countPHPStanErrors(),
            testStatus: $this->analyzer->getTestStatus(),
            architectureMetrics: $this->analyzer->getArchitectureMetrics(),
            modernPhpAdoption: $this->analyzer->getModernPhpAdoption()
        );
    }

    private function resolveCommand(string $command): string
    {
        return self::COMMAND_ALIASES[$command] ?? $command;
    }

    private function executeErrorFixing(array $options): ScriptResult
    {
        $fixer = new ConsolidatedErrorFixer(
            $this->projectRoot,
            $this->config->getErrorFixingConfig()
        );

        return $fixer->fix($options);
    }

    private function executeTesting(array $options): ScriptResult
    {
        $tester = new ConsolidatedTestManager(
            $this->projectRoot,
            $this->config->getTestingConfig()
        );

        return $tester->manage($options);
    }

    private function executeAnalysis(array $options): ScriptResult
    {
        $analyzer = new ConsolidatedAnalyzer(
            $this->projectRoot,
            $this->config->getAnalysisConfig()
        );

        return $analyzer->analyze($options);
    }

    private function executeDeployment(array $options): ScriptResult
    {
        $deployer = new ConsolidatedDeployer(
            $this->projectRoot,
            $this->config->getDeploymentConfig()
        );

        return $deployer->deploy($options);
    }

    private function executeMaintenance(array $options): ScriptResult
    {
        $maintainer = new ConsolidatedMaintainer(
            $this->projectRoot,
            $this->config->getMaintenanceConfig()
        );

        return $maintainer->maintain($options);
    }
}

/**
 * 腳本執行結果值物件
 */
final readonly class ScriptResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public array $details = [],
        public float $executionTime = 0.0,
        public int $exitCode = 0
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function hasErrors(): bool
    {
        return !$this->success || $this->exitCode !== 0;
    }
}

/**
 * 專案狀態值物件
 */
final readonly class ProjectStatus
{
    public function __construct(
        public int $phpstanErrors,
        public TestStatus $testStatus,
        public ArchitectureMetrics $architectureMetrics,
        public ModernPhpAdoption $modernPhpAdoption
    ) {}

    public function isHealthy(): bool
    {
        return $this->phpstanErrors === 0
            && $this->testStatus->allPassing()
            && $this->modernPhpAdoption->isGood();
    }
}

/**
 * 測試狀態值物件
 */
final readonly class TestStatus
{
    public function __construct(
        public int $totalTests,
        public int $passingTests,
        public int $failingTests,
        public float $coverage
    ) {}

    public function allPassing(): bool
    {
        return $this->failingTests === 0 && $this->passingTests > 0;
    }
}

/**
 * 架構指標值物件
 */
final readonly class ArchitectureMetrics
{
    public function __construct(
        public int $totalClasses,
        public int $totalInterfaces,
        public int $dddContexts,
        public float $psrCompliance
    ) {}
}

/**
 * 現代 PHP 採用程度值物件
 */
final readonly class ModernPhpAdoption
{
    public function __construct(
        public float $adoptionRate,
        public array $modernFeatures,
        public array $suggestions
    ) {}

    public function isGood(): bool
    {
        return $this->adoptionRate >= 0.8; // 80% 現代 PHP 特性使用率
    }
}

// 介面定義

interface ScriptConfigurationInterface
{
    public function getErrorFixingConfig(): ErrorFixingConfig;
    public function getTestingConfig(): TestingConfig;
    public function getAnalysisConfig(): AnalysisConfig;
    public function getDeploymentConfig(): DeploymentConfig;
    public function getMaintenanceConfig(): MaintenanceConfig;
}

interface ScriptExecutorInterface
{
    public function execute(string $command, array $args = []): ScriptResult;
    public function executeBackground(string $command, array $args = []): string; // 回傳 process ID
}

interface ScriptAnalyzerInterface
{
    public function scanAvailableScripts(): array;
    public function countPHPStanErrors(): int;
    public function getTestStatus(): TestStatus;
    public function getArchitectureMetrics(): ArchitectureMetrics;
    public function getModernPhpAdoption(): ModernPhpAdoption;
}

// 設定值物件

final readonly class ErrorFixingConfig
{
    public function __construct(
        public bool $autoFix = true,
        public int $maxLevel = 8,
        public array $excludePaths = [],
        public bool $useBleedingEdge = true
    ) {}
}

final readonly class TestingConfig
{
    public function __construct(
        public string $framework = 'phpunit',
        public bool $coverage = true,
        public string $coverageFormat = 'html',
        public array $testSuites = []
    ) {}
}

final readonly class AnalysisConfig
{
    public function __construct(
        public bool $deepScan = true,
        public bool $dddAnalysis = true,
        public bool $modernPhpCheck = true,
        public array $metrics = []
    ) {}
}

final readonly class DeploymentConfig
{
    public function __construct(
        public string $environment = 'production',
        public bool $backup = true,
        public bool $ssl = true,
        public array $deploymentSteps = []
    ) {}
}

final readonly class MaintenanceConfig
{
    public function __construct(
        public bool $cacheClear = true,
        public bool $logRotation = true,
        public bool $databaseOptimization = true,
        public int $retentionDays = 30
    ) {}
}
