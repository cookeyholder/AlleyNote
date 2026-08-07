<?php

declare(strict_types=1);

namespace App\Domains\Notification\Models;

use JsonSerializable;

class Notification implements JsonSerializable
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $uuid,
        private readonly ?int $userId,
        private readonly string $title,
        private readonly string $message,
        private readonly string $type,
        private bool $isRead,
        private ?string $readAt,
        private readonly string $createdAt,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getReadAt(): ?string
    {
        return $this->readAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function markAsRead(string $readAt): void
    {
        $this->isRead = true;
        $this->readAt = $readAt;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        $id = isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null;
        $uuid = isset($row['uuid']) && is_string($row['uuid']) ? $row['uuid'] : '';
        $userId = isset($row['user_id']) && is_numeric($row['user_id']) ? (int) $row['user_id'] : null;
        $title = isset($row['title']) && is_string($row['title']) ? $row['title'] : '';
        $message = isset($row['message']) && is_string($row['message']) ? $row['message'] : '';
        $type = isset($row['type']) && is_string($row['type']) ? $row['type'] : 'system';
        $isRead = !empty($row['is_read']);
        $readAt = isset($row['read_at']) && is_string($row['read_at']) ? $row['read_at'] : null;
        $createdAt = isset($row['created_at']) && is_string($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

        return new self(
            id: $id,
            uuid: $uuid,
            userId: $userId,
            title: $title,
            message: $message,
            type: $type,
            isRead: $isRead,
            readAt: $readAt,
            createdAt: $createdAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'uuid'       => $this->uuid,
            'user_id'    => $this->userId,
            'title'      => $this->title,
            'message'    => $this->message,
            'type'       => $this->type,
            'is_read'    => $this->isRead,
            'read_at'    => $this->readAt,
            'created_at' => $this->createdAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
