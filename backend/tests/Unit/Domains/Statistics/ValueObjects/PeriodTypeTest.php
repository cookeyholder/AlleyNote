<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\PeriodType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * PeriodType 枚舉單元測試.
 */
final class PeriodTypeTest extends TestCase
{
    #[Test]
    public function it_has_correct_values(): void
    {
        // Assert
        $this->assertSame('daily', PeriodType::DAILY->value);
        $this->assertSame('weekly', PeriodType::WEEKLY->value);
        $this->assertSame('monthly', PeriodType::MONTHLY->value);
        $this->assertSame('yearly', PeriodType::YEARLY->value);
    }

    #[Test]
    public function it_can_get_display_names(): void
    {
        // Act & Assert
        $this->assertSame('日統計', PeriodType::DAILY->getDisplayName());
        $this->assertSame('週統計', PeriodType::WEEKLY->getDisplayName());
        $this->assertSame('月統計', PeriodType::MONTHLY->getDisplayName());
        $this->assertSame('年統計', PeriodType::YEARLY->getDisplayName());
    }

    #[Test]
    public function it_can_get_sort_orders(): void
    {
        // Act & Assert
        $this->assertSame(1, PeriodType::DAILY->getSortOrder());
        $this->assertSame(2, PeriodType::WEEKLY->getSortOrder());
        $this->assertSame(3, PeriodType::MONTHLY->getSortOrder());
        $this->assertSame(4, PeriodType::YEARLY->getSortOrder());
    }

    #[Test]
    public function it_can_get_all_types(): void
    {
        // Act
        $allTypes = PeriodType::getAllTypes();

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsArray($allTypes);
        $this->assertCount(4, $allTypes);
        $this->assertContainsOnlyInstancesOf(PeriodType::class, $allTypes);
        $this->assertContains(PeriodType::DAILY, $allTypes);
        $this->assertContains(PeriodType::WEEKLY, $allTypes);
        $this->assertContains(PeriodType::MONTHLY, $allTypes);
        $this->assertContains(PeriodType::YEARLY, $allTypes);
    }

    #[Test]
    public function it_can_get_all_values(): void
    {
        // Act
        $allValues = PeriodType::getAllValues();

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsArray($allValues);
        $this->assertCount(4, $allValues);
        $this->assertContainsOnly('string', $allValues);
        $this->assertContains('daily', $allValues);
        $this->assertContains('weekly', $allValues);
        $this->assertContains('monthly', $allValues);
        $this->assertContains('yearly', $allValues);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        // Act & Assert
        $this->assertSame(PeriodType::DAILY, PeriodType::from('daily'));
        $this->assertSame(PeriodType::WEEKLY, PeriodType::from('weekly'));
        $this->assertSame(PeriodType::MONTHLY, PeriodType::from('monthly'));
        $this->assertSame(PeriodType::YEARLY, PeriodType::from('yearly'));
    }

    #[Test]
    public function it_can_try_from_string(): void
    {
        // Act & Assert
        $this->assertSame(PeriodType::DAILY, PeriodType::tryFrom('daily'));
        $this->assertNull(PeriodType::tryFrom('invalid'));
    }

    #[Test]
    public function it_sorts_correctly_by_order(): void
    {
        // Arrange
        $types = [
            PeriodType::YEARLY,
            PeriodType::DAILY,
            PeriodType::MONTHLY,
            PeriodType::WEEKLY,
        ];

        // Act
        usort($types, fn(PeriodType $a, PeriodType $b) => $a->getSortOrder() <=> $b->getSortOrder());

        // Assert
        $this->assertSame(PeriodType::DAILY, $types[0]);
        $this->assertSame(PeriodType::WEEKLY, $types[1]);
        $this->assertSame(PeriodType::MONTHLY, $types[2]);
        $this->assertSame(PeriodType::YEARLY, $types[3]);
    }
}
