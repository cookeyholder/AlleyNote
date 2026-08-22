<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Events;

use App\Domains\Auth\Events\UserLoggedIn;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * 使用者登入事件單元測試.
 */
final class UserLoggedInTest extends UnitTestCase
{
    public function testEventGettersAndData(): void
    {
        $now = new DateTimeImmutable('2026-08-22 14:00:00');
        $event = new UserLoggedIn(
            userId: 10,
            username: 'alice',
            loggedInAt: $now,
            ipAddress: '192.168.1.50',
            userAgent: 'Mozilla/5.0',
            deviceType: 'desktop',
        );

        $this->assertSame(10, $event->userId);
        $this->assertSame('alice', $event->username);
        $this->assertSame($now, $event->loggedInAt);
        $this->assertSame('192.168.1.50', $event->ipAddress);
        $this->assertSame('Mozilla/5.0', $event->userAgent);
        $this->assertSame('desktop', $event->deviceType);

        $this->assertSame('user.logged_in', $event->getEventName());

        $this->assertSame([
            'user_id'      => 10,
            'username'     => 'alice',
            'logged_in_at' => '2026-08-22 14:00:00',
            'ip_address'   => '192.168.1.50',
            'user_agent'   => 'Mozilla/5.0',
            'device_type'  => 'desktop',
        ], $event->getEventData());
    }
}
