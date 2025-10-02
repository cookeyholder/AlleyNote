<?php

declare(strict_types=1);

namespace App\Domains\Post\Events;

use App\Shared\Events\AbstractDomainEvent;
use DateTimeImmutable;

/**
 * 文章內容已更新事件.
 *
 * 當文章的標題或內容被修改時觸發
 */
final class PostContentUpdated extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $postId,
        public readonly string $title,
        public readonly DateTimeImmutable $updatedAt,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'post.content_updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'post_id' => $this->postId,
            'title' => $this->title,
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
