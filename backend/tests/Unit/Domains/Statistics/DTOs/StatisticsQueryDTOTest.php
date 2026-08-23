<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\DTOs;

use App\Domains\Statistics\DTOs\StatisticsQueryDTO;
use DateTimeImmutable;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * 統計查詢 DTO 測試.
 */
final class StatisticsQueryDTOTest extends UnitTestCase
{
    public function testDefaultValues(): void
    {
        $dto = new StatisticsQueryDTO();

        $this->assertNull($dto->getStartDate());
        $this->assertNull($dto->getEndDate());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(20, $dto->getLimit());
        $this->assertSame('created_at', $dto->getSortBy());
        $this->assertSame('desc', $dto->getSortDirection());
        $this->assertSame([], $dto->getFilters());
        $this->assertSame(0, $dto->getOffset());
        $this->assertFalse($dto->hasDateRange());
        $this->assertNull($dto->getDateRangeInDays());
    }

    public function testCustomValuesAndCalculations(): void
    {
        $start = new DateTimeImmutable('2025-01-01');
        $end = new DateTimeImmutable('2025-01-11');
        $dto = new StatisticsQueryDTO(
            startDate: $start,
            endDate: $end,
            page: 3,
            limit: 15,
            sortBy: 'views',
            sortDirection: 'asc',
            filters: ['status' => 'published'],
        );

        $this->assertSame($start, $dto->getStartDate());
        $this->assertSame($end, $dto->getEndDate());
        $this->assertSame(3, $dto->getPage());
        $this->assertSame(15, $dto->getLimit());
        $this->assertSame('views', $dto->getSortBy());
        $this->assertSame('asc', $dto->getSortDirection());
        $this->assertSame(['status' => 'published'], $dto->getFilters());
        $this->assertSame(30, $dto->getOffset()); // (3 - 1) * 15
        $this->assertTrue($dto->hasDateRange());
        $this->assertSame(10, $dto->getDateRangeInDays());
    }

    public function testPageValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('頁數必須大於 0');
        new StatisticsQueryDTO(page: 0);
    }

    public function testLimitLowerBoundValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('每頁筆數必須介於 1-100 之間');
        new StatisticsQueryDTO(limit: 0);
    }

    public function testLimitUpperBoundValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('每頁筆數必須介於 1-100 之間');
        new StatisticsQueryDTO(limit: 101);
    }

    public function testStartDateAfterEndDateValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('開始日期不能大於結束日期');
        new StatisticsQueryDTO(
            startDate: new DateTimeImmutable('2025-01-10'),
            endDate: new DateTimeImmutable('2025-01-01'),
        );
    }

    public function testInvalidSortDirectionValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('排序方向只能為 asc 或 desc');
        new StatisticsQueryDTO(sortDirection: 'invalid');
    }

    public function testDateRangeExceedsOneYearValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('查詢時間範圍不能超過 1 年');
        new StatisticsQueryDTO(
            startDate: new DateTimeImmutable('2024-01-01'),
            endDate: new DateTimeImmutable('2025-01-05'),
        );
    }
}
