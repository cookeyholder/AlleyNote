<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Controllers\Api\V1;

use App\Application\Controllers\Api\V1\NotificationController;
use App\Domains\Notification\Contracts\NotificationServiceInterface;
use App\Domains\Notification\DTOs\CreateNotificationDTO;
use App\Domains\Notification\Models\Notification;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Tests\Support\UnitTestCase;

/**
 * NotificationController 單元測試.
 */
#[CoversClass(NotificationController::class)]
class NotificationControllerTest extends UnitTestCase
{
    private NotificationController $controller;

    private NotificationServiceInterface&MockInterface $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = Mockery::mock(NotificationServiceInterface::class);
        $this->controller = new NotificationController(
            $this->notificationService,
        );
    }

    #[Test]
    public function testIndexSuccessWithUserIdAttribute(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn('5');
        $request->shouldReceive('getQueryParams')->andReturn([
            'unread_only' => 'true',
            'limit'       => '30',
            'offset'      => '10',
        ]);
        $response = $this->createMockResponse();

        $this->notificationService
            ->shouldReceive('getUserNotifications')
            ->once()
            ->with(5, true, 30, 10)
            ->andReturn([
                'notifications' => [],
                'unread_count'  => 0,
                'limit'         => 30,
                'offset'        => 10,
            ]);

        $result = $this->controller->index($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testIndexSuccessWithUserArrayAttribute(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(null);
        $request->shouldReceive('getAttribute')->with('user')->andReturn(['id' => 7]);
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse();

        $this->notificationService
            ->shouldReceive('getUserNotifications')
            ->once()
            ->with(7, false, 20, 0)
            ->andReturn([
                'notifications' => [],
                'unread_count'  => 2,
                'limit'         => 20,
                'offset'        => 0,
            ]);

        $result = $this->controller->index($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testIndexExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
        $request->shouldReceive('getQueryParams')->andReturn([]);
        $response = $this->createMockResponse(500);

        $this->notificationService
            ->shouldReceive('getUserNotifications')
            ->once()
            ->andThrow(new Exception('Database error'));

        $result = $this->controller->index($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testUnreadCountSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn('3');
        $response = $this->createMockResponse();

        $this->notificationService
            ->shouldReceive('getUnreadCount')
            ->once()
            ->with(3)
            ->andReturn(5);

        $result = $this->controller->unreadCount($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testUnreadCountExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(null);
        $request->shouldReceive('getAttribute')->with('user')->andReturn(null);
        $response = $this->createMockResponse(500);

        $this->notificationService
            ->shouldReceive('getUnreadCount')
            ->once()
            ->with(null)
            ->andThrow(new Exception('Service error'));

        $result = $this->controller->unreadCount($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testMarkAsReadSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('15');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn('2');
        $response = $this->createMockResponse();

        $this->notificationService
            ->shouldReceive('markAsRead')
            ->once()
            ->with(15, 2)
            ->andReturn(true);

        $result = $this->controller->markAsRead($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testMarkAsReadExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('id')->andReturn('15');
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(null);
        $request->shouldReceive('getAttribute')->with('user')->andReturn(null);
        $response = $this->createMockResponse(400);

        $this->notificationService
            ->shouldReceive('markAsRead')
            ->once()
            ->andThrow(new Exception('Notification not found or access denied'));

        $result = $this->controller->markAsRead($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testMarkAllAsReadSuccess(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn('5');
        $response = $this->createMockResponse();

        $this->notificationService
            ->shouldReceive('markAllAsRead')
            ->once()
            ->with(5)
            ->andReturn(12);

        $result = $this->controller->markAllAsRead($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function testMarkAllAsReadExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
        $response = $this->createMockResponse(500);

        $this->notificationService
            ->shouldReceive('markAllAsRead')
            ->once()
            ->with(1)
            ->andThrow(new Exception('Failed to update'));

        $result = $this->controller->markAllAsRead($request, $response);

        $this->assertEquals(500, $result->getStatusCode());
    }

    #[Test]
    public function testBroadcastSuccess(): void
    {
        $notificationData = [
            'title'   => '系統公告',
            'message' => '維護通知',
            'type'    => 'system',
        ];

        $request = $this->createMockRequest();
        $request->shouldReceive('getParsedBody')->andReturn($notificationData);
        $response = $this->createMockResponse(201);

        $notification = new Notification(
            1,
            'uuid-123',
            null,
            '系統公告',
            '維護通知',
            'system',
            false,
            null,
            '2025-01-01T00:00:00Z',
        );

        $this->notificationService
            ->shouldReceive('broadcastNotification')
            ->once()
            ->with(Mockery::type(CreateNotificationDTO::class))
            ->andReturn($notification);

        $result = $this->controller->broadcast($request, $response);

        $this->assertEquals(201, $result->getStatusCode());
    }

    #[Test]
    public function testBroadcastInvalidData(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getParsedBody')->andReturn(null);
        $response = $this->createMockResponse(400);

        $result = $this->controller->broadcast($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    #[Test]
    public function testBroadcastExceptionHandling(): void
    {
        $request = $this->createMockRequest();
        $request->shouldReceive('getParsedBody')->andReturn(['title' => '']);
        $response = $this->createMockResponse(400);

        $this->notificationService
            ->shouldReceive('broadcastNotification')
            ->once()
            ->andThrow(new Exception('Invalid DTO parameters'));

        $result = $this->controller->broadcast($request, $response);

        $this->assertEquals(400, $result->getStatusCode());
    }

    private function createMockRequest(): ServerRequestInterface&MockInterface
    {
        return Mockery::mock(ServerRequestInterface::class);
    }

    private function createMockResponse(int $statusCode = 200): ResponseInterface&MockInterface
    {
        $response = Mockery::mock(ResponseInterface::class);
        $stream = Mockery::mock(StreamInterface::class);

        $response->shouldReceive('getBody')
            ->andReturn($stream);

        $stream->shouldReceive('write')
            ->andReturnUsing(fn(string $string): int => strlen($string));

        $response->shouldReceive('withHeader')
            ->andReturnSelf();

        $response->shouldReceive('withStatus')
            ->andReturnUsing(function ($code) use ($response, &$statusCode) {
                $statusCode = $code;

                return $response;
            });

        $response->shouldReceive('getStatusCode')
            ->andReturnUsing(fn() => $statusCode);

        return $response;
    }
}
