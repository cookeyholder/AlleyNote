<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entity;

/**
 * 聚合根基礎類別
 * 提供領域事件記錄功能.
 */
abstract class AggregateRoot
{
    /** @var array<object> 領域事件列表 */
    private array $domainEvents = [];

    /**
     * 記錄領域事件.
     */
    protected function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * 取得所有領域事件.
     * @return array<string, mixed><object>
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * 清除所有領域事件.
     */
    public function clearEvents(): void
    {
        $this->domainEvents = [];
    }

    /**
     * 檢查是否有領域事件.
     */
    public function hasEvents(): bool
    {
        return !empty($this->domainEvents);
    }

    /**
     * 取得領域事件數量.
     */
    public function getEventCount(): int
    {
        return count($this->domainEvents);
    }
}
