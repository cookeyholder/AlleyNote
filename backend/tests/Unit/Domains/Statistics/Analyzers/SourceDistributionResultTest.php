<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\SourceDistributionResult;
use Tests\Support\UnitTestCase;

/**
 * 來源分佈分析結果測試.
 */
final class SourceDistributionResultTest extends UnitTestCase
{
    public function testGettersAndSerialization(): void
    {
        $trafficQuality = ['organic_score' => 85.5];
        $channelPerformance = ['direct' => ['views' => 1000]];
        $deviceUsage = ['mobile_ratio' => 0.65];
        $trendInsights = ['growth_trend' => 'upward'];

        $result = new SourceDistributionResult(
            trafficQualityAnalysis: $trafficQuality,
            channelPerformanceAnalysis: $channelPerformance,
            deviceUsagePattern: $deviceUsage,
            trendInsights: $trendInsights,
        );

        $this->assertSame($trafficQuality, $result->getTrafficQualityAnalysis());
        $this->assertSame($channelPerformance, $result->getChannelPerformanceAnalysis());
        $this->assertSame($deviceUsage, $result->getDeviceUsagePattern());
        $this->assertSame($trendInsights, $result->getTrendInsights());

        $expectedArray = [
            'traffic_quality_analysis'     => $trafficQuality,
            'channel_performance_analysis' => $channelPerformance,
            'device_usage_pattern'         => $deviceUsage,
            'trend_insights'               => $trendInsights,
        ];

        $this->assertSame($expectedArray, $result->toArray());
        $this->assertSame($expectedArray, $result->jsonSerialize());
    }
}
