<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Analyzers;

use App\Domains\Statistics\Analyzers\StatisticsOverviewResult;
use Tests\Support\UnitTestCase;

/**
 * 統計概覽分析結果測試.
 */
final class StatisticsOverviewResultTest extends UnitTestCase
{
    public function testGettersAndSerialization(): void
    {
        $result = new StatisticsOverviewResult(
            activityLevel: 'HIGH',
            activityScore: 92.5,
        );

        $this->assertSame('HIGH', $result->getActivityLevel());
        $this->assertSame(92.5, $result->getActivityScore());

        $expectedArray = [
            'activity_level' => 'HIGH',
        ];

        $this->assertSame($expectedArray, $result->toArray());
        $this->assertSame($expectedArray, $result->jsonSerialize());
    }
}
