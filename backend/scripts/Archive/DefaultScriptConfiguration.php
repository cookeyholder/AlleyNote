<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Core;

/**
 * 預設腳本設定實作
 */
final readonly class DefaultScriptConfiguration implements ScriptConfigurationInterface
{
    public function __construct(
        private string $projectRoot
    ) {}

    public function getErrorFixingConfig(): ErrorFixingConfig
    {
        return new ErrorFixingConfig(
            autoFix: true,
            maxLevel: 8,
            excludePaths: [
                'vendor/',
                'node_modules/',
                'tests/*/data/*'
            ],
            useBleedingEdge: true
        );
    }

    public function getTestingConfig(): TestingConfig
    {
        return new TestingConfig(
            framework: 'phpunit',
            coverage: true,
            coverageFormat: 'html',
            testSuites: ['Unit', 'Integration', 'UI']
        );
    }

    public function getAnalysisConfig(): AnalysisConfig
    {
        return new AnalysisConfig(
            deepScan: true,
            dddAnalysis: true,
            modernPhpCheck: true,
            metrics: ['complexity', 'coupling', 'cohesion']
        );
    }

    public function getDeploymentConfig(): DeploymentConfig
    {
        return new DeploymentConfig(
            environment: 'production',
            backup: true,
            ssl: true,
            deploymentSteps: [
                'backup',
                'maintenance-mode',
                'deploy',
                'migrate',
                'cache-warm',
                'maintenance-off'
            ]
        );
    }

    public function getMaintenanceConfig(): MaintenanceConfig
    {
        return new MaintenanceConfig(
            cacheClear: true,
            logRotation: true,
            databaseOptimization: true,
            retentionDays: 30
        );
    }
}
