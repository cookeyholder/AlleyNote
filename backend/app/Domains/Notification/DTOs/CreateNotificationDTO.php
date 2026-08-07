<?php

declare(strict_types=1);

namespace App\Domains\Notification\DTOs;

class CreateNotificationDTO
{
    public function __construct(
        public readonly ?int $userId,
        public readonly string $title,
        public readonly string $message,
        public readonly string $type = 'system',
    ) {}

    /**
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $userId = isset($data['user_id']) && is_numeric($data['user_id']) ? (int) $data['user_id'] : null;
        $title = isset($data['title']) && is_string($data['title']) ? $data['title'] : '';
        $message = isset($data['message']) && is_string($data['message']) ? $data['message'] : '';
        $type = isset($data['type']) && is_string($data['type']) ? $data['type'] : 'system';

        return new self(
            userId: $userId,
            title: $title,
            message: $message,
            type: $type,
        );
    }
}
