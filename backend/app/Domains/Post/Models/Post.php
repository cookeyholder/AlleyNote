<?php

declare(strict_types=1);

namespace App\Domains\Post\Models;

use App\Domains\Post\Enums\PostStatus;
use App\Shared\Contracts\OutputSanitizerInterface;
use DateTime;
use DateTimeZone;
use Exception;
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
        // 驗證並設定 ID
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            throw new \InvalidArgumentException('Post ID must be a valid number');
        }
        $this->id = (int) $data['id'];

        // 驗證並設定 UUID
        if (!isset($data['uuid']) || !is_string($data['uuid'])) {
            $data['uuid'] = generate_uuid();
        }
        $this->uuid = $data['uuid'];

        // 設定序號
        $this->seqNumber = isset($data['seq_number']) && is_string($data['seq_number'])
            ? $data['seq_number']
            : null;

        // 驗證並設定標題
        if (!isset($data['title']) || !is_string($data['title'])) {
            throw new \InvalidArgumentException('Post title must be a string');
        }
        $this->title = $data['title'];

        // 驗證並設定內容
        if (!isset($data['content']) || !is_string($data['content'])) {
            throw new \InvalidArgumentException('Post content must be a string');
        }
        $this->content = $data['content'];

        // 驗證並設定使用者 ID
        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            throw new \InvalidArgumentException('User ID must be a valid number');
        }
        $this->userId = (int) $data['user_id'];

        // 設定使用者 IP
        $this->userIp = isset($data['user_ip']) && is_string($data['user_ip'])
            ? $data['user_ip']
            : null;

        // 設定置頂狀態
        $this->isPinned = filter_var($data['is_pinned'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // 設定狀態
        $statusValue = is_string($data['status'] ?? 'draft') ? ($data['status'] ?? 'draft') : 'draft';
        $this->setStatus(PostStatus::tryFrom($statusValue) ?? PostStatus::DRAFT);

        // 設定發布日期
        $this->publishDate = isset($data['publish_date']) && is_string($data['publish_date'])
            ? $data['publish_date']
            : null;

        // 驗證並設定瀏覽數
        if (!isset($data['views']) || !is_numeric($data['views'])) {
            $data['views'] = 0;
        }
        $this->views = (int) $data['views'];

        // 設定建立時間
        if (!isset($data['created_at']) || !is_string($data['created_at'])) {
            $data['created_at'] = format_datetime();
        }
        $this->createdAt = $data['created_at'];

        // 設定更新時間
        if (!isset($data['updated_at']) || !is_string($data['updated_at'])) {
            $data['updated_at'] = format_datetime();
        }
        $this->updatedAt = $data['updated_at'];

        // 設定建立來源
        $this->creationSource = isset($data['creation_source']) && is_string($data['creation_source'])
            ? $data['creation_source']
            : null;

        // 設定建立來源詳情
        $this->creationSourceDetail = isset($data['creation_source_detail']) && is_string($data['creation_source_detail'])
            ? $data['creation_source_detail']
            : null;

        // 設定作者
        $this->author = isset($data['author']) && is_string($data['author'])
            ? $data['author']
            : null;
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
     * 資料庫儲存的是 UTC 時間，需要明確加上時區資訊.
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
            $dateTime = new DateTime($this->publishDate, new DateTimeZone('UTC'));

            return $dateTime->format(DateTime::ATOM); // RFC3339 格式
        } catch (Exception $e) {
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
        $title = isset($data['title']) && is_string($data['title']) ? $data['title'] : '';
        $content = isset($data['content']) && is_string($data['content']) ? $data['content'] : '';
        $data['title'] = $sanitizer->sanitizeHtml($title);
        $data['content'] = $sanitizer->sanitizeHtml($content);

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
