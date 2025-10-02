# AlleyNote DDD 架構設計文件

## 📋 文件目的

本文件提供 AlleyNote 專案完整的 DDD（Domain-Driven Design）架構設計，包括聚合根、領域事件、限界上下文等關鍵概念的詳細說明和實作範例。

**狀態**: 🟡 設計階段 - 需要團隊討論和評審
**版本**: 1.0.0
**最後更新**: 2025-10-01

---

## 🏛️ 第一部分：聚合根設計

### 1.1 Post 聚合根

#### 概述
Post 聚合是系統的核心聚合，管理文章的完整生命週期，包括建立、發布、編輯、刪除等所有操作。

#### 聚合邊界
```
Post Aggregate Root
├── Post (實體 - Aggregate Root)
├── PostTitle (值物件)
├── PostContent (值物件)
├── PostSlug (值物件)
├── PostId (值物件)
├── ViewCount (值物件)
├── CreationSource (值物件)
└── PostStatus (枚舉)
```

#### 不變條件 (Invariants)
1. 已發布的文章必須有標題和內容
2. 文章的 slug 在系統中必須唯一
3. 草稿狀態的文章不能有發布日期
4. 瀏覽次數只能增加，不能減少
5. 文章狀態轉換必須遵循特定流程：draft → published → archived

#### 範例實作

```php
<?php

declare(strict_types=1);

namespace App\Domains\Post\Aggregates;

use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Events\PostPublished;
use App\Domains\Post\Events\PostViewIncremented;
use App\Domains\Post\Events\PostArchived;
use App\Domains\Post\ValueObjects\{
    PostId,
    PostTitle,
    PostContent,
    PostSlug,
    ViewCount,
    CreationSource
};
use App\Domains\Shared\ValueObjects\{Timestamp, IPAddress};
use App\Domains\Auth\ValueObjects\UserId;
use DomainException;

/**
 * Post 聚合根
 * 
 * 管理文章的完整生命週期和業務規則
 */
final class Post
{
    private array $domainEvents = [];
    
    private function __construct(
        private readonly PostId $id,
        private PostTitle $title,
        private PostContent $content,
        private readonly PostSlug $slug,
        private readonly UserId $authorId,
        private PostStatus $status,
        private ViewCount $viewCount,
        private readonly CreationSource $creationSource,
        private readonly Timestamp $createdAt,
        private Timestamp $updatedAt,
        private ?Timestamp $publishedAt = null,
        private ?IPAddress $creationIp = null,
        private bool $isPinned = false,
    ) {
        $this->validateInvariants();
    }

    /**
     * 建立新的草稿文章
     */
    public static function createDraft(
        PostId $id,
        PostTitle $title,
        PostContent $content,
        PostSlug $slug,
        UserId $authorId,
        CreationSource $creationSource,
        ?IPAddress $creationIp = null,
    ): self {
        return new self(
            id: $id,
            title: $title,
            content: $content,
            slug: $slug,
            authorId: $authorId,
            status: PostStatus::DRAFT,
            viewCount: ViewCount::zero(),
            creationSource: $creationSource,
            createdAt: Timestamp::now(),
            updatedAt: Timestamp::now(),
            creationIp: $creationIp,
        );
    }

    /**
     * 發布文章
     */
    public function publish(): void
    {
        if ($this->status === PostStatus::PUBLISHED) {
            throw new DomainException('文章已經是發布狀態');
        }

        if ($this->status === PostStatus::ARCHIVED) {
            throw new DomainException('已封存的文章不能發布');
        }

        $this->status = PostStatus::PUBLISHED;
        $this->publishedAt = Timestamp::now();
        $this->updatedAt = Timestamp::now();

        // 發布領域事件
        $this->recordEvent(new PostPublished(
            postId: $this->id,
            title: $this->title,
            publishedAt: $this->publishedAt,
            authorId: $this->authorId,
        ));
    }

    /**
     * 封存文章
     */
    public function archive(): void
    {
        if ($this->status === PostStatus::ARCHIVED) {
            throw new DomainException('文章已經是封存狀態');
        }

        $this->status = PostStatus::ARCHIVED;
        $this->updatedAt = Timestamp::now();

        $this->recordEvent(new PostArchived(
            postId: $this->id,
            archivedAt: $this->updatedAt,
        ));
    }

    /**
     * 更新內容
     */
    public function updateContent(PostTitle $title, PostContent $content): void
    {
        $this->title = $title;
        $this->content = $content;
        $this->updatedAt = Timestamp::now();
    }

    /**
     * 增加瀏覽次數
     */
    public function incrementView(): void
    {
        $this->viewCount = $this->viewCount->increment();
        
        $this->recordEvent(new PostViewIncremented(
            postId: $this->id,
            newViewCount: $this->viewCount,
        ));
    }

    /**
     * 置頂文章
     */
    public function pin(): void
    {
        $this->isPinned = true;
        $this->updatedAt = Timestamp::now();
    }

    /**
     * 取消置頂
     */
    public function unpin(): void
    {
        $this->isPinned = false;
        $this->updatedAt = Timestamp::now();
    }

    /**
     * 驗證不變條件
     */
    private function validateInvariants(): void
    {
        // 已發布的文章必須有標題和內容
        if ($this->status === PostStatus::PUBLISHED) {
            if ($this->title->isEmpty()) {
                throw new DomainException('已發布的文章必須有標題');
            }
            if ($this->content->isEmpty()) {
                throw new DomainException('已發布的文章必須有內容');
            }
        }

        // 草稿不能有發布日期
        if ($this->status === PostStatus::DRAFT && $this->publishedAt !== null) {
            throw new DomainException('草稿不能有發布日期');
        }
    }

    /**
     * 記錄領域事件
     */
    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * 取得並清除領域事件
     */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    // Getters
    public function getId(): PostId { return $this->id; }
    public function getTitle(): PostTitle { return $this->title; }
    public function getContent(): PostContent { return $this->content; }
    public function getSlug(): PostSlug { return $this->slug; }
    public function getStatus(): PostStatus { return $this->status; }
    public function getViewCount(): ViewCount { return $this->viewCount; }
    public function isPinned(): bool { return $this->isPinned; }
    public function isPublished(): bool { return $this->status === PostStatus::PUBLISHED; }
}
```

#### Repository 介面

```php
<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;

use App\Domains\Post\Aggregates\Post;
use App\Domains\Post\ValueObjects\{PostId, PostSlug};

/**
 * Post 聚合根的 Repository 介面
 */
interface PostRepositoryInterface
{
    /**
     * 儲存聚合根
     */
    public function save(Post $post): void;

    /**
     * 根據 ID 查找
     */
    public function findById(PostId $id): ?Post;

    /**
     * 根據 Slug 查找
     */
    public function findBySlug(PostSlug $slug): ?Post;

    /**
     * 刪除聚合根
     */
    public function delete(Post $post): void;

    /**
     * 檢查 Slug 是否已存在
     */
    public function slugExists(PostSlug $slug): bool;

    /**
     * 取得下一個 ID
     */
    public function nextId(): PostId;
}
```

---

### 1.2 User 聚合根

#### 概述
User 聚合管理使用者的身份認證、授權和個人資料。

#### 聚合邊界
```
User Aggregate Root
├── User (實體 - Aggregate Root)
├── UserId (值物件)
├── Email (值物件)
├── Username (值物件)
├── Password (值物件)
└── UserRole (枚舉)
```

#### 不變條件
1. Email 在系統中必須唯一
2. Username 在系統中必須唯一
3. 密碼必須符合強度要求
4. 使用者狀態轉換必須合法

#### 範例實作

```php
<?php

declare(strict_types=1);

namespace App\Domains\Auth\Aggregates;

use App\Domains\Auth\Enums\UserStatus;
use App\Domains\Auth\Events\{UserRegistered, UserAuthenticated, PasswordChanged};
use App\Domains\Auth\ValueObjects\{UserId, Email, Username, Password};
use App\Domains\Shared\ValueObjects\Timestamp;
use DomainException;

/**
 * User 聚合根
 */
final class User
{
    private array $domainEvents = [];

    private function __construct(
        private readonly UserId $id,
        private Email $email,
        private Username $username,
        private Password $password,
        private UserStatus $status,
        private readonly Timestamp $registeredAt,
        private ?Timestamp $lastLoginAt = null,
    ) {
        $this->validateInvariants();
    }

    /**
     * 註冊新使用者
     */
    public static function register(
        UserId $id,
        Email $email,
        Username $username,
        Password $password,
    ): self {
        $user = new self(
            id: $id,
            email: $email,
            username: $username,
            password: $password,
            status: UserStatus::ACTIVE,
            registeredAt: Timestamp::now(),
        );

        $user->recordEvent(new UserRegistered(
            userId: $id,
            email: $email,
            username: $username,
            registeredAt: $user->registeredAt,
        ));

        return $user;
    }

    /**
     * 驗證密碼並記錄登入
     */
    public function authenticate(string $plainPassword): bool
    {
        if (!$this->password->verify($plainPassword)) {
            return false;
        }

        $this->lastLoginAt = Timestamp::now();

        $this->recordEvent(new UserAuthenticated(
            userId: $this->id,
            authenticatedAt: $this->lastLoginAt,
        ));

        return true;
    }

    /**
     * 變更密碼
     */
    public function changePassword(string $currentPassword, Password $newPassword): void
    {
        if (!$this->password->verify($currentPassword)) {
            throw new DomainException('當前密碼不正確');
        }

        $this->password = $newPassword;

        $this->recordEvent(new PasswordChanged(
            userId: $this->id,
            changedAt: Timestamp::now(),
        ));
    }

    /**
     * 停用使用者
     */
    public function deactivate(): void
    {
        if ($this->status === UserStatus::INACTIVE) {
            throw new DomainException('使用者已經是停用狀態');
        }

        $this->status = UserStatus::INACTIVE;
    }

    /**
     * 啟用使用者
     */
    public function activate(): void
    {
        if ($this->status === UserStatus::ACTIVE) {
            throw new DomainException('使用者已經是啟用狀態');
        }

        $this->status = UserStatus::ACTIVE;
    }

    private function validateInvariants(): void
    {
        // 所有驗證都在值物件中完成
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    // Getters
    public function getId(): UserId { return $this->id; }
    public function getEmail(): Email { return $this->email; }
    public function getUsername(): Username { return $this->username; }
    public function getStatus(): UserStatus { return $this->status; }
    public function isActive(): bool { return $this->status === UserStatus::ACTIVE; }
}
```

---

### 1.3 Statistics 聚合根

#### 概述
Statistics 聚合管理系統的統計資料快照和計算邏輯。

#### 聚合邊界
```
Statistics Aggregate Root
├── StatisticsSnapshot (實體 - Aggregate Root)
├── StatisticsPeriod (值物件)
├── StatisticsMetric (值物件)
└── Various Chart Value Objects
```

#### 範例實作

```php
<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Aggregates;

use App\Domains\Statistics\ValueObjects\{StatisticsPeriod, StatisticsMetric};
use App\Domains\Statistics\Events\StatisticsCalculated;
use App\Domains\Shared\ValueObjects\Timestamp;

/**
 * Statistics 聚合根
 */
final class StatisticsSnapshot
{
    private array $domainEvents = [];

    private function __construct(
        private readonly int $id,
        private readonly StatisticsPeriod $period,
        private readonly array $metrics,
        private readonly Timestamp $calculatedAt,
    ) {}

    /**
     * 建立統計快照
     */
    public static function create(
        StatisticsPeriod $period,
        array $metrics,
    ): self {
        $snapshot = new self(
            id: 0, // Will be set by repository
            period: $period,
            metrics: $metrics,
            calculatedAt: Timestamp::now(),
        );

        $snapshot->recordEvent(new StatisticsCalculated(
            period: $period,
            metrics: $metrics,
            calculatedAt: $snapshot->calculatedAt,
        ));

        return $snapshot;
    }

    /**
     * 取得指定指標
     */
    public function getMetric(string $name): ?StatisticsMetric
    {
        return $this->metrics[$name] ?? null;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
```

---

## 🔄 第二部分：領域事件設計

### 2.1 事件架構概述

領域事件是 DDD 中的重要概念，用於捕捉聚合根狀態變化並觸發相應的業務邏輯。

#### 事件基類

```php
<?php

declare(strict_types=1);

namespace App\Domains\Shared\Events;

use App\Domains\Shared\ValueObjects\Timestamp;

/**
 * 領域事件基類
 */
abstract class DomainEvent
{
    private readonly Timestamp $occurredAt;

    public function __construct()
    {
        $this->occurredAt = Timestamp::now();
    }

    public function getOccurredAt(): Timestamp
    {
        return $this->occurredAt;
    }

    /**
     * 取得事件名稱
     */
    abstract public function getEventName(): string;

    /**
     * 轉換為陣列
     */
    abstract public function toArray(): array;
}
```

### 2.2 Post 領域事件

#### PostPublished 事件

```php
<?php

declare(strict_types=1);

namespace App\Domains\Post\Events;

use App\Domains\Shared\Events\DomainEvent;
use App\Domains\Post\ValueObjects\{PostId, PostTitle};
use App\Domains\Auth\ValueObjects\UserId;
use App\Domains\Shared\ValueObjects\Timestamp;

/**
 * 文章發布事件
 */
final readonly class PostPublished extends DomainEvent
{
    public function __construct(
        public PostId $postId,
        public PostTitle $title,
        public Timestamp $publishedAt,
        public UserId $authorId,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'post.published';
    }

    public function toArray(): array
    {
        return [
            'post_id' => $this->postId->getValue(),
            'title' => $this->title->getValue(),
            'published_at' => $this->publishedAt->toIso8601(),
            'author_id' => $this->authorId->getValue(),
            'occurred_at' => $this->getOccurredAt()->toIso8601(),
        ];
    }
}
```

#### PostViewIncremented 事件

```php
<?php

declare(strict_types=1);

namespace App\Domains\Post\Events;

use App\Domains\Shared\Events\DomainEvent;
use App\Domains\Post\ValueObjects\{PostId, ViewCount};

/**
 * 文章瀏覽次數增加事件
 */
final readonly class PostViewIncremented extends DomainEvent
{
    public function __construct(
        public PostId $postId,
        public ViewCount $newViewCount,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'post.view_incremented';
    }

    public function toArray(): array
    {
        return [
            'post_id' => $this->postId->getValue(),
            'view_count' => $this->newViewCount->getValue(),
            'occurred_at' => $this->getOccurredAt()->toIso8601(),
        ];
    }
}
```

### 2.3 Auth 領域事件

#### UserRegistered 事件

```php
<?php

declare(strict_types=1);

namespace App\Domains\Auth\Events;

use App\Domains\Shared\Events\DomainEvent;
use App\Domains\Auth\ValueObjects\{UserId, Email, Username};
use App\Domains\Shared\ValueObjects\Timestamp;

/**
 * 使用者註冊事件
 */
final readonly class UserRegistered extends DomainEvent
{
    public function __construct(
        public UserId $userId,
        public Email $email,
        public Username $username,
        public Timestamp $registeredAt,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'user.registered';
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId->getValue(),
            'email' => $this->email->getValue(),
            'username' => $this->username->getValue(),
            'registered_at' => $this->registeredAt->toIso8601(),
            'occurred_at' => $this->getOccurredAt()->toIso8601(),
        ];
    }
}
```

### 2.4 事件處理器架構

#### 事件分發器介面

```php
<?php

declare(strict_types=1);

namespace App\Domains\Shared\EventDispatcher;

use App\Domains\Shared\Events\DomainEvent;

/**
 * 事件分發器介面
 */
interface EventDispatcherInterface
{
    /**
     * 分發單一事件
     */
    public function dispatch(DomainEvent $event): void;

    /**
     * 分發多個事件
     */
    public function dispatchAll(array $events): void;

    /**
     * 註冊事件監聽器
     */
    public function listen(string $eventName, callable $listener): void;
}
```

#### 範例事件監聽器

```php
<?php

declare(strict_types=1);

namespace App\Application\EventListeners;

use App\Domains\Post\Events\PostPublished;
use App\Domains\Statistics\Services\StatisticsServiceInterface;

/**
 * 文章發布後更新統計
 */
final readonly class UpdateStatisticsOnPostPublished
{
    public function __construct(
        private StatisticsServiceInterface $statisticsService,
    ) {}

    public function handle(PostPublished $event): void
    {
        // 更新文章總數統計
        $this->statisticsService->incrementPublishedPostCount();
        
        // 更新作者的文章數統計
        $this->statisticsService->incrementAuthorPostCount($event->authorId);
    }
}
```

---

## 🗺️ 第三部分：限界上下文與通信

### 3.1 限界上下文地圖

```
┌─────────────────────┐         ┌──────────────────────┐
│   Post Context      │◄────────│   Auth Context       │
│                     │         │                      │
│ - Post Aggregate    │         │ - User Aggregate     │
│ - Post Repository   │         │ - User Repository    │
│ - Post Events       │         │ - Auth Events        │
└──────────┬──────────┘         └───────────┬──────────┘
           │                                 │
           │    ┌────────────────────────┐  │
           └────►  Statistics Context    ◄──┘
                │                        │
                │ - Statistics Aggregate │
                │ - Statistics Repository│
                │ - Statistics Events    │
                └────────────────────────┘
```

### 3.2 上下文間通信協議

#### 共享內核 (Shared Kernel)

```php
// app/Domains/Shared/
├── ValueObjects/
│   ├── Email.php
│   ├── IPAddress.php
│   └── Timestamp.php
├── Events/
│   └── DomainEvent.php
└── Contracts/
    └── EventDispatcherInterface.php
```

#### Anti-Corruption Layer 範例

```php
<?php

declare(strict_types=1);

namespace App\Application\AntiCorruption;

use App\Domains\Post\ValueObjects\PostId;
use App\Domains\Auth\ValueObjects\UserId;
use App\Domains\Statistics\Services\StatisticsServiceInterface;

/**
 * Statistics Context 的防腐層
 * 
 * 保護 Statistics 上下文不受其他上下文變更的影響
 */
final readonly class StatisticsAntiCorruptionLayer
{
    public function __construct(
        private StatisticsServiceInterface $statisticsService,
    ) {}

    /**
     * 記錄文章瀏覽（從 Post Context）
     */
    public function recordPostView(PostId $postId, UserId $viewerId): void
    {
        // 轉換為 Statistics Context 的概念
        $this->statisticsService->recordView(
            entityId: $postId->getValue(),
            entityType: 'post',
            viewerId: $viewerId->getValue(),
        );
    }

    /**
     * 記錄使用者註冊（從 Auth Context）
     */
    public function recordUserRegistration(UserId $userId): void
    {
        $this->statisticsService->incrementUserCount();
    }
}
```

---

## 📊 第四部分：Repository 模式優化

### 4.1 Repository 設計原則

1. **只處理聚合根**：Repository 只負責持久化和檢索聚合根
2. **介面優先**：先定義介面，再實作
3. **查詢物件模式**：複雜查詢使用查詢物件
4. **事務邊界**：一個聚合根的變更在一個事務中完成

### 4.2 查詢物件範例

```php
<?php

declare(strict_types=1);

namespace App\Domains\Post\Queries;

use App\Domains\Post\Enums\PostStatus;
use App\Domains\Auth\ValueObjects\UserId;

/**
 * 文章查詢物件
 */
final readonly class PostQuery
{
    public function __construct(
        public ?PostStatus $status = null,
        public ?UserId $authorId = null,
        public ?bool $isPinned = null,
        public ?int $limit = null,
        public ?int $offset = null,
        public ?string $orderBy = null,
        public ?string $orderDirection = 'DESC',
    ) {}

    /**
     * 建立查詢所有已發布文章
     */
    public static function publishedPosts(): self
    {
        return new self(status: PostStatus::PUBLISHED);
    }

    /**
     * 建立查詢特定作者的文章
     */
    public static function byAuthor(UserId $authorId): self
    {
        return new self(authorId: $authorId);
    }

    /**
     * 建立查詢置頂文章
     */
    public static function pinnedPosts(): self
    {
        return new self(
            status: PostStatus::PUBLISHED,
            isPinned: true,
        );
    }
}
```

---

## ✅ 第五部分：實施建議

### 5.1 分階段實施計劃

#### 第一階段：基礎設施
- [ ] 建立 DomainEvent 基類
- [ ] 實作 EventDispatcher
- [ ] 建立測試環境

#### 第二階段：Post 聚合
- [ ] 重構 Post 為聚合根
- [ ] 實作 PostRepository
- [ ] 建立 Post 領域事件
- [ ] 撰寫單元測試

#### 第三階段：User 聚合
- [ ] 重構 User 為聚合根
- [ ] 實作 UserRepository
- [ ] 建立 Auth 領域事件
- [ ] 撰寫單元測試

#### 第四階段：統計聚合
- [ ] 實作 Statistics 聚合
- [ ] 建立 Anti-Corruption Layer
- [ ] 整合事件處理

### 5.2 注意事項

1. **漸進式重構**：不要一次性大規模重構
2. **保持向後相容**：確保現有功能不受影響
3. **完整測試覆蓋**：每個聚合根都要有完整的測試
4. **團隊共識**：重要決策需要團隊討論

### 5.3 成功指標

- [ ] 所有聚合根都有清楚的邊界
- [ ] 不變條件在程式碼中明確表達
- [ ] 領域事件正確發布和處理
- [ ] Repository 只處理聚合根
- [ ] 測試覆蓋率 > 80%

---

## 📚 參考資源

- [Domain-Driven Design by Eric Evans](https://www.domainlanguage.com/ddd/)
- [Implementing Domain-Driven Design by Vaughn Vernon](https://vaughnvernon.com/implementing-domain-driven-design/)
- [Domain-Driven Design Distilled by Vaughn Vernon](https://www.oreilly.com/library/view/domain-driven-design-distilled/9780134434964/)

---

**文件狀態**: 此文件為設計階段文件，需要經過團隊評審後才能開始實施。

**下一步**: 安排團隊會議討論此設計，收集反饋並調整設計方案。
