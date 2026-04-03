<?php

declare(strict_types=1);

namespace App\Shared\Events\Contracts;
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
