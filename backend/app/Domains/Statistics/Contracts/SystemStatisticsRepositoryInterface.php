<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;

/**
 * 系統統計資料存取介面.
 *
 * 定義系統層級統計資料的查詢方法。
 * 專注於系統效能、資源使用、安全性等統計資料的存取。
 */
interface SystemStatisticsRepositoryInterface
{
    /**
     * 取得系統整體效能統計.
     *
     * @param StatisticsPeriod $period 統計週期
     */
    public function getSystemPerformanceStats(StatisticsPeriod $period): array;

    /**
     * 取得資料庫使用統計.
     */
    public function getDatabaseUsageStats(): array;

    /**
     * 取得系統活動熱圖資料.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array> 活動熱圖資料（日期 => 小時活動量）
     */
    public function getSystemActivityHeatmap(StatisticsPeriod $period): array;

    /**
     * 取得快取效能統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{cache_hits: int, cache_misses: int, cache_hit_rate: float, cache_size_mb: float, cache_entries: int} 快取效能統計
     */
    public function getCachePerformanceStats(StatisticsPeriod $period): array;

    /**
     * 取得 API 使用統計.
     *
     * @param StatisticsPeriod $period 統計週期
     */
    public function getApiUsageStats(StatisticsPeriod $period): array;

    /**
     * 取得錯誤與異常統計.
     *
     * @param StatisticsPeriod $period 統計週期
     */
    public function getErrorAndExceptionStats(StatisticsPeriod $period): array;

    /**
     * 取得系統資源使用統計.
     *
     * @return array{cpu_usage_percentage: float, memory_usage_mb: float, memory_usage_percentage: float, disk_usage_mb: float, disk_usage_percentage: float, network_io_mb: float} 系統資源使用統計
     */
    public function getSystemResourceUsageStats(): array;

    /**
     * 取得系統安全統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{security_events: int, unique_users_involved: int, unique_ips: int, failed_login_attempts: int, suspicious_activities: int, blocked_ips: int} 安全統計
     */
    public function getSystemSecurityStats(StatisticsPeriod $period): array;

    /**
     * 取得系統備份與維護統計.
     *
     * @return array{last_backup_date: string|null, backup_frequency_days: int, backup_size_mb: float, maintenance_windows: int, system_uptime_percentage: float} 備份維護統計
     */
    public function getSystemBackupAndMaintenanceStats(): array;

    /**
     * 取得系統配置與版本資訊.
     *
     * @return array{php_version: string, php_memory_limit: string, php_max_execution_time: string, database_type: string, database_version: string, system_timezone: string, system_time: string, application_environment: string} 系統配置資訊
     */
    public function getSystemConfigurationInfo(): array;

    /**
     * 取得系統健康檢查統計.
     *
     * @param StatisticsPeriod $period 統計週期
     */
    public function getSystemHealthCheckStats(StatisticsPeriod $period): array;

    /**
     * 取得系統負載統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{avg_response_time: float, peak_concurrent_users: int, total_requests: int, avg_requests_per_second: float} 系統負載統計
     */
    public function getSystemLoadStats(StatisticsPeriod $period): array;

    /**
     * 取得系統儲存空間統計.
     *
     * @return array{total_storage_mb: float, used_storage_mb: float, available_storage_mb: float, storage_usage_percentage: float, file_count: int} 儲存空間統計
     */
    public function getSystemStorageStats(): array;

    /**
     * 取得系統網路統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{total_bandwidth_mb: float, incoming_traffic_mb: float, outgoing_traffic_mb: float, unique_ip_addresses: int} 網路統計
     */
    public function getSystemNetworkStats(StatisticsPeriod $period): array;

    /**
     * 取得系統會話統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{total_sessions: int, active_sessions: int, avg_session_duration: float, bounce_rate: float} 會話統計
     */
    public function getSystemSessionStats(StatisticsPeriod $period): array;

    /**
     * 取得系統版本更新統計.
     *
     * @return array{current_version: string, last_update_date: string|null, pending_updates: int, security_patches: int} 版本更新統計
     */
    public function getSystemVersionUpdateStats(): array;

    /**
     * 取得系統監控警報統計.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{total_alerts: int, critical_alerts: int, warning_alerts: int, info_alerts: int, resolved_alerts: int} 監控警報統計
     */
    public function getSystemMonitoringAlertStats(StatisticsPeriod $period): array;

    /**
     * 檢查系統在指定週期是否正常運行.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return bool 正常運行時回傳 true，否則回傳 false
     */
    public function isSystemHealthyInPeriod(StatisticsPeriod $period): bool;

    /**
     * 取得系統關鍵指標摘要
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{uptime_percentage: float, error_rate: float, avg_response_time: float, peak_memory_usage: float, total_events: int} 關鍵指標摘要
     */
    public function getSystemKeyMetricsSummary(StatisticsPeriod $period): array;

    /**
     * 取得系統效能指標.
     *
     * @param StatisticsPeriod $period 統計週期
     * @return array{avg_response_time: float, peak_memory_usage: float, cpu_usage: float, throughput: float} 效能指標
     */
    public function getPerformanceMetrics(StatisticsPeriod $period): array;

    /**
     * 取得錯誤統計資料.
     *
     * @param StatisticsPeriod $period 統計週期
     */
    public function getErrorStatistics(StatisticsPeriod $period): array;

    /**
     * 取得資源使用統計.
     *
     * @param StatisticsPeriod $period 統計週期
     */
    public function getResourceUsageStatistics(StatisticsPeriod $period): array;
}
