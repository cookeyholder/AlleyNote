<?php

declare(strict_types=1);

namespace App\Domains\Post\Events;

use App\Shared\Events\AbstractDomainEvent;
use DateTimeImmutable;

final class PostStatusChanged extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $postId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly DateTimeImmutable $changedAt,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'post.status_changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'post_id' => $this->postId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_at' => $this->changedAt->format('Y-m-d H:i:s'),
        ];
    }
}
