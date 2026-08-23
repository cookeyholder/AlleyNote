<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\DTOs;

use App\Domains\Statistics\DTOs\PaginatedStatisticsDTO;
use Tests\Support\UnitTestCase;

/**
 * 分頁統計 DTO 測試.
 */
final class PaginatedStatisticsDTOTest extends UnitTestCase
{
    public function testGettersAndPaginationCalculation(): void
    {
        $data = [['id' => 1], ['id' => 2]];
        $metadata = ['query_time_ms' => 12.5];

        $dto = new PaginatedStatisticsDTO(
            data: $data,
            totalCount: 45,
            currentPage: 2,
            perPage: 20,
            metadata: $metadata,
        );

        $this->assertSame($data, $dto->getData());
        $this->assertSame(45, $dto->getTotalCount());
        $this->assertSame(2, $dto->getCurrentPage());
        $this->assertSame(20, $dto->getPerPage());
        $this->assertSame($metadata, $dto->getMetadata());

        $this->assertSame(3, $dto->getTotalPages());
        $this->assertTrue($dto->hasNextPage());
        $this->assertTrue($dto->hasPreviousPage());
        $this->assertSame(21, $dto->getFrom());
        $this->assertSame(40, $dto->getTo());

        $array = $dto->toArray();
        $this->assertSame($data, $array['data']);
        $pagination = $array['pagination'];
        $this->assertIsArray($pagination);
        $this->assertSame(2, $pagination['current_page']);
        $this->assertSame(20, $pagination['per_page']);
        $this->assertSame(45, $pagination['total_count']);
        $this->assertSame(3, $pagination['total_pages']);
        $this->assertTrue($pagination['has_next_page']);
        $this->assertTrue($pagination['has_previous_page']);
        $this->assertSame(21, $pagination['from']);
        $this->assertSame(40, $pagination['to']);
        $this->assertSame($metadata, $array['metadata']);
    }

    public function testEdgeCasesWithZeroTotalCount(): void
    {
        $dto = new PaginatedStatisticsDTO(
            data: [],
            totalCount: 0,
            currentPage: 1,
            perPage: 10,
        );

        $this->assertSame(0, $dto->getTotalPages());
        $this->assertFalse($dto->hasNextPage());
        $this->assertFalse($dto->hasPreviousPage());
        $this->assertSame(0, $dto->getFrom());
        $this->assertSame(0, $dto->getTo());
    }

    public function testLastPageToCalculation(): void
    {
        $dto = new PaginatedStatisticsDTO(
            data: [['id' => 41], ['id' => 42]],
            totalCount: 42,
            currentPage: 3,
            perPage: 20,
        );

        $this->assertSame(3, $dto->getTotalPages());
        $this->assertFalse($dto->hasNextPage());
        $this->assertTrue($dto->hasPreviousPage());
        $this->assertSame(41, $dto->getFrom());
        $this->assertSame(42, $dto->getTo()); // min(60, 42) -> 42
    }
}
