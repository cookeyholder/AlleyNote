<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Enums;

use App\Domains\Statistics\Enums\StatisticsType;
use Tests\Support\UnitTestCase;

/**
 * 統計類型列舉測試.
 */
final class StatisticsTypeTest extends UnitTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('overview', StatisticsType::OVERVIEW->value);
        $this->assertSame('posts', StatisticsType::POSTS->value);
        $this->assertSame('users', StatisticsType::USERS->value);
        $this->assertSame('trends', StatisticsType::TRENDS->value);
        $this->assertSame('sources', StatisticsType::SOURCES->value);
    }

    public function testTryFrom(): void
    {
        $this->assertSame(StatisticsType::OVERVIEW, StatisticsType::tryFrom('overview'));
        $this->assertSame(StatisticsType::POSTS, StatisticsType::tryFrom('posts'));
        $this->assertSame(StatisticsType::USERS, StatisticsType::tryFrom('users'));
        $this->assertSame(StatisticsType::TRENDS, StatisticsType::tryFrom('trends'));
        $this->assertSame(StatisticsType::SOURCES, StatisticsType::tryFrom('sources'));
        $this->assertNull(StatisticsType::tryFrom('nonexistent'));
    }
}
