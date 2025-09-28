<?php

declare(strict_types=1);

namespace App\Shared\Events;

use App\Shared\Events\Contracts\DomainEventInterface;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * 抽象領域事件基底類別.
 *
 * 提供領域事件的基本實作，所有具體的領域事件都應繼承此類別
 */
abstract class AbstractDomainEvent implements DomainEventInterface
{
    private readonly string $eventId;

    private readonly DateTimeImmutable $occurredOn;

    public function __construct()
    {
        $this->eventId = Uuid::uuid4()->toString();
        $this->occurredOn = new DateTimeImmutable();
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->getEventId(),
            'event_name' => $this->getEventName(),
            'occurred_on' => $this->getOccurredOn()->format('c'),
            'event_data' => $this->getEventData(),
        ];
    }

    /**
     * 抽象方法：子類別必須實作事件名稱.
     */
    abstract public function getEventName(): string;

    /**
     * 抽象方法：子類別必須實作事件資料.
     *
     * @return array<string, mixed>
     */
    abstract public function getEventData(): array;
}
