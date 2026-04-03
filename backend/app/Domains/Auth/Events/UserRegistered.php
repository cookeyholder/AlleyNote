<?php

declare(strict_types=1);

namespace App\Domains\Auth\Events;
use App\Shared\Events\AbstractDomainEvent;
use DateTimeImmutable;
final class UserRegistered extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $username,
        public readonly string $email,
        public readonly DateTimeImmutable $registeredAt,
        public readonly ?string $registrationSource = null,
        public readonly ?string $ipAddress = null,
    ) {
        parent::__construct();
    }
    public function getEventName(): string
    {
        return 'user.registered';
    }
    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return [
            'user_id' => $this->userId,
            'username' => $this->username,
            'email' => $this->email,
            'registered_at' => $this->registeredAt->format('Y-m-d H:i:s'),
            'registration_source' => $this->registrationSource,
            'ip_address' => $this->ipAddress,
        ];
    }
}
