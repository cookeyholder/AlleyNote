<?php

declare(strict_types=1);

namespace App\Shared\Events\Contracts;

/**
 * 事件分派器介面.
 *
 * 定義事件分派器的基本合約，負責分派事件到相對應的監聽器
 */
interface EventDispatcherInterface
{
    /**
     * 分派事件.
     */
    public function dispatch(DomainEventInterface $event): void;

    /**
     * 註冊事件監聽器.
     */
    public function listen(string $eventName, EventListenerInterface $listener): void;

    /**
     * 移除事件監聽器.
     */
    public function removeListener(string $eventName, string $listenerName): void;

    /**
     * 取得所有已註冊的監聽器.
     *
     * @return array<string, EventListenerInterface[]>
     */
    public function getListeners(): array;

    /**
     * 取得特定事件的監聽器.
     *
     * @return EventListenerInterface[]
     */
    public function getListenersForEvent(string $eventName): array;

    /**
     * 檢查是否有監聽器監聽特定事件.
     */
    public function hasListeners(string $eventName): bool;
}
