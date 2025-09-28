<?php

declare(strict_types=1);

namespace App\Shared\Events;

use App\Shared\Events\Contracts\DomainEventInterface;
use App\Shared\Events\Contracts\EventDispatcherInterface;
use App\Shared\Events\Contracts\EventListenerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 簡單事件分派器實作.
 *
 * 提供輕量級的同步事件分派功能，適合統計功能使用
 */
class SimpleEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, EventListenerInterface[]>
     */
    private array $listeners = [];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function dispatch(DomainEventInterface $event): void
    {
        $eventName = $event->getEventName();
        $listeners = $this->getListenersForEvent($eventName);

        if (empty($listeners)) {
            $this->logger?->debug("No listeners found for event: {$eventName}", [
                'event_id' => $event->getEventId(),
            ]);

            return;
        }

        $this->logger?->info("Dispatching event: {$eventName}", [
            'event_id' => $event->getEventId(),
            'listeners_count' => count($listeners),
        ]);

        foreach ($listeners as $listener) {
            try {
                $listener->handle($event);

                $this->logger?->debug('Event handled successfully', [
                    'event_id' => $event->getEventId(),
                    'event_name' => $eventName,
                    'listener' => $listener->getName(),
                ]);
            } catch (Throwable $e) {
                $this->logger?->error('Failed to handle event', [
                    'event_id' => $event->getEventId(),
                    'event_name' => $eventName,
                    'listener' => $listener->getName(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // 在統計功能中，我們選擇不讓事件處理失敗中斷主流程
                // 只記錄錯誤，繼續執行其他監聽器
                continue;
            }
        }
    }

    public function listen(string $eventName, EventListenerInterface $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        // 檢查是否已經註冊相同名稱的監聽器
        $listenerName = $listener->getName();
        foreach ($this->listeners[$eventName] as $existingListener) {
            if ($existingListener->getName() === $listenerName) {
                $this->logger?->warning('Listener already registered for event', [
                    'event_name' => $eventName,
                    'listener' => $listenerName,
                ]);

                return;
            }
        }

        $this->listeners[$eventName][] = $listener;

        $this->logger?->debug('Event listener registered', [
            'event_name' => $eventName,
            'listener' => $listenerName,
        ]);
    }

    public function removeListener(string $eventName, string $listenerName): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        $this->listeners[$eventName] = array_filter(
            $this->listeners[$eventName],
            fn(EventListenerInterface $listener): bool => $listener->getName() !== $listenerName,
        );

        // 如果沒有監聽器了，移除事件鍵
        if (empty($this->listeners[$eventName])) {
            unset($this->listeners[$eventName]);
        }

        $this->logger?->debug('Event listener removed', [
            'event_name' => $eventName,
            'listener' => $listenerName,
        ]);
    }

    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function getListenersForEvent(string $eventName): array
    {
        return $this->listeners[$eventName] ?? [];
    }

    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * 批量註冊監聽器.
     *
     * @param EventListenerInterface[] $listeners
     */
    public function registerListeners(array $listeners): void
    {
        foreach ($listeners as $listener) {
            $listenedEvents = $listener->getListenedEvents();

            foreach ($listenedEvents as $eventName) {
                $this->listen($eventName, $listener);
            }
        }
    }

    /**
     * 取得事件分派統計資訊.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $totalEvents = count($this->listeners);
        $totalListeners = 0;

        foreach ($this->listeners as $eventListeners) {
            $totalListeners += count($eventListeners);
        }

        return [
            'total_event_types' => $totalEvents,
            'total_listeners' => $totalListeners,
            'event_types' => array_keys($this->listeners),
            'listeners_per_event' => array_map(
                fn(array $eventListeners): int => count($eventListeners),
                $this->listeners,
            ),
        ];
    }
}
