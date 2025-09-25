<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Events;

use App\Shared\Events\Contracts\DomainEventInterface;
use App\Shared\Events\Contracts\EventListenerInterface;
use App\Shared\Events\SimpleEventDispatcher;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * SimpleEventDispatcher 單元測試.
 */
class SimpleEventDispatcherTest extends TestCase
{
    private SimpleEventDispatcher $dispatcher;

    /** @var LoggerInterface&MockInterface */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->dispatcher = new SimpleEventDispatcher($this->logger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanRegisterAndDispatchEvent(): void
    {
        // Arrange
        $event = $this->createMockEvent('test.event');
        $listener = $this->createMockListener(['test.event']);

        $this->logger
            ->shouldReceive('debug')
            ->twice()
            ->shouldReceive('info')
            ->once();

        $listener
            ->shouldReceive('handle')
            ->once()
            ->with($event);

        // Act
        $this->dispatcher->listen('test.event', $listener);
        $this->dispatcher->dispatch($event);

        // Assert
        $this->assertTrue($this->dispatcher->hasListeners('test.event'));
    }

    public function testDispatchWithNoListeners(): void
    {
        // Arrange
        $event = $this->createMockEvent('nonexistent.event');

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with(
                'No listeners found for event: nonexistent.event',
                Mockery::type('array'),
            );

        // Act
        $this->dispatcher->dispatch($event);

        // Assert - No exception should be thrown
        $this->assertFalse($this->dispatcher->hasListeners('nonexistent.event'));
    }

    public function testListenerExceptionHandling(): void
    {
        // Arrange
        $event = $this->createMockEvent('error.event');
        $listener = $this->createMockListener(['error.event']);

        $exception = new RuntimeException('Test exception');

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->shouldReceive('info')
            ->once()
            ->shouldReceive('error')
            ->once()
            ->with(
                'Failed to handle event',
                Mockery::type('array'),
            );

        $listener
            ->shouldReceive('handle')
            ->once()
            ->with($event)
            ->andThrow($exception);

        // Act
        $this->dispatcher->listen('error.event', $listener);
        $this->dispatcher->dispatch($event);

        // Assert - Dispatcher should continue execution despite exception
        $this->assertTrue($this->dispatcher->hasListeners('error.event'));
    }

    public function testMultipleListenersForSameEvent(): void
    {
        // Arrange
        $event = $this->createMockEvent('multi.event');
        $listener1 = $this->createMockListener(['multi.event'], 'listener1');
        $listener2 = $this->createMockListener(['multi.event'], 'listener2');

        $this->logger
            ->shouldReceive('debug')
            ->times(4)
            ->shouldReceive('info')
            ->once();

        $listener1
            ->shouldReceive('handle')
            ->once()
            ->with($event);

        $listener2
            ->shouldReceive('handle')
            ->once()
            ->with($event);

        // Act
        $this->dispatcher->listen('multi.event', $listener1);
        $this->dispatcher->listen('multi.event', $listener2);
        $this->dispatcher->dispatch($event);

        // Assert
        $listeners = $this->dispatcher->getListenersForEvent('multi.event');
        $this->assertCount(2, $listeners);
    }

    public function testRegisterListenersBatch(): void
    {
        // Arrange
        $listener1 = $this->createMockListener(['event1', 'event2'], 'listener1');
        $listener2 = $this->createMockListener(['event2', 'event3'], 'listener2');

        $this->logger
            ->shouldReceive('debug')
            ->times(4);

        // Act
        $this->dispatcher->registerListeners([$listener1, $listener2]);

        // Assert
        $this->assertTrue($this->dispatcher->hasListeners('event1'));
        $this->assertTrue($this->dispatcher->hasListeners('event2'));
        $this->assertTrue($this->dispatcher->hasListeners('event3'));
        $this->assertCount(1, $this->dispatcher->getListenersForEvent('event1'));
        $this->assertCount(2, $this->dispatcher->getListenersForEvent('event2'));
    }

    public function testRemoveListener(): void
    {
        // Arrange
        $listener = $this->createMockListener(['test.event'], 'test_listener');

        $this->logger
            ->shouldReceive('debug')
            ->twice();

        // Act
        $this->dispatcher->listen('test.event', $listener);
        $this->assertTrue($this->dispatcher->hasListeners('test.event'));

        $this->dispatcher->removeListener('test.event', 'test_listener');

        // Assert
        $this->assertFalse($this->dispatcher->hasListeners('test.event'));
    }

    public function testGetStatistics(): void
    {
        // Arrange
        $listener1 = $this->createMockListener(['event1'], 'listener1');
        $listener2 = $this->createMockListener(['event1'], 'listener2');
        $listener3 = $this->createMockListener(['event2'], 'listener3');

        $this->logger->shouldReceive('debug')->times(3);

        // Act
        $this->dispatcher->listen('event1', $listener1);
        $this->dispatcher->listen('event1', $listener2);
        $this->dispatcher->listen('event2', $listener3);

        $stats = $this->dispatcher->getStatistics();

        // Assert
        $this->assertEquals(2, $stats['total_event_types']);
        $this->assertEquals(3, $stats['total_listeners']);
        $this->assertContains('event1', $stats['event_types']);
        $this->assertContains('event2', $stats['event_types']);
        $this->assertEquals(2, $stats['listeners_per_event']['event1']);
        $this->assertEquals(1, $stats['listeners_per_event']['event2']);
    }

    public function testPreventDuplicateListenerRegistration(): void
    {
        // Arrange
        $listener = $this->createMockListener(['test.event'], 'duplicate_listener');

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->shouldReceive('warning')
            ->once()
            ->with(
                'Listener already registered for event',
                Mockery::type('array'),
            );

        // Act
        $this->dispatcher->listen('test.event', $listener);
        $this->dispatcher->listen('test.event', $listener); // Duplicate

        // Assert
        $listeners = $this->dispatcher->getListenersForEvent('test.event');
        $this->assertCount(1, $listeners); // Should still be only one
    }

    private function createMockEvent(string $eventName): DomainEventInterface
    {
        $event = Mockery::mock(DomainEventInterface::class);
        $event->shouldReceive('getEventName')->andReturn($eventName);
        $event->shouldReceive('getEventId')->andReturn('test-event-id-123');

        return $event;
    }

    /**
     * @param array<string> $events
     */
    private function createMockListener(array $events, string $name = 'test_listener'): EventListenerInterface
    {
        $listener = Mockery::mock(EventListenerInterface::class);
        $listener->shouldReceive('getListenedEvents')->andReturn($events);
        $listener->shouldReceive('getName')->andReturn($name);

        return $listener;
    }
}
