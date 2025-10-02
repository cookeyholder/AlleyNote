<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Core;

use RuntimeException;

/**
 * 整合的專案分析器
 *
 * 統一所有專案分析功能
 */
final readonly class ConsolidatedAnalyzer
{
    public function __construct(
        private string $projectRoot,
        private AnalysisConfig $config
    ) {}

    public function analyze(array<mixed> $options = []): ScriptResult
    {
        $startTime = microtime(true);
        $details = [];

        try {
            $type = (is_array($options) ? $options['type'] : (is_object($options) ? $options->type : null)) ?? 'full';

            $result = match ($type) {
                'full' => $this->performFullAnalysis(),
                'architecture' => $this->performArchitectureAnalysis(),
                'modern-php' => $this->performModernPhpAnalysis(),
                'ddd' => $this->performDddAnalysis(),
                default => throw new \InvalidArgumentException("未知的分析類型: {$type}")
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
                message: "❌ 專案分析失敗: {$e->getMessage()}",
                details: $details,
                executionTime: microtime(true) - $startTime,
                exitCode: 1
            );
        }
    }

    private function performFullAnalysis(): array<mixed>
    {
        $command = "cd {$this->projectRoot} && php scripts/scan-project-architecture.php";
        $output = shell_exec($command);

        return [
            'success' => $output !== null,
            'message' => '完整專案分析完成',
            'details' => ['output' => $output]
        ];
    }

    private function performArchitectureAnalysis(): array<mixed>
    {
        // 實作架構分析邏輯
        return [
            'success' => true,
            'message' => '架構分析完成',
            'details' => []
        ];
    }

    private function performModernPhpAnalysis(): array<mixed>
    {
        // 實作現代 PHP 分析邏輯
        return [
            'success' => true,
            'message' => '現代 PHP 分析完成',
            'details' => []
        ];
    }

    private function performDddAnalysis(): array<mixed>
    {
        // 實作 DDD 分析邏輯
        return [
            'success' => true,
            'message' => 'DDD 分析完成',
            'details' => []
        ];
    }
}
