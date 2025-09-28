<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics\DTOs;

/**
 * 分頁統計結果傳輸物件.
 *
 * 包含分頁後的統計資料和分頁資訊
 */
final class PaginatedStatisticsDTO
{
    public function __construct(
        private readonly array $data,
        private readonly int $totalCount,
        private readonly int $currentPage,
        private readonly int $perPage,
        private readonly array $metadata = [],
    ) {}

    public function getData(): array
    {
        return $this->data;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getTotalPages(): int
    {
        return (int) ceil($this->totalCount / $this->perPage);
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function getFrom(): int
    {
        if ($this->totalCount === 0) {
            return 0;
        }

        return (($this->currentPage - 1) * $this->perPage) + 1;
    }

    public function getTo(): int
    {
        if ($this->totalCount === 0) {
            return 0;
        }

        $to = $this->currentPage * $this->perPage;

        return min($to, $this->totalCount);
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'pagination' => [
                'current_page' => $this->currentPage,
                'per_page' => $this->perPage,
                'total_count' => $this->totalCount,
                'total_pages' => $this->getTotalPages(),
                'has_next_page' => $this->hasNextPage(),
                'has_previous_page' => $this->hasPreviousPage(),
                'from' => $this->getFrom(),
                'to' => $this->getTo(),
            ],
            'metadata' => $this->metadata,
        ];
    }
}
