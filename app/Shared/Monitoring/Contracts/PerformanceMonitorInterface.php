<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Contracts;

/**
 * 效能監控介面。
 *
 * 提供應用程式效能監控功能，包含請求追蹤、效能指標收集和分析
 */
interface PerformanceMonitorInterface
{
    /**
     * 開始監控一個操作。
     */
    public function startMonitoring(string $operation, array $context = []): string;

    /**
     * 結束監控一個操作。
     */
    public function endMonitoring(string $monitoringId, array $context = []): void;

    /**
     * 記錄一個性能指標。
     */
    public function recordMetric(string $name, float $value, string $unit = 'ms', array $tags = []): void;

    /**
     * 記錄一個計數器指標。
     */
    public function incrementCounter(string $name, array $tags = []): void;

    /**
     * 記錄一個計量表指標。
     */
    public function recordGauge(string $name, float $value, array $tags = []): void;

    /**
     * 記錄一個直方圖指標。
     */
    public function recordHistogram(string $name, float $value, array $tags = []): void;

    /**
     * 取得效能統計資料。
     */
    public function getPerformanceStats(?string $operation = null): array;

    /**
     * 取得慢查詢記錄。
     */
    public function getSlowQueries(int $limit = 10): array;

    /**
     * 取得效能警告。
     */
    public function getPerformanceWarnings(): array;

    /**
     * 清除舊的效能資料。
     */
    public function cleanupOldData(int $daysToKeep = 7): int;
}
