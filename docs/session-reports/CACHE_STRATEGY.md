# AlleyNote 快取策略文件

## 概述

本專案採用分層快取策略，區分管理員後台和前台使用者的資料存取方式。

---

## 快取策略原則

### 1. 管理員後台（Admin Panel）

**路徑**：`/admin/*`  
**控制器**：`App\Application\Controllers\PostController`

**策略**：**不使用快取，直接存取資料庫**

**理由**：
- 管理員需要看到最新、最準確的資料
- 管理員操作頻率低，效能影響小
- 避免快取不一致問題

**實作方式**：
```php
// 直接使用 PDO 連接資料庫
$dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
$pdo = new PDO("sqlite:{$dbPath}");
```

**適用操作**：
- ✅ 列表查詢（`GET /api/posts`）- 直接從資料庫讀取
- ✅ 單篇文章（`GET /api/posts/{id}`）- 直接從資料庫讀取
- ✅ 新增文章（`POST /api/posts`）- 直接寫入資料庫
- ✅ 更新文章（`PUT /api/posts/{id}`）- 直接寫入資料庫
- ✅ 刪除文章（`DELETE /api/posts/{id}`）- 直接從資料庫刪除

---

### 2. 前台使用者（Public Frontend）

**路徑**：`/api/v1/*`  
**控制器**：`App\Application\Controllers\Api\V1\PostController`  
**服務層**：`App\Domains\Post\Services\PostService`  
**資料層**：`App\Domains\Post\Repositories\PostRepository`

**策略**：**讀取使用快取，寫入清除快取**

**理由**：
- 前台訪問量大，快取可顯著提升效能
- 使用者對資料即時性要求較低
- 減少資料庫負載

**實作方式**：

#### 讀取操作（使用快取）
```php
public function find(int $id): ?Post
{
    $cacheKey = PostCacheKeyService::post($id);
    
    return $this->cache->remember($cacheKey, function () use ($id) {
        // 從資料庫查詢
        return $this->queryDatabase($id);
    });
}
```

#### 寫入操作（清除快取）
```php
public function create(array $data): Post
{
    return $this->executeInTransaction(function () use ($data) {
        // 1. 寫入資料庫
        $post = $this->insertToDatabase($data);
        
        // 2. 清除相關快取
        $this->cache->delete(PostCacheKeyService::post($post->getId()));
        $this->cache->deletePattern(PostCacheKeyService::postsListPattern());
        
        return $post;
    });
}
```

---

## 寫入操作的快取清除

### 新增文章（Create）

**直接寫入資料庫**：✅  
**清除的快取**：
- 文章列表快取（所有分頁）
- 首頁置頂文章快取
- 使用者文章列表快取（如果有）

**實作位置**：
- `PostRepository::create()`

### 更新文章（Update）

**直接寫入資料庫**：✅  
**清除的快取**：
- 該文章的快取
- 該文章 UUID 的快取
- 文章標籤關聯快取
- 文章列表快取（可能影響排序）
- 置頂文章快取（如果修改了置頂狀態）

**實作位置**：
- `PostRepository::update()`

### 刪除文章（Delete）

**直接從資料庫刪除**：✅  
**清除的快取**：
- 該文章的所有快取
- 文章列表快取
- 相關標籤的文章列表快取

**實作位置**：
- `PostRepository::delete()`

---

## 快取鍵命名規範

**服務類別**：`App\Domains\Post\Services\PostCacheKeyService`

**快取鍵格式**：
```
post:{id}                          # 單篇文章
post:uuid:{uuid}                   # 依 UUID 查詢
post:{id}:tags                     # 文章標籤
post:{id}:views                    # 文章瀏覽數
posts:list:*                       # 文章列表（支援萬用字元刪除）
posts:pinned                       # 置頂文章
user:{user_id}:posts:*            # 使用者文章列表
```

---

## 資料庫連接方式對照

### 管理員後台（PostController）

```php
// 簡化版本，直接連接
$pdo = new PDO("sqlite:{$dbPath}");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

**特點**：
- 不通過服務層
- 不使用 Repository 模式
- 簡單直接，效能最佳
- 適合內部工具

### 前台 API（ApiPostController）

```php
// 使用依賴注入
public function __construct(
    private readonly PostServiceInterface $postService,
    private readonly ValidatorInterface $validator,
    private readonly OutputSanitizerInterface $sanitizer,
) {}

// 透過服務層操作
$result = $this->postService->listPosts($page, $limit, $filters);
```

**特點**：
- 符合 DDD 架構
- 使用 Repository + Service 層
- 自動處理快取
- 適合對外 API

---

## 快取清除策略

### 自動清除（推薦）

**實作位置**：`PostRepository` 的 `create()`, `update()`, `delete()` 方法

```php
// 新增後自動清除
$this->cache->deletePattern(PostCacheKeyService::postsListPattern());

// 更新後自動清除
$this->cache->delete(PostCacheKeyService::post($post->getId()));
```

### 手動清除（緊急情況）

```bash
# 清除所有文章相關快取
docker compose exec web php artisan cache:clear

# 或直接刪除快取檔案
docker compose exec web rm -rf /var/www/html/storage/cache/posts/*
```

---

## 效能考量

### 快取命中率

**目標**：
- 前台文章查詢：> 90% 命中率
- 文章列表查詢：> 85% 命中率

### 快取失效時機

1. **立即失效**：
   - 文章被新增、修改、刪除時
   - 文章狀態變更時（草稿 ↔ 已發布）

2. **定時失效**：
   - 文章列表快取：1 小時
   - 單篇文章快取：6 小時
   - 置頂文章快取：30 分鐘

### 快取大小控制

- 單個快取項目不超過 1MB
- 總快取大小不超過 100MB
- 使用 LRU 策略淘汰舊快取

---

## 測試驗證

### 驗證管理員後台不使用快取

```bash
# 1. 新增一篇文章
curl -X POST http://localhost:8080/api/posts \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"測試文章","content":"測試內容","status":"published"}'

# 2. 立即查詢列表（應該看到新文章）
curl http://localhost:8080/api/posts \
  -H "Authorization: Bearer $TOKEN"
```

### 驗證前台使用快取

```bash
# 1. 查詢文章（第一次，建立快取）
time curl http://localhost:8080/api/v1/posts/1

# 2. 再次查詢（第二次，使用快取，應該更快）
time curl http://localhost:8080/api/v1/posts/1
```

---

## 開發建議

### 開發環境

建議在開發環境中**禁用快取**，以避免資料不一致問題：

```env
# .env
CACHE_ENABLED=false
```

### 生產環境

生產環境必須**啟用快取**：

```env
# .env
CACHE_ENABLED=true
CACHE_DRIVER=redis  # 或 memcached
```

---

## 常見問題

### Q1：為什麼管理員看不到最新資料？

**A**：檢查是否誤用了帶快取的 API 端點。管理員應使用 `/api/posts`，而非 `/api/v1/posts`。

### Q2：如何強制刷新快取？

**A**：重啟 web 容器或手動清除快取目錄。

### Q3：為什麼新增文章後前台還是看不到？

**A**：檢查文章狀態是否為 `published`，以及 `publish_date` 是否在當前時間之前。

---

## 總結

| 功能 | 管理員後台 | 前台 API |
|------|-----------|---------|
| **讀取操作** | 直接查詢資料庫 | 使用快取 |
| **新增操作** | 直接寫入資料庫 | 直接寫入 + 清除快取 |
| **更新操作** | 直接寫入資料庫 | 直接寫入 + 清除快取 |
| **刪除操作** | 直接刪除 | 直接刪除 + 清除快取 |
| **控制器** | `PostController` | `ApiPostController` |
| **架構** | 簡化 PDO | DDD + Repository |

---

**最後更新**：2025-10-07  
**維護者**：開發團隊
