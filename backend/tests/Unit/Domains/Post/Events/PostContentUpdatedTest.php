<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Events;

use App\Domains\Post\Events\PostContentUpdated;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * PostContentUpdated 領域事件測試.
 */
final class PostContentUpdatedTest extends UnitTestCase
{
    public function test_can_create_event_and_get_name(): void
    {
        $updatedAt = new DateTimeImmutable('2026-01-02 03:04:05');
        $event = new PostContentUpdated('42', '新標題', $updatedAt);

        $this->assertSame('42', $event->postId);
        $this->assertSame('新標題', $event->title);
        $this->assertSame($updatedAt, $event->updatedAt);
        $this->assertSame('post.content_updated', $event->getEventName());
    }

    public function test_get_event_data_returns_expected_payload(): void
    {
        $updatedAt = new DateTimeImmutable('2026-05-06 07:08:09');
        $event = new PostContentUpdated('99', '更新後的標題', $updatedAt);

        $this->assertSame([
            'post_id'    => '99',
            'title'      => '更新後的標題',
            'updated_at' => '2026-05-06 07:08:09',
        ], $event->getEventData());
    }

    public function test_inherits_event_id_and_occurred_on(): void
    {
        $event = new PostContentUpdated('1', '標題', new DateTimeImmutable());

        $this->assertNotSame('', $event->getEventId());
        $this->assertInstanceOf(DateTimeImmutable::class, $event->getOccurredOn());
    }
}
