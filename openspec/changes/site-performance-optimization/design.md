## Context

當前 AlleyNote 在 PHP 8.4 環境下採用 PHP-DI 與 FastRoute，但兩者於每次請求時皆進行動態建構；資料庫缺少覆蓋性複合索引；前端首頁同步載入後台重型套件（CKEditor 與 Chart.js）；Nginx 未開啟 gzip 壓縮。

## Goals / Non-Goals

**Goals:**
- 提供 PHP-DI 容器編譯快取能力，大幅減少反射開銷。
- 提供 FastRoute 路由清單快取能力，減少路由載入與正規表示法編譯時間。
- 在 SQLite 資料庫建立高頻列表查詢之複合覆蓋索引（`idx_posts_feed`）。
- 實作前端動態腳本載入器（`script-loader.js`），將 CKEditor 5 與 Chart.js 改為按需載入。
- 在 Nginx 設定中啟用 Gzip 傳輸壓縮。
- 撰寫效能基準測試，驗證優化前後的效能提升幅度。

**Non-Goals:**
- 改寫前後端架構或更換現有函式庫。
- 引入複雜的 Node.js 前端打包構建工具（維持純 ES6 模組架構）。

## Decisions

### 1. PHP-DI 容器編譯
- **決策**：在 `Application::initializeContainer()` 中，當 `APP_ENV === 'production'` 或設定開啟編譯時，啟用 `$builder->enableCompilation($cacheDir)` 與 `$builder->writeProxiesToFile(true, $proxyDir)`。
- **替代方案**：使用 OPcache 預載入（Preloading）——需要 root 權限配置 php.ini，容器編譯更具彈性且易於自動化管理。

### 2. FastRoute 路由快取
- **決策**：於 `RouteLoader` / `RoutingServiceProvider` 提供快取檢驗與持久化，當快取檔案存在時直接載入編譯後的路由分派資料。
- **替代方案**：每次請求都 require 9 個路由檔案——造成重複磁碟 I/O 與陣列合併。

### 3. 前端依賴按需非同步載入
- **決策**：實作輕量 Promise-based `loadScript()` / `loadStylesheet()` 工具函式，在使用者瀏覽至文章編輯頁面時動態載入 CKEditor，進入統計頁面時動態載入 Chart.js。
- **替代方案**：在 SPA 初始化時預載入——仍會佔用初始頻寬與記憶體。

### 4. Nginx Gzip 壓縮
- **決策**：在 Nginx 配置檔中開啟 `gzip on`，並針對 `application/json`、`application/javascript`、`text/css`、`text/plain`、`image/svg+xml` 等 MIME types 啟用 compression level 6。

## Risks / Trade-offs

- [開發與測試環境快取干擾] → 開發與測試環境（`APP_ENV=testing` / `development`）預設不啟用容器快取，或提供清除快取機制。
- [動態載入腳本網路延遲] → 在按需載入時加入 Loading 狀態提示，並利用 Promise 快取確保同一腳本僅載入一次。
