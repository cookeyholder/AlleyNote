<?php

declare(strict_types=1);

namespace Tests\Unit\Notification;

use App\Domains\Notification\Contracts\NotificationRepositoryInterface;
use App\Domains\Notification\DTOs\CreateNotificationDTO;
use App\Domains\Notification\Models\Notification;
use App\Domains\Notification\Services\NotificationService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class NotificationServiceTest extends TestCase
{
    public function testGetNotifications(): void
    {
        $mockRepo = $this->createMock(NotificationRepositoryInterface::class);
        $sampleNotification = new Notification(
            id: 1,
            uuid: 'test-uuid-1',
            userId: 10,
            title: '測試通知',
            message: '測試內容',
            type: 'system',
            isRead: false,
            readAt: null,
            createdAt: '2026-08-07 10:00:00',
        );

        $mockRepo->expects($this->once())
            ->method('findForUser')
            ->with(10, false, 20, 0)
            ->willReturn([$sampleNotification]);

        $mockRepo->expects($this->once())
            ->method('countUnreadForUser')
            ->with(10)
            ->willReturn(1);

        $service = new NotificationService($mockRepo);
        $result = $service->getUserNotifications(10, false, 20, 0);

        $this->assertEquals(1, $result['unread_count']);
        /** @var array<int, array<string, mixed>> $notifications */
        $notifications = $result['notifications'];
        $this->assertCount(1, $notifications);
        $this->assertEquals('測試通知', $notifications[0]['title']);
    }

    public function testBroadcastNotification(): void
    {
        $mockRepo = $this->createMock(NotificationRepositoryInterface::class);
        $dto = new CreateNotificationDTO(null, '全體公告', '廣播訊息內容', 'system');
        $notification = new Notification(
            id: 2,
            uuid: 'test-uuid-2',
            userId: null,
            title: '全體公告',
            message: '廣播訊息內容',
            type: 'system',
            isRead: false,
            readAt: null,
            createdAt: '2026-08-07 10:00:00',
        );

        $mockRepo->expects($this->once())
            ->method('create')
            ->with($dto)
            ->willReturn($notification);

        $service = new NotificationService($mockRepo);
        $res = $service->broadcastNotification($dto);

        $this->assertEquals('全體公告', $res->getTitle());
    }

    public function testBroadcastNotificationThrowsOnEmptyTitle(): void
    {
        $mockRepo = $this->createMock(NotificationRepositoryInterface::class);
        $dto = new CreateNotificationDTO(null, '', '內容', 'system');

        $service = new NotificationService($mockRepo);

        $this->expectException(RuntimeException::class);
        $service->broadcastNotification($dto);
    }
}
