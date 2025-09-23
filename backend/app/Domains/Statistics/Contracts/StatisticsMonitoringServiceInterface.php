<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use DateTime;

/**
 * 統計監控服務合約介面.
 *
 * 定義統計功能監控的標準操作，包括健康檢查、
 * 效能監控、事件記錄和警報系統。
 */
interface StatisticsMonitoringServiceInterface
{
    /**
     * 取得統計計算時間監控資料.
     *
     * @return array{
     *     avg_calculation_time: float,
     *     max_calculation_time: float,
     *     total_calculations: int,
     *     failed_calculations: int
     * }
     */
    public function getCalculationTimeMetrics(): array;

    /**
     * 取得快取命中率監控資料.
     *
     * @return array{
     *     hit_rate: float,
     *     miss_rate: float,
     *     total_requests: int,
     *     cache_size: int
     * }
     */
    public function getCacheMetrics(): array;

    /**
     * 取得 API 回應時間監控資料.
     *
     * @return array{
     *     avg_response_time: float,
     *     p95_response_time: float,
     *     p99_response_time: float,
     *     total_requests: int,
     *     error_rate: float
     * }
     */
    public function getApiResponseTimeMetrics(): array;

    /**
     * 取得錯誤率監控資料.
     *
     * @return array{
     *     total_errors: int,
     *     error_rate: float,
     *     slow_query_count: int,
     *     critical_errors: int
     * }
     */
    public function getErrorMetrics(): array;

    /**
     * 執行完整的健康檢查.
     *
     * @return array{
     *     status: string,
     *     timestamp: string,
     *     checks: array,
     *     overall_health: int
     * }
     */
    public function performHealthCheck(): array;

    /**
     * 記錄統計操作事件.
     */
    public function logStatisticsEvent(string $eventType, array $context = []): bool;

    /**
     * 產生監控摘要報告.
     *
     * @return array{
     *     summary: string,
     *     metrics: array,
     *     health_status: array,
     *     alerts: array,
     *     generated_at: string
     * }
     */
    public function generateMonitoringSummary(): array;

    /**
     * 清理過期的監控記錄.
     */
    public function cleanupOldMonitoringData(): int;

    /**
     * 檢測系統警告條件.
     *
     * @return array<array{type: string, severity: string, message: string, timestamp: string}>
     */
    public function checkAlertConditions(): array;

    /**
     * 取得特定時間範圍的監控統計.
     *
     * @return array{
     *     period: string,
     *     total_calculations: int,
     *     avg_response_time: float,
     *     error_count: int,
     *     cache_performance: array
     * }
     */
    public function getMonitoringStatistics(DateTime $startDate, DateTime $endDate): array;
}
