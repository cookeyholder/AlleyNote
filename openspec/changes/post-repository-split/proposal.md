## Why

`PostRepository`（972 行、24 個公開方法）是典型的「胖儲存庫」，混合了四種不同職責的邏輯：

1. **CRUD 操作**（find, create, update, delete, paginate, tags, views, pinned） → ~500 行
2. **搜尋查詢**（searchByTitle, search） → ~50 行
3. **來源分析查詢**（findByCreationSource, getSourceDistribution, findByCreationSourceAndDetail, countByCreationSource, paginateByCreationSource） → ~160 行
4. **快取失效與交易管理**（invalidateCache, executeInTransaction）貫穿各處

單一類別同時處理四種職責，造成以下問題：
- **難以測試**：測試 analytics 邏輯時必須 mock 整個 CRUD 介面，反之亦然
- **修改風險高**：修改快取策略可能意外影響 CRUD 行為
- **違反單一職責原則**：Repository 同時是資料存取層、查詢引擎、快取管理器、交易控制器
- **介面肥胖**：`PostRepositoryInterface` 有 21 個方法（15 個直接宣告 + 6 個自 `RepositoryInterface` 繼承），實作方被迫實作用不到的方法

此外，`PostRepositoryInterface` 缺少 3 個已在實作中存在的方法（`searchByTitle`、`search`、`findLatestByUserId`），導致介面與實作間的契約不一致。

## What Changes

- 將 `PostRepository` 拆分為三個職責明確的儲存庫類別
- 提取快取失效邏輯為獨立服務
- 提取交易管理為可複用的基底特徵（trait）
- 保留 `PostRepositoryInterface` 作為 Facade，委派給三個實作
- 補齊介面中缺少的方法宣告
- 更新 DI 容器綁定與測試檔案

## Capabilities

### New Capabilities

- `PostCrudRepository`：處理標準 CRUD、標籤管理、觀看次數、置頂操作。預計 ~500 行
- `PostSearchRepository`：處理標題與全文搜尋。預計 ~50 行
- `PostAnalyticsRepository`：處理來源分析查詢。預計 ~160 行
- `PostCacheInvalidator`：封裝所有 Post Domain 的快取失效邏輯，可供 Service 層直接呼叫（方法接收 `Post` 模型而非僅 `int $postId`，因失效時需讀取 `$post->getUserId()`）
- `PostBaseRepository` 抽象類別：提取 CRUD、Search、Analytics 三儲存庫共用的 `private` 輔助方法（`buildSelectQuery`、`preparePostData`、`addDeletedAtCondition`、`POST_SELECT_FIELDS`、`CACHE_TTL`）
- `HasTransactionSupport` trait：封裝 `executeInTransaction()` 邏輯，供需要交易支援的 Repository 複用（使用處需具備 `PDO $db` 屬性）

### Modified Capabilities

- `PostRepository`：改為 Facade 委派類別，介面保持不變
- `PostRepositoryInterface`：新增 `searchByTitle()`、`search()`、`findLatestByUserId()` 宣告
- `PostService`：建構子保持接收 `PostRepositoryInterface`，不受拆分影響
- `AttachmentService`：注入型別從 `PostRepository` 改為 `PostRepositoryInterface`，建構子參數名稱與 DI 綁定同步更新

## Impact

- **修改**：`backend/app/Domains/Post/Repositories/PostRepository.php`、`backend/app/Domains/Post/Contracts/PostRepositoryInterface.php`、`backend/config/container.php`
- **新增**：`PostCrudRepository.php`、`PostSearchRepository.php`、`PostAnalyticsRepository.php`、`PostCacheInvalidator.php`、`PostBaseRepository.php`（抽象基底）、`HasTransactionSupport.php`
- **測試**：9 個測試檔案需同步更新（含 Unit、Integration、Security、Performance）；另有 `AttachmentService` 使用 `PostRepository` 直接呼叫，需同步變更
- **無 API 破壞性變更**：不改變對外 API 端點或 DTO 結構
- **依賴**：不引入新套件
