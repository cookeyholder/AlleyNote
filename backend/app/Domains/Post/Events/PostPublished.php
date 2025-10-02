<?php

declare(strict_types=1);

namespace App\Domains\Post\Events;

use App\Shared\Events\AbstractDomainEvent;
use DateTimeImmutable;

/**
 * 文章已發佈事件.
 *
 * 當文章狀態從草稿或其他狀態變更為已發佈時觸發
 */
final class PostPublished extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $postId,
        public readonly string $title,
        public readonly int $authorId,
        public readonly DateTimeImmutable $publishedAt,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'post.published';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'post_id' => $this->postId,
            'title' => $this->title,
            'author_id' => $this->authorId,
            'published_at' => $this->publishedAt->format('Y-m-d H:i:s'),
        ];
    }
}
