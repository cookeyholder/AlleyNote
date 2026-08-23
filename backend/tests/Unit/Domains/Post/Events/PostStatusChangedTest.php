<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Events;

use App\Domains\Post\Events\PostStatusChanged;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * PostStatusChanged 領域事件測試.
 */
final class PostStatusChangedTest extends UnitTestCase
{
    public function test_can_create_event_and_get_name(): void
    {
        $changedAt = new DateTimeImmutable('2026-03-04 05:06:07');
        $event = new PostStatusChanged('55', 'draft', 'published', $changedAt);

        $this->assertSame('55', $event->postId);
        $this->assertSame('draft', $event->oldStatus);
        $this->assertSame('published', $event->newStatus);
        $this->assertSame($changedAt, $event->changedAt);
        $this->assertSame('post.status_changed', $event->getEventName());
    }

    public function test_get_event_data_returns_expected_payload(): void
    {
        $changedAt = new DateTimeImmutable('2026-08-09 10:11:12');
        $event = new PostStatusChanged('66', 'published', 'archived', $changedAt);

        $this->assertSame([
            'post_id'    => '66',
            'old_status' => 'published',
            'new_status' => 'archived',
            'changed_at' => '2026-08-09 10:11:12',
        ], $event->getEventData());
    }
}
