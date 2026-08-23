<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\UserStatisticsResult;
use Tests\Support\UnitTestCase;

/**
 * 使用者統計分析結果測試.
 */
final class UserStatisticsResultTest extends UnitTestCase
{
    public function testGettersAndSerialization(): void
    {
        $engagement = ['retention_rate' => 78.2];
        $activity = ['peak_hours' => [9, 14, 20]];

        $result = new UserStatisticsResult(
            engagementAnalysis: $engagement,
            activityInsights: $activity,
        );

        $this->assertSame($engagement, $result->getEngagementAnalysis());
        $this->assertSame($activity, $result->getActivityInsights());

        $expectedArray = [
            'engagement_analysis' => $engagement,
            'activity_insights'   => $activity,
        ];

        $this->assertSame($expectedArray, $result->toArray());
        $this->assertSame($expectedArray, $result->jsonSerialize());
    }
}
