<?php

declare(strict_types=1);

namespace App\Domains\Statistics\DTOs;

use App\Domains\Statistics\Helpers\ArraySanitizer;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

class ContentInsightsDTO implements JsonSerializable
{
    /**
     * @param array<int, array<string, mixed>> $topPerformingContent 表現最佳內容清單
     * @param array<string, mixed> $contentPerformanceMetrics 內容效能指標
     * @param array<string, int> $popularTopics 熱門主題統計
     * @param array<string, int> $contentFormats 內容格式統計
     * @param array<string, mixed> $userEngagementPatterns 使用者參與模式
     * @param array<string, mixed> $contentLifecycleAnalysis 內容生命週期分析
     * @param array<string, int|float> $readingPatterns 閱讀模式統計
     * @param array<string, mixed> $shareability 分享度分析
     * @param array<string, mixed> $seasonalTrends 季節性趨勢
     * @param array<string, mixed> $contentOptimization 內容優化建議
     * @param DateTimeImmutable|null $generatedAt 生成時間
     * @param array<string, mixed> $metadata 額外元資料
     */
    public function __construct(
        private readonly array $topPerformingContent,
        private readonly array $contentPerformanceMetrics,
        private readonly array $popularTopics,
        private readonly array $contentFormats,
        private readonly array $userEngagementPatterns,
        private readonly array $contentLifecycleAnalysis,
        private readonly array $readingPatterns,
        private readonly array $shareability,
        private readonly array $seasonalTrends,
        private readonly array $contentOptimization,
        private readonly ?DateTimeImmutable $generatedAt = null,
        private readonly array $metadata = [],
    ) {
        $this->validateData();
    }

    /**
     * 從陣列建立 DTO.
     *
     * @param array<string, mixed> $data 原始資料陣列
     *
     * @throws InvalidArgumentException 當資料格式不正確時
     */
    public static function fromArray(array $data): self
    {
        $generatedAt = null;
        if (isset($data['generated_at']) && is_string($data['generated_at'])) {
            $generatedAt = new DateTimeImmutable($data['generated_at']);
        }

        return new self(
            topPerformingContent: ArraySanitizer::ensureIntArrayStringMixedArray($data['top_performing_content'] ?? []),
            contentPerformanceMetrics: ArraySanitizer::ensureStringMixedArray($data['content_performance_metrics'] ?? []),
            popularTopics: ArraySanitizer::ensureStringIntArray($data['popular_topics'] ?? []),
            contentFormats: ArraySanitizer::ensureStringIntArray($data['content_formats'] ?? []),
            userEngagementPatterns: ArraySanitizer::ensureStringMixedArray($data['user_engagement_patterns'] ?? []),
            contentLifecycleAnalysis: ArraySanitizer::ensureStringMixedArray($data['content_lifecycle_analysis'] ?? []),
            readingPatterns: ArraySanitizer::ensureStringNumberArray($data['reading_patterns'] ?? []),
            shareability: ArraySanitizer::ensureStringMixedArray($data['shareability'] ?? []),
            seasonalTrends: ArraySanitizer::ensureStringMixedArray($data['seasonal_trends'] ?? []),
            contentOptimization: ArraySanitizer::ensureStringMixedArray($data['content_optimization'] ?? []),
            generatedAt: $generatedAt,
            metadata: ArraySanitizer::ensureStringMixedArray($data['metadata'] ?? []),
        );
    }

    // Getters
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopPerformingContent(): array
    {
        return $this->topPerformingContent;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentPerformanceMetrics(): array
    {
        return $this->contentPerformanceMetrics;
    }

    /**
     * @return array<string, int>
     */
    public function getPopularTopics(): array
    {
        return $this->popularTopics;
    }

    /**
     * @return array<string, int>
     */
    public function getContentFormats(): array
    {
        return $this->contentFormats;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserEngagementPatterns(): array
    {
        return $this->userEngagementPatterns;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentLifecycleAnalysis(): array
    {
        return $this->contentLifecycleAnalysis;
    }

    /**
     * @return array<string, int|float>
     */
    public function getReadingPatterns(): array
    {
        return $this->readingPatterns;
    }

    /**
     * @return array<string, mixed>
     */
    public function getShareability(): array
    {
        return $this->shareability;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSeasonalTrends(): array
    {
        return $this->seasonalTrends;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentOptimization(): array
    {
        return $this->contentOptimization;
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
    public function getAverageViewsPerContent(): float
    {
        $avgViews = $this->contentPerformanceMetrics['avg_views_per_content'] ?? 0.0;

        return is_numeric($avgViews) ? (float) $avgViews : 0.0;
    }

    public function getAverageEngagementRate(): float
    {
        $engagementRate = $this->contentPerformanceMetrics['avg_engagement_rate'] ?? 0.0;

        return is_numeric($engagementRate) ? (float) $engagementRate : 0.0;
    }

    public function getAverageReadTime(): int
    {
        $readTime = $this->contentPerformanceMetrics['avg_read_time'] ?? 0;

        return is_numeric($readTime) ? (int) $readTime : 0;
    }

    public function getBounceRate(): float
    {
        $bounceRate = $this->contentPerformanceMetrics['bounce_rate'] ?? 0.0;

        return is_numeric($bounceRate) ? (float) $bounceRate : 0.0;
    }

    public function getCompletionRate(): float
    {
        $completionRate = $this->contentPerformanceMetrics['completion_rate'] ?? 0.0;

        return is_numeric($completionRate) ? (float) $completionRate : 0.0;
    }

    public function getShareRate(): float
    {
        $shareRate = $this->contentPerformanceMetrics['share_rate'] ?? 0.0;

        return is_numeric($shareRate) ? (float) $shareRate : 0.0;
    }

    public function getTopTopic(): ?string
    {
        if (empty($this->popularTopics)) {
            return null;
        }
        $maxCount = max($this->popularTopics);
        $topTopics = array_keys($this->popularTopics, $maxCount);

        return $topTopics[0] ?? null;
    }

    public function getMostPopularFormat(): ?string
    {
        if (empty($this->contentFormats)) {
            return null;
        }
        $maxCount = max($this->contentFormats);
        $topFormats = array_keys($this->contentFormats, $maxCount);

        return $topFormats[0] ?? null;
    }

    public function getBestPerformingContent(): ?array
    {
        return $this->topPerformingContent[0] ?? null;
    }

    public function getAverageSharesPerContent(): float
    {
        $avgShares = $this->shareability['avg_shares_per_content'] ?? 0.0;

        return is_numeric($avgShares) ? (float) $avgShares : 0.0;
    }

    public function getMostSharableContentType(): ?string
    {
        $type = $this->shareability['most_sharable_type'] ?? null;

        return is_string($type) ? $type : null;
    }

    public function getPeakEngagementHour(): ?string
    {
        $hour = $this->userEngagementPatterns['peak_hour'] ?? null;

        return is_string($hour) ? $hour : null;
    }

    public function getPeakEngagementDay(): ?string
    {
        $day = $this->userEngagementPatterns['peak_day'] ?? null;

        return is_string($day) ? $day : null;
    }

    public function getContentLifespanDays(): int
    {
        $lifespan = $this->contentLifecycleAnalysis['avg_lifespan_days'] ?? 0;

        return is_numeric($lifespan) ? (int) $lifespan : 0;
    }

    public function getPeakViewsPeriod(): ?string
    {
        $period = $this->contentLifecycleAnalysis['peak_views_period'] ?? null;

        return is_string($period) ? $period : null;
    }

    public function getOptimalContentLength(): int
    {
        $length = $this->readingPatterns['optimal_length_words'] ?? 0;

        return is_numeric($length) ? (int) $length : 0;
    }

    public function getAverageScrollDepth(): float
    {
        $scrollDepth = $this->readingPatterns['avg_scroll_depth'] ?? 0.0;

        return is_numeric($scrollDepth) ? (float) $scrollDepth : 0.0;
    }

    public function getReturnReaderRate(): float
    {
        $returnRate = $this->readingPatterns['return_reader_rate'] ?? 0.0;

        return is_numeric($returnRate) ? (float) $returnRate : 0.0;
    }

    /**
     * 轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'top_performing_content'      => $this->topPerformingContent,
            'content_performance_metrics' => $this->contentPerformanceMetrics,
            'popular_topics'              => $this->popularTopics,
            'content_formats'             => $this->contentFormats,
            'user_engagement_patterns'    => $this->userEngagementPatterns,
            'content_lifecycle_analysis'  => $this->contentLifecycleAnalysis,
            'reading_patterns'            => $this->readingPatterns,
            'shareability'                => $this->shareability,
            'seasonal_trends'             => $this->seasonalTrends,
            'content_optimization'        => $this->contentOptimization,
            'calculated_metrics'          => [
                'avg_views_per_content' => $this->getAverageViewsPerContent(),
                'avg_engagement_rate'   => $this->getAverageEngagementRate(),
                'avg_read_time'         => $this->getAverageReadTime(),
                'bounce_rate'           => $this->getBounceRate(),
                'completion_rate'       => $this->getCompletionRate(),
                'share_rate'            => $this->getShareRate(),
                'top_topic'             => $this->getTopTopic(),
                'most_popular_format'   => $this->getMostPopularFormat(),
            ],
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
        return !empty($this->topPerformingContent)
               || !empty($this->contentPerformanceMetrics)
               || !empty($this->popularTopics);
    }

    /**
     * 取得摘要資訊.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'avg_engagement_rate'    => $this->getAverageEngagementRate(),
            'completion_rate'        => $this->getCompletionRate(),
            'top_topic'              => $this->getTopTopic(),
            'most_popular_format'    => $this->getMostPopularFormat(),
            'optimal_content_length' => $this->getOptimalContentLength(),
        ];
    }

    /**
     * 驗證資料完整性.
     *
     * @throws InvalidArgumentException 當資料無效時
     */
    private function validateData(): void
    {
        // 驗證表現最佳內容
        foreach ($this->topPerformingContent as $content) {
            if (!is_array($content) || !isset($content['id'], $content['title'], $content['metric_value'])) {
                throw new InvalidArgumentException('表現最佳內容資料結構不正確');
            }
        }
        // 驗證內容效能指標
        if (!empty($this->contentPerformanceMetrics)) {
            $requiredMetrics = ['avg_views_per_content', 'avg_engagement_rate', 'avg_read_time', 'bounce_rate', 'completion_rate', 'share_rate'];
            foreach ($requiredMetrics as $metric) {
                if (!array_key_exists($metric, $this->contentPerformanceMetrics)) {
                    throw new InvalidArgumentException("內容效能指標缺少必要的鍵: {$metric}");
                }
            }
        }
        // 驗證熱門主題統計
        foreach ($this->popularTopics as $topic => $count) {
            if (!is_string($topic) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('熱門主題統計資料格式不正確');
            }
        }
        // 驗證內容格式統計
        foreach ($this->contentFormats as $format => $count) {
            if (!is_string($format) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('內容格式統計資料格式不正確');
            }
        }
        // 驗證閱讀模式統計
        foreach ($this->readingPatterns as $pattern => $value) {
            if (!is_string($pattern)) {
                throw new InvalidArgumentException('閱讀模式統計鍵必須是字符串');
            }
            if (!is_int($value) && !is_float($value)) {
                throw new InvalidArgumentException('閱讀模式統計值必須是數字');
            }
        }
    }
}
