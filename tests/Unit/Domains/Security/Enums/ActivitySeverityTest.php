<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\Enums;

use App\Domains\Security\Enums\ActivitySeverity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActivitySeverity::class)]
class ActivitySeverityTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertEquals(1, ActivitySeverity::LOW->value);
        $this->assertEquals(2, ActivitySeverity::NORMAL->value);
        $this->assertEquals(3, ActivitySeverity::MEDIUM->value);
        $this->assertEquals(4, ActivitySeverity::HIGH->value);
        $this->assertEquals(5, ActivitySeverity::CRITICAL->value);
    }

    public function testGetDisplayName(): void
    {
        $this->assertEquals('低', ActivitySeverity::LOW->getDisplayName());
        $this->assertEquals('正常', ActivitySeverity::NORMAL->getDisplayName());
        $this->assertEquals('中等', ActivitySeverity::MEDIUM->getDisplayName());
        $this->assertEquals('高', ActivitySeverity::HIGH->getDisplayName());
        $this->assertEquals('關鍵', ActivitySeverity::CRITICAL->getDisplayName());
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('一般性操作，對系統影響很小', ActivitySeverity::LOW->getDescription());
        $this->assertEquals('標準操作，對系統有正常影響', ActivitySeverity::NORMAL->getDescription());
        $this->assertEquals('中等重要操作，需要留意', ActivitySeverity::MEDIUM->getDescription());
        $this->assertEquals('高重要性操作，需要特別關注', ActivitySeverity::HIGH->getDescription());
        $this->assertEquals('關鍵操作，對系統安全有重大影響', ActivitySeverity::CRITICAL->getDescription());
    }

    public function testIsAtLeast(): void
    {
        $this->assertTrue(ActivitySeverity::HIGH->isAtLeast(ActivitySeverity::MEDIUM));
        $this->assertTrue(ActivitySeverity::HIGH->isAtLeast(ActivitySeverity::HIGH));
        $this->assertFalse(ActivitySeverity::MEDIUM->isAtLeast(ActivitySeverity::HIGH));
        $this->assertTrue(ActivitySeverity::CRITICAL->isAtLeast(ActivitySeverity::LOW));
    }

    public function testIsAtMost(): void
    {
        $this->assertTrue(ActivitySeverity::MEDIUM->isAtMost(ActivitySeverity::HIGH));
        $this->assertTrue(ActivitySeverity::MEDIUM->isAtMost(ActivitySeverity::MEDIUM));
        $this->assertFalse(ActivitySeverity::HIGH->isAtMost(ActivitySeverity::MEDIUM));
        $this->assertTrue(ActivitySeverity::LOW->isAtMost(ActivitySeverity::CRITICAL));
    }

    public function testIsHighRisk(): void
    {
        $this->assertFalse(ActivitySeverity::LOW->isHighRisk());
        $this->assertFalse(ActivitySeverity::NORMAL->isHighRisk());
        $this->assertFalse(ActivitySeverity::MEDIUM->isHighRisk());
        $this->assertTrue(ActivitySeverity::HIGH->isHighRisk());
        $this->assertTrue(ActivitySeverity::CRITICAL->isHighRisk());
    }

    public function testIsLowRisk(): void
    {
        $this->assertTrue(ActivitySeverity::LOW->isLowRisk());
        $this->assertTrue(ActivitySeverity::NORMAL->isLowRisk());
        $this->assertFalse(ActivitySeverity::MEDIUM->isLowRisk());
        $this->assertFalse(ActivitySeverity::HIGH->isLowRisk());
        $this->assertFalse(ActivitySeverity::CRITICAL->isLowRisk());
    }

    public function testGetAllLevels(): void
    {
        $levels = ActivitySeverity::getAllLevels();

        $this->assertIsArray($levels);
        $this->assertCount(5, $levels);
        $this->assertContains(ActivitySeverity::LOW, $levels);
        $this->assertContains(ActivitySeverity::NORMAL, $levels);
        $this->assertContains(ActivitySeverity::MEDIUM, $levels);
        $this->assertContains(ActivitySeverity::HIGH, $levels);
        $this->assertContains(ActivitySeverity::CRITICAL, $levels);
    }

    public function testFromValue(): void
    {
        $this->assertEquals(ActivitySeverity::LOW, ActivitySeverity::fromValue(1));
        $this->assertEquals(ActivitySeverity::NORMAL, ActivitySeverity::fromValue(2));
        $this->assertEquals(ActivitySeverity::MEDIUM, ActivitySeverity::fromValue(3));
        $this->assertEquals(ActivitySeverity::HIGH, ActivitySeverity::fromValue(4));
        $this->assertEquals(ActivitySeverity::CRITICAL, ActivitySeverity::fromValue(5));
        $this->assertNull(ActivitySeverity::fromValue(999));
    }
}
