<?php

declare(strict_types=1);

namespace App\Application\DTOs\Statistics;

use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 文章統計資料傳輸物件.
 *
 * 用於傳輸文章統計資料的 DTO 類別。
 * 包含文章基本資訊、統計指標、效能分析等資訊。
 *
 * 設計原則：
 * - 不可變物件 (Immutable)
 * - 支援 JSON 序列化
 * - 包含資料驗證邏輯
 * - 提供統計分析方法
 */
final readonly class PostStatisticsDTO implements JsonSerializable
{
    /**
     * @param Uuid $postId 文章識別碼
     * @param string $title 文章標題
     * @param SourceType $sourceType 來源類型
     * @param StatisticsMetric $viewCount 瀏覽次數
     * @param StatisticsMetric $likeCount 按讚次數
     * @param StatisticsMetric $commentCount 評論次數
     * @param StatisticsMetric $shareCount 分享次數
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $additionalMetrics 額外統計指標
     * @param DateTimeImmutable $publishedAt 發布時間
     * @param DateTimeImmutable $updatedAt 更新時間
     */
    public function __construct(
        public Uuid $postId,
        public string $title,
        public SourceType $sourceType,
        public StatisticsMetric $viewCount,
        public StatisticsMetric $likeCount,
        public StatisticsMetric $commentCount,
        public StatisticsMetric $shareCount,
        public StatisticsPeriod $period,
        public array $additionalMetrics,
        public DateTimeImmutable $publishedAt,
        public DateTimeImmutable $updatedAt,
    ) {
        $this->validateTitle($title);
        $this->validateAdditionalMetrics($additionalMetrics);
    }

    /**
     * 從文章資料建立 DTO.
     * @param array<string, mixed> $postData
     */
    public static function fromPostData(array $postData, StatisticsPeriod $period): self
    {
        // 確保必要欄位存在且型別正確
        $id = is_string($postData['id'] ?? null) ? $postData['id'] : '';
        if ($id === '') {
            throw new InvalidArgumentException('文章 ID 不能為空');
        }

        $title = is_string($postData['title'] ?? null) ? $postData['title'] : '';
        if ($title === '') {
            throw new InvalidArgumentException('文章標題不能為空');
        }

        $sourceType = $postData['source_type'] ?? 'web';
        if (!is_string($sourceType) && !is_int($sourceType)) {
            $sourceType = 'web';
        }

        $viewCount = is_numeric($postData['view_count'] ?? null) ? (int) $postData['view_count'] : 0;
        $likeCount = is_numeric($postData['like_count'] ?? null) ? (int) $postData['like_count'] : 0;
        $commentCount = is_numeric($postData['comment_count'] ?? null) ? (int) $postData['comment_count'] : 0;
        $shareCount = is_numeric($postData['share_count'] ?? null) ? (int) $postData['share_count'] : 0;

        /** @var array<string, mixed> $additionalMetrics */
        $additionalMetrics = is_array($postData['additional_metrics'] ?? null) ? $postData['additional_metrics'] : [];

        $publishedAt = is_string($postData['published_at'] ?? null) ? $postData['published_at'] : '';
        if ($publishedAt === '') {
            throw new InvalidArgumentException('發布時間不能為空');
        }

        $updatedAt = is_string($postData['updated_at'] ?? null) ? $postData['updated_at'] : $publishedAt;

        return new self(
            Uuid::fromString($id),
            $title,
            SourceType::from($sourceType),
            StatisticsMetric::count($viewCount, '瀏覽次數'),
            StatisticsMetric::count($likeCount, '按讚次數'),
            StatisticsMetric::count($commentCount, '評論次數'),
            StatisticsMetric::count($shareCount, '分享次數'),
            $period,
            $additionalMetrics,
            new DateTimeImmutable($publishedAt),
            new DateTimeImmutable($updatedAt),
        );
    }

    /**
     * 建立帶有統計分析的 DTO.
     * @param array<string, mixed> $rawMetrics
     */
    public static function withAnalysis(
        Uuid $postId,
        string $title,
        SourceType $sourceType,
        array $rawMetrics,
        StatisticsPeriod $period,
        DateTimeImmutable $publishedAt,
    ): self {
        // 建立統計指標
        $viewsValue = is_numeric($rawMetrics['views'] ?? null) ? (int) $rawMetrics['views'] : 0;
        $likesValue = is_numeric($rawMetrics['likes'] ?? null) ? (int) $rawMetrics['likes'] : 0;
        $commentsValue = is_numeric($rawMetrics['comments'] ?? null) ? (int) $rawMetrics['comments'] : 0;
        $sharesValue = is_numeric($rawMetrics['shares'] ?? null) ? (int) $rawMetrics['shares'] : 0;

        $viewCount = StatisticsMetric::count($viewsValue, '瀏覽次數');
        $likeCount = StatisticsMetric::count($likesValue, '按讚次數');
        $commentCount = StatisticsMetric::count($commentsValue, '評論次數');
        $shareCount = StatisticsMetric::count($sharesValue, '分享次數');

        // 計算額外指標
        /** @var array<string, mixed> $additionalMetrics */
        $additionalMetrics = [
            'engagement_rate' => self::calculateEngagementRate($rawMetrics),
            'performance_score' => self::calculatePerformanceScore($rawMetrics),
            'trend_direction' => is_string($rawMetrics['trend_direction'] ?? null) ? $rawMetrics['trend_direction'] : 'stable',
            'peak_time' => $rawMetrics['peak_time'] ?? null,
        ];

        return new self(
            $postId,
            $title,
            $sourceType,
            $viewCount,
            $likeCount,
            $commentCount,
            $shareCount,
            $period,
            $additionalMetrics,
            $publishedAt,
            new DateTimeImmutable(),
        );
    }

    /**
     * 取得互動率.
     */
    public function getEngagementRate(): float
    {
        if ($this->viewCount->value === 0) {
            return 0.0;
        }

        $totalEngagements = $this->likeCount->value + $this->commentCount->value + $this->shareCount->value;

        return round(($totalEngagements / $this->viewCount->value) * 100, 2);
    }

    /**
     * 取得總互動數.
     */
    public function getTotalEngagements(): int
    {
        return (int) ($this->likeCount->value + $this->commentCount->value + $this->shareCount->value);
    }

    /**
     * 取得效能評分.
     */
    public function getPerformanceScore(): float
    {
        $score = $this->additionalMetrics['performance_score'] ?? null;

        return is_numeric($score) ? (float) $score : $this->calculateDefaultPerformanceScore();
    }

    /**
     * 檢查是否為熱門文章.
     */
    public function isPopular(float $viewThreshold = 100, float $engagementThreshold = 5.0): bool
    {
        return $this->viewCount->value >= $viewThreshold
               && $this->getEngagementRate() >= $engagementThreshold;
    }

    /**
     * 檢查是否為病毒式傳播.
     */
    public function isViral(float $shareRatio = 0.1): bool
    {
        if ($this->viewCount->value === 0) {
            return false;
        }

        return ($this->shareCount->value / $this->viewCount->value) >= $shareRatio;
    }

    /**
     * 取得趨勢方向.
     */
    public function getTrendDirection(): string
    {
        $direction = $this->additionalMetrics['trend_direction'] ?? 'stable';

        return is_string($direction) ? $direction : 'stable';
    }

    /**
     * 取得文章年齡（天數）.
     */
    public function getAgeInDays(): int
    {
        $diff = new DateTimeImmutable()->diff($this->publishedAt);

        return (int) $diff->days;
    }

    /**
     * 取得格式化的統計資訊.
     * 
     * @return array<string, mixed>
     */
    public function getFormattedStatistics(): array
    {
        return [
            'basic_info' => [
                'id' => $this->postId->toString(),
                'title' => $this->title,
                'source_type' => $this->sourceType->value,
                'published_at' => $this->publishedAt->format('Y-m-d H:i:s'),
                'age_days' => $this->getAgeInDays(),
            ],
            'metrics' => [
                'views' => [
                    'value' => $this->viewCount->value,
                    'formatted' => $this->viewCount->getFormattedValueWithUnit(),
                ],
                'likes' => [
                    'value' => $this->likeCount->value,
                    'formatted' => $this->likeCount->getFormattedValueWithUnit(),
                ],
                'comments' => [
                    'value' => $this->commentCount->value,
                    'formatted' => $this->commentCount->getFormattedValueWithUnit(),
                ],
                'shares' => [
                    'value' => $this->shareCount->value,
                    'formatted' => $this->shareCount->getFormattedValueWithUnit(),
                ],
            ],
            'calculated_metrics' => [
                'total_engagements' => $this->getTotalEngagements(),
                'engagement_rate' => $this->getEngagementRate(),
                'performance_score' => $this->getPerformanceScore(),
                'is_popular' => $this->isPopular(),
                'is_viral' => $this->isViral(),
                'trend_direction' => $this->getTrendDirection(),
            ],
            'period' => [
                'start_date' => $this->period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $this->period->endDate->format('Y-m-d H:i:s'),
                'type' => $this->period->type->value,
            ],
        ];
    }

    /**
     * 比較與另一篇文章的效能.
     * 
     * @return array<string, mixed>
     */
    public function compareWith(PostStatisticsDTO $other): array
    {
        return [
            'views_ratio' => $other->viewCount->value > 0
                ? round($this->viewCount->value / $other->viewCount->value, 2) : 0,
            'engagement_rate_diff' => round($this->getEngagementRate() - $other->getEngagementRate(), 2),
            'performance_score_diff' => round($this->getPerformanceScore() - $other->getPerformanceScore(), 2),
            'better_metrics' => [
                'views' => $this->viewCount->value > $other->viewCount->value,
                'engagement_rate' => $this->getEngagementRate() > $other->getEngagementRate(),
                'performance_score' => $this->getPerformanceScore() > $other->getPerformanceScore(),
            ],
        ];
    }

    /**
     * 轉換為陣列.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'post_id' => $this->postId->toString(),
            'title' => $this->title,
            'source_type' => $this->sourceType->value,
            'metrics' => [
                'view_count' => $this->viewCount->value,
                'like_count' => $this->likeCount->value,
                'comment_count' => $this->commentCount->value,
                'share_count' => $this->shareCount->value,
            ],
            'calculated_metrics' => [
                'total_engagements' => $this->getTotalEngagements(),
                'engagement_rate' => $this->getEngagementRate(),
                'performance_score' => $this->getPerformanceScore(),
            ],
            'flags' => [
                'is_popular' => $this->isPopular(),
                'is_viral' => $this->isViral(),
            ],
            'period' => [
                'start_date' => $this->period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $this->period->endDate->format('Y-m-d H:i:s'),
                'type' => $this->period->type->value,
            ],
            'timestamps' => [
                'published_at' => $this->publishedAt->format('Y-m-d H:i:s'),
                'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
                'age_days' => $this->getAgeInDays(),
            ],
            'additional_metrics' => $this->additionalMetrics,
        ];
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
     * 轉換為字串.
     */
    public function __toString(): string
    {
        return sprintf(
            'PostStatistics[%s: %d views, %.2f%% engagement]',
            substr($this->title, 0, 30) . (strlen($this->title) > 30 ? '...' : ''),
            $this->viewCount->value,
            $this->getEngagementRate(),
        );
    }

    /**
     * 計算互動率.
     * 
     * @param array<string, mixed> $metrics
     */
    private static function calculateEngagementRate(array $metrics): float
    {
        $views = is_numeric($metrics['views'] ?? 0) ? (int) ($metrics['views'] ?? 0) : 0;
        if ($views === 0) {
            return 0.0;
        }

        $likes = is_numeric($metrics['likes'] ?? 0) ? (int) ($metrics['likes'] ?? 0) : 0;
        $comments = is_numeric($metrics['comments'] ?? 0) ? (int) ($metrics['comments'] ?? 0) : 0;
        $shares = is_numeric($metrics['shares'] ?? 0) ? (int) ($metrics['shares'] ?? 0) : 0;

        $engagements = $likes + $comments + $shares;

        return round(($engagements / $views) * 100, 2);
    }

    /**
     * 計算效能評分.
     * 
     * @param array<string, mixed> $metrics
     */
    private static function calculatePerformanceScore(array $metrics): float
    {
        $views = is_numeric($metrics['views'] ?? 0) ? (int) ($metrics['views'] ?? 0) : 0;
        $engagementRate = self::calculateEngagementRate($metrics);

        // 基本評分公式：瀏覽數權重 70%，互動率權重 30%
        $viewScore = min((float) $views / 1000.0, 1.0) * 70.0; // 1000 瀏覽為滿分
        $engagementScore = min($engagementRate / 10.0, 1.0) * 30.0; // 10% 互動率為滿分

        return round($viewScore + $engagementScore, 2);
    }

    /**
     * 計算預設效能評分.
     */
    private function calculateDefaultPerformanceScore(): float
    {
        return self::calculatePerformanceScore([
            'views' => $this->viewCount->value,
            'likes' => $this->likeCount->value,
            'comments' => $this->commentCount->value,
            'shares' => $this->shareCount->value,
        ]);
    }

    /**
     * 驗證標題.
     */
    private function validateTitle(string $title): void
    {
        if (empty(trim($title))) {
            throw new InvalidArgumentException('文章標題不能為空');
        }

        if (strlen($title) > 255) {
            throw new InvalidArgumentException('文章標題長度不能超過 255 個字元');
        }
    }

    /**
     * 驗證額外指標.
     * 
     * @param array<string, mixed> $metrics
     */
    private function validateAdditionalMetrics(array $metrics): void
    {
        // 檢查是否有無效的數值類型
        foreach ($metrics as $key => $value) {
            if (is_numeric($value) && $value < 0) {
                throw new InvalidArgumentException("額外指標 '{$key}' 不能為負數");
            }
        }
    }
}
