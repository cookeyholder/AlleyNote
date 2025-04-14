<?php

declare(strict_types=1);

namespace App\Models;

use JsonSerializable;

class Post implements JsonSerializable
{
    private int $id;
    private string $uuid;
    private ?string $seqNumber;  // 改為 nullable string
    private string $title;
    private string $content;
    private int $userId;
    private ?string $userIp;
    private bool $isPinned;
    private string $status;
    private ?string $publishDate;
    private int $viewCount;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(array $data)
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : 0;  // 修改這行，當未提供 id 時預設為 0
        $this->uuid = $data['uuid'] ?? bin2hex(random_bytes(16));  // 如果沒有提供 uuid，則產生一個新的
        $this->seqNumber = isset($data['seq_number']) ? (string)$data['seq_number'] : null;  // 確保型別轉換
        $this->title = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $this->content = htmlspecialchars($data['content'], ENT_QUOTES, 'UTF-8');
        $this->userId = (int)$data['user_id'];
        $this->userIp = $data['user_ip'] ?? null;
        $this->isPinned = filter_var($data['is_pinned'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->status = $data['status'] ?? 'draft';
        $this->publishDate = $data['publish_date'] ?? null;
        $this->viewCount = (int)($data['view_count'] ?? 0);
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt = $data['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSeqNumber(): ?string
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

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPublishDate(): ?string
    {
        return $this->publishDate;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
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
            'view_count' => $this->viewCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
