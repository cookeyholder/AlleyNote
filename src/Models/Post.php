<?php

declare(strict_types=1);

namespace App\Models;

class Post
{
    public function __construct(
        private readonly int $id = 0,
        private readonly string $uuid = '',
        private readonly int $seq_number = 0,
        private string $title = '',
        private string $content = '',
        private int $user_id = 0,
        private string $user_ip = '',
        private int $views = 0,
        private bool $is_pinned = false,
        private int $status = 1,
        private string $publish_date = '',
        private string $created_at = '',
        private string $updated_at = ''
    ) {}

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
        return $this->seq_number;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getUserIp(): string
    {
        return $this->user_ip;
    }

    public function setUserIp(string $user_ip): self
    {
        $this->user_ip = $user_ip;
        return $this;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function incrementViews(): self
    {
        $this->views++;
        return $this;
    }

    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    public function setIsPinned(bool $is_pinned): self
    {
        $this->is_pinned = $is_pinned;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPublishDate(): string
    {
        return $this->publish_date;
    }

    public function setPublishDate(string $publish_date): self
    {
        $this->publish_date = $publish_date;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): string
    {
        return $this->updated_at;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'seq_number' => $this->seq_number,
            'title' => $this->title,
            'content' => $this->content,
            'user_id' => $this->user_id,
            'user_ip' => $this->user_ip,
            'views' => $this->views,
            'is_pinned' => $this->is_pinned,
            'status' => $this->status,
            'publish_date' => $this->publish_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            uuid: $data['uuid'] ?? '',
            seq_number: $data['seq_number'] ?? 0,
            title: $data['title'] ?? '',
            content: $data['content'] ?? '',
            user_id: $data['user_id'] ?? 0,
            user_ip: $data['user_ip'] ?? '',
            views: $data['views'] ?? 0,
            is_pinned: (bool)($data['is_pinned'] ?? false),
            status: $data['status'] ?? 1,
            publish_date: $data['publish_date'] ?? '',
            created_at: $data['created_at'] ?? '',
            updated_at: $data['updated_at'] ?? ''
        );
    }
}
