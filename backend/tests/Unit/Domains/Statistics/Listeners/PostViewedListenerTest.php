<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Listeners;

use App\Domains\Statistics\Contracts\StatisticsMonitoringServiceInterface;
use App\Domains\Statistics\Events\PostViewed;
use App\Domains\Statistics\Listeners\PostViewedListener;
use App\Shared\Events\AbstractDomainEvent;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * PostViewedListener 單元測試.
 */
class PostViewedListenerTest extends TestCase
{
    private PostViewedListener $listener;

    /** @var StatisticsMonitoringServiceInterface&MockInterface */
    private $monitoringService;

    /** @var LoggerInterface&MockInterface */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monitoringService = Mockery::mock(StatisticsMonitoringServiceInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->listener = new PostViewedListener($this->monitoringService, $this->logger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetName(): void
    {
        $this->assertEquals('statistics.post_viewed_listener', $this->listener->getName());
    }

    public function testGetListenedEvents(): void
    {
        $events = $this->listener->getListenedEvents();
        $this->assertEquals(['statistics.post.viewed'], $events);
    }

    public function testHandlePostViewedEventSuccessfully(): void
    {
        // Arrange
        $event = PostViewed::createAuthenticated(
            postId: 123,
            userId: 456,
            userIp: '192.168.1.1',
            userAgent: 'Chrome/91.0',
            referrer: 'https://example.com',
        );

        $this->logger
            ->shouldReceive('info')
            ->twice()
            ->with(Mockery::type('string'), Mockery::type('array'));

        $this->monitoringService
            ->shouldReceive('logStatisticsEvent')
            ->once()
            ->with('post_viewed', Mockery::type('array'));

        // Act
        $this->listener->handle($event);

        // Assert - No exception should be thrown
        $this->assertTrue(true);
    }

    public function testHandleAnonymousPostViewedEvent(): void
    {
        // Arrange
        $event = PostViewed::createAnonymous(
            postId: 789,
            userIp: '10.0.0.1',
            userAgent: 'Safari/14.0',
        );

        $this->logger
            ->shouldReceive('info')
            ->twice()
            ->with(Mockery::type('string'), Mockery::type('array'));

        $this->monitoringService
            ->shouldReceive('logStatisticsEvent')
            ->once()
            ->with(
                'post_viewed',
                Mockery::on(function ($context) {
                    return $context['post_id'] === 789
                        && $context['user_id'] === null
                        && $context['user_ip'] === '10.0.0.1'
                        && $context['is_authenticated'] === false;
                }),
            );

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function testHandleNonPostViewedEvent(): void
    {
        // Arrange
        $wrongEvent = Mockery::mock(AbstractDomainEvent::class);
        $wrongEvent->shouldReceive('getEventName')->andReturn('other.event');
        $wrongEvent->shouldReceive('getEventId')->andReturn('test-id');

        $this->logger
            ->shouldReceive('warning')
            ->once()
            ->with(
                'PostViewedListener received non-PostViewed event',
                Mockery::type('array'),
            );

        // Act
        $this->listener->handle($wrongEvent);

        // Assert - Should handle gracefully without processing
        $this->assertTrue(true);
    }

    public function testHandleMonitoringServiceException(): void
    {
        // Arrange
        $event = PostViewed::createAnonymous(1, '127.0.0.1');
        $exception = new RuntimeException('Monitoring service error');

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->shouldReceive('warning')
            ->once()
            ->with(
                'Failed to record view event in monitoring service',
                Mockery::type('array'),
            )
            ->shouldReceive('info')
            ->once()
            ->with(
                'PostViewed event processed successfully',
                Mockery::type('array'),
            );

        $this->monitoringService
            ->shouldReceive('logStatisticsEvent')
            ->once()
            ->andThrow($exception);

        // Act
        $this->listener->handle($event);

        // Assert - Should continue execution despite monitoring failure
        $this->assertTrue(true);
    }

    public function testHandleMonitoringServiceExceptionRecovery(): void
    {
        // Arrange
        $event = PostViewed::createAnonymous(1, '127.0.0.1');
        $exception = new RuntimeException('Monitoring service error');

        $this->logger
            ->shouldReceive('info')
            ->twice()
            ->with(Mockery::type('string'), Mockery::type('array'));

        $this->logger
            ->shouldReceive('warning')
            ->once()
            ->with(
                'Failed to record view event in monitoring service',
                Mockery::type('array'),
            );

        $this->monitoringService
            ->shouldReceive('logStatisticsEvent')
            ->once()
            ->with('post_viewed', Mockery::type('array'))
            ->andThrow($exception);

        // Act - Should not throw exception due to graceful error handling
        $this->listener->handle($event);

        // Assert - Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function testRecordViewEventContextData(): void
    {
        // Arrange
        $postId = 999;
        $userId = 888;
        $userIp = '172.16.0.1';
        $userAgent = 'Firefox/89.0';
        $referrer = 'https://google.com';

        $event = PostViewed::createAuthenticated(
            $postId,
            $userId,
            $userIp,
            $userAgent,
            $referrer,
        );

        $this->logger->shouldReceive('info')->twice();

        $this->monitoringService
            ->shouldReceive('logStatisticsEvent')
            ->once()
            ->with(
                'post_viewed',
                Mockery::on(function ($context) use ($postId, $userId, $userIp, $userAgent, $referrer) {
                    return $context['post_id'] === $postId
                        && $context['user_id'] === $userId
                        && $context['user_ip'] === $userIp
                        && $context['user_agent'] === $userAgent
                        && $context['referrer'] === $referrer
                        && $context['is_authenticated'] === true
                        && isset($context['viewed_at'])
                        && isset($context['event_id']);
                }),
            );

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }
}
