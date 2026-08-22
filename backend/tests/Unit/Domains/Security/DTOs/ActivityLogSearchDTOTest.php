<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Security\DTOs;

use App\Domains\Security\DTOs\ActivityLogSearchDTO;
use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivitySeverity;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * ActivityLogSearchDTO 測試.
 */
#[CoversClass(ActivityLogSearchDTO::class)]
class ActivityLogSearchDTOTest extends UnitTestCase
{
    #[Test]
    public function it_has_sensible_defaults(): void
    {
        $dto = ActivityLogSearchDTO::create();

        $this->assertNull($dto->getUserId());
        $this->assertNull($dto->getSessionId());
        $this->assertNull($dto->getActionType());
        $this->assertNull($dto->getActionCategory());
        $this->assertNull($dto->getStatus());
        $this->assertNull($dto->getMinSeverity());
        $this->assertNull($dto->getTargetType());
        $this->assertNull($dto->getTargetId());
        $this->assertNull($dto->getIpAddress());
        $this->assertNull($dto->getStartDate());
        $this->assertNull($dto->getEndDate());
        $this->assertNull($dto->getSearchKeyword());
        $this->assertSame(1, $dto->getPage());
        $this->assertSame(20, $dto->getPerPage());
        $this->assertSame('created_at', $dto->getSortBy());
        $this->assertSame('desc', $dto->getSortOrder());
        $this->assertFalse($dto->hasFilters());
    }

    #[Test]
    public function it_can_set_all_filters_via_fluent_builders(): void
    {
        $start = new DateTime('2026-01-01 00:00:00');
        $end = new DateTime('2026-01-31 23:59:59');

        $dto = ActivityLogSearchDTO::create()
            ->withUserId(42)
            ->withSessionId('sess-abc')
            ->withActionType(ActivityType::LOGIN_FAILED)
            ->withActionCategory(ActivityCategory::AUTHENTICATION)
            ->withStatus(ActivityStatus::FAILED)
            ->withMinSeverity(ActivitySeverity::HIGH)
            ->withTarget('post', '99')
            ->withIpAddress('192.168.1.10')
            ->withTimeRange($start, $end)
            ->withSearchKeyword('登入')
            ->withPagination(3, 50)
            ->withSort('occurred_at', 'asc');

        $this->assertSame(42, $dto->getUserId());
        $this->assertSame('sess-abc', $dto->getSessionId());
        $this->assertSame(ActivityType::LOGIN_FAILED, $dto->getActionType());
        $this->assertSame(ActivityCategory::AUTHENTICATION, $dto->getActionCategory());
        $this->assertSame(ActivityStatus::FAILED, $dto->getStatus());
        $this->assertSame(ActivitySeverity::HIGH, $dto->getMinSeverity());
        $this->assertSame('post', $dto->getTargetType());
        $this->assertSame('99', $dto->getTargetId());
        $this->assertSame('192.168.1.10', $dto->getIpAddress());
        $this->assertEquals($start, $dto->getStartDate());
        $this->assertEquals($end, $dto->getEndDate());
        $this->assertSame('登入', $dto->getSearchKeyword());
        $this->assertSame(3, $dto->getPage());
        $this->assertSame(50, $dto->getPerPage());
        $this->assertSame('occurred_at', $dto->getSortBy());
        $this->assertSame('asc', $dto->getSortOrder());
        $this->assertTrue($dto->hasFilters());
    }

    #[Test]
    public function it_rejects_inverted_time_range_in_constructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('開始時間不能大於結束時間');

        new ActivityLogSearchDTO(
            startDate: new DateTime('2026-02-01'),
            endDate: new DateTime('2026-01-01'),
        );
    }

    #[Test]
    public function it_rejects_invalid_page_in_constructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('頁碼必須大於 0');

        new ActivityLogSearchDTO(page: 0);
    }

    #[Test]
    public function it_rejects_per_page_out_of_range_in_constructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('每頁筆數必須介於 1 到 100 之間');

        new ActivityLogSearchDTO(perPage: 101);
    }

    #[Test]
    public function it_rejects_inverted_time_range_in_builder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('開始時間不能大於結束時間');

        ActivityLogSearchDTO::create()->withTimeRange(
            new DateTime('2026-05-01'),
            new DateTime('2026-04-01'),
        );
    }

    #[Test]
    public function it_validates_pagination_bounds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('頁碼必須大於 0');

        ActivityLogSearchDTO::create()->withPagination(0, 10);
    }

    #[Test]
    public function it_validates_per_page_lower_bound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('每頁筆數必須介於 1 到 100 之間');

        ActivityLogSearchDTO::create()->withPagination(1, 0);
    }

    #[Test]
    public function it_validates_per_page_upper_bound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('每頁筆數必須介於 1 到 100 之間');

        ActivityLogSearchDTO::create()->withPagination(1, 101);
    }

    #[Test]
    public function it_rejects_unsupported_sort_field(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的排序欄位：evil_column');

        ActivityLogSearchDTO::create()->withSort('evil_column');
    }

    #[Test]
    public function it_rejects_invalid_sort_order(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('排序順序必須是 asc 或 desc');

        ActivityLogSearchDTO::create()->withSort('created_at', 'random');
    }

    #[Test]
    public function it_computes_offset_from_pagination(): void
    {
        $dto = ActivityLogSearchDTO::create()->withPagination(4, 25);

        $this->assertSame(75, $dto->getOffset());
    }

    #[Test]
    public function it_converts_to_array_with_enum_values_and_dates(): void
    {
        $dto = ActivityLogSearchDTO::create()
            ->withUserId(7)
            ->withActionType(ActivityType::LOGIN_SUCCESS)
            ->withTimeRange(
                new DateTime('2026-03-01 08:00:00'),
                new DateTime('2026-03-02 09:30:00'),
            );

        $array = $dto->toArray();

        $this->assertSame(7, $array['user_id']);
        $this->assertSame('login_success', $array['action_type']);
        $this->assertSame('2026-03-01 08:00:00', $array['start_date']);
        $this->assertSame('2026-03-02 09:30:00', $array['end_date']);
        $this->assertSame(1, $array['page']);
        $this->assertSame(20, $array['per_page']);
        $this->assertSame('created_at', $array['sort_by']);
        $this->assertSame('desc', $array['sort_order']);
        $this->assertArrayNotHasKey('unknown_key', $array);
    }
}
