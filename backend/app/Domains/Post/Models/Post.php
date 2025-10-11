<?php

declare(strict_types=1);

namespace App\Domains\Post\Models;

use App\Domains\Post\Enums\PostStatus;
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

    private PostStatus $status;

    private ?string $publishDate;

    private int $views;

    private string $createdAt;

    private string $updatedAt;

    private ?string $creationSource;

    private ?string $creationSourceDetail;

    private ?string $author; // 作者用戶名（從 users 表 JOIN 得到）

    public function __construct(array $data)
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->uuid = (string) ($data['uuid'] ?? generate_uuid());
        $this->seqNumber = isset($data['seq_number']) ? (string) $data['seq_number'] : null;
        $this->title = (string) ($data['title'] ?? '');
        $this->content = (string) ($data['content'] ?? '');
        $this->userId = (int) ($data['user_id'] ?? 0);
        $this->userIp = isset($data['user_ip']) ? (string) $data['user_ip'] : null;
        $this->isPinned = filter_var($data['is_pinned'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->setStatus(PostStatus::tryFrom((string) ($data['status'] ?? 'draft')) ?? PostStatus::DRAFT);
        $this->publishDate = isset($data['publish_date']) ? (string) $data['publish_date'] : null;
        $this->views = (int) ($data['views'] ?? 0);
        $this->createdAt = (string) ($data['created_at'] ?? format_datetime());
        $this->updatedAt = (string) ($data['updated_at'] ?? format_datetime());
        $this->creationSource = isset($data['creation_source']) && $data['creation_source'] !== null ? (string) $data['creation_source'] : null;
        $this->creationSourceDetail = isset($data['creation_source_detail']) && $data['creation_source_detail'] !== null ? (string) $data['creation_source_detail'] : null;
        $this->author = isset($data['author']) && $data['author'] !== null ? (string) $data['author'] : null;
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

    public function getStatus(): PostStatus
    {
        return $this->status;
    }

    public function setStatus(PostStatus $status): void
    {
        $this->status = $status;
    }

    public function hasStatus(PostStatus $status): bool
    {
        return $this->status === $status;
    }

    public function getStatusValue(): string
    {
        return $this->status->value;
    }

    public function getPublishDate(): ?string
    {
        // 返回格式化為 RFC3339 的時間
        return $this->formatPublishDateForApi();
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

    public function getCreationSource(): ?string
    {
        return $this->creationSource;
    }

    public function getCreationSourceDetail(): ?string
    {
        return $this->creationSourceDetail;
    }

    /**
     * @return array<mixed>
     */
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
            'status' => $this->getStatusValue(),
            'publish_date' => $this->formatPublishDateForApi(),
            'views' => $this->views,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'creation_source' => $this->creationSource,
            'creation_source_detail' => $this->creationSourceDetail,
            'author' => $this->author,
        ];
    }

    /**
     * 格式化發布時間為 API 輸出格式（RFC3339 / ISO 8601）
     * 資料庫儲存的是 UTC 時間，需要明確加上時區資訊
     */
    protected function formatPublishDateForApi(): ?string
    {
        if ($this->publishDate === null) {
            return null;
        }

        // 如果已經是 ISO 8601 格式（包含 T 和時區），直接返回
        if (strpos($this->publishDate, 'T') !== false) {
            return $this->publishDate;
        }

        // 資料庫格式：YYYY-MM-DD HH:MM:SS (UTC)
        // 轉換為：YYYY-MM-DDTHH:MM:SSZ (RFC3339)
        try {
            $dateTime = new \DateTime($this->publishDate, new \DateTimeZone('UTC'));
            return $dateTime->format(\DateTime::ATOM); // RFC3339 格式
        } catch (\Exception $e) {
            // 如果轉換失敗，返回原始值
            return $this->publishDate;
        }
    }

    /**
     * 取得清理過的資料陣列，適用於前端顯示.
     *
     * @param OutputSanitizerInterface $sanitizer 清理服務
     * @return array<mixed>
     */
    public function toSafeArray(OutputSanitizerInterface $sanitizer): mixed
    {
        $data = $this->toArray();

        // 清理可能包含 HTML 的欄位
        $data['title'] = $sanitizer->sanitizeHtml((string) $data['title']);
        $data['content'] = $sanitizer->sanitizeHtml((string) $data['content']);

        return $data;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
