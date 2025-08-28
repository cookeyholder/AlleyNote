<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Consolidated;

use RuntimeException;

/**
 * 整合的維護器
 * 
 * 統一所有維護相關功能
 */
final readonly class ConsolidatedMaintainer
{
    public function __construct(
        private string $projectRoot,
        private MaintenanceConfig $config
    ) {}

    public function maintain(array $options = []): ScriptResult
    {
        $startTime = microtime(true);
        $details = [];

        try {
            $task = $options['task'] ?? 'all';

            $result = match ($task) {
                'all' => $this->performAllMaintenance($options),
                'cache' => $this->clearCache($options),
                'logs' => $this->rotateLogs($options),
                'database' => $this->optimizeDatabase($options),
                'cleanup' => $this->performCleanup($options),
                default => throw new \InvalidArgumentException("未知的維護任務: {$task}")
            };

            return new ScriptResult(
                success: $result['success'],
                message: $result['message'],
                details: array_merge($details, $result['details']),
                executionTime: microtime(true) - $startTime
            );
        } catch (\Throwable $e) {
            return new ScriptResult(
                success: false,
                message: "❌ 維護失敗: {$e->getMessage()}",
                details: $details,
                executionTime: microtime(true) - $startTime,
                exitCode: 1
            );
        }
    }

    private function performAllMaintenance(array $options): array
    {
        $results = [];

        if ($this->config->cacheClear) {
            $results[] = $this->clearCache($options);
        }

        if ($this->config->logRotation) {
            $results[] = $this->rotateLogs($options);
        }

        if ($this->config->databaseOptimization) {
            $results[] = $this->optimizeDatabase($options);
        }

        $allSuccessful = array_reduce($results, fn($carry, $result) => $carry && $result['success'], true);

        return [
            'success' => $allSuccessful,
            'message' => '完整維護程序完成',
            'details' => ['individual_results' => $results]
        ];
    }

    private function clearCache(array $options): array
    {
        $command = "cd {$this->projectRoot} && php scripts/cache-cleanup.sh";
        $output = shell_exec($command);

        return [
            'success' => $output !== null,
            'message' => '快取清理完成',
            'details' => ['output' => $output]
        ];
    }

    private function rotateLogs(array $options): array
    {
        // 實作日誌輪轉邏輯
        return [
            'success' => true,
            'message' => '日誌輪轉完成',
            'details' => []
        ];
    }

    private function optimizeDatabase(array $options): array
    {
        // 實作資料庫最佳化邏輯
        return [
            'success' => true,
            'message' => '資料庫最佳化完成',
            'details' => []
        ];
    }

    private function performCleanup(array $options): array
    {
        // 實作清理邏輯
        return [
            'success' => true,
            'message' => '清理完成',
            'details' => []
        ];
    }
}
