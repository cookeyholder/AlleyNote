<?php

declare(strict_types=1);

namespace App\Models;

use JsonSerializable;

class Post implements JsonSerializable
{
    private int $id;
    private string $uuid;
    private int $seqNumber;
    private string $title;
    private string $content;
    private int $userId;
    private ?string $userIp;
    private bool $isPinned;
    private string $status;
    private string $publishDate;
    private string $createdAt;
    private string $updatedAt;
    private int $viewCount;

    public function __construct(array $attributes)
    {
        $this->id = $attributes['id'] ?? 0;
        $this->uuid = $attributes['uuid'] ?? '';
        $this->seqNumber = $attributes['seq_number'] ?? 0;
        $this->title = $attributes['title'] ?? '';
        $this->content = $attributes['content'] ?? '';
        $this->userId = $attributes['user_id'] ?? 0;
        $this->userIp = $attributes['user_ip'] ?? null;
        $this->isPinned = is_bool($attributes['is_pinned'] ?? false)
            ? $attributes['is_pinned']
            : (bool) $attributes['is_pinned'];
        $this->status = $attributes['status'] ?? 'draft';
        $this->publishDate = $attributes['publish_date'] ?? date('Y-m-d H:i:s');
        $this->createdAt = $attributes['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt = $attributes['updated_at'] ?? date('Y-m-d H:i:s');
        $this->viewCount = $attributes['view_count'] ?? 0;
    }

    /**
     * 從陣列建立文章物件
     * @param array $data 文章資料陣列
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSeqNumber(): int
    {
        return $this->seqNumber;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserIp(): ?string
    {
        return $this->userIp;
    }

    public function getIsPinned(): bool
    {
        return $this->isPinned;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPublishDate(): string
    {
        return $this->publishDate;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function getViews(): int
    {
        return $this->viewCount;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'seq_number' => $this->seqNumber,
            'title' => $this->title,
            'content' => $this->content,
            'user_id' => $this->userId,
            'user_ip' => $this->userIp,
            'is_pinned' => $this->isPinned,
            'status' => $this->status,
            'publish_date' => $this->publishDate,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'view_count' => $this->viewCount
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
