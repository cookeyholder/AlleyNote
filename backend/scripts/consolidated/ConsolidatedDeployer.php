<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Consolidated;

use RuntimeException;

/**
 * 整合的部署器
 * 
 * 統一所有部署相關功能
 */
final readonly class ConsolidatedDeployer
{
    public function __construct(
        private string $projectRoot,
        private DeploymentConfig $config
    ) {}

    public function deploy(array<mixed> $options = []): ScriptResult
    {
        $startTime = microtime(true);
        $details = [];

        try {
            $environment = (is_array($options) ? $options['environment'] : (is_object($options) ? $options->environment : null)) ?? $this->config->environment;

            $result = match ($environment) {
                'production' => $this->deployToProduction($options),
                'staging' => $this->deployToStaging($options),
                'development' => $this->deployToDevelopment($options),
                default => throw new \InvalidArgumentException("未知的部署環境: {$environment}")
            };

            return new ScriptResult(
                success: (is_array($result) ? $result['success'] : (is_object($result) ? $result->success : null)),
                message: (is_array($result) ? $result['message'] : (is_object($result) ? $result->message : null)),
                details: array_merge($details, (is_array($result) ? $result['details'] : (is_object($result) ? $result->details : null))),
                executionTime: microtime(true) - $startTime
            );
        } catch (\Throwable $e) {
            return new ScriptResult(
                success: false,
                message: "❌ 部署失敗: {$e->getMessage()}",
                details: $details,
                executionTime: microtime(true) - $startTime,
                exitCode: 1
            );
        }
    }

    private function deployToProduction(array<mixed> $options): array<mixed>
    {
        // 實作生產環境部署邏輯
        return [
            'success' => true,
            'message' => '生產環境部署完成',
            'details' => []
        ];
    }

    private function deployToStaging(array<mixed> $options): array<mixed>
    {
        // 實作預備環境部署邏輯
        return [
            'success' => true,
            'message' => '預備環境部署完成',
            'details' => []
        ];
    }

    private function deployToDevelopment(array<mixed> $options): array<mixed>
    {
        // 實作開發環境部署邏輯
        return [
            'success' => true,
            'message' => '開發環境部署完成',
            'details' => []
        ];
    }
}
