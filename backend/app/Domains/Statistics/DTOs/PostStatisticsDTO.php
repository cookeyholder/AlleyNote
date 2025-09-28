<?php

declare(strict_types=1);

namespace App\Domains\Statistics\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 文章統計 DTO.
 *
 * 封裝文章相關統計資料的傳輸物件，包含狀態分布、來源分析、瀏覽統計等。
 * 專門用於文章統計 API 的回應格式與內部資料傳遞。
 */
class PostStatisticsDTO implements JsonSerializable
{
    /**
     * @param array<string, int> $byStatus 按狀態分組的統計
     * @param array<string, int> $bySource 按來源分組的統計
     * @param array<string, mixed> $viewsStatistics 瀏覽量統計
     * @param array<int, array<string, mixed>> $topPosts 熱門文章清單
     * @param array<string, mixed> $lengthStatistics 文章長度統計
     * @param array<string, int> $timeDistribution 發布時間分布
     * @param array<int, array<string, mixed>> $topAuthors 活躍作者清單
     * @param array<string, mixed> $pinnedStats 置頂文章統計
     * @param DateTimeImmutable|null $generatedAt 生成時間
     * @param array<string, mixed> $metadata 額外元資料
     */
    public function __construct(
        private readonly int $totalPosts,
        private readonly array $byStatus,
        private readonly array $bySource,
        private readonly array $viewsStatistics,
        private readonly array $topPosts,
        private readonly array $lengthStatistics,
        private readonly array $timeDistribution,
        private readonly array $topAuthors,
        private readonly array $pinnedStats,
        private readonly ?DateTimeImmutable $generatedAt = null,
        private readonly array $metadata = [],
    ) {
        $this->validateData();
    }

    /**
     * 從陣列建立實例.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $generatedAt = null;
        if (isset($data['generated_at']) && is_string($data['generated_at'])) {
            $generatedAt = new DateTimeImmutable($data['generated_at']);
        }

        // 過濾並清理資料
        $byStatus = self::filterIntegerMap($data['by_status'] ?? []);
        $bySource = self::filterIntegerMap($data['by_source'] ?? []);
        $timeDistribution = self::filterIntegerMap($data['time_distribution'] ?? []);

        return new self(
            totalPosts: isset($data['total_posts']) && is_numeric($data['total_posts']) ? (int) $data['total_posts'] : 0,
            byStatus: $byStatus,
            bySource: $bySource,
            viewsStatistics: self::ensureStringMixedArray($data['views_statistics'] ?? []),
            topPosts: self::ensureIntArrayStringMixedArray($data['top_posts'] ?? []),
            lengthStatistics: self::ensureStringMixedArray($data['length_statistics'] ?? []),
            timeDistribution: $timeDistribution,
            topAuthors: self::ensureIntArrayStringMixedArray($data['top_authors'] ?? []),
            pinnedStats: self::ensureStringMixedArray($data['pinned_stats'] ?? []),
            generatedAt: $generatedAt,
            metadata: self::ensureStringMixedArray($data['metadata'] ?? []),
        );
    }

    /**
     * 過濾字串鍵和整數值的映射.
     *
     * @param mixed $data
     * @return array<string, int>
     */
    private static function filterIntegerMap($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $filtered = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && is_numeric($value) && $value >= 0) {
                $filtered[$key] = (int) $value;
            }
        }

        return $filtered;
    }

    /**
     * 確保回傳 array<string, mixed> 型別.
     *
     * @param mixed $data
     * @return array<string, mixed>
     */
    private static function ensureStringMixedArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 確保回傳 array<int, array<string, mixed>> 型別.
     *
     * @param mixed $data
     * @return array<int, array<string, mixed>>
     */
    private static function ensureIntArrayStringMixedArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $filteredItem = [];
                foreach ($item as $key => $value) {
                    if (is_string($key)) {
                        $filteredItem[$key] = $value;
                    }
                }
                $result[] = $filteredItem;
            }
        }

        return $result;
    }

    // Getters
    public function getTotalPosts(): int
    {
        return $this->totalPosts;
    }

    /**
     * @return array<string, int>
     */
    public function getByStatus(): array
    {
        return $this->byStatus;
    }

    /**
     * @return array<string, int>
     */
    public function getBySource(): array
    {
        return $this->bySource;
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewsStatistics(): array
    {
        return $this->viewsStatistics;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopPosts(): array
    {
        return $this->topPosts;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLengthStatistics(): array
    {
        return $this->lengthStatistics;
    }

    /**
     * @return array<string, int>
     */
    public function getTimeDistribution(): array
    {
        return $this->timeDistribution;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopAuthors(): array
    {
        return $this->topAuthors;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPinnedStats(): array
    {
        return $this->pinnedStats;
    }

    public function getGeneratedAt(): ?DateTimeImmutable
    {
        return $this->generatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    // 計算方法
    public function getPublishedPosts(): int
    {
        return $this->byStatus['published'] ?? 0;
    }

    public function getPublishedCount(): int
    {
        return $this->getPublishedPosts();
    }

    public function getDraftPosts(): int
    {
        return $this->byStatus['draft'] ?? 0;
    }

    public function getDraftCount(): int
    {
        return $this->getDraftPosts();
    }

    public function getPendingPosts(): int
    {
        return $this->byStatus['pending'] ?? 0;
    }

    public function getArchivedPosts(): int
    {
        return $this->byStatus['archived'] ?? 0;
    }

    // 瀏覽統計方法
    public function getTotalViews(): int
    {
        $totalViews = $this->viewsStatistics['total_views'] ?? 0;

        return is_numeric($totalViews) ? (int) $totalViews : 0;
    }

    public function getAverageViewsPerPost(): float
    {
        $avgViews = $this->viewsStatistics['avg_views_per_post'] ?? 0.0;

        return is_numeric($avgViews) ? (float) $avgViews : 0.0;
    }

    public function getMostViewedPostViews(): int
    {
        $mostViewed = $this->viewsStatistics['most_viewed_post'] ?? 0;

        return is_numeric($mostViewed) ? (int) $mostViewed : 0;
    }

    // 其他計算方法
    public function getTopPost(): ?array
    {
        return $this->topPosts[0] ?? null;
    }

    public function getAverageLength(): int
    {
        $avgLength = $this->lengthStatistics['avg_length'] ?? 0;

        return is_numeric($avgLength) ? (int) $avgLength : 0;
    }

    public function getMinLength(): int
    {
        $minLength = $this->lengthStatistics['min_length'] ?? 0;

        return is_numeric($minLength) ? (int) $minLength : 0;
    }

    public function getMaxLength(): int
    {
        $maxLength = $this->lengthStatistics['max_length'] ?? 0;

        return is_numeric($maxLength) ? (int) $maxLength : 0;
    }

    public function getTopAuthor(): ?array
    {
        return $this->topAuthors[0] ?? null;
    }

    public function getTotalPinnedPosts(): int
    {
        $pinnedCount = $this->pinnedStats['total_pinned'] ?? 0;

        return is_numeric($pinnedCount) ? (int) $pinnedCount : 0;
    }

    public function getPinnedPostsViews(): int
    {
        $pinnedViews = $this->pinnedStats['pinned_views'] ?? 0;

        return is_numeric($pinnedViews) ? (int) $pinnedViews : 0;
    }

    public function getAveragePinnedEngagement(): float
    {
        $avgEngagement = $this->pinnedStats['avg_pinned_engagement'] ?? 0.0;

        return is_numeric($avgEngagement) ? (float) $avgEngagement : 0.0;
    }

    public function getMostActiveHour(): ?string
    {
        if (empty($this->timeDistribution)) {
            return null;
        }

        $maxCount = max($this->timeDistribution);
        $peakHours = array_keys($this->timeDistribution, $maxCount);

        return $peakHours[0] ?? null;
    }

    public function getPublishedPercentage(): float
    {
        if ($this->totalPosts === 0) {
            return 0.0;
        }

        return round(($this->getPublishedPosts() / $this->totalPosts) * 100, 2);
    }

    public function getEngagementMetrics(): array
    {
        $totalViews = $this->getTotalViews();
        $totalPosts = $this->getTotalPosts();

        $authorProductivity = $this->calculateAuthorProductivity();
        $avgProductivity = 0.0;
        if (!empty($this->topAuthors)) {
            $totalPostCount = array_sum(array_column($this->topAuthors, 'posts_count'));
            $avgProductivity = $totalPostCount / count($this->topAuthors);
        }

        return [
            'views_per_post_ratio' => $totalPosts > 0 ? round($totalViews / $totalPosts, 2) : 0.0,
            'pinned_engagement_rate' => $totalViews > 0 ? round(($this->getPinnedPostsViews() / $totalViews) * 100, 2) : 0.0,
            'author_productivity' => $avgProductivity,
        ];
    }

    public function getContentAnalysis(): array
    {
        return [
            'length_distribution' => [
                'average' => $this->getAverageLength(),
                'minimum' => $this->getMinLength(),
                'maximum' => $this->getMaxLength(),
            ],
            'optimal_length_score' => $this->calculateOptimalLengthScore(),
            'content_diversity' => $this->calculateContentDiversity(),
        ];
    }

    public function getPublishRate(): float
    {
        $total = $this->getTotalPosts();
        if ($total === 0) {
            return 0.0;
        }

        return round(($this->getPublishedCount() / $total) * 100, 2);
    }

    public function getAverageViews(): float
    {
        $avgViews = $this->viewsStatistics['avg_views_per_post'] ?? 0.0;

        return is_numeric($avgViews) ? (float) $avgViews : 0.0;
    }

    public function getMostPopularSource(): ?string
    {
        if (empty($this->bySource)) {
            return null;
        }

        return array_key_first($this->bySource);
    }

    public function getPeakHour(): ?string
    {
        if (empty($this->timeDistribution)) {
            return null;
        }

        $maxCount = max($this->timeDistribution);
        $peakHours = array_keys($this->timeDistribution, $maxCount);

        return $peakHours[0] ?? null;
    }

    /**
     * 取得內容品質指標.
     *
     * @return array<string, mixed>
     */
    public function getContentQualityMetrics(): array
    {
        $avgLength = $this->lengthStatistics['avg_length'] ?? 0;
        $avgLengthFloat = is_numeric($avgLength) ? (float) $avgLength : 0.0;
        $totalViews = $this->getTotalViews();
        $totalPosts = $this->getTotalPosts();

        return [
            'average_length' => $avgLengthFloat,
            'quality_score' => $this->calculateQualityScore(),
            'engagement_ratio' => $totalPosts > 0 ? round($totalViews / $totalPosts, 2) : 0.0,
            'publish_rate' => $this->getPublishRate(),
        ];
    }

    /**
     * 轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'total_posts' => $this->totalPosts,
            'by_status' => $this->byStatus,
            'by_source' => $this->bySource,
            'views_statistics' => $this->viewsStatistics,
            'top_posts' => $this->topPosts,
            'length_statistics' => $this->lengthStatistics,
            'time_distribution' => $this->timeDistribution,
            'top_authors' => $this->topAuthors,
            'pinned_stats' => $this->pinnedStats,
            'calculated_metrics' => [
                'total_posts' => $this->getTotalPosts(),
                'published_count' => $this->getPublishedCount(),
                'draft_count' => $this->getDraftCount(),
                'publish_rate' => $this->getPublishRate(),
                'average_views' => $this->getAverageViews(),
                'most_popular_source' => $this->getMostPopularSource(),
                'peak_hour' => $this->getPeakHour(),
            ],
            'engagement_metrics' => $this->getEngagementMetrics(),
            'content_analysis' => $this->getContentAnalysis(),
            'content_quality' => $this->getContentQualityMetrics(),
        ];

        if ($this->generatedAt !== null) {
            $data['generated_at'] = $this->generatedAt->format('Y-m-d\TH:i:s\Z');
        }

        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * JSON 序列化.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查是否有有效資料.
     */
    public function hasData(): bool
    {
        return $this->totalPosts > 0
            || !empty($this->byStatus)
            || !empty($this->viewsStatistics)
            || !empty($this->topPosts);
    }

    /**
     * 取得摘要資訊.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $topAuthor = $this->getTopAuthor();

        return [
            'total_posts' => $this->getTotalPosts(),
            'published_posts' => $this->getPublishedCount(),
            'total_views' => $this->getTotalViews(),
            'avg_views_per_post' => $this->getAverageViews(),
            'top_author' => $topAuthor ? $topAuthor['name'] : null,
            'most_active_hour' => $this->getMostActiveHour(),
        ];
    }

    /**
     * 驗證資料完整性.
     *
     * @throws InvalidArgumentException 當資料無效時
     */
    private function validateData(): void
    {
        if ($this->totalPosts < 0) {
            throw new InvalidArgumentException('文章總數不能為負數');
        }

        // 驗證狀態分布
        foreach ($this->byStatus as $status => $count) {
            if (!is_string($status) || (!is_int($count) && !is_numeric($count)) || $count < 0) {
                throw new InvalidArgumentException('狀態統計資料格式不正確');
            }
        }

        // 驗證來源分布
        foreach ($this->bySource as $source => $count) {
            if (!is_string($source) || (!is_int($count) && !is_numeric($count)) || $count < 0) {
                throw new InvalidArgumentException('來源統計資料格式不正確');
            }
        }

        // 驗證時間分布
        foreach ($this->timeDistribution as $time => $count) {
            if (!is_string($time) || (!is_int($count) && !is_numeric($count)) || $count < 0) {
                throw new InvalidArgumentException('時間分布統計資料格式不正確');
            }
        }

        // 驗證瀏覽統計 (只檢查必要鍵)
        if (!empty($this->viewsStatistics) && isset($this->viewsStatistics['total_views']) && !isset($this->viewsStatistics['avg_views_per_post'])) {
            // avg_views_per_post 不是必要的，可以計算得出
        }

        // 驗證熱門文章 (檢查基本結構，支援 id 或 post_id)
        foreach ($this->topPosts as $post) {
            if (!is_array($post)) {
                throw new InvalidArgumentException('熱門文章資料結構不正確');
            }

            // 支援 id 或 post_id, title 或 name, views 或 metric_value
            $hasId = isset($post['id']) || isset($post['post_id']);
            $hasTitle = isset($post['title']) || isset($post['name']);
            $hasMetric = isset($post['views']) || isset($post['metric_value']);

            if (!$hasId || !$hasTitle || !$hasMetric) {
                throw new InvalidArgumentException('熱門文章資料結構不正確');
            }
        }

        // 驗證長度統計 (只檢查存在的鍵)
        if (!empty($this->lengthStatistics)) {
            foreach (['avg_length', 'min_length', 'max_length'] as $key) {
                if (isset($this->lengthStatistics[$key]) && !is_numeric($this->lengthStatistics[$key])) {
                    throw new InvalidArgumentException("長度統計 {$key} 必須是數值");
                }
            }
        }

        // 驗證活躍作者 (支援多種格式)
        foreach ($this->topAuthors as $author) {
            if (!is_array($author)) {
                throw new InvalidArgumentException('熱門作者資料結構不正確');
            }

            // 支援 user_id 或 author_id, posts_count 必須存在
            $hasId = isset($author['user_id']) || isset($author['author_id']);
            $hasCount = isset($author['posts_count']);

            if (!$hasId || !$hasCount) {
                throw new InvalidArgumentException('熱門作者資料結構不正確');
            }
        }

        // 驗證置頂統計 (只檢查存在的鍵)
        if (!empty($this->pinnedStats)) {
            foreach ($this->pinnedStats as $key => $value) {
                if (!is_numeric($value)) {
                    throw new InvalidArgumentException("置頂統計 {$key} 必須是數值");
                }
            }
        }
    }

    /**
     * 計算內容品質分數.
     */
    private function calculateQualityScore(): float
    {
        $avgLength = $this->lengthStatistics['avg_length'] ?? 0;
        $avgLengthFloat = is_numeric($avgLength) ? (float) $avgLength : 0.0;
        $publishRate = $this->getPublishRate();
        $avgViews = $this->getAverageViews();

        // 長度分數 (0-40分)
        $lengthScore = min(($avgLengthFloat / 500) * 40, 40);

        // 發布率分數 (0-30分)
        $publishScore = ($publishRate / 100) * 30;

        // 瀏覽量分數 (0-30分)
        $viewsScore = min(($avgViews / 100) * 30, 30);

        return round($lengthScore + $publishScore + $viewsScore, 2);
    }

    /**
     * 計算作者生產力.
     *
     * @return array<string, mixed>
     */
    private function calculateAuthorProductivity(): array
    {
        if (!empty($this->topAuthors)) {
            $totalPostCount = 0;
            foreach ($this->topAuthors as $author) {
                if (is_array($author) && isset($author['posts_count']) && is_numeric($author['posts_count'])) {
                    $totalPostCount += (int) $author['posts_count'];
                }
            }

            return ['average_posts_per_author' => $totalPostCount / count($this->topAuthors)];
        }

        return ['average_posts_per_author' => 0.0];
    }

    /**
     * 計算最佳長度分數.
     */
    private function calculateOptimalLengthScore(): float
    {
        $avgLength = $this->getAverageLength();
        $optimalRange = [500, 2000]; // 假設最佳文章長度範圍

        if ($avgLength >= $optimalRange[0] && $avgLength <= $optimalRange[1]) {
            return 1.0; // 完美分數
        }

        if ($avgLength < $optimalRange[0]) {
            return $avgLength / $optimalRange[0]; // 低於最佳範圍
        }

        // 高於最佳範圍時，分數遞減
        $excess = $avgLength - $optimalRange[1];
        $penalty = min($excess / $optimalRange[1], 0.5); // 最多扣除50%

        return max(0.5, 1.0 - $penalty);
    }

    /**
     * 計算內容多樣性.
     */
    private function calculateContentDiversity(): float
    {
        // 基於文章長度變化計算內容多樣性
        $maxLength = $this->getMaxLength();
        $minLength = $this->getMinLength();
        $avgLength = $this->getAverageLength();

        if ($maxLength === $minLength || $avgLength === 0) {
            return 0.0; // 沒有變化或數據不足
        }

        $lengthRange = $maxLength - $minLength;
        $diversityScore = min($lengthRange / $avgLength, 2.0); // 限制最大值為2.0

        return round($diversityScore, 2);
    }
}
