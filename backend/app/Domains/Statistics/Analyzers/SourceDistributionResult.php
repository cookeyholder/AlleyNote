<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Analyzers;

use JsonSerializable;

/**
 * 來源分佈分析結果.
 */
readonly class SourceDistributionResult implements JsonSerializable
{
    /**
     * @param array<string, mixed> $trafficQualityAnalysis 流量品質分析
     * @param array<string, mixed> $channelPerformanceAnalysis 管道效能分析
     * @param array<string, mixed> $deviceUsagePattern 裝置使用模式
     * @param array<string, mixed> $trendInsights 趨勢洞察
     */
    public function __construct(
        private array $trafficQualityAnalysis,
        private array $channelPerformanceAnalysis,
        private array $deviceUsagePattern,
        private array $trendInsights,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getTrafficQualityAnalysis(): array
    {
        return $this->trafficQualityAnalysis;
    }

    /**
     * @return array<string, mixed>
     */
    public function getChannelPerformanceAnalysis(): array
    {
        return $this->channelPerformanceAnalysis;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDeviceUsagePattern(): array
    {
        return $this->deviceUsagePattern;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrendInsights(): array
    {
        return $this->trendInsights;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'traffic_quality_analysis'     => $this->trafficQualityAnalysis,
            'channel_performance_analysis' => $this->channelPerformanceAnalysis,
            'device_usage_pattern'         => $this->deviceUsagePattern,
            'trend_insights'               => $this->trendInsights,
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
