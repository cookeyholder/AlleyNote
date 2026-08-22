<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Events;

use App\Domains\Auth\Events\UserRegistered;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * 使用者註冊事件單元測試.
 */
final class UserRegisteredTest extends UnitTestCase
{
    public function testEventGettersAndData(): void
    {
        $now = new DateTimeImmutable('2026-08-22 14:30:00');
        $event = new UserRegistered(
            userId: 20,
            username: 'bob',
            email: 'bob@example.com',
            registeredAt: $now,
            registrationSource: 'web_form',
            ipAddress: '127.0.0.1',
        );

        $this->assertSame(20, $event->userId);
        $this->assertSame('bob', $event->username);
        $this->assertSame('bob@example.com', $event->email);
        $this->assertSame($now, $event->registeredAt);
        $this->assertSame('web_form', $event->registrationSource);
        $this->assertSame('127.0.0.1', $event->ipAddress);

        $this->assertSame('user.registered', $event->getEventName());

        $this->assertSame([
            'user_id'             => 20,
            'username'            => 'bob',
            'email'               => 'bob@example.com',
            'registered_at'       => '2026-08-22 14:30:00',
            'registration_source' => 'web_form',
            'ip_address'          => '127.0.0.1',
        ], $event->getEventData());
    }
}
