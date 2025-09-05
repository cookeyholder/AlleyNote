<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;

/**
 * 使用者統計資料存取介面.
 *
 * 定義使用者相關統計資料的存取操作，
 * 專門處理使用者行為分析與統計計算。
 *
 * 設計原則：
 * - 專注於使用者相關的統計分析
 * - 提供豐富的使用者行為洞察
 * - 支援使用者生命週期分析
 * - 完整的使用者參與度評估
 *
 * @author GitHub Copilot
 * @version 1.0.0
 * @since 2025-01-05
 */
interface UserStatisticsRepositoryInterface
{
    /**
     * 計算指定週期內的新註冊使用者數量.
     */
    public function countNewUsersByPeriod(StatisticsPeriod $period): int;

    /**
     * 計算指定週期內的活躍使用者數量.
     */
    public function countActiveUsersByPeriod(StatisticsPeriod $period): int;

    /**
     * 計算指定週期內的總使用者數量.
     */
    public function countTotalUsersByPeriod(StatisticsPeriod $period): int;

    /**
     * 取得使用者註冊趨勢資料（按日期分組）.
     */
    public function getUserRegistrationTrends(StatisticsPeriod $period): array;

    /**
     * 取得使用者活躍度統計.
     */
    public function getUserActivityStats(StatisticsPeriod $period): array;

    /**
     * 取得最活躍的使用者列表.
     */
    public function getMostActiveUsers(StatisticsPeriod $period, int $limit = 10): array;

    /**
     * 取得使用者行為模式分析.
     */
    public function getUserBehaviorPatterns(StatisticsPeriod $period): array;

    /**
     * 計算使用者留存率.
     */
    public function getUserRetentionRate(StatisticsPeriod $period, int $retentionDays = 30): array;

    /**
     * 取得使用者分群統計.
     */
    public function getUserSegmentationStats(StatisticsPeriod $period): array;

    /**
     * 計算使用者流失率.
     */
    public function getUserChurnRate(StatisticsPeriod $period, int $inactivityDays = 30): array;

    /**
     * 取得新使用者首次活動分析.
     */
    public function getNewUserFirstActivityAnalysis(StatisticsPeriod $period): array;

    /**
     * 取得使用者互動網路分析.
     */
    public function getUserInteractionNetworkStats(StatisticsPeriod $period): array;

    /**
     * 計算使用者生命週期價值分析.
     */
    public function getUserLifetimeValueAnalysis(StatisticsPeriod $period): array;

    /**
     * 取得使用者地理分布統計.
     */
    public function getUserGeographicDistribution(StatisticsPeriod $period): array;

    /**
     * 計算使用者參與度評分.
     */
    public function getUserEngagementScores(StatisticsPeriod $period, int $limit = 100): array;
}
