## Why

AlleyNote 的應用層與前端在預設情況下存在數個可大幅改善效能的瓶頸：
1. PHP-DI 容器與 FastRoute 路由於每個 HTTP 請求皆需重複掃描並動態構建，造成不必要的 I/O 與 CPU 反射開銷。
2. SQLite 資料庫缺少高頻文章列表查詢（`status`, `deleted_at`, `is_pinned`, `published_at`）的複合覆蓋索引，導致排序時額外使用臨時表。
3. 前端首頁同步載入 CKEditor 5 與 Chart.js 等數百 KB 之大型函式庫，拖慢首頁首次內容繪製（FCP）與最大內容繪製（LCP）。
4. Nginx 缺乏靜態與動態 JSON 的 Gzip 傳輸壓縮配置。

實施這些設定與架構調優可顯著降低 API 響應時間（TTFB）並加速前端頁面首屏載入速度。

## What Changes

- **PHP-DI 容器編譯快取**：在生產環境（或透過環境變數/CLI 命令）編譯 PHP-DI 容器與代理類別至 `storage/cache/container`，跳過執行期動態反射解析。
- **FastRoute 路由快取**：支援將 9 個路由定義檔案編譯輸出為單一快取陣列檔案，分派器改用快取分派。
- **SQLite 複合索引強化**：於 `init_db.php` 與資料庫遷移中新增 `idx_posts_feed`（`status, deleted_at, is_pinned DESC, published_at DESC`）與關聯複合索引。
- **前端重型套件按需延遲載入**：將 CKEditor 5 與 Chart.js 自 `index.html` 移除，改為在編輯器與統計圖表頁面初始化時非同步動態載入。
- **Nginx 傳輸壓縮**：於 Nginx 正式設定與前端設定中啟用 Gzip 壓縮（包含 JSON、JS、CSS、SVG）。

## Capabilities

### New Capabilities
- `framework-caching`: 支援 PHP-DI 容器與 FastRoute 路由於生產環境自動或命令式編譯快取機制。
- `database-indexing`: 建立前台高頻查詢之 SQLite 複合索引以加速列表過濾與排序。
- `asset-delivery-optimization`: 實作前端重型函式庫延遲載入與 Web 伺服器傳輸壓縮。

### Modified Capabilities

## Impact

- 後端：`backend/app/Application.php`、`backend/app/Infrastructure/Routing/`、`backend/init_db.php`、`backend/config/`
- 前端：`frontend/index.html`、`frontend/js/pages/admin/`、`frontend/js/utils/`
- 基礎設施：`docker/nginx/`、`docker/php/php.ini`
- 相容性：不影響任何現有 API 契約或資料結構。
