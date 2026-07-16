## Context

AlleyNote 後端採用 DDD 架構，`Domains/Post/` 包含公告/文章相關的業務邏輯。`PostRepository` 是 Post Domain 的主要資料存取層，隨著功能累積已成長為 972 行、24 個公開方法的巨型類別。

程式碼審查識別出四種混雜的職責：

1. **CRUD 操作**：建立、讀取、更新、刪除文章，以及標籤管理、觀看次數、置頂操作
2. **搜尋查詢**：`searchByTitle()`、`search()` 兩個搜尋方法
3. **來源分析查詢**：5 個以 `creation_source` 為核心的分析方法
4. **基礎設施關注點**：快取失效（`invalidateCache`）、交易包裹（`executeInTransaction`）

此外，`PostRepositoryInterface` 宣告了 21 個方法（15 個直接宣告 + 6 個自 `RepositoryInterface` 繼承），但實作中有 3 個方法未在介面中定義（`searchByTitle`、`search`、`findLatestByUserId`），導致型別系統無法保證契約一致性。

## Goals / Non-Goals

**Goals:**
- 將 `PostRepository` 按職責拆分為三個專注的儲存庫
- 提取快取失效邏輯為獨立服務（`PostCacheInvalidator`）
- 提取交易包裹為可複用 trait（`HasTransactionSupport`）
- 補齊 `PostRepositoryInterface` 中缺少的方法宣告
- 更新 DI 容器綁定，確保現有依賴不受影響
- 所有現有測試通過（無回歸）

**Non-Goals:**
- 更改任何公開 API 端點的行為或 DTO 結構
- 引入新的外部套件
- 重構 Post Model、PostService 或 TagManagementService 的業務邏輯
- 新增或修改前端程式碼
- 變更資料庫 Migration 或 Schema
- 變更快取金鑰產生邏輯（`PostCacheKeyService`）

## Decisions

### D1：保留 `PostRepositoryInterface` 作為 Facade

**決策**：不修改現有介面契約。`PostRepositoryInterface` 保持 21+ 方法，由 Facade 類別（瘦身後的 `PostRepository`）實作，內部委派給三個具體儲存庫。

**備選方案 A**：建立三個獨立介面（`PostCrudRepositoryInterface`、`PostSearchRepositoryInterface`、`PostAnalyticsRepositoryInterface`），各司其職。  
→ 這是更乾淨的設計，但變動範圍過大：所有依賴 `PostRepositoryInterface` 的 Consumer（PostService、TagManagementService、Middleware、Controller、測試等）都需更新。

**備選方案 B**：保持單一介面，讓 Consumer 自行注入多個 Repository。  
→ Consumer 需知道哪些查詢該用哪個 Repository，增加耦合度。

**理由**：Facade 模式在過渡階段最務實。Consumer 無需知道內部拆分，DI 容器也只需保留單一綁定。若未來有需求，可逐步將 Consumer 改為注入更小的介面。

### D2：按「查詢意圖」而非「資料表」拆分

**決策**：三個儲存庫按查詢意圖（query intent）而非依資料表劃分：

- `PostCrudRepository`：find / create / update / delete / paginate / tags / views / pinned
- `PostSearchRepository`：searchByTitle / search / findLatestByUserId
- `PostAnalyticsRepository`：findByCreationSource / getSourceDistribution / findByCreationSourceAndDetail / countByCreationSource / paginateByCreationSource

**備選方案**：按資料表拆分（一個 `posts` 表一個 Repository）→ 所有查詢都集中在同一類別，無法達到職責分離的目的。

**理由**：查詢意圖拆分最能反映變動原因。分析查詢的修改頻率與 CRUD 不同，且通常伴隨不同的快取策略與 SQL 複雜度。

### D3：快取失效提取為獨立服務

**決策**：新增 `PostCacheInvalidator`，封裝所有 Post Domain 的快取鍵失效邏輯。`PostCrudRepository` 和 `PostAnalyticsRepository` 不再直接操作快取失效，而是委派給此服務。

**理由**：
- 快取失效策略可能因業務需求而變化（如批次失效、延遲失效），獨立服務讓策略調整不影響資料存取邏輯
- 快取鍵產生邏輯集中管理
- 測試時可 mock `PostCacheInvalidator` 驗證失效行為

**實作細節**：
```php
class PostCacheInvalidator {
    public function __construct(
        private CacheServiceInterface $cache,
        private PostCacheKeyService $keyService,
    ) {}
    
    // 接收 Post 模型而非僅 postId，因 invalidateCache 原實作需檢查 $post->getUserId()
    public function invalidatePost(Post $post): void { ... }
    public function invalidateList(): void { ... }
    public function invalidatePinned(): void { ... }
    public function invalidateAnalytics(): void { ... }
}
```

**注意 — `CACHE_TTL` 歸屬**：`CACHE_TTL`（3600 秒）原本用於 `$this->cache->remember()` 的 TTL 參數，遍佈 CRUD 與 Analytics 的查詢方法。失效服務 `PostCacheInvalidator` 只需要刪除快取，不需要 TTL 值。因此 `CACHE_TTL` 應保留在各 Repository（或移至 `PostBaseRepository` 抽象類別），而非遷移至 `PostCacheInvalidator`。

### D4：交易包裹提取為 `HasTransactionSupport` trait

**決策**：新增 `HasTransactionSupport` trait，封裝 `executeInTransaction()` 邏輯，供需要交易支援的 Repository 複用。

**理由**：
- `safeDelete`、`safeSetPinned`、`create` 等 CRUD 方法依賴交易包裹
- 提取為 trait 避免重複實作，且單元測試可專注測試交易行為
- `PostCrudRepository` 與其 Facade 都可複用此 trait

**注意**：此 trait 需存取 `PDO $db` 屬性。使用此 trait 的類別必須具備型別為 `PDO` 的 `$db` 屬性（或定義對應的 abstract getter）。所有 PostgreSQL Repository 皆已具備此屬性，無需額外修改。

### D5：共享輔助方法提取為抽象基底類別

**決策**：建立 `PostBaseRepository` 抽象類別，包含三個具體 Repository 共用的輔助方法。

**背景**：`PostRepository` 有 11 個 `private` 輔助元素，分散在三種職責的查詢中被使用：

| 輔助元素 | 被誰使用 | 拆後歸屬 |
|----------|----------|----------|
| `buildSelectQuery()` | find、paginate、search、analytics 全部 | `PostBaseRepository`（protected） |
| `addDeletedAtCondition()` | 僅 `buildSelectQuery` 內部 | `PostBaseRepository`（private） |
| `preparePostData()` | find、paginate、search、analytics 全部 | `PostBaseRepository`（protected） |
| `POST_SELECT_FIELDS` | `searchByTitle` | `PostBaseRepository`（protected） |
| `ALLOWED_UPDATE_FIELDS` | `update` 方法 | 保留在 `PostCrudRepository` |
| `ALLOWED_CONDITION_FIELDS` | `paginate` 方法 | 保留在 `PostCrudRepository` |
| `CACHE_TTL` | CRUD + Analytics 的 `cache->remember()` | `PostBaseRepository`（protected） |
| `SQL_INSERT_POST` / `SQL_INSERT_TAG` | `create` + `assignTags` | 保留在 `PostCrudRepository` |
| `executeInTransaction()` | `safeDelete` / `safeSetPinned` / `create` | `HasTransactionSupport` trait |
| `invalidateCache()` | update / delete / setPinned / setTags | `PostCacheInvalidator` 服務 |
| `prepareNewPostData()` / `getNextSeqNumber()` | `create` | 保留在 `PostCrudRepository` |
| `tagsExist()` / `assignTags()` / `updateTagsUsageCount()` | `create` / `setTags` | 保留在 `PostCrudRepository` |

**實作**：
```php
abstract class PostBaseRepository {
    protected PDO $db;
    protected CacheServiceInterface $cache;
    protected const CACHE_TTL = 3600;
    protected const POST_SELECT_FIELDS = '...';
    protected function buildSelectQuery(...): string { ... }
    protected function preparePostData(array $result): mixed { ... }
    private function addDeletedAtCondition(...): string { ... }
}
```

三個具體 Repository 皆 `extends PostBaseRepository`。

### D6：`PostRepositoryInterface` 補齊缺少的方法宣告

**決策**：在 `PostRepositoryInterface` 中新增以下方法宣告：
- `searchByTitle(string $title): mixed`
- `search(string $keyword): mixed`
- `findLatestByUserId(int $userId): ?Post`

**理由**：這三個方法已存在於實作中但未在介面宣告，造成型別系統無法保證 Consumer 可安全呼叫這些方法。補齊宣告修復介面契約完整性。

## Risks / Trade-offs

- **[風險] 測試檔案直接使用 `PostRepository` 類別而非介面** → 9 個測試檔案需更新 import 與 mock 策略。緩和：逐一檢查，優先改為 mock `PostRepositoryInterface`
- **[風險] Facade 委派增加一層 method call** → 對 CRUD 操作無顯著效能影響。若極端要求效能，可繞過 Facade 直接注入具體實作
- **[風險] 拆分後快取失效可能不一致** → `PostCacheInvalidator` 集中管理失效邏輯，降低遺漏風險
- **[風險] `private` 輔助提取為 `protected` 或共用** → 11 個 `private` 輔助方法/常數需調整可見性。緩和：設計 `PostBaseRepository` 抽象類別，將跨職責共用者提取為 `protected`
- **[風險] `AttachmentService` 直接依賴 `PostRepository` 類別** → 拆分後 `PostRepository` 建構子改變，`AttachmentService` 會中斷。緩和：改注入 `PostRepositoryInterface`
- **[取捨] 保留單一介面使介面依然肥大** → 已納入 Non-Goals。可於未來迭代逐步收斂
- **[風險] 此變更與其他在進行中的重構分支衝突** → 在獨立 worktree 進行，完成後立即發 PR

## Migration Plan

1. 建立 `HasTransactionSupport` trait，包含 `executeInTransaction()`（注意：trait 需存取 `$db` 屬性）
2. 建立 `PostBaseRepository` 抽象類別，提取共用輔助方法（`buildSelectQuery`、`preparePostData`、`addDeletedAtCondition`、`POST_SELECT_FIELDS`、`CACHE_TTL`）
3. 建立 `PostCacheInvalidator` 服務，封裝快取失效邏輯（方法接收 `Post` 模型而非僅 `int $postId`）
4. 從 `PostRepository` 提取 CRUD 邏輯至 `PostCrudRepository`（`extends PostBaseRepository`、`use HasTransactionSupport`）
5. 從 `PostRepository` 提取搜尋邏輯至 `PostSearchRepository`（`extends PostBaseRepository`）
6. 從 `PostRepository` 提取分析邏輯至 `PostAnalyticsRepository`（`extends PostBaseRepository`）
7. 瘦身 `PostRepository` 為 Facade，委派給三個具體實作
8. 補齊 `PostRepositoryInterface` 缺少的 3 個方法宣告
9. 更新 `config/container.php` DI 綁定
10. 更新 `AttachmentService` 注入型別從 `PostRepository` 改為 `PostRepositoryInterface`
11. 更新 9 個測試檔案（直接 `new PostRepository` 或 mock `PostRepository::class` 者）
12. 執行 `composer test`、`composer analyse`、`composer cs-fix` 確認無回歸

**Rollback**：此變更不改動對外行為，若有問題可直接 revert 整個 commit。`PostRepository.php` 原始內容可從 git history 恢復。

## Open Questions

- `PostService` 目前注入 `PostRepositoryInterface`，拆分後應注入 Facade 還是直接注入三個具體介面？（設計決策 D1 選擇 Facade，答案已定）
- `AdminPostReadRepository` 是否也應納入此拆分範圍（它有獨立的查詢邏輯）？
