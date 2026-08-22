<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\PostStatisticsResult;
use Tests\Support\UnitTestCase;

/**
 * 文章統計分析結果測試.
 */
final class PostStatisticsResultTest extends UnitTestCase
{
    public function testGettersAndSerialization(): void
    {
        $quality = ['word_count' => 500];
        $engagement = ['view_rate' => 12.5];
        $content = ['tags' => ['tech', 'news']];

        $result = new PostStatisticsResult(
            contentQualityMetrics: $quality,
            engagementMetrics: $engagement,
            contentAnalysis: $content,
        );

        $this->assertSame($quality, $result->getContentQualityMetrics());
        $this->assertSame($engagementMetrics ?? $engagement, $result->getEngagementMetrics());
        $this->assertSame($content, $result->getContentAnalysis());

        $expectedArray = [
            'engagement_metrics' => $engagement,
            'content_analysis'   => $content,
            'content_quality'    => $quality,
        ];

        $this->assertSame($expectedArray, $result->toArray());
        $this->assertSame($expectedArray, $result->jsonSerialize());
    }
}
