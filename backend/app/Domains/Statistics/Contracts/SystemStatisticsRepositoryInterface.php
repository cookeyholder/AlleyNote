<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;

/**
 * 系統統計資料存取介面.
 *
 * 定義系統層級統計資料的存取操作，
 * 專門處理系統效能與使用量分析。
 *
 * 設計原則：
 * - 專注於系統層級的統計分析
 * - 提供系統效能監控資料
 * - 支援系統健康狀態評估
 * - 完整的系統使用量追蹤
 *
 * @author GitHub Copilot
 * @version 1.0.0
 * @since 2025-01-05
 */
interface SystemStatisticsRepositoryInterface
{
    /**
     * 取得系統整體效能統計.
     */
    public function getSystemPerformanceStats(StatisticsPeriod $period): array;

    /**
     * 取得資料庫使用統計.
     */
    public function getDatabaseUsageStats(): array;

    /**
     * 取得系統活動熱圖資料.
     */
    public function getSystemActivityHeatmap(StatisticsPeriod $period): array;

    /**
     * 取得快取效能統計.
     */
    public function getCachePerformanceStats(StatisticsPeriod $period): array;

    /**
     * 取得 API 使用統計.
     */
    public function getApiUsageStats(StatisticsPeriod $period): array;

    /**
     * 取得錯誤與異常統計.
     */
    public function getErrorAndExceptionStats(StatisticsPeriod $period): array;

    /**
     * 取得系統資源使用統計.
     */
    public function getSystemResourceUsageStats(): array;

    /**
     * 取得系統安全統計.
     */
    public function getSystemSecurityStats(StatisticsPeriod $period): array;

    /**
     * 取得系統備份與維護統計.
     */
    public function getSystemBackupAndMaintenanceStats(): array;

    /**
     * 取得系統配置與版本資訊.
     */
    public function getSystemConfigurationInfo(): array;
}
