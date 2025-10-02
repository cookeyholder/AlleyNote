<?php

declare(strict_types=1);

namespace App\Domains\Post\Aggregates;

use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Events\PostContentUpdated;
use App\Domains\Post\Events\PostPublished;
use App\Domains\Post\Events\PostStatusChanged;
use App\Domains\Post\Exceptions\PostValidationException;
use App\Domains\Post\ValueObjects\PostContent;
use App\Domains\Post\ValueObjects\PostId;
use App\Domains\Post\ValueObjects\PostSlug;
use App\Domains\Post\ValueObjects\PostTitle;
use App\Domains\Post\ValueObjects\ViewCount;
use App\Shared\Events\AbstractDomainEvent;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Post 聚合根.
 *
 * 負責維護 Post 實體的一致性邊界和業務規則
 * 所有對 Post 的修改都應通過此聚合根進行
 */
final class PostAggregate
{
    /** @var array<AbstractDomainEvent> */
    private array $domainEvents = [];

    private DateTimeImmutable $createdAt;

    private DateTimeImmutable $updatedAt;

    private ?DateTimeImmutable $publishedAt = null;

    /**
     * @param PostId $id 文章唯一識別碼
     * @param PostTitle $title 文章標題
     * @param PostContent $content 文章內容
     * @param int $authorId 作者 ID
     * @param PostStatus $status 文章狀態
     * @param ViewCount $viewCount 瀏覽次數
     * @param bool $isPinned 是否置頂
     * @param PostSlug|null $slug URL 友善代稱
     * @param string|null $creationSource 建立來源
     */
    private function __construct(
        private readonly PostId $id,
        private PostTitle $title,
        private PostContent $content,
        private readonly int $authorId,
        private PostStatus $status,
        private ViewCount $viewCount,
        private bool $isPinned = false,
        private ?PostSlug $slug = null,
        private readonly ?string $creationSource = null,
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * 建立新的文章聚合.
     *
     * @param PostId $id 文章 ID
     * @param PostTitle $title 文章標題
     * @param PostContent $content 文章內容
     * @param int $authorId 作者 ID
     * @param string|null $creationSource 建立來源
     * @throws InvalidArgumentException 當參數無效時
     */
    public static function create(
        PostId $id,
        PostTitle $title,
        PostContent $content,
        int $authorId,
        ?string $creationSource = null,
    ): self {
        if ($authorId <= 0) {
            throw new InvalidArgumentException('作者 ID 必須大於 0');
        }

        $aggregate = new self(
            id: $id,
            title: $title,
            content: $content,
            authorId: $authorId,
            status: PostStatus::DRAFT,
            viewCount: ViewCount::zero(),
            isPinned: false,
            slug: PostSlug::fromTitle($title->toString()),
            creationSource: $creationSource,
        );

        return $aggregate;
    }

    /**
     * 從現有資料重建聚合.
     *
     * @param array<string, mixed> $data 資料陣列
     * @throws InvalidArgumentException 當資料無效時
     */
    public static function reconstitute(array $data): self
    {
        // 提取並驗證必要欄位
        /** @var string $uuid */
        $uuid = $data['uuid'] ?? '';
        /** @var string $title */
        $title = $data['title'] ?? '';
        /** @var string $content */
        $content = $data['content'] ?? '';
        /** @var int $userId */
        $userId = $data['user_id'] ?? 0;
        /** @var string $status */
        $status = $data['status'] ?? 'draft';
        /** @var int $views */
        $views = $data['views'] ?? 0;
        /** @var bool $isPinned */
        $isPinned = $data['is_pinned'] ?? false;
        /** @var string|null $seqNumber */
        $seqNumber = $data['seq_number'] ?? null;
        /** @var string|null $creationSource */
        $creationSource = $data['creation_source'] ?? null;

        $aggregate = new self(
            id: PostId::fromString($uuid),
            title: PostTitle::fromString($title),
            content: PostContent::fromString($content),
            authorId: $userId,
            status: PostStatus::tryFrom($status) ?? PostStatus::DRAFT,
            viewCount: ViewCount::fromInt($views),
            isPinned: $isPinned,
            slug: $seqNumber !== null ? PostSlug::fromString($seqNumber) : null,
            creationSource: $creationSource,
        );

        if (isset($data['created_at'])) {
            /** @var string $createdAt */
            $createdAt = $data['created_at'];
            $aggregate->createdAt = new DateTimeImmutable($createdAt);
        }

        if (isset($data['updated_at'])) {
            /** @var string $updatedAt */
            $updatedAt = $data['updated_at'];
            $aggregate->updatedAt = new DateTimeImmutable($updatedAt);
        }

        if (isset($data['publish_date'])) {
            /** @var string|null $publishDate */
            $publishDate = $data['publish_date'];
            if ($publishDate !== null) {
                $aggregate->publishedAt = new DateTimeImmutable($publishDate);
            }
        }

        return $aggregate;
    }

    /**
     * 發佈文章.
     *
     * @throws PostValidationException 當文章狀態不允許發佈時
     */
    public function publish(): void
    {
        if ($this->status === PostStatus::PUBLISHED) {
            throw new PostValidationException('文章已經發佈');
        }

        if ($this->status === PostStatus::ARCHIVED) {
            throw new PostValidationException('已封存的文章不能發佈');
        }

        $this->ensureContentIsValid();

        $this->status = PostStatus::PUBLISHED;
        $this->publishedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(new PostPublished(
            postId: $this->id->toString(),
            title: $this->title->toString(),
            authorId: $this->authorId,
            publishedAt: $this->publishedAt,
        ));
    }

    /**
     * 更新文章內容.
     *
     * @param PostTitle $title 新標題
     * @param PostContent $content 新內容
     * @throws PostValidationException 當文章狀態不允許編輯時
     */
    public function updateContent(PostTitle $title, PostContent $content): void
    {
        if ($this->status === PostStatus::ARCHIVED) {
            throw new PostValidationException('已封存的文章不能編輯');
        }

        $oldTitle = $this->title;
        $this->title = $title;
        $this->content = $content;
        $this->updatedAt = new DateTimeImmutable();

        // 如果標題改變，更新 slug
        if (!$oldTitle->equals($title)) {
            $this->slug = PostSlug::fromTitle($title->toString());
        }

        $this->recordEvent(new PostContentUpdated(
            postId: $this->id->toString(),
            title: $this->title->toString(),
            updatedAt: $this->updatedAt,
        ));
    }

    /**
     * 封存文章.
     *
     * @throws PostValidationException 當文章已封存時
     */
    public function archive(): void
    {
        if ($this->status === PostStatus::ARCHIVED) {
            throw new PostValidationException('文章已經封存');
        }

        $oldStatus = $this->status;
        $this->status = PostStatus::ARCHIVED;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(new PostStatusChanged(
            postId: $this->id->toString(),
            oldStatus: $oldStatus->value,
            newStatus: $this->status->value,
            changedAt: $this->updatedAt,
        ));
    }

    /**
     * 設為草稿.
     */
    public function setAsDraft(): void
    {
        if ($this->status === PostStatus::DRAFT) {
            return;
        }

        $oldStatus = $this->status;
        $this->status = PostStatus::DRAFT;
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(new PostStatusChanged(
            postId: $this->id->toString(),
            oldStatus: $oldStatus->value,
            newStatus: $this->status->value,
            changedAt: $this->updatedAt,
        ));
    }

    /**
     * 設定置頂狀態.
     *
     * @param bool $isPinned 是否置頂
     */
    public function setPin(bool $isPinned): void
    {
        if ($this->isPinned === $isPinned) {
            return;
        }

        $this->isPinned = $isPinned;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * 增加瀏覽次數.
     */
    public function incrementViewCount(): void
    {
        $this->viewCount = $this->viewCount->increment();
        // 瀏覽次數增加不更新 updatedAt，因為這不是實質性的內容變更
    }

    /**
     * 檢查是否為草稿.
     */
    public function isDraft(): bool
    {
        return $this->status === PostStatus::DRAFT;
    }

    /**
     * 檢查是否已發佈.
     */
    public function isPublished(): bool
    {
        return $this->status === PostStatus::PUBLISHED;
    }

    /**
     * 檢查是否已封存.
     */
    public function isArchived(): bool
    {
        return $this->status === PostStatus::ARCHIVED;
    }

    /**
     * 檢查是否由特定作者撰寫.
     *
     * @param int $authorId 作者 ID
     */
    public function isAuthoredBy(int $authorId): bool
    {
        return $this->authorId === $authorId;
    }

    // Getters
    public function getId(): PostId
    {
        return $this->id;
    }

    public function getTitle(): PostTitle
    {
        return $this->title;
    }

    public function getContent(): PostContent
    {
        return $this->content;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function getStatus(): PostStatus
    {
        return $this->status;
    }

    public function getViewCount(): ViewCount
    {
        return $this->viewCount;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function getSlug(): ?PostSlug
    {
        return $this->slug;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getCreationSource(): ?string
    {
        return $this->creationSource;
    }

    /**
     * 取得所有領域事件.
     *
     * @return array<AbstractDomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * 轉換為陣列表示.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->id->toString(),
            'title' => $this->title->toString(),
            'content' => $this->content->toString(),
            'user_id' => $this->authorId,
            'status' => $this->status->value,
            'views' => $this->viewCount->getValue(),
            'is_pinned' => $this->isPinned,
            'seq_number' => $this->slug?->toString(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'publish_date' => $this->publishedAt?->format('Y-m-d H:i:s'),
            'creation_source' => $this->creationSource,
        ];
    }

    /**
     * 記錄領域事件.
     *
     * @param AbstractDomainEvent $event 領域事件
     */
    private function recordEvent(AbstractDomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * 確保內容有效.
     *
     * @throws PostValidationException 當內容無效時
     */
    private function ensureContentIsValid(): void
    {
        if ($this->title->getLength() === 0) {
            throw new PostValidationException('文章標題不能為空');
        }

        if ($this->content->getLength() === 0) {
            throw new PostValidationException('文章內容不能為空');
        }
    }
}
