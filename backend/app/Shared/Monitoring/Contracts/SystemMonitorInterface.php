<?php

declare(strict_types=1);

namespace App\Shared\Monitoring\Contracts;

/**
 * 系統監控介面。
 *
 * 提供系統效能和資源監控功能，支援多種指標收集和即時監控
 */
interface SystemMonitorInterface
{
    /**
     * 取得系統基本資訊。
     * @return array<string, mixed>
     */
    public function getSystemInfo(): array;

    /**
     * 取得記憶體使用統計。
     * @return array<string, mixed>
     */
    public function getMemoryUsage(): array;

    /**
     * 取得 CPU 使用率（如果可用）。
     * @return array<string, mixed>
     */
    public function getCpuUsage(): array;

    /**
     * 取得磁碟使用統計。
     * @return array<string, mixed>
     */
    public function getDiskUsage(string $path = '/'): array;

    /**
     * 取得資料庫連線狀態和統計。
     * @return array<string, mixed>
     */
    public function getDatabaseStatus(): array;

    /**
     * 取得應用程式健康狀態。
     * @return array<string, mixed>
     */
    public function getHealthCheck(): array;

    /**
     * 記錄系統指標到日誌。
     */
    public function logSystemMetrics(): void;

    /**
     * 檢查系統是否正常運作。
     */
    public function isSystemHealthy(): bool;

    /**
     * 取得所有系統指標。
     * @return array<string, mixed>
     */
    public function getAllMetrics(): array;
}
