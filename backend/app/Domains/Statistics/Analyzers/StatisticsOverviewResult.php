<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use JsonSerializable;

/**
 * 統計概覽分析結果.
 */
readonly class StatisticsOverviewResult implements JsonSerializable
{
    /**
     * @param string $activityLevel 活動等級
     * @param float $activityScore 活動分數
     */
    public function __construct(
        private string $activityLevel,
        private float $activityScore,
    ) {}

    public function getActivityLevel(): string
    {
        return $this->activityLevel;
    }

    public function getActivityScore(): float
    {
        return $this->activityScore;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'activity_level' => $this->activityLevel,
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
