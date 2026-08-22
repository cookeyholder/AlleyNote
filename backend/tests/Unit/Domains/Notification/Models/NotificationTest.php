<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Notification\Models;

use App\Domains\Notification\Models\Notification;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * Notification 模型測試.
 */
#[CoversClass(Notification::class)]
class NotificationTest extends UnitTestCase
{
    #[Test]
    public function it_exposes_all_properties_via_getters(): void
    {
        $notification = new Notification(
            id: 5,
            uuid: 'uuid-1234',
            userId: 9,
            title: '系統公告',
            message: '系統將於今晚維護',
            type: 'system',
            isRead: false,
            readAt: null,
            createdAt: '2026-07-01 10:00:00',
        );

        $this->assertSame(5, $notification->getId());
        $this->assertSame('uuid-1234', $notification->getUuid());
        $this->assertSame(9, $notification->getUserId());
        $this->assertSame('系統公告', $notification->getTitle());
        $this->assertSame('系統將於今晚維護', $notification->getMessage());
        $this->assertSame('system', $notification->getType());
        $this->assertFalse($notification->isRead());
        $this->assertNull($notification->getReadAt());
        $this->assertSame('2026-07-01 10:00:00', $notification->getCreatedAt());
    }

    #[Test]
    public function it_marks_notification_as_read(): void
    {
        $notification = new Notification(
            id: 1,
            uuid: 'uuid-1',
            userId: 2,
            title: '標題',
            message: '內容',
            type: 'alert',
            isRead: false,
            readAt: null,
            createdAt: '2026-07-01 10:00:00',
        );

        $notification->markAsRead('2026-07-02 08:30:00');

        $this->assertTrue($notification->isRead());
        $this->assertSame('2026-07-02 08:30:00', $notification->getReadAt());
    }

    #[Test]
    public function it_hydrates_from_database_row(): void
    {
        $row = [
            'id'         => '12',
            'uuid'       => 'uuid-db',
            'user_id'    => '3',
            'title'      => '資料庫通知',
            'message'    => '訊息內容',
            'type'       => 'info',
            'is_read'    => '1',
            'read_at'    => '2026-06-15 09:00:00',
            'created_at' => '2026-06-14 09:00:00',
        ];

        $notification = Notification::fromDatabaseRow($row);

        $this->assertSame(12, $notification->getId());
        $this->assertSame('uuid-db', $notification->getUuid());
        $this->assertSame(3, $notification->getUserId());
        $this->assertTrue($notification->isRead());
        $this->assertSame('2026-06-15 09:00:00', $notification->getReadAt());
    }

    #[Test]
    public function it_applies_defaults_for_missing_row_fields(): void
    {
        $notification = Notification::fromDatabaseRow([]);

        $this->assertNull($notification->getId());
        $this->assertSame('', $notification->getUuid());
        $this->assertNull($notification->getUserId());
        $this->assertSame('', $notification->getTitle());
        $this->assertSame('', $notification->getMessage());
        $this->assertSame('system', $notification->getType());
        $this->assertFalse($notification->isRead());
        $this->assertNull($notification->getReadAt());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $notification->getCreatedAt());
    }

    #[Test]
    public function it_converts_to_array_and_json(): void
    {
        $notification = new Notification(
            id: 7,
            uuid: 'uuid-7',
            userId: null,
            title: '全域通知',
            message: '所有使用者可見',
            type: 'broadcast',
            isRead: true,
            readAt: '2026-07-03 12:00:00',
            createdAt: '2026-07-01 00:00:00',
        );

        $array = $notification->toArray();

        $this->assertSame(7, $array['id']);
        $this->assertSame('uuid-7', $array['uuid']);
        $this->assertNull($array['user_id']);
        $this->assertSame('全域通知', $array['title']);
        $this->assertSame('所有使用者可見', $array['message']);
        $this->assertSame('broadcast', $array['type']);
        $this->assertTrue($array['is_read']);
        $this->assertSame('2026-07-03 12:00:00', $array['read_at']);
        $this->assertSame('2026-07-01 00:00:00', $array['created_at']);

        $this->assertSame($array, $notification->jsonSerialize());
        $this->assertJson((string) json_encode($notification));
    }
}
