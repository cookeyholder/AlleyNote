<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Events;

use App\Domains\Statistics\Events\PostViewed;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * PostViewed 事件單元測試.
 */
class PostViewedTest extends TestCase
{
    public function testCreateAnonymousPostViewedEvent(): void
    {
        // Arrange
        $postId = 123;
        $userIp = '192.168.1.1';
        $userAgent = 'Mozilla/5.0';
        $referrer = 'https://example.com';

        // Act
        $event = PostViewed::createAnonymous($postId, $userIp, $userAgent, $referrer);

        // Assert
        $this->assertEquals('statistics.post.viewed', $event->getEventName());
        $this->assertEquals($postId, $event->getPostId());
        $this->assertNull($event->getUserId());
        $this->assertEquals($userIp, $event->getUserIp());
        $this->assertEquals($userAgent, $event->getUserAgent());
        $this->assertEquals($referrer, $event->getReferrer());
        $this->assertFalse($event->isAuthenticatedUser());
        $this->assertInstanceOf(DateTimeImmutable::class, $event->getOccurredOn());
        $this->assertIsString($event->getEventId());
    }

    public function testCreateAuthenticatedPostViewedEvent(): void
    {
        // Arrange
        $postId = 456;
        $userId = 789;
        $userIp = '10.0.0.1';
        $userAgent = 'Chrome/91.0';

        // Act
        $event = PostViewed::createAuthenticated($postId, $userId, $userIp, $userAgent);

        // Assert
        $this->assertEquals('statistics.post.viewed', $event->getEventName());
        $this->assertEquals($postId, $event->getPostId());
        $this->assertEquals($userId, $event->getUserId());
        $this->assertEquals($userIp, $event->getUserIp());
        $this->assertEquals($userAgent, $event->getUserAgent());
        $this->assertNull($event->getReferrer());
        $this->assertTrue($event->isAuthenticatedUser());
    }

    public function testGetEventName(): void
    {
        // Arrange
        $event = PostViewed::createAnonymous(1, '127.0.0.1');

        // Act
        $eventName = $event->getEventName();

        // Assert
        $this->assertEquals('statistics.post.viewed', $eventName);
    }

    public function testToArrayFormat(): void
    {
        // Arrange
        $event = PostViewed::createAnonymous(1, '127.0.0.1');

        // Act
        $array = $event->toArray();

        // Assert
        $this->assertArrayHasKey('event_id', $array);
        $this->assertArrayHasKey('event_name', $array);
        $this->assertArrayHasKey('occurred_on', $array);
        $this->assertArrayHasKey('event_data', $array);
        $this->assertEquals('statistics.post.viewed', $array['event_name']);
        $this->assertIsArray($array['event_data']);
    }

    public function testCustomViewedAt(): void
    {
        // Arrange
        $viewedAt = new DateTimeImmutable('2025-01-01 12:00:00');

        // Act
        $event = new PostViewed(
            postId: 123,
            userId: null,
            userIp: '192.168.1.1',
            userAgent: null,
            referrer: null,
            viewedAt: $viewedAt,
        );

        // Assert
        $this->assertEquals($viewedAt, $event->getViewedAt());
        $eventData = $event->getEventData();
        $this->assertEquals($viewedAt->format('c'), $eventData['viewed_at']);
    }

    public function testMinimalEvent(): void
    {
        // Arrange & Act
        $event = new PostViewed(
            postId: 1,
            userId: null,
            userIp: '0.0.0.0',
        );

        // Assert
        $this->assertEquals(1, $event->getPostId());
        $this->assertNull($event->getUserId());
        $this->assertEquals('0.0.0.0', $event->getUserIp());
        $this->assertNull($event->getUserAgent());
        $this->assertNull($event->getReferrer());
        $this->assertFalse($event->isAuthenticatedUser());
    }
}
