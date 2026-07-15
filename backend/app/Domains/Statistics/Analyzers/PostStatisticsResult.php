<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use JsonSerializable;

/**
 * 文章統計分析結果.
 */
readonly class PostStatisticsResult implements JsonSerializable
{
    /**
     * @param array<string, mixed> $contentQualityMetrics 內容品質指標
     * @param array<string, mixed> $engagementMetrics 互動指標
     * @param array<string, mixed> $contentAnalysis 內容分析
     */
    public function __construct(
        private array $contentQualityMetrics,
        private array $engagementMetrics,
        private array $contentAnalysis,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getContentQualityMetrics(): array
    {
        return $this->contentQualityMetrics;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEngagementMetrics(): array
    {
        return $this->engagementMetrics;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentAnalysis(): array
    {
        return $this->contentAnalysis;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'engagement_metrics' => $this->engagementMetrics,
            'content_analysis'   => $this->contentAnalysis,
            'content_quality'    => $this->contentQualityMetrics,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
