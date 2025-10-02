<?php

declare(strict_types=1);

namespace App\Domains\Auth\Events;

use App\Shared\Events\AbstractDomainEvent;
use DateTimeImmutable;

/**
 * 使用者已登入事件.
 *
 * 當使用者成功登入時觸發
 */
final class UserLoggedIn extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $username,
        public readonly DateTimeImmutable $loggedInAt,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly ?string $deviceType = null,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'user.logged_in';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'user_id' => $this->userId,
            'username' => $this->username,
            'logged_in_at' => $this->loggedInAt->format('Y-m-d H:i:s'),
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'device_type' => $this->deviceType,
        ];
    }
}
