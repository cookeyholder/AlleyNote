<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Contracts;

/**
 * 快取監控介面。
 *
 * 提供快取系統的監控功能，包含效能指標收集、健康狀態檢查和統計分析
 */
interface CacheMonitorInterface
{
    /**
     * 記錄快取操作。
     * 
     * @param string $operation 操作類型 (get, set, delete, flush, etc.)
     * @param string $driver 使用的驅動名稱
     * @param bool $success 操作是否成功
     * @param float $duration 操作耗時（毫秒）
     * @param array $context 額外上下文資訊
     */
    public function recordOperation(
        string $operation, 
        string $driver, 
        bool $success, 
        float $duration, 
        array $context = []
    ): void;

    /**
     * 記錄快取命中。
     * 
     * @param string $driver 驅動名稱
     * @param string $key 快取鍵
     * @param float $duration 操作耗時
     */
    public function recordHit(string $driver, string $key, float $duration): void;

    /**
     * 記錄快取未命中。
     * 
     * @param string $driver 驅動名稱
     * @param string $key 快取鍵
     * @param float $duration 操作耗時
     */
    public function recordMiss(string $driver, string $key, float $duration): void;

    /**
     * 記錄快取錯誤。
     * 
     * @param string $driver 驅動名稱
     * @param string $operation 操作類型
     * @param string $error 錯誤訊息
     * @param array $context 錯誤上下文
     */
    public function recordError(string $driver, string $operation, string $error, array $context = []): void;

    /**
     * 記錄驅動健康狀態。
     * 
     * @param string $driver 驅動名稱
     * @param bool $healthy 是否健康
     * @param array $details 健康檢查詳細資訊
     */
    public function recordHealthStatus(string $driver, bool $healthy, array $details = []): void;

    /**
     * 取得快取統計資料。
     * 
     * @param string|null $driver 指定驅動，null 表示所有驅動
     * @param string|null $timeRange 時間範圍 (1h, 24h, 7d, 30d)
     */
    public function getCacheStats(?string $driver = null, ?string $timeRange = null): array;

    /**
     * 取得命中率統計。
     * 
     * @param string|null $timeRange 時間範圍
     */
    public function getHitRateStats(?string $timeRange = null): array;

    /**
     * 取得驅動效能比較。
     */
    public function getDriverPerformanceComparison(): array;

    /**
     * 取得慢速快取操作。
     * 
     * @param int $limit 限制數量
     * @param int $thresholdMs 閾值（毫秒）
     */
    public function getSlowCacheOperations(int $limit = 10, int $thresholdMs = 100): array;

    /**
     * 取得快取容量使用情況。
     */
    public function getCacheCapacityStats(): array;

    /**
     * 取得快取錯誤統計。
     * 
     * @param string|null $timeRange 時間範圍
     */
    public function getErrorStats(?string $timeRange = null): array;

    /**
     * 取得快取健康狀態。
     */
    public function getHealthOverview(): array;

    /**
     * 清理舊的監控資料。
     * 
     * @param int $daysToKeep 保留天數
     */
    public function cleanup(int $daysToKeep = 7): int;

    /**
     * 匯出監控資料。
     * 
     * @param string $format 匯出格式 (json, csv)
     * @param string|null $timeRange 時間範圍
     */
    public function exportData(string $format = 'json', ?string $timeRange = null): string;
}