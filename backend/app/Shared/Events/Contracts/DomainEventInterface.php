<?php

declare(strict_types=1);

namespace App\Shared\Events\Contracts;

use DateTimeImmutable;

/**
 * 領域事件介面.
 *
 * 定義所有領域事件必須實作的基本合約
 */
interface DomainEventInterface
{
    /**
     * 取得事件名稱.
     */
    public function getEventName(): string;

    /**
     * 取得事件發生時間.
     */
    public function getOccurredOn(): DateTimeImmutable;

    /**
     * 取得事件資料.
     *
     * @return array<string, mixed>
     */
    public function getEventData(): array;

    /**
     * 取得事件 ID.
     */
    public function getEventId(): string;

    /**
     * 將事件轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
