<?php

declare(strict_types=1);

namespace App\Shared\Events\Contracts;

/**
 * 事件監聽器介面.
 *
 * 定義事件監聽器的基本合約
 */
interface EventListenerInterface
{
    /**
     * 處理事件.
     */
    public function handle(DomainEventInterface $event): void;

    /**
     * 取得監聽器支援的事件.
     *
     * @return array<string>
     */
    public function getListenedEvents(): array;

    /**
     * 取得監聽器名稱.
     */
    public function getName(): string;
}
