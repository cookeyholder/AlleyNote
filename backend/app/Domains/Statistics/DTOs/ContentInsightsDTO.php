<?php

declare(strict_types=1);

namespace App\Domains\Statistics\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 內容洞察統計 DTO.
 *
 * 封裝內容洞察統計資料的傳輸物件，包含內容效能分析、主題分析、使用者互動洞察等。
 * 專門用於內容分析 API 的回應格式與內部資料傳遞。
 */
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
     * @throws InvalidArgumentException 當資料格式不正確時
     */
    public static function fromArray(array $data): self
    {
        $generatedAt = null;
        if (isset($data['generated_at']) && is_string($data['generated_at'])) {
            $generatedAt = new DateTimeImmutable($data['generated_at']);
        }

        return new self(
            topPerformingContent: self::ensureIntArrayStringMixedArray($data['top_performing_content'] ?? []),
            contentPerformanceMetrics: self::ensureStringMixedArray($data['content_performance_metrics'] ?? []),
            popularTopics: self::ensureStringIntArray($data['popular_topics'] ?? []),
            contentFormats: self::ensureStringIntArray($data['content_formats'] ?? []),
            userEngagementPatterns: self::ensureStringMixedArray($data['user_engagement_patterns'] ?? []),
            contentLifecycleAnalysis: self::ensureStringMixedArray($data['content_lifecycle_analysis'] ?? []),
            readingPatterns: self::ensureStringNumberArray($data['reading_patterns'] ?? []),
            shareability: self::ensureStringMixedArray($data['shareability'] ?? []),
            seasonalTrends: self::ensureStringMixedArray($data['seasonal_trends'] ?? []),
            contentOptimization: self::ensureStringMixedArray($data['content_optimization'] ?? []),
            generatedAt: $generatedAt,
            metadata: self::ensureStringMixedArray($data['metadata'] ?? []),
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
     * 取得內容效能評級.
     */
    public function getPerformanceGrade(): string
    {
        $engagementRate = $this->getAverageEngagementRate();
        $completionRate = $this->getCompletionRate();
        $shareRate = $this->getShareRate();

        $score = ($engagementRate * 0.4) + ($completionRate * 0.4) + ($shareRate * 0.2);

        return match (true) {
            $score >= 80 => 'A+',
            $score >= 70 => 'A',
            $score >= 60 => 'B+',
            $score >= 50 => 'B',
            $score >= 40 => 'C+',
            $score >= 30 => 'C',
            default => 'D',
        };
    }

    /**
     * 取得內容策略建議.
     *
     * @return array<string, mixed>
     */
    public function getContentStrategyRecommendations(): array
    {
        $recommendations = [];

        // 基於參與率的建議
        if ($this->getAverageEngagementRate() < 5.0) {
            $recommendations['engagement'] = [
                'priority' => 'high',
                'action' => '提升內容互動性',
                'suggestions' => ['增加問答環節', '加入互動元素', '改善內容標題'],
            ];
        }

        // 基於完成率的建議
        if ($this->getCompletionRate() < 60.0) {
            $recommendations['completion'] = [
                'priority' => 'high',
                'action' => '優化內容結構',
                'suggestions' => ['縮短內容長度', '改善排版', '增加視覺元素'],
            ];
        }

        // 基於分享率的建議
        if ($this->getShareRate() < 2.0) {
            $recommendations['sharing'] = [
                'priority' => 'medium',
                'action' => '提升內容分享價值',
                'suggestions' => ['創造更多有價值的內容', '優化分享功能', '增加社群元素'],
            ];
        }

        // 基於熱門主題的建議
        $topTopic = $this->getTopTopic();
        if ($topTopic !== null) {
            $recommendations['topics'] = [
                'priority' => 'medium',
                'action' => "專注於熱門主題：{$topTopic}",
                'suggestions' => ['創作更多相關內容', '深入探討熱門話題', '建立主題系列'],
            ];
        }

        return $recommendations;
    }

    /**
     * 取得內容優化洞察.
     *
     * @return array<string, mixed>
     */
    public function getOptimizationInsights(): array
    {
        return [
            'optimal_publish_time' => [
                'hour' => $this->getPeakEngagementHour(),
                'day' => $this->getPeakEngagementDay(),
            ],
            'content_specifications' => [
                'optimal_length' => $this->getOptimalContentLength(),
                'target_read_time' => $this->getAverageReadTime(),
                'recommended_format' => $this->getMostPopularFormat(),
            ],
            'engagement_optimization' => [
                'target_engagement_rate' => max(8.0, $this->getAverageEngagementRate() * 1.2),
                'target_completion_rate' => max(70.0, $this->getCompletionRate() * 1.1),
                'target_share_rate' => max(3.0, $this->getShareRate() * 1.3),
            ],
            'lifecycle_management' => [
                'content_lifespan' => $this->getContentLifespanDays(),
                'peak_period' => $this->getPeakViewsPeriod(),
                'refresh_recommendations' => $this->getRefreshRecommendations(),
            ],
        ];
    }

    /**
     * 取得季節性內容策略.
     *
     * @return array<string, mixed>
     */
    public function getSeasonalContentStrategy(): array
    {
        $currentSeason = $this->getCurrentSeason();
        $seasonalData = $this->seasonalTrends[$currentSeason] ?? [];

        return [
            'current_season' => $currentSeason,
            'seasonal_performance' => $seasonalData,
            'recommended_topics' => is_array($seasonalData) && isset($seasonalData['trending_topics']) ? $seasonalData['trending_topics'] : [],
            'optimal_formats' => is_array($seasonalData) && isset($seasonalData['popular_formats']) ? $seasonalData['popular_formats'] : [],
            'engagement_patterns' => is_array($seasonalData) && isset($seasonalData['engagement_patterns']) ? $seasonalData['engagement_patterns'] : [],
            'content_calendar_suggestions' => $this->generateContentCalendarSuggestions($currentSeason),
        ];
    }

    /**
     * 取得讀者行為分析.
     *
     * @return array<string, mixed>
     */
    public function getReaderBehaviorAnalysis(): array
    {
        return [
            'reading_habits' => [
                'avg_scroll_depth' => $this->getAverageScrollDepth(),
                'completion_rate' => $this->getCompletionRate(),
                'return_rate' => $this->getReturnReaderRate(),
                'bounce_rate' => $this->getBounceRate(),
            ],
            'engagement_preferences' => [
                'preferred_content_length' => $this->getOptimalContentLength(),
                'optimal_read_time' => $this->getAverageReadTime(),
                'most_engaging_format' => $this->getMostPopularFormat(),
            ],
            'interaction_patterns' => [
                'peak_activity_time' => $this->getPeakEngagementHour(),
                'preferred_day' => $this->getPeakEngagementDay(),
                'sharing_behavior' => [
                    'avg_shares' => $this->getAverageSharesPerContent(),
                    'most_shared_type' => $this->getMostSharableContentType(),
                ],
            ],
            'content_discovery' => $this->userEngagementPatterns['discovery_patterns'] ?? [],
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
            'top_performing_content' => $this->topPerformingContent,
            'content_performance_metrics' => $this->contentPerformanceMetrics,
            'popular_topics' => $this->popularTopics,
            'content_formats' => $this->contentFormats,
            'user_engagement_patterns' => $this->userEngagementPatterns,
            'content_lifecycle_analysis' => $this->contentLifecycleAnalysis,
            'reading_patterns' => $this->readingPatterns,
            'shareability' => $this->shareability,
            'seasonal_trends' => $this->seasonalTrends,
            'content_optimization' => $this->contentOptimization,
            'calculated_metrics' => [
                'avg_views_per_content' => $this->getAverageViewsPerContent(),
                'avg_engagement_rate' => $this->getAverageEngagementRate(),
                'avg_read_time' => $this->getAverageReadTime(),
                'bounce_rate' => $this->getBounceRate(),
                'completion_rate' => $this->getCompletionRate(),
                'share_rate' => $this->getShareRate(),
                'performance_grade' => $this->getPerformanceGrade(),
                'top_topic' => $this->getTopTopic(),
                'most_popular_format' => $this->getMostPopularFormat(),
            ],
            'strategy_recommendations' => $this->getContentStrategyRecommendations(),
            'optimization_insights' => $this->getOptimizationInsights(),
            'seasonal_content_strategy' => $this->getSeasonalContentStrategy(),
            'reader_behavior_analysis' => $this->getReaderBehaviorAnalysis(),
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
            'performance_grade' => $this->getPerformanceGrade(),
            'avg_engagement_rate' => $this->getAverageEngagementRate(),
            'completion_rate' => $this->getCompletionRate(),
            'top_topic' => $this->getTopTopic(),
            'most_popular_format' => $this->getMostPopularFormat(),
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

    private function getCurrentSeason(): string
    {
        $month = (int) date('n');

        return match (true) {
            in_array($month, [12, 1, 2], true) => 'winter',
            in_array($month, [3, 4, 5], true) => 'spring',
            in_array($month, [6, 7, 8], true) => 'summer',
            default => 'autumn',
        };
    }

    /**
     * @return array<string>
     */
    private function getRefreshRecommendations(): array
    {
        $lifespan = $this->getContentLifespanDays();

        if ($lifespan < 30) {
            return ['每週檢查內容效能', '快速更新過時資訊', '持續優化標題和描述'];
        } elseif ($lifespan < 90) {
            return ['每月評估內容相關性', '更新統計數據', '添加新的相關資訊'];
        } else {
            return ['每季度全面檢視', '重新評估內容價值', '考慮重新撰寫或合併內容'];
        }
    }

    /**
     * @return array<string>
     */
    private function generateContentCalendarSuggestions(string $season): array
    {
        return match ($season) {
            'spring' => ['新年目標相關內容', '春季清理技巧', '成長和學習主題'],
            'summer' => ['度假和旅遊內容', '戶外活動指南', '輕鬆娛樂主題'],
            'autumn' => ['回到學校內容', '準備冬季主題', '反思和規劃內容'],
            'winter' => ['年終總結', '新年計劃', '室內活動推薦'],
            default => ['通用主題內容'],
        };
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
     * 確保回傳 array<string, int> 型別.
     *
     * @param mixed $data
     * @return array<string, int>
     */
    private static function ensureStringIntArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && is_numeric($value)) {
                $result[$key] = (int) $value;
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

    /**
     * 確保回傳 array<string, int|float> 型別.
     *
     * @param mixed $data
     * @return array<string, int|float>
     */
    private static function ensureStringNumberArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && (is_int($value) || is_float($value))) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
