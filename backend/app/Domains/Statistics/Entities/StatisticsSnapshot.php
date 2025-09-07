<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Entities;

use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\Events\StatisticsSnapshotCreated;
use App\Domains\Statistics\Events\StatisticsSnapshotUpdated;
use App\Domains\Statistics\Exceptions\InvalidStatisticsSnapshotException;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Domain\Entity\AggregateRoot;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;

/**
 * 統計快照實體（聚合根）
 * 表示特定時間段的統計資料快照.
 */
class StatisticsSnapshot extends AggregateRoot
{
    /**
     * @param Uuid $id 識別碼
     * @param StatisticsPeriod $period 統計週期
     * @param StatisticsMetric $totalPosts 總文章數
     * @param StatisticsMetric $totalViews 總瀏覽數
     * @param array<SourceStatistics> $sourceStats 來源統計
     * @param array<string, StatisticsMetric> $additionalMetrics 額外指標
     * @param DateTimeImmutable $createdAt 建立時間
     * @param DateTimeImmutable|null $updatedAt 更新時間
     */
    private function __construct(
        private Uuid $id,
        private StatisticsPeriod $period,
        private StatisticsMetric $totalPosts,
        private StatisticsMetric $totalViews,
        private array /** @var array<SourceStatistics> $sourceStats */ $sourceStats,
        private array /** @var array<string, StatisticsMetric> $additionalMetrics */ $additionalMetrics,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * 建立新的統計快照.
     *
     * @param array<SourceStatistics> $sourceStats
     * @param array<string, StatisticsMetric> $additionalMetrics
     */
    public static function create(
        Uuid $id,
        StatisticsPeriod $period,
        int $totalPosts = 0,
        int $totalViews = 0,
        /** @var array<string, mixed> */
        array $sourceStats = [],
        /** @var array<string, mixed> */
        array $additionalMetrics = [],
    ): self {
        // 驗證來源統計
        foreach ($sourceStats as $sourceStatistics) {
            if (!$sourceStatistics instanceof SourceStatistics) {
                throw new InvalidStatisticsSnapshotException(
                    '來源統計必須是 SourceStatistics 實例',
                );
            }
        }

        // 驗證額外指標
        foreach ($additionalMetrics as $key => $metric) {
            if (!$metric instanceof StatisticsMetric) {
                throw new InvalidStatisticsSnapshotException(
                    "額外指標 '{$key}' 必須是 StatisticsMetric 實例",
                );
            }
        }

        $totalPostsMetric = StatisticsMetric::count($totalPosts, '總文章數');
        $totalViewsMetric = StatisticsMetric::count($totalViews, '總瀏覽數');
        $now = new DateTimeImmutable();

        // 確保類型正確性
        foreach ($sourceStats as $sourceStat) {
            if (!$sourceStat instanceof SourceStatistics) {
                throw new InvalidStatisticsSnapshotException('所有來源統計必須是 SourceStatistics 實例');
            }
        }

        foreach ($additionalMetrics as $key => $metric) {
            if (!is_string($key) || !$metric instanceof StatisticsMetric) {
                throw new InvalidStatisticsSnapshotException('額外指標必須是 string => StatisticsMetric 格式');
            }
        }

        $snapshot = new self(
            $id,
            $period,
            $totalPostsMetric,
            $totalViewsMetric,
            $sourceStats,
            $additionalMetrics,
            $now,
        );

        // 記錄領域事件
        $snapshot->record(new StatisticsSnapshotCreated(
            $id,
            $period,
            $totalPostsMetric,
            $totalViewsMetric,
            $now,
        ));

        return $snapshot;
    }

    /**
     * 從資料重建統計快照.
     *
     * @param array<SourceStatistics> $sourceStats
     * @param array<string, StatisticsMetric> $additionalMetrics
     */
    public static function fromData(
        Uuid $id,
        StatisticsPeriod $period,
        StatisticsMetric $totalPosts,
        StatisticsMetric $totalViews,
        /** @var array<string, mixed> */
        array $sourceStats,
        /** @var array<string, mixed> */
        array $additionalMetrics,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $updatedAt = null,
    ): self {
        // 確保類型正確性
        foreach ($sourceStats as $sourceStat) {
            if (!$sourceStat instanceof SourceStatistics) {
                throw new InvalidStatisticsSnapshotException('所有來源統計必須是 SourceStatistics 實例');
            }
        }

        foreach ($additionalMetrics as $key => $metric) {
            if (!is_string($key) || !$metric instanceof StatisticsMetric) {
                throw new InvalidStatisticsSnapshotException('額外指標必須是 string => StatisticsMetric 格式');
            }
        }

        return new self(
            $id,
            $period,
            $totalPosts,
            $totalViews,
            $sourceStats,
            $additionalMetrics,
            $createdAt,
            $updatedAt,
        );
    }

    /**
     * 取得識別碼.
     */
    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * 取得統計週期.
     */
    public function getPeriod(): StatisticsPeriod
    {
        return $this->period;
    }

    /**
     * 取得總文章數.
     */
    public function getTotalPosts(): StatisticsMetric
    {
        return $this->totalPosts;
    }

    /**
     * 取得總瀏覽數.
     */
    public function getTotalViews(): StatisticsMetric
    {
        return $this->totalViews;
    }

    /**
     * 取得所有來源統計.
     *
     * @return array<SourceStatistics>
     */
    public function getSourceStats(): array
    {
        return $this->sourceStats;
    }

    /**
     * 取得特定來源的統計.
     */
    public function getSourceStatistics(SourceType $sourceType): ?SourceStatistics
    {
        foreach ($this->sourceStats as $sourceStats) {
            if ($sourceStats->sourceType === $sourceType) {
                return $sourceStats;
            }
        }

        return null;
    }

    /**
     * 取得額外指標.
     */
    public function getAdditionalMetric(string $key): ?StatisticsMetric
    {
        return $this->additionalMetrics[$key] ?? null;
    }

    /**
     * 取得所有額外指標.
     *
     * @return array<string, StatisticsMetric>
     */
    public function getAdditionalMetrics(): array
    {
        return $this->additionalMetrics;
    }

    /**
     * 取得建立時間.
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * 取得更新時間.
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * 判斷是否已更新過.
     */
    public function isUpdated(): bool
    {
        return $this->updatedAt !== null;
    }

    /**
     * 判斷快照是否為空（無資料）.
     */
    public function isEmpty(): bool
    {
        return $this->totalPosts->isZero() && $this->totalViews->isZero();
    }

    /**
     * 判斷是否有文章資料.
     */
    public function hasPosts(): bool
    {
        return $this->totalPosts->isPositive();
    }

    /**
     * 判斷是否有瀏覽資料.
     */
    public function hasViews(): bool
    {
        return $this->totalViews->isPositive();
    }

    /**
     * 判斷是否有來源統計資料.
     */
    public function hasSourceStats(): bool
    {
        return !empty($this->sourceStats);
    }

    /**
     * 判斷快照是否為當前期間.
     */
    public function isCurrentPeriod(): bool
    {
        return $this->period->isCurrent();
    }

    /**
     * 判斷快照是否過期（需要更新）.
     */
    public function isStale(int $maxAgeInSeconds = 3600): bool
    {
        $ageInSeconds = new DateTimeImmutable()->getTimestamp() - $this->createdAt->getTimestamp();

        return $ageInSeconds > $maxAgeInSeconds;
    }

    /**
     * 更新文章數量.
     */
    public function updatePostCount(int $newCount): void
    {
        if ($newCount < 0) {
            throw new InvalidStatisticsSnapshotException(
                '文章數量不能為負數',
            );
        }

        $this->totalPosts = StatisticsMetric::count($newCount, '總文章數');
        $this->markAsUpdated();
    }

    /**
     * 更新瀏覽數量.
     */
    public function updateViewCount(int $newCount): void
    {
        if ($newCount < 0) {
            throw new InvalidStatisticsSnapshotException(
                '瀏覽數量不能為負數',
            );
        }

        $this->totalViews = StatisticsMetric::count($newCount, '總瀏覽數');
        $this->markAsUpdated();
    }

    /**
     * 更新來源統計.
     *
     * @param array<SourceStatistics> $sourceStats
     */
    public function updateSourceStats(array $sourceStats): void
    {
        // 驗證來源統計
        foreach ($sourceStats as $sourceStatistics) {
            if (!$sourceStatistics instanceof SourceStatistics) {
                throw new InvalidStatisticsSnapshotException(
                    '來源統計必須是 SourceStatistics 實例',
                );
            }
        }

        $this->sourceStats = $sourceStats;
        $this->markAsUpdated();
    }

    /**
     * 新增來源統計.
     */
    public function addSourceStatistics(SourceStatistics $sourceStatistics): void
    {
        // 檢查是否已存在相同來源類型的統計
        foreach ($this->sourceStats as $index => $existingStats) {
            if ($existingStats->sourceType === $sourceStatistics->sourceType) {
                $this->sourceStats[$index] = $sourceStatistics;
                $this->markAsUpdated();

                return;
            }
        }

        // 新增統計
        $this->sourceStats[] = $sourceStatistics;
        $this->markAsUpdated();
    }

    /**
     * 移除特定來源的統計.
     */
    public function removeSourceStatistics(SourceType $sourceType): void
    {
        $originalCount = count($this->sourceStats);

        $this->sourceStats = array_filter(
            $this->sourceStats,
            fn(SourceStatistics $stats): array => $stats->sourceType !== $sourceType,
        );

        // 重新索引陣列
        $this->sourceStats = array_values($this->sourceStats);

        if (count($this->sourceStats) < $originalCount) {
            $this->markAsUpdated();
        }
    }

    /**
     * 新增或更新額外指標.
     */
    public function setAdditionalMetric(string $key, StatisticsMetric $metric): void
    {
        $this->additionalMetrics[$key] = $metric;
        $this->markAsUpdated();
    }

    /**
     * 移除額外指標.
     */
    public function removeAdditionalMetric(string $key): void
    {
        if (isset($this->additionalMetrics[$key])) {
            unset($this->additionalMetrics[$key]);
            $this->markAsUpdated();
        }
    }

    /**
     * 計算平均每日文章數.
     */
    public function getAveragePostsPerDay(): StatisticsMetric
    {
        $days = $this->period->getDaysCount();
        if ($days === 0) {
            return StatisticsMetric::create(0, '篇/日', '平均每日文章數', 2);
        }

        $average = (float) $this->totalPosts->value / $days;

        return StatisticsMetric::create($average, '篇/日', '平均每日文章數', 2);
    }

    /**
     * 計算平均每日瀏覽數.
     */
    public function getAverageViewsPerDay(): StatisticsMetric
    {
        $days = $this->period->getDaysCount();
        if ($days === 0) {
            return StatisticsMetric::create(0, '次/日', '平均每日瀏覽數', 2);
        }

        $average = (float) $this->totalViews->value / $days;

        return StatisticsMetric::create($average, '次/日', '平均每日瀏覽數', 2);
    }

    /**
     * 計算每篇文章平均瀏覽數.
     */
    public function getAverageViewsPerPost(): StatisticsMetric
    {
        if ($this->totalPosts->isZero()) {
            return StatisticsMetric::create(0, '次/篇', '每篇文章平均瀏覽數', 2);
        }

        $average = (float) $this->totalViews->value / (float) $this->totalPosts->value;

        return StatisticsMetric::create($average, '次/篇', '每篇文章平均瀏覽數', 2);
    }

    /**
     * 取得最主要的來源類型.
     */
    public function getPrimarySourceType(): ?SourceType
    {
        if (empty($this->sourceStats)) {
            return null;
        }

        $primarySource = null;
        $maxCount = 0;

        foreach ($this->sourceStats as $sourceStats) {
            if ($sourceStats->getCountValue() > $maxCount) {
                $maxCount = $sourceStats->getCountValue();
                $primarySource = $sourceStats->sourceType;
            }
        }

        return $primarySource;
    }

    /**
     * 取得排序後的來源統計（按計數降序）.
     *
     * @return array<SourceStatistics>
     */
    public function getSortedSourceStats(): array
    {
        $sortedStats = $this->sourceStats;

        usort(
            $sortedStats,
            fn(SourceStatistics $a, SourceStatistics $b): array => $a->compareTo($b),
        );

        return $sortedStats;
    }

    /**
     * 取得統計摘要.
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'period' => $this->period->getDisplayName(),
            'total_posts' => $this->totalPosts->getFormattedValueWithUnit(),
            'total_views' => $this->totalViews->getFormattedValueWithUnit(),
            'avg_posts_per_day' => $this->getAveragePostsPerDay()->getFormattedValueWithUnit(),
            'avg_views_per_day' => $this->getAverageViewsPerDay()->getFormattedValueWithUnit(),
            'avg_views_per_post' => $this->getAverageViewsPerPost()->getFormattedValueWithUnit(),
            'primary_source' => $this->getPrimarySourceType()?->getDisplayName(),
            'source_count' => count($this->sourceStats),
            'is_current' => $this->isCurrentPeriod(),
            'has_data' => !$this->isEmpty(),
        ];
    }

    /**
     * 轉換為陣列.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $sourceStatsArray = [];
        foreach ($this->sourceStats as $sourceStats) {
            $sourceStatsArray[] = $sourceStats->toArray();
        }

        $additionalMetricsArray = [];
        foreach ($this->additionalMetrics as $key => $metric) {
            $additionalMetricsArray[$key] = $metric->toArray();
        }

        return [
            'id' => $this->id->toString(),
            'period' => $this->period->toArray(),
            'total_posts' => $this->totalPosts->toArray(),
            'total_views' => $this->totalViews->toArray(),
            'source_stats' => $sourceStatsArray,
            'additional_metrics' => $additionalMetricsArray,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'summary' => $this->getSummary(),
        ];
    }

    /**
     * 標記為已更新.
     */
    private function markAsUpdated(): void
    {
        $oldUpdatedAt = $this->updatedAt;
        $this->updatedAt = new DateTimeImmutable();

        // 如果是第一次更新，記錄領域事件
        if ($oldUpdatedAt === null) {
            $this->record(new StatisticsSnapshotUpdated(
                $this->id,
                $this->period,
                $this->totalPosts,
                $this->totalViews,
                $this->updatedAt,
            ));
        }
    }
}
