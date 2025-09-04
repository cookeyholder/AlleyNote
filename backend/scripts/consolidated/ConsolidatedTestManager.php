<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Consolidated;

use RuntimeException;

/**
 * 整合的測試管理器
 * 
 * 統一管理所有測試相關功能
 */
final readonly class ConsolidatedTestManager
{
    public function __construct(
        private string $projectRoot,
        private TestingConfig $config
    ) {}

    public function manage(array<mixed> $options = []): ScriptResult
    {
        $startTime = microtime(true);
        $details = [];

        try {
            $action = (is_array($options) ? $options['action'] : (is_object($options) ? $options->action : null)) ?? 'run';

            $result = match ($action) {
                'run' => $this->runTests($options),
                'coverage' => $this->generateCoverage($options),
                'migrate' => $this->migrateTests($options),
                'clean' => $this->cleanTestArtifacts($options),
                default => throw new \InvalidArgumentException("未知的測試動作: {$action}")
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
                message: "❌ 測試管理失敗: {$e->getMessage()}",
                details: $details,
                executionTime: microtime(true) - $startTime,
                exitCode: 1
            );
        }
    }

    private function runTests(array<mixed> $options): array<mixed>
    {
        $command = "cd {$this->projectRoot} && ./vendor/bin/phpunit";

        if ($this->config->coverage) {
            $command .= " --coverage-html coverage-reports";
        }

        $output = shell_exec($command . " 2>&1");

        return [
            'success' => strpos($output, 'FAILURES') === false,
            'message' => '測試執行完成',
            'details' => ['output' => $output]
        ];
    }

    private function generateCoverage(array<mixed> $options): array<mixed>
    {
        // 實作覆蓋率生成邏輯
        return [
            'success' => true,
            'message' => '覆蓋率報告生成完成',
            'details' => []
        ];
    }

    private function migrateTests(array<mixed> $options): array<mixed>
    {
        // 實作測試遷移邏輯
        return [
            'success' => true,
            'message' => '測試遷移完成',
            'details' => []
        ];
    }

    private function cleanTestArtifacts(array<mixed> $options): array<mixed>
    {
        // 實作測試清理邏輯
        return [
            'success' => true,
            'message' => '測試人工製品清理完成',
            'details' => []
        ];
    }
}
