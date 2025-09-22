<?php

declare(strict_types=1);

namespace App\Application\Services\Statistics\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * 統計查詢參數傳輸物件.
 *
 * 封裝統計查詢的參數，包括時間範圍、分頁、排序等條件
 */
final class StatisticsQueryDTO
{
    public function __construct(
        private readonly ?DateTimeImmutable $startDate = null,
        private readonly ?DateTimeImmutable $endDate = null,
        private readonly int $page = 1,
        private readonly int $limit = 20,
        private readonly string $sortBy = 'created_at',
        private readonly string $sortDirection = 'desc',
        private readonly array $filters = [],
    ) {
        $this->validate();
    }

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    public function hasDateRange(): bool
    {
        return $this->startDate !== null && $this->endDate !== null;
    }

    public function getDateRangeInDays(): ?int
    {
        if (!$this->hasDateRange() || $this->startDate === null || $this->endDate === null) {
            return null;
        }

        $diff = $this->startDate->diff($this->endDate);
        return $diff->days !== false ? $diff->days : null;
    }

    private function validate(): void
    {
        if ($this->page < 1) {
            throw new InvalidArgumentException('頁數必須大於 0');
        }

        if ($this->limit < 1 || $this->limit > 100) {
            throw new InvalidArgumentException('每頁筆數必須介於 1-100 之間');
        }

        if ($this->startDate && $this->endDate && $this->startDate > $this->endDate) {
            throw new InvalidArgumentException('開始日期不能大於結束日期');
        }

        if (!in_array($this->sortDirection, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('排序方向只能為 asc 或 desc');
        }

        // 限制查詢範圍不超過 1 年
        if ($this->hasDateRange() && $this->getDateRangeInDays() > 365) {
            throw new InvalidArgumentException('查詢時間範圍不能超過 1 年');
        }
    }
}
