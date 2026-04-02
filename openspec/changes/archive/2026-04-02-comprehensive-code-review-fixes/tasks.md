## 開發流程規範

> **所有任務嚴格遵循 TDD（Red → Green → Refactor）與高頻提交策略。**
>
> 每個任務的執行順序：
>
> 1. **先寫測試（Red）**：撰寫會失敗的測試案例
> 2. **實作通過（Green）**：實作最小可行程式碼使測試通過
> 3. **重構最佳化（Refactor）**：在測試保護下最佳化程式碼
> 4. **高頻提交**：每個步驟獨立提交，提交訊息遵循 `type(scope): description` 格式
> 5. **驗證全綠**：提交前確認所有測試、PHPStan、CS Fixer 通過

---

## 第一階段：P0 致命等級修復（資安與正式環境阻斷問題）

### 任務 1.1：修復 RichTextProcessorService 以實際呼叫 HTMLPurifier

- [ ] 更新 `processContent()`，依據使用者等級呼叫對應的淨化器
- [ ] 修正 `getCachePath()` 使用可設定路徑（`storage/cache/htmlpurifier`）
- [ ] 移除 TODO 註解並實作實際淨化邏輯
- [ ] 為三種淨化等級（basic / extended / admin）新增單元測試
- [ ] 驗證 HTMLPurifier 設定已禁止 script、iframe、object、embed 標籤
- **驗收標準**：格式化文字內容被正確淨化；XSS 攻擊載荷被移除；測試通過

### 任務 1.2：修復或移除 ContentModerationService

- [ ] 取消註解並修復所有審核邏輯
- [ ] 修正註解程式碼中的語法錯誤
- [ ] 實作敏感字詞偵測
- [ ] 實作 XSS 特徵偵測
- [ ] 實作垃圾內容評分計算
- [ ] 為審核結果新增單元測試
- **驗收標準**：`moderateContent()` 依據內容回傳準確結果；惡意內容被標記；測試通過

### 任務 1.3：移除 PostController 直接 PDO 程式碼路徑

- [ ] 確認所有 PostController 路由已被 `Api/V1/PostController` 涵蓋
- [ ] 將遺漏功能遷移至領域服務層
- [ ] 從 `routes.php` 移除舊路由
- [ ] 刪除 `PostController.php` 檔案
- [ ] 更新參考 PostController 的測試
- **驗收標準**：控制器中無直接 PDO 查詢；所有文章操作經過領域層；測試通過

### 任務 1.4：移除 JWT Token 查詢字串支援

- [ ] 更新 `JwtAuthenticationMiddleware::extractToken()` 僅檢查 Authorization Header
- [ ] 移除查詢字串 Token 提取程式碼
- [ ] 更新相關測試
- **驗收標準**：JWT Token 僅接受 Bearer Header；查詢字串 Token 被拒絕；測試通過

### 任務 1.5：修復前端 Math.random() 密碼產生

- [ ] 在 `passwordGenerator.js` 中以 `crypto.getRandomValues()` 取代 `Math.random()`
- [ ] 更新 `secureRandom()` 輔助函式
- [ ] 新增密碼產生隨機性測試
- **驗收標準**：密碼使用密碼學安全的隨機來源產生

### 任務 1.6：使 display_errors 依賴環境

- [ ] 更新 `public/index.php` 讀取 `APP_DEBUG` 環境變數
- [ ] 依據除錯旗標設定 `display_errors`
- [ ] 驗證正式環境關閉錯誤顯示
- **驗收標準**：錯誤顯示由環境變數控制；正式環境不顯示錯誤

### 任務 1.7：修復所有前端 innerHTML XSS 漏洞

- [ ] 在所有使用者資料的模板字串中加入 `escapeHtml()`：
  - `pages/public/post.js`
  - `pages/admin/posts.js`
  - `pages/admin/dashboard.js`
  - `pages/admin/statistics.js`
  - `layouts/DashboardLayout.js`
- [ ] 考慮新增 `safeHTML` 標籤模板字串輔助函式
- **驗收標準**：所有 `innerHTML` 中的使用者產生內容被正確跳脫；無法透過文章標題執行 XSS

### 任務 1.8：修復 Nginx 正式環境設定

- [ ] 將 `fastcgi_pass php:9000` 改為 `fastcgi_pass web:9000`
- [ ] 更新 SSL 憑證路徑至 Certbot 實際路徑
- [ ] 使 `server_name` 可透過環境變數設定
- **驗收標準**：正式環境 Nginx 正確路由至 PHP-FPM 並提供 HTTPS 服務

### 任務 1.9：修復前端 ES Module 中的 require()

- [ ] 在 `main.js` 中以 ES Module import 取代 `require()`
- [ ] 確保 `window.navigateTo()` 正常運作
- **驗收標準**：呼叫 `navigateTo()` 時不會拋出 ReferenceError

---

## 第二階段：P1 嚴重等級修復

### 任務 2.1：實作 CSRF 中介層

- [ ] 在 `app/Application/Middleware/` 建立 `CsrfMiddleware` 類別
- [ ] 實作 POST / PUT / PATCH / DELETE 的 Token 驗證
- [ ] 在 `routes.php` 中為狀態變更路由新增 CSRF 中介層
- [ ] 新增 CSRF 保護整合測試
- **驗收標準**：無有效 CSRF Token 的狀態變更請求回傳 403

### 任務 2.2：修復 PostController 資料庫交易保護

- [ ] 將文章建立包裝在資料庫交易中
- [ ] 確保標籤關聯插入屬於同一交易
- [ ] 失敗時執行復原
- **驗收標準**：標籤插入失敗不會留下孤立文章

### 任務 2.3：從版本控制移除測試私鑰

- [ ] 移除 `test_private_key.pem` 與 `test_public_key.pem`
- [ ] 在 `.gitignore` 中加入 `*.pem`（金鑰目錄除外）
- [ ] 更新測試設定以動態產生金鑰或使用安全的測試固定資料
- **驗收標準**：版本控制中無私鑰；測試仍然通過

### 任務 2.4：修復重複路由定義

- [ ] 合併 `/api/users/*` 與 `/api/admin/users/*` 路由
- [ ] 確保授權邊界清晰
- **驗收標準**：無重複路由；授權清晰且一致

### 任務 2.5：修復 AuthorizationMiddleware 的 exit() 使用

- [ ] 以正確的 PSR-15 回應回傳取代 `exit()`
- [ ] 確保中介層鏈不被中斷
- **驗收標準**：中介層遵循 PSR-15 模式；無提前退出

### 任務 2.6：修復 PostService 死碼

- [ ] 移除被註解的損壞語法行
- [ ] 確保時間戳記更新邏輯正確
- **驗收標準**：PostService 中無死碼；時間戳記被正確管理

### 任務 2.7：修復容器重複定義

- [ ] 移除重複的 `CacheServiceProvider::getDefinitions()` 呼叫
- [ ] 移除重複的 PDO 定義
- **驗收標準**：容器中無重複定義

### 任務 2.8：修復 CI 管線 — PHPStan 與 npm audit

- [ ] 移除 `ci.yml` 中 `composer analyse` 的 `|| true`
- [ ] 移除 `frontend-ci.yml` 中 npm audit 的 `continue-on-error: true`
- **驗收標準**：PHPStan 與 npm audit 失敗會阻斷管線

### 任務 2.9：修復 .env.example 弱密碼

- [ ] 將 `Admin@123456` 替換為 `<請產生強密碼>`
- [ ] 同步更新 `.env.testing`
- **驗收標準**：範例檔案中無弱預設密碼

### 任務 2.10：為前端新增骨架屏載入狀態

- [ ] 為文章列表、文章詳情、儀表板頁面新增骨架屏
- [ ] 以骨架 UI 取代僅旋轉器的載入狀態
- **驗收標準**：頁面在載入期間顯示骨架內容

---

## 第三階段：P2 中等等級修復

### 任務 3.1：修復基於 IP 的認證

- [ ] 實作可信代理驗證
- [ ] 僅信任來自已知代理 IP 的轉發標頭
- [ ] 集中 IP 提取邏輯
- **驗收標準**：基於 IP 的認證無法透過標頭偽造繞過

### 任務 3.2：保護開發環境 Redis

- [ ] 在 `docker-compose.yml` 中為 Redis 新增 `requirepass`
- [ ] 記錄安全注意事項
- **驗收標準**：Redis 無認證無法存取

### 任務 3.3：強化 CSP 設定

- [ ] 在正式環境以 nonce 或雜湊取代 `unsafe-inline` 與 `unsafe-eval`
- **驗收標準**：正式環境 CSP 不使用不安全指令

### 任務 3.4：修復快取路徑硬編碼

- [ ] 使 HTMLPurifier 快取路徑可設定
- [ ] 使用 `storage/cache/htmlpurifier` 取代 `/tmp`
- **驗收標準**：快取路徑可設定且符合環境需求

### 任務 3.5：實作批次刪除 API

- [ ] 建立 `DELETE /api/v1/posts/batch` 端點
- [ ] 在 PostService 中以交易實作批次刪除
- [ ] 更新前端使用批次端點
- **驗收標準**：可透過單一請求刪除多篇文章

### 任務 3.6：為 CDN 資源新增 SRI 雜湊

- [ ] 為所有 CDN script / link 標籤新增 `integrity` 屬性
- [ ] 固定 CDN 版本而非使用 latest
- **驗收標準**：所有 CDN 資源具備 SRI 雜湊

### 任務 3.7：修復密碼產生中的 str_shuffle()

- [ ] 以 CSPRNG 為基礎的洗牌取代 `str_shuffle()`
- **驗收標準**：密碼產生具備密碼學安全性

### 任務 3.8：修復時區 DST 處理

- [ ] 以正確的 DST 感知時區處理取代硬編碼偏移對應
- [ ] 考慮使用時區函式庫
- **驗收標準**：時區轉換全年正確

### 任務 3.9：修復 beforeunload 監聽器洩漏

- [ ] 在離開文章編輯器時移除 beforeunload 監聽器
- **驗收標準**：後續頁面不會出現過時警告

### 任務 3.10：清理前端重複程式碼

- [ ] 移除遺留 API 模組（auth.js、posts.js、users.js、statistics.js）
- [ ] 移除重複的 CKEditorWrapper.js
- [ ] 更新所有 import 使用 modules/ 版本
- **驗收標準**：無重複 API 模組或 CKEditor 包裝器

### 任務 3.11：新增 .dockerignore

- [ ] 建立 `.dockerignore`，排除 node_modules、.git、vendor 等
- **驗收標準**：Docker 建置上下文最小化

---

## 第四階段：P3 輕微等級與清理

### 任務 4.1：將魔術數字提取為常數

- [ ] 建立常數檔案，存放超時值、頁面大小、動畫持續時間
- **驗收標準**：商業邏輯中無硬編碼魔術數字

### 任務 4.2：統一錯誤處理模式

- [ ] 確保所有控制器回傳一致的 ResponseInterface
- [ ] 標準化錯誤回應格式
- **驗收標準**：所有 API 錯誤遵循相同的 JSON 結構

### 任務 4.3：修復手動快取破壞查詢字串

- [ ] 移除 import 中的 `?v=` 查詢字串
- [ ] 使用建置流程或 ETag 進行正確的快取破壞
- **驗收標準**：無手動快取破壞查詢字串

### 任務 4.4：為 PostController 替代品新增輸入驗證

- [ ] 確保 PostService 驗證標題與內容長度
- [ ] 為純文字欄位新增 HTML 剝離
- **驗收標準**：所有輸入在處理前經過驗證

### 任務 4.5：簡化 Nginx 設定結構

- [ ] 合併重疊的 Nginx 設定檔案
- [ ] 記錄各環境使用的設定
- **驗收標準**：清晰的 Nginx 設定結構，無衝突

### 任務 4.6：固定 Docker 映像標籤

- [ ] 將 `latest` 與 `alpine` 標籤替換為特定版本
- **驗收標準**：所有 Docker 映像使用固定版本

### 任務 4.7：新增 Redis 健康檢查

- [ ] 在 `docker-compose.production.yml` 中為 Redis 新增健康檢查
- **驗收標準**：Redis 健康狀態被監控

### 任務 4.8：轉換或移除手動測試

- [ ] 將手動測試指令稿轉換為 PHPUnit 測試或移除
- **驗收標準**：無獨立的手動測試指令稿

### 任務 4.9：新增資料庫備份策略

- [ ] 建立 SQLite 資料庫備份指令稿
- [ ] 新增 cron 工作或備份磁碟區
- **驗收標準**：資料庫定期備份

---

## 整體驗收標準

- [ ] 所有 2,225+ 筆現有測試通過
- [ ] PHPStan Level 10 以 0 錯誤通過
- [ ] PHP CS Fixer 通過所有檔案
- [ ] 無殘留的 P0 或 P1 問題
- [ ] CI 管線無 `|| true` 或 `continue-on-error` 下通過
- [ ] 安全審計（`composer audit`）通過
- [ ] npm audit 通過，無中等以上漏洞
- [ ] E2E 測試通過
- [ ] 正式環境 Docker Compose 啟動無錯誤
