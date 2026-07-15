## 1. 準備工作

- [ ] 1.1 執行 `git status` 確認工作目錄乾淨
- [ ] 1.2 執行 `composer test` 確認基線測試全數通過（作為回歸基準）
- [ ] 1.3 盤點 `PostRepository` 所有公開方法，製作方法對應表（CRUD / Search / Analytics / Infrastructure）
- [ ] 1.4 確認 9 個測試檔案各自使用 PostRepository 的方式（直接實例化 vs 透過介面）
- [ ] 1.5 盤點 `PostRepository` 所有 `private` 輔助方法與常數，標註哪些被跨職責共用（如 `buildSelectQuery`、`preparePostData`、`POST_SELECT_FIELDS`、`CACHE_TTL`）

**驗收標準**：基線測試全數通過；方法對應表完成，每種職責對應的方法清單明確；輔助方法歸屬表完成。

## 2. 提取基礎設施關注點

- [ ] 2.1 在 `Domains/Post/Repositories/` 新增 `HasTransactionSupport.php` trait，封裝 `executeInTransaction()` 邏輯
  - 注意：trait 需存取 `PDO $db` 屬性，使用處必須具備此屬性
- [ ] 2.2 在 `Domains/Post/Repositories/` 新增 `PostBaseRepository.php` 抽象類別，提取跨職責共用的輔助元素：
  - `buildSelectQuery()`（原 `private` → `protected`）
  - `preparePostData()`（原 `private` → `protected`）
  - `addDeletedAtCondition()`（原 `private` → `private`，僅 `buildSelectQuery` 內部使用）
  - `POST_SELECT_FIELDS` 常數（原 `private` → `protected`）
  - `CACHE_TTL` 常數（原 `private` → `protected`）
  - 注入 `PDO $db` 與 `CacheServiceInterface $cache`
- [ ] 2.3 在 `Domains/Post/Services/` 新增 `PostCacheInvalidator.php`，包含：
  - `invalidatePost(Post $post): void`（接收 Post 模型，需檢查 `$post->getUserId()`）
  - `invalidateList(): void`
  - `invalidatePinned(): void`
  - `invalidateAnalytics(): void`
  - 注意：`CACHE_TTL` 常數不移至此服務（快取失效不需要 TTL 值）
- [ ] 2.4 執行 `composer test` 確認新建類別不破壞既有測試

**驗收標準**：`HasTransactionSupport` trait 可被任何 Repository use（需有 `$db` 屬性）；`PostBaseRepository` 包含所有跨職責共用輔助方法；`PostCacheInvalidator` 封裝所有快取失效方法且接收 Post 模型；測試仍通過。

## 3. 建立 PostCrudRepository

- [ ] 3.1 在 `Domains/Post/Repositories/` 新增 `PostCrudRepository.php`（`extends PostBaseRepository`）
- [ ] 3.2 從 `PostRepository` 搬移以下方法：
  - `find()`、`findByUuid()`、`findBySeqNumber()`、`findWithLock()`
  - `create()`、`update()`、`delete()`
  - `paginate()`、`getPinnedPosts()`、`getPostsByTag()`
  - `incrementViews()`、`getPostTags()`、`setPinned()`、`setTags()`
  - `safeDelete()`、`safeSetPinned()`
  - 輔助方法：`tagsExist()`、`assignTags()`、`updateTagsUsageCount()`
- [ ] 3.3 `PostCrudRepository` 複用 `HasTransactionSupport` trait
- [ ] 3.4 `PostCrudRepository` 委派快取失效給 `PostCacheInvalidator`（呼叫 `invalidatePost(Post $post)`）
- [ ] 3.5 保留 CRUD 專屬的 `private` 輔助方法（`prepareNewPostData`、`getNextSeqNumber`、`tagsExist`、`assignTags`、`updateTagsUsageCount`、`ALLOWED_UPDATE_FIELDS`、`ALLOWED_CONDITION_FIELDS`）
- [ ] 3.6 執行 `composer test` 確認無回歸

**驗收標準**：`PostCrudRepository` 約 500 行，涵蓋所有 CRUD 與關聯操作；交易支援透過 trait 取得；快取失效透過 `PostCacheInvalidator`。

## 4. 建立 PostSearchRepository

- [ ] 4.1 在 `Domains/Post/Repositories/` 新增 `PostSearchRepository.php`（`extends PostBaseRepository`）
- [ ] 4.2 從 `PostRepository` 搬移以下方法：
  - `searchByTitle(string $title): mixed`
  - `search(string $keyword): mixed`
  - `findLatestByUserId(int $userId): ?Post`
- [ ] 4.3 執行 `composer test` 確認無回歸

**驗收標準**：`PostSearchRepository` 約 50 行，僅含搜尋相關方法；測試通過。

## 5. 建立 PostAnalyticsRepository

- [ ] 5.1 在 `Domains/Post/Repositories/` 新增 `PostAnalyticsRepository.php`（`extends PostBaseRepository`）
- [ ] 5.2 從 `PostRepository` 搬移以下方法：
  - `findByCreationSource()`、`getSourceDistribution()`
  - `findByCreationSourceAndDetail()`、`countByCreationSource()`
  - `paginateByCreationSource()`
- [ ] 5.3 `PostAnalyticsRepository` 委派快取失效給 `PostCacheInvalidator`
- [ ] 5.4 執行 `composer test` 確認無回歸

**驗收標準**：`PostAnalyticsRepository` 約 160 行，僅含來源分析查詢；快取失效透過 `PostCacheInvalidator`；測試通過。

## 6. 瘦身 PostRepository 為 Facade

- [ ] 6.1 `PostRepository` 注入三個具體 Repository 與 `PostCacheInvalidator`
- [ ] 6.2 `PostRepository` 的所有公開方法改為委派呼叫（一行委派）
- [ ] 6.3 保留 `PostRepositoryInterface` 的實作（`implements PostRepositoryInterface`）
- [ ] 6.4 移除除委派外的所有商業邏輯
- [ ] 6.5 執行 `composer test` 確認無回歸

**驗收標準**：`PostRepository` 降至 ~60 行以內；每個公開方法只做委派；`PostRepositoryInterface` 實作不變。

## 7. 補齊 PostRepositoryInterface 缺少的方法宣告

- [ ] 7.1 在 `PostRepositoryInterface` 新增：
  - `public function searchByTitle(string $title): mixed;`
  - `public function search(string $keyword): mixed;`
  - `public function findLatestByUserId(int $userId): ?Post;`
- [ ] 7.2 確認 Facade 實作這三個方法
- [ ] 7.3 執行 `composer test` 確認無回歸

**驗收標準**：`PostRepositoryInterface` 宣告 24 個方法（18 直接宣告 + 6 自 `RepositoryInterface` 繼承）；所有 Consumer 可安全呼叫這三個方法；型別系統契約完整。

## 8. 更新 DI 容器

- [ ] 8.1 在 `config/container.php` 新增以下註冊：
  - `PostCrudRepository`（autowire）
  - `PostSearchRepository`（autowire）
  - `PostAnalyticsRepository`（autowire）
  - `PostCacheInvalidator`（autowire）
- [ ] 8.2 更新 `PostRepository` 的綁定，注入三個 Repository 與 `PostCacheInvalidator`
- [ ] 8.3 確認 `PostRepositoryInterface::class` 仍指向 `PostRepository`
- [ ] 8.4 更新 `AttachmentService` 的 DI 綁定：`PostRepository` → `PostRepositoryInterface`
- [ ] 8.5 執行 `composer test` 確認無回歸

**驗收標準**：DI 容器可正確解析所有新類別；「`PostRepositoryInterface::class` → `PostRepository`」綁定不變。

## 9. 更新測試檔案

- [ ] 9.1 逐一檢查 9 個直接使用 `PostRepository` 的測試檔案：
  - `tests/Unit/Repository/PostRepositoryTest.php` — `new PostRepository(...)`
  - `tests/Unit/Repository/PostRepositoryPerformanceTest.php` — `new PostRepository(...)`
  - `tests/Integration/Repositories/PostRepositoryTest.php` — `new PostRepository(...)`
  - `tests/Integration/AttachmentUploadTest.php` — `new PostRepository(...)`
  - `tests/Integration/Security/SqlInjectionTest.php` — `new PostRepository(...)`
  - `tests/Security/SqlInjectionTest.php` — `new PostRepository(...)`
  - `tests/Unit/Services/AttachmentServiceTest.php` — `Mockery::mock(PostRepository::class)`
  - `tests/Security/FileUploadSecurityTest.php` — `Mockery::mock(PostRepository::class)`
  - `tests/Performance/ApiPerformanceTest.php` — `$container->get(PostRepository::class)`
- [ ] 9.2 依使用方式分三類處理：
  - 透過 `PostRepositoryInterface` 注入 → 無需修改
  - 直接實例化 `PostRepository` → 改為 mock `PostRepositoryInterface`
  - 測試特定方法（如 analytics 或 search）→ 考慮改用對應的具體 Repository
- [ ] 9.3 更新 `PostRepositoryPerformanceTest` 的 setUp 與依賴注入
- [ ] 9.4 更新 `AttachmentServiceTest`：mock 型別從 `PostRepository::class` 改為 `PostRepositoryInterface::class`
- [ ] 9.5 更新 `FileUploadSecurityTest`：mock 型別從 `PostRepository::class` 改為 `PostRepositoryInterface::class`
- [ ] 9.6 執行 `composer test` 確認所有測試通過

**驗收標準**：9 個測試檔案皆可正確編譯與執行；`rg 'new PostRepository' backend/tests` 及 `rg 'Mockery::mock.*PostRepository::class' backend/tests` 回傳空結果。

## 10. 收尾與驗證

- [ ] 10.1 執行 `composer test` 確認完整測試套件無回歸
- [ ] 10.2 執行 `composer analyse`（PHPStan Level 10）確認無新增型別錯誤
- [ ] 10.3 執行 `composer cs-fix` 確認程式碼風格一致
- [ ] 10.4 驗證 DI 容器可正確解析所有新類別：執行 `tests/Integration/DIValidationIntegrationTest.php`（若有）或手動確認
- [ ] 10.5 依任務群組提交 commit（使用 `refactor:` prefix）
- [ ] 10.6 開立 PR，描述拆分策略、各檔案職責說明與測試結果

**驗收標準**：全數測試通過；PHPStan 無新增錯誤；每個任務群組有獨立 commit；PR 已開立且文件完整。
