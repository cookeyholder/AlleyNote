<?php

declare(strict_types=1);

namespace App\Domains\Post\Models;

use App\Shared\Contracts\OutputSanitizerInterface;
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

    private int $views;

    private string $createdAt;

    private string $updatedAt;

    public function __construct(array $data)
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : 0;
        $this->uuid = $data['uuid'] ?? generate_uuid();
        $this->seqNumber = isset($data['seq_number']) ? (string) $data['seq_number'] : null;
        $this->title = $data['title'] ?? '';
        $this->content = $data['content'] ?? '';
        $this->userId = (int) ($data['user_id'] ?? 0);
        $this->userIp = $data['user_ip'] ?? null;
        $this->isPinned = filter_var($data['is_pinned'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->status = $data['status'] ?? 'draft';
        $this->publishDate = $data['publish_date'] ?? null;
        $this->views = (int) ($data['views'] ?? 0);
        $this->createdAt = $data['created_at'] ?? format_datetime();
        $this->updatedAt = $data['updated_at'] ?? format_datetime();
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

    /**
     * 相容性方法：提供 getIsPinned 作為 isPinned 的別名.
     */
    public function getIsPinned(): bool
    {
        return $this->isPinned();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPublishDate(): ?string
    {
        return $this->publishDate;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    /**
     * 相容性方法：提供 getViewCount 作為 getViews 的別名.
     * @deprecated 使用 getViews() 替代
     */
    public function getViewCount(): int
    {
        return $this->getViews();
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
            'views' => $this->views,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * 取得清理過的資料陣列，適用於前端顯示.
     *
     * @param OutputSanitizerInterface $sanitizer 清理服務
     */
    public function toSafeArray(OutputSanitizerInterface $sanitizer): array
    {
        $data = $this->toArray();

        // 清理可能包含 HTML 的欄位
        $data['title'] = $sanitizer->sanitizeHtml($data['title']);
        $data['content'] = $sanitizer->sanitizeHtml($data['content']);

        return $data;
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
