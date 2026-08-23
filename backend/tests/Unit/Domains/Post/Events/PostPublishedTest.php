<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Events;

use App\Domains\Post\Events\PostPublished;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * PostPublished 領域事件測試.
 */
final class PostPublishedTest extends UnitTestCase
{
    public function test_can_create_event_and_get_name(): void
    {
        $publishedAt = new DateTimeImmutable('2026-02-03 04:05:06');
        $event = new PostPublished('7', '發布標題', 15, $publishedAt);

        $this->assertSame('7', $event->postId);
        $this->assertSame('發布標題', $event->title);
        $this->assertSame(15, $event->authorId);
        $this->assertSame($publishedAt, $event->publishedAt);
        $this->assertSame('post.published', $event->getEventName());
    }

    public function test_get_event_data_returns_expected_payload(): void
    {
        $publishedAt = new DateTimeImmutable('2026-07-08 09:10:11');
        $event = new PostPublished('123', '公告上線', 8, $publishedAt);

        $this->assertSame([
            'post_id'      => '123',
            'title'        => '公告上線',
            'author_id'    => 8,
            'published_at' => '2026-07-08 09:10:11',
        ], $event->getEventData());
    }
}
