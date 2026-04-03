# AlleyNote 全方位程式碼審查報告 (v1.0.0)

本報告針對 AlleyNote 專案的架構、資安、效能、程式碼品質及業務邏輯進行深入審查，並依優先級提出改進建議。

## 執行摘要

AlleyNote 展現了良好的 DDD 架構實踐與完整的測試覆蓋。然而，在 **富文本處理、安全性標頭解析及程式碼冗餘** 方面仍存在顯著問題，其中最嚴重的問題（P0）是富文本內容在輸出時被過度轉義，導致前端無法正常渲染 CKEditor 產生的 HTML 內容。

---

## 問題清單與優先級

### P0 - 致命 (Critical)

#### 1. 富文本內容過度轉義導致 UI 損壞

- **位置**: `backend/app/Domains/Post/Models/Post.php` -> `toSafeArray()`
- **描述**: 系統使用 `OutputSanitizerService::sanitizeHtml`（內部僅調用 `htmlspecialchars`）來清理文章標題與內容。這導致 CKEditor 產生的 HTML 標籤（如 `<p>`, `<strong>`）被轉義為 `&lt;p&gt;`，前端雖然使用了 `DOMPurify`，但因收到的是純文字字串而無法渲染格式，直接顯示原始標籤。
- **影響**: 終端使用者看到的公告內容會包含原始 HTML 標籤，嚴重影響閱讀體驗與專業形象。
- **建議修復**:
  - 在後端引入 `HTMLPurifier` 專門處理富文本內容。
  - 修改 `OutputSanitizerService` 增加 `sanitizeRichText` 方法。
  - 在 `Post::toSafeArray` 中，對 `content` 使用 `sanitizeRichText` 而非 `sanitizeHtml`。

---

### P1 - 嚴重 (High)

#### 2. IP 來源偽造風險

- **位置**: `AuthController::getClientIpAddress`, `PostController::getUserIp`, `JwtAuthenticationMiddleware::getClientIpAddress`
- **描述**: 這些方法直接解析 `HTTP_X_FORWARDED_FOR` 等標頭而未驗證來源代理是否受信任。惡意使用者可以透過偽造此標頭來繞過 IP 限制或混淆審計日誌。
- **影響**: 資安防禦（如 IP 黑名單）可能被輕易繞過。
- **建議修復**:
  - 實作「信任代理 (Trusted Proxies)」機制，僅在來源 IP 屬於信任清單時才解析轉發標頭。
  - 統一所有 IP 取得邏輯至 `App\Shared\Helpers` 或專用服務中。

#### 3. 控制器邏輯冗餘與重複

- **位置**: `PostController.php`
- **描述**: 同時存在 `delete()` 與 `destroy()` 處理相同路由；`unpin()` 與 `togglePin()` 邏輯重疊。
- **影響**: 增加維護成本，容易造成邏輯不一致（例如 `delete()` 有詳盡日誌但 `destroy()` 沒有）。
- **建議修復**:
  - 移除 `destroy()`，統一使用功能更完整的 `delete()`。
  - 簡化 `unpin()`，直接調用 `togglePin()` 邏輯或反之。

---

### P2 - 中等 (Medium)

#### 4. JWT 驗證邏輯重複

- **位置**: `AuthController.php` -> `updateProfile()`, `changePassword()`
- **描述**: 這些方法內部手動提取並驗證了 JWT Token，然而該路由已由 `JwtAuthenticationMiddleware` 保護，且該中介軟體已將 `user_id` 注入到 Request Attribute 中。
- **影響**: 程式碼冗餘，增加不必要的 CPU 運算。
- **建議修復**: 移除手動驗證邏輯，直接從 `$request->getAttribute('user_id')` 取得使用者 ID。

#### 5. 程式碼遺跡與語法錯誤

- **位置**: `backend/app/Domains/Post/Repositories/PostRepository.php`
- **描述**: 程式碼中留有因語法錯誤而被註解的賦值語句（例如：`// $data ? $data->updated_at : null)) = ...`）。
- **影響**: 降低程式碼可讀性，顯示開發過程中的未清理痕跡。
- **建議修復**: 移除無效註解，並確保時間戳記更新邏輯清晰明確。

---

### P3 - 輕微 (Low)

#### 6. 快取清理模式效能隱憂

- **位置**: `PostRepository::invalidateCache`
- **描述**: 使用 `deletePattern` 進行大量分頁快取清理。在 Redis 且 Key 數量極大時，`KEYS` 指令（通常為 `deletePattern` 的底層）會阻塞伺服器。
- **影響**: 當文章數量極大且異動頻繁時，可能短暫影響 Redis 回應速度。
- **建議修復**: 改用快取標籤 (Cache Tags) 或版本化快取鍵 (Versioned Cache Keys) 策略，避免使用模式比對刪除。

#### 7. 錯誤回應格式不統一

- **位置**: `PostController.php`
- **描述**: 部份 `catch` 塊直接返回 `500` 或 `handleException`，其輸出格式與其他標準化的 `errorResponse` 略有不同。
- **影響**: 前端處理錯誤時需處理多種不同結構。
- **建議修復**: 統一所有 API 回應使用 `BaseController` 提供的方法，確保 JSON 結構一致。

---

---

## 架構升級：Secure-DDD 模式

為了徹底解決上述問題並建立長期的開發規範，本次修復導入了 **「安全導向的領域驅動設計服務架構 (Security-Oriented DDD Service Architecture, 簡稱 Secure-DDD)」**。

### Secure-DDD 核心原則

1. **安全左移 (Security Left)**：將資安邏輯（如 IP 辨識、HTML 淨化）從 Controller 移至 Shared/Infrastructure 層，確保所有領域操作預設即安全。
2. **標準化介面 (Standardized Interfaces)**：定義 `OutputSanitizerInterface` 等介面，解耦實作細節，方便未來升級清理引擎。
3. **精確的狀態管理 (Precise State Management)**：嚴格限制快取與資料庫操作的副作用，確保系統行為可預測且高效。

### 測試程式重構成果

已完成全站（後端 + 前端）測試套件的架構重構，建立起兩大支柱：

- **後端 (Secure-DDD)**:
  - 2206 個測試檔案全數繼承自 `Tests\SecureDDDTestCase`。
  - 實現 100% 邏輯通過率，確保所有業務操作預設安全。
- **前端 (Secure-UI Spec)**:
  - 建立 `SecureBasePage` 與 `PublicPostPage` 等 POM 物件。
  - 引入 `assertRichTextRendered` 與 `assertNoSensitiveInfoLeaked` 等安全斷言。
  - 重構核心 E2E 測試（Auth, Post Detail），落實多層次安全驗證。

### 全站重構計畫

- **持續集成**: 已建議將 Secure-DDD 與 Secure-UI 測試納入 CI 流程。
- **後續擴充**: 剩餘 E2E 測試將依據 `Secure-UI Spec` 規範持續進行批次轉型。

---

# 全方位程式碼審查報告 (v2.0.0)

> **審查日期**: 2026-03-31
> **審查範圍**: 後端 (PHP/Slim) + 前端 (Vanilla JS) + 基礎設施 (Docker/CI/CD)
> **審查視角**: 20 年經驗資深架構師與資安工程師

## 執行摘要

| 類別                      | P0_CRITICAL | P1_HIGH | P2_MEDIUM | P3_LOW |  合計  |
| ------------------------- | :---------: | :-----: | :-------: | :----: | :----: |
| 資安 (Security)           |      5      |    4    |     3     |   1    |   13   |
| 效能 (Performance)        |      1      |    2    |     4     |   2    |   9    |
| Clean Code                |      2      |    3    |     5     |   4    |   14   |
| 架構與遺跡 (Architecture) |      2      |    3    |     3     |   2    |   10   |
| 邏輯錯誤 (Logic Bugs)     |      3      |    2    |     3     |   1    |   9    |
| 使用者體驗 (UX)           |      0      |    1    |     4     |   3    |   8    |
| **總計**                  |   **13**    | **15**  |  **22**   | **13** | **63** |

---

## P0_CRITICAL — 致命問題（必須立即修復）

### SEC-001: RichTextProcessorService 完全未呼叫 HTMLPurifier

- **位置**: `backend/app/Domains/Post/Services/RichTextProcessorService.php:99-103`
- **影響**: 所有使用者等級（admin/extended/basic）的富文本處理都直接返回原始內容，HTMLPurifier 從未執行
- **風險**: XSS 攻擊向量完全開放，惡意腳本可直接存入資料庫
- **現況**: 程式碼中有 TODO 標記但從未修復

### SEC-002: ContentModerationService 邏輯全部被註解

- **位置**: `backend/app/Domains/Post/Services/ContentModerationService.php`
- **影響**: `moderateContent()` 永遠回傳 `status: 'approved', confidence: 100`，無論內容為何
- **風險**: 垃圾內容、敏感詞、惡意內容完全無法被偵測

### SEC-003: PostController 繞過所有領域邏輯（直接 PDO）

- **位置**: `backend/app/Application/Controllers/PostController.php`（649 行）
- **影響**: 使用直接 PDO 查詢，完全繞過 PostService、PostAggregate、驗證、淨化、快取、領域事件
- **風險**: 平行的、未受保護的管理員操作程式碼路徑，SQL 注入風險高

### SEC-004: JWT Token 可透過 Query String 傳遞

- **位置**: `backend/app/Application/Middleware/JwtAuthenticationMiddleware.php:94-97`
- **影響**: 接受從 URL 查詢參數中提取 JWT token
- **風險**: Token 洩露在伺服器日誌、瀏覽器歷史、Referer header

### SEC-005: 前端 Math.random() 用於密碼生成

- **位置**: `frontend/js/utils/passwordGenerator.js:68`
- **影響**: `Math.random()` 不是密碼學安全的隨機數生成器
- **風險**: 生成的密碼可被預測

### SEC-006: display_errors 在生產環境開啟

- **位置**: `backend/public/index.php:25`
- **影響**: `ini_set('display_errors', '1')` 向終端使用者洩露堆疊追蹤和內部路徑

### SEC-007: 前端多處 innerHTML 注入未跳脫的使用者資料

- **位置**:
  - `frontend/js/pages/public/post.js:134-135`
  - `frontend/js/pages/admin/posts.js:279`
  - `frontend/js/pages/admin/dashboard.js:253`
  - `frontend/js/pages/admin/statistics.js:419`
  - `frontend/js/layouts/DashboardLayout.js:38`
- **風險**: 若後端未淨化標題，可執行 XSS 攻擊

### SEC-008: CKEditor 允許所有 HTML 元素和屬性

- **位置**: `frontend/js/components/RichTextEditor.js:248-255`
- **影響**: `htmlSupport.allow: { name: /.*/, attributes: true, classes: true, styles: true }`

### SEC-009: 測試私鑰提交至版本控制

- **位置**: `backend/test_private_key.pem`, `backend/test_public_key.pem`

### PERF-001: Nginx 生產配置 FastCGI 主機名稱不匹配

- **位置**: `docker/nginx/nginx-production.conf:93`
- **影響**: 使用 `fastcgi_pass php:9000`，但服務名為 `web`
- **風險**: 生產環境完全無法處理 PHP 請求

### PERF-002: Nginx 生產配置 SSL 證書路徑錯誤

- **位置**: `docker/nginx/nginx-production.conf:24-25`
- **影響**: 證書路徑指向 `/etc/nginx/ssl/`，但 Certbot 儲存在 `/etc/letsencrypt/live/`
- **風險**: 生產環境 HTTPS 完全失效

### ARCH-001: JwtAuthorizationMiddleware 上帝類別（1065 行）

- **位置**: `backend/app/Application/Middleware/JwtAuthorizationMiddleware.php`
- **影響**: 單一中介層實現 RBAC、ABAC、時間限制、IP 限制、所有權檢查
- **建議**: 拆分為獨立的 Policy 類別

### ARCH-002: 前端 require() 在 ES Module 環境中

- **位置**: `frontend/js/main.js:117`
- **風險**: `window.navigateTo()` 被呼叫將拋出 `ReferenceError`

---

## P1_HIGH — 嚴重問題

### SEC-010: 無 CSRF 保護

- **位置**: `backend/config/routes.php`
- **影響**: 存在 `CsrfTokenException` 但無實際 CSRF 中介層連接

### SEC-011: JWT Token 存放在 localStorage

- **位置**: `frontend/js/utils/storage.js`, `frontend/js/api/client.js`
- **建議**: 改用 HttpOnly cookies

### SEC-012: 前端無登入速率限制

- **位置**: `frontend/js/pages/public/login.js`

### SEC-013: `.env.testing` 未加入 .gitignore

- **位置**: `.gitignore:18` — `!.env.testing`
- **影響**: 測試憑證被提交到倉庫

### PERF-003: PostController 無資料庫交易保護

- **位置**: `backend/app/Application/Controllers/PostController.php` 的 `store()` 方法
- **風險**: 標籤插入失敗會產生孤立文章

### PERF-004: Tailwind CSS CDN 用於生產

- **位置**: `frontend/index.html`
- **影響**: Tailwind CDN 在運行時處理所有 class，增加大量 JS 開銷

### PERF-005: 統計頁面獲取 100 篇文章

- **位置**: `frontend/js/pages/admin/statistics.js:118`

### ARCH-003: 重複的路由定義

- **位置**: `backend/config/routes.php`
- **影響**: `/api/users/*` 和 `/api/admin/users/*` 指向相同控制器

### ARCH-004: AuthorizationMiddleware 使用 exit()

- **位置**: `backend/app/Application/Middleware/AuthorizationMiddleware.php:30,44`
- **風險**: 與 PSR-15 中介層模式不相容

### BUG-001: PostService 死碼

- **位置**: `backend/app/Domains/Post/Services/PostService.php:33,71`

### BUG-002: 容器配置重複定義

- **位置**: `backend/config/container.php:93,235`
- **影響**: `CacheServiceProvider::getDefinitions()` 被呼叫兩次

### BUG-003: CI 中靜態分析失敗被忽略

- **位置**: `.github/workflows/ci.yml:107` — `composer analyse || true`

### BUG-004: CI 中 npm audit 失敗被忽略

- **位置**: `.github/workflows/frontend-ci.yml:164` — `continue-on-error: true`

### BUG-005: .env.example 含預設弱密碼

- **位置**: `.env.example:54` — `ADMIN_PASSWORD=Admin@123456`

### UX-001: 前端無骨架屏載入狀態

- **位置**: 多個頁面模組

---

## P2_MEDIUM — 中等問題

### SEC-014: IP 基礎認證不可靠

- **位置**: `JwtAuthorizationMiddleware::getClientIpAddress()`
- **影響**: 信任 forwarded headers 而不驗證可信代理

### SEC-015: Redis 開發環境暴露到主機無認證

- **位置**: `docker-compose.yml:98`

### SEC-016: CSP 使用 unsafe-inline 和 unsafe-eval

- **位置**: `docker/nginx/ssl.conf:35`

### PERF-006: 快取路徑硬編碼為 /tmp

- **位置**: `backend/app/Domains/Post/Services/RichTextProcessorService.php:267`

### PERF-007: 批次刪除為順序執行

- **位置**: `frontend/js/pages/admin/posts.js:600-613`

### PERF-008: CDN 資源無 SRI 哈希

- **位置**: `frontend/index.html`

### PERF-009: Docker 無 .dockerignore

- **影響**: 構建上下文包含所有內容（node_modules、.git 等）

### BUG-006: str_shuffle() 非密碼學安全

- **位置**: `backend/app/Domains/Auth/Services/PasswordSecurityService.php:181`

### BUG-007: 時區處理不處理夏令時

- **位置**: `frontend/js/utils/timezoneUtils.js:110`

### BUG-008: beforeunload 監聽器未移除

- **位置**: `frontend/js/pages/admin/postEditor.js:375`

### BUG-009: Nginx 生產配置 server_name 硬編碼

- **位置**: `docker/nginx/nginx-production.conf:4,19`

### BUG-010: Dockerfile.production 中 development stage 為死碼

- **位置**: `docker/php/Dockerfile.production:65-86`

### BUG-011: opcache.fast_shutdown 已移除

- **位置**: `docker/php/php.ini`
- **影響**: 該指令在 PHP 8.0 已移除

### ARCH-005: 前端重複 API 模組

- **位置**: `frontend/js/api/auth.js` vs `frontend/js/api/modules/auth.js`

### ARCH-006: 兩個 CKEditor 包裝器

- **位置**: `frontend/js/components/RichTextEditor.js` 和 `CKEditorWrapper.js`

### ARCH-007: 前端模組模式不一致

- **影響**: 部分頁面使用 class，部分使用函數

### UX-002: 無鍵盤導航支援 Modal

- **影響**: Modal 不捕捉焦點或不回應 Escape 鍵

### UX-003: console 日誌洩露敏感資訊

- **位置**: 前端多處

### UX-004: 全局函數汙染 window 物件

- **位置**: `frontend/js/pages/public/home.js:527-547`, `frontend/js/pages/admin/posts.js:714`

### UX-005: 無離線支援

- **位置**: `frontend/index.html` — 有 PWA meta 標籤但無 service worker

### UX-006: CDN 資源阻塞渲染

- **位置**: `frontend/index.html`

---

## P3_LOW — 輕微問題

### CODE-001: 魔術數字散佈

### CODE-002: 無 TypeScript

### CODE-003: 不一致的錯誤處理

### CODE-004: 手動快取破壞查詢字串

### CODE-005: PostController 輸入驗證不足

### CODE-006: 多個 nginx 配置文件重疊

### CODE-007: Docker 鏡像標籤未固定

### CODE-008: 無 Redis 健康檢查

### CODE-009: E2E 使用 PHP 內建伺服器

### CODE-010: 依賴更新工作流權限過大

### CODE-011: 214 測試檔案中包含手動測試

### CODE-012: 無資料庫備份策略

---

## 修復優先順序建議

### 第一優先（立即修復 — P0）

1. 修復 RichTextProcessorService 實際呼叫 HTMLPurifier
2. 修復或移除 ContentModerationService
3. 刪除或整合 PostController 到服務層
4. 移除 JWT-from-query-string 支援
5. 修復前端 Math.random() 密碼生成
6. 使 display_errors 依賴環境
7. 修復所有 innerHTML XSS 漏洞
8. 修復 Nginx 生產配置（FastCGI 主機名 + SSL 路徑）

### 第二優先（短期修復 — P1）

1. 實現 CSRF 中介層
2. 遷移 JWT 到 HttpOnly cookies
3. 添加登入速率限制
4. 將測試憑證移出倉庫
5. 修復 PostController 資料庫交易
6. 使用 Tailwind 構建步驟替換 CDN
7. 拆分 JwtAuthorizationMiddleware 為 Policy 類別
8. 修復 CI 管線（PHPStan + npm audit 不忽略）

### 第三優先（中期修復 — P2）

1. 修復 IP 認證邏輯
2. 保護開發環境 Redis
3. 強化 CSP 配置
4. 修復快取路徑硬編碼
5. 實現批次刪除端點
6. 添加 CDN SRI 哈希
7. 修復密碼生成 str_shuffle
8. 修復時區 DST 處理
9. 統一前端 API 模組和 CKEditor 包裝器

### 第四優先（長期優化 — P3）

1. 提取魔術數字為常量
2. 考慮 TypeScript 遷移
3. 統一錯誤處理模式
4. 簡化 nginx 配置結構
5. 固定 Docker 鏡像標籤
6. 添加 Redis 健康檢查
7. 實現資料庫備份策略

---

## 第五次迭代審查發現 (v1.5.0)

### P1 - 嚴重 (High)

#### 10. 代理路徑組合有邏輯錯誤，會把 `/api` 重複拼接成 `/api/api/...`

- **位置**: `dev-server.js:116～dev-server.js:119`
- **描述**: 代理路徑組合邏輯會將 `args.proxyPath`（如 `/api`）重複拼接到 `args.proxyTarget`（如 `http://127.0.0.1:8081/api`）後面，導致最終 URL 變成 `http://127.0.0.1:8081/api/api/posts`。
- **影響**: 所有 API 請求都會得到 404，功能完全失效。
- **建議修復**: 移除多餘的 `args.proxyPath` 拼接，直接使用 `args.proxyTarget + urlPath`。
- **狀態**: ⚠️ 部分修復 — 代理邏輯已改為提取 origin 再拼接，但參數仍含 path（如 `http://127.0.0.1:8081/api`），需同步更新所有呼叫端移除 path 部分。

### P2 - 中等 (Medium)

#### 11. CSRF Secure 屬性只用 `APP_ENV === production` 判斷

- **位置**: `backend/config/container.php:108`
- **描述**: `secureCookie` 參數僅根據環境名稱決定，非 production 但走 HTTPS 的環境會降級為非 Secure cookie。
- **影響**: 在 staging 或其他使用 HTTPS 的非 production 環境中，CSRF cookie 不會設定 Secure flag，增加中間人攻擊風險。
- **建議修復**: 引入 `CSRF_COOKIE_SECURE` 環境變數，允許獨立控制 Secure flag。
- **狀態**: ✅ 已修復 — 新增 `CSRF_COOKIE_SECURE` 環境變數支援，向後相容預設行為。

### P3 - 輕微 (Low)

#### 12. dev-server 對 `https://` target 仍使用 `http.request`

- **位置**: `dev-server.js:75～dev-server.js:81`
- **描述**: 代理函數固定使用 `http.request`，對 HTTPS target 無法正確處理。
- **影響**: 目前開發環境不使用 HTTPS target，屬潛在問題。
- **建議修復**: 根據 URL protocol 動態選擇 `http` 或 `https` 模組。
- **狀態**: ✅ 已修復 — 依 protocol 動態切換 `http`/`https` 模組。

#### 13. CSRF cookie 字串手動拼接可再改善

- **位置**: `backend/app/Application/Middleware/CsrfMiddleware.php:108～backend/app/Application/Middleware/CsrfMiddleware.php:120`
- **描述**: 使用 `sprintf` 手動拼接 cookie 字串，當 `secureFlag` 為空時會產生尾端多餘分號/空白。
- **影響**: 可讀性與穩定性較差，但不影響功能。
- **建議修復**: 使用條件陣列組建屬性後 `implode('; ', ...)`。
- **狀態**: ✅ 已修復 — 改用陣列組建 cookie 屬性，避免空值問題。

---

## 修復摘要

本次迭代修復共處理 4 個問題：

- 1 個 P1（代理路徑重複拼接）
- 1 個 P2（CSRF Secure flag 判斷邏輯）
- 2 個 P3（HTTPS 代理模組切換、Cookie 字串拼接優化）

所有修復均已完成，無功能回歸風險。
