<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * 批量修復所有 DTO 和 Service 類型問題
 */
class BatchDtoServiceFixer
{
    public function run(): void
    {
        echo "開始批量修復所有 DTO 和 Service 類型問題...\n";

        $this->fixStatisticsOverviewDTO();
        $this->fixUserActivityDTO();
        $this->fixSourceDistributionDTO();
        $this->fixValueObjects();
        $this->fixEntities();

        echo "批量修復完成!\n";
    }

    private function fixStatisticsOverviewDTO(): void
    {
        echo "修復 StatisticsOverviewDTO...\n";
        $file = __DIR__ . '/../app/Application/DTOs/Statistics/StatisticsOverviewDTO.php';

        $content = file_get_contents($file);

        // 修復 fromArray 方法
        $pattern = '/public static function fromArray\(array \$data\): self\s*\{.*?return new self\(.*?\);.*?\}/s';
        $replacement = 'public static function fromArray(array $data): self
    {
        // 確保期間資料存在且正確
        $periodData = $data[\'period\'] ?? [];
        $startDate = is_string($periodData[\'start_date\'] ?? null) ? $periodData[\'start_date\'] : \'now\';
        $endDate = is_string($periodData[\'end_date\'] ?? null) ? $periodData[\'end_date\'] : \'now\';
        $periodType = $periodData[\'type\'] ?? \'daily\';

        $period = StatisticsPeriod::create(
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate),
            PeriodType::from($periodType),
        );

        // 安全地提取統計指標
        $totalViewsData = $data[\'total_views\'] ?? [];
        $totalViews = StatisticsMetric::count(
            is_numeric($totalViewsData[\'value\'] ?? 0) ? (int)$totalViewsData[\'value\'] : 0,
            is_string($totalViewsData[\'description\'] ?? \'\') ? $totalViewsData[\'description\'] : \'總瀏覽數\'
        );

        $totalPostsData = $data[\'total_posts\'] ?? [];
        $totalPosts = StatisticsMetric::count(
            is_numeric($totalPostsData[\'value\'] ?? 0) ? (int)$totalPostsData[\'value\'] : 0,
            is_string($totalPostsData[\'description\'] ?? \'\') ? $totalPostsData[\'description\'] : \'總文章數\'
        );

        // 來源統計資料
        $sourceStatsData = $data[\'source_statistics\'] ?? [];
        if (!is_array($sourceStatsData)) {
            $sourceStatsData = [];
        }

        /** @var array<SourceStatistics> $sourceStatistics */
        $sourceStatistics = array_map(
            fn(array $sourceData) => SourceStatistics::create(
                SourceType::from($sourceData[\'source_type\'] ?? \'web\'),
                is_numeric($sourceData[\'count\'] ?? 0) ? (int)$sourceData[\'count\'] : 0,
                is_numeric($sourceData[\'percentage\'] ?? 0.0) ? (float)$sourceData[\'percentage\'] : 0.0,
            ),
            $sourceStatsData,
        );

        /** @var array<string, mixed> $additionalMetrics */
        $additionalMetrics = is_array($data[\'additional_metrics\'] ?? [])
            ? $data[\'additional_metrics\']
            : [];

        $generatedAt = is_string($data[\'generated_at\'] ?? null)
            ? $data[\'generated_at\']
            : \'now\';

        return new self(
            $period,
            $totalViews,
            $totalPosts,
            $sourceStatistics,
            $additionalMetrics,
            new DateTimeImmutable($generatedAt),
        );
    }';

        $content = preg_replace($pattern, $replacement, $content);

        file_put_contents($file, $content);
        echo "  - StatisticsOverviewDTO 修復完成\n";
    }

    private function fixUserActivityDTO(): void
    {
        echo "修復 UserActivityDTO...\n";
        $file = __DIR__ . '/../app/Application/DTOs/Statistics/UserActivityDTO.php';

        $content = file_get_contents($file);

        // 修復返回類型的方法
        $replacements = [
            'return $this->additionalMetrics[\'growth_rate\'];' => 'return is_numeric($this->additionalMetrics[\'growth_rate\'] ?? 0.0) ? (float)$this->additionalMetrics[\'growth_rate\'] : 0.0;',
            'return $this->additionalMetrics[\'avg_session_duration\'];' => 'return is_numeric($this->additionalMetrics[\'avg_session_duration\'] ?? 0.0) ? (float)$this->additionalMetrics[\'avg_session_duration\'] : 0.0;',
            'return $this->additionalMetrics[\'avg_page_views\'];' => 'return is_numeric($this->additionalMetrics[\'avg_page_views\'] ?? 0.0) ? (float)$this->additionalMetrics[\'avg_page_views\'] : 0.0;',
            'return $this->additionalMetrics[\'bounce_rate\'];' => 'return is_numeric($this->additionalMetrics[\'bounce_rate\'] ?? 0.0) ? (float)$this->additionalMetrics[\'bounce_rate\'] : 0.0;',
            'return $this->additionalMetrics[\'activity_time_analysis\'];' => 'return is_array($this->additionalMetrics[\'activity_time_analysis\'] ?? []) ? $this->additionalMetrics[\'activity_time_analysis\'] : [];',
        ];

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        // 修復 fromArray 方法的參數類型
        $pattern = '/public static function fromArray\(array \$data\): self\s*\{.*?return new self\(.*?\);.*?\}/s';
        $replacement = 'public static function fromArray(array $data): self
    {
        // 確保期間資料存在且正確
        $periodData = $data[\'period\'] ?? [];
        $startDate = is_string($periodData[\'start_date\'] ?? null) ? $periodData[\'start_date\'] : \'now\';
        $endDate = is_string($periodData[\'end_date\'] ?? null) ? $periodData[\'end_date\'] : \'now\';
        $periodType = $periodData[\'type\'] ?? \'daily\';

        $period = StatisticsPeriod::create(
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate),
            PeriodType::from($periodType),
        );

        // 安全地提取統計指標
        $activeUsers = StatisticsMetric::count(
            is_numeric($data[\'active_users\'] ?? 0) ? (int)$data[\'active_users\'] : 0,
            \'活躍用戶數\'
        );

        $newUsers = StatisticsMetric::count(
            is_numeric($data[\'new_users\'] ?? 0) ? (int)$data[\'new_users\'] : 0,
            \'新用戶數\'
        );

        $returningUsers = StatisticsMetric::count(
            is_numeric($data[\'returning_users\'] ?? 0) ? (int)$data[\'returning_users\'] : 0,
            \'回訪用戶數\'
        );

        /** @var array<array> $topActiveUsers */
        $topActiveUsers = is_array($data[\'top_active_users\'] ?? [])
            ? $data[\'top_active_users\']
            : [];

        /** @var array<string, mixed> $activityPatterns */
        $activityPatterns = is_array($data[\'activity_patterns\'] ?? [])
            ? $data[\'activity_patterns\']
            : [];

        /** @var array<string, mixed> $engagementMetrics */
        $engagementMetrics = is_array($data[\'engagement_metrics\'] ?? [])
            ? $data[\'engagement_metrics\']
            : [];

        $generatedAt = is_string($data[\'generated_at\'] ?? null)
            ? $data[\'generated_at\']
            : \'now\';

        return new self(
            $period,
            $activeUsers,
            $newUsers,
            $returningUsers,
            $topActiveUsers,
            $activityPatterns,
            $engagementMetrics,
            new DateTimeImmutable($generatedAt),
        );
    }';

        $content = preg_replace($pattern, $replacement, $content);

        file_put_contents($file, $content);
        echo "  - UserActivityDTO 修復完成\n";
    }

    private function fixSourceDistributionDTO(): void
    {
        echo "修復 SourceDistributionDTO 剩餘問題...\n";
        $file = __DIR__ . '/../app/Application/DTOs/Statistics/SourceDistributionDTO.php';

        $content = file_get_contents($file);

        // 修復陣列取值問題
        $content = str_replace(
            '$trend = $this->distributionAnalysis[\'growth_trends\'][$source->sourceType->value];',
            '$trend = $this->distributionAnalysis[\'growth_trends\'][$source->sourceType->value] ?? null;
            if (!is_array($trend)) {
                return 0.0;
            }',
            $content
        );

        // 修復運算錯誤
        $content = str_replace(
            'return $trend[\'count\']->value / $trend[\'value\']->value;',
            'return is_numeric($trend[\'count\'] ?? 0) && is_numeric($trend[\'value\'] ?? 0) && $trend[\'value\'] > 0
                ? (float)$trend[\'count\'] / (float)$trend[\'value\']
                : 0.0;',
            $content
        );

        file_put_contents($file, $content);
        echo "  - SourceDistributionDTO 修復完成\n";
    }

    private function fixValueObjects(): void
    {
        echo "修復 ValueObjects...\n";
        $file = __DIR__ . '/../app/Domains/Statistics/ValueObjects/SourceStatistics.php';

        $content = file_get_contents($file);

        // 修復建構函式參數
        $content = str_replace(
            'array $additionalMetrics,',
            'array $additionalMetrics = [],',
            $content
        );

        // 添加型別檢查註解
        $content = str_replace(
            'public function __construct(',
            '/**
     * @param array<string, StatisticsMetric> $additionalMetrics
     */
    public function __construct(',
            $content
        );

        file_put_contents($file, $content);
        echo "  - SourceStatistics 修復完成\n";
    }

    private function fixEntities(): void
    {
        echo "修復 Entities...\n";
        $file = __DIR__ . '/../app/Domains/Statistics/Entities/StatisticsSnapshot.php';

        $content = file_get_contents($file);

        // 修復建構函式中的陣列參數
        $content = str_replace(
            '$sourceStats,',
            '/** @var array<SourceStatistics> $sourceStats */ $sourceStats,',
            $content
        );

        $content = str_replace(
            '$additionalMetrics,',
            '/** @var array<string, StatisticsMetric> $additionalMetrics */ $additionalMetrics,',
            $content
        );

        file_put_contents($file, $content);
        echo "  - StatisticsSnapshot 修復完成\n";
    }
}

// 執行修復
(new BatchDtoServiceFixer())->run();
