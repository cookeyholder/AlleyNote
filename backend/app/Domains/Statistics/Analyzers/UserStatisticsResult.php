<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use JsonSerializable;

/**
 * 使用者統計分析結果.
 */
readonly class UserStatisticsResult implements JsonSerializable
{
    /**
     * @param array<string, mixed> $engagementAnalysis 參與度分析
     * @param array<string, mixed> $activityInsights 活動洞察
     */
    public function __construct(
        private array $engagementAnalysis,
        private array $activityInsights,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getEngagementAnalysis(): array
    {
        return $this->engagementAnalysis;
    }

    /**
     * @return array<string, mixed>
     */
    public function getActivityInsights(): array
    {
        return $this->activityInsights;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'engagement_analysis' => $this->engagementAnalysis,
            'activity_insights'   => $this->activityInsights,
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
