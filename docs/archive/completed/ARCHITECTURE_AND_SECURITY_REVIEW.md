# AlleyNote 專案架構與安全性審查報告

## 1. 總體概述

AlleyNote 專案採用了前後端分離的現代化架構，並透過 Docker 進行了全面的容器化，展現了良好的工程實踐基礎。

- **後端**：基於 PHP 的輕量級組件化架構，未使用完整的框架，而是整合了如 `php-di`、`phinx` 和 `php-jwt` 等高品質的獨立函式庫。這種方式提供了極高的靈活性。
- **前端**：一個無框架的單頁應用程式 (Vanilla JS SPA)，直接利用 `Navigo` 進行路由管理，並整合了 `Tailwind CSS` 和 `CKEditor` 等工具。
- **基礎設施**：以 Docker Compose 管理的 LEMP (Nginx, MySQL/SQLite, PHP) 堆疊，並額外包含了 Redis 和 Certbot，配置健全且易於部署。
- **開發與 CI/CD**：擁有非常完善的開發工具鏈，包含自動化測試、靜態分析、程式碼風格檢查以及依賴套件安全掃描，並已整合到 GitHub Actions CI 流程中。

總體而言，該專案的後端和基礎設施非常穩固，但在前端架構和部分安全性細節上有進一步提升的空間。

---

## 2. 架構建議

### 2.1. 前端架構現代化 (高優先級)

**現狀**:
目前前端採用 Vanilla JS + Navigo 的模式。雖然靈活，但隨著應用程式功能日趨複雜，將面臨以下挑戰：
- **狀態管理困難**：缺乏統一的狀態管理機制，容易導致資料不一致和程式碼混亂。
- **元件化不足**：難以重用 UI 元件，程式碼重複性高，維護成本增加。
- **開發效率和生態系**：無法享受現代前端框架（如 Vue, React）帶來的開發效率提升、豐富的生態系工具和龐大的社群支援。

**建議**:
建議將前端逐步遷移到一個現代 JavaScript 框架。
- **Vue.js**：學習曲線平緩，與現有專案的整合也相對容易，適合快速迭代。
- **React**：生態系最為龐大，社群活躍，適合構建大型且複雜的應用程式。

**遷移步驟建議**:
1.  選擇一個框架並建立新的前端專案結構（例如使用 `Vite`）。
2.  將現有的路由、API 服務和 UI 元件逐步遷移到新框架的架構下。
3.  可以先從一個功能模組（例如「使用者管理」）開始，實現新舊架構並存，逐步替換。

### 2.2. 後端應用程式結構

**現狀**:
後端採用了自訂的應用程式結構，`composer.json` 中定義了 `App\` 的命名空間。從檔案目錄看，似乎有 `Domains` 資料夾，可能嘗試了領域驅動設計 (DDD) 的概念。

**建議**:
- **明確架構模式**：如果正在實踐 DDD，建議將核心領域邏輯（實體、值物件、領域服務、倉儲介面）嚴格限制在 `app/Domains` 目錄下。應用程式服務和基礎設施（如資料庫實現、外部 API 客戶端）應分別放在 `app/Application` 和 `app/Infrastructure`。確保依賴方向是 `Application -> Domain` 和 `Infrastructure -> Domain`，而不是反向依賴。
- **標準化框架的考慮**：雖然目前的輕量級架構很靈活，但如果團隊成員變動或專案規模持續擴大，採用一個像 **Slim Framework** 或 **Laravel** 這樣的標準化框架可以降低新成員的上手成本，並獲得更豐富的官方文件和社群支援。考慮到目前已使用許多獨立組件，遷移到 Slim 的成本相對較低。

### 2.3. 設定管理

**現狀**:
專案已使用 `.env` 檔案來管理環境變數，這是很好的實踐。`docker-compose.yml` 也正確地從中讀取設定。

**建議**:
- **集中化設定檔**：建議在 `backend/config` 目錄下為不同的設定（如資料庫、JWT、快取）建立獨立的 PHP 設定檔。這些檔案從環境變數中讀取值，並提供預設值。這樣可以讓設定更結構化，也更容易進行快取以提升效能。

---

## 3. 安全性建議

專案的 CI 流程中已包含 `composer audit`，這是一個非常出色的安全實踐。以下是一些可以進一步強化的建議。

### 3.1. HTTP 安全性標頭 (高優先級)

**現狀**:
未在 Nginx 設定中看到明確的安全性標頭配置。瀏覽器預設行為可能使應用程式暴露於點擊劫持 (Clickjacking)、XSS 等風險中。

**建議**:
在 Nginx 設定檔 (`docker/nginx/frontend-backend.conf`) 的 `server` 區塊中，為所有請求添加以下安全性標頭：
```nginx
server {
    # ... 其他設定 ...

    # 添加安全性標頭
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' https://cdn.tailwindcss.com https://cdn.ckeditor.com https://cdn.jsdelivr.net https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self' https://api.alleynote.com; frame-src 'self';" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
}
```
**注意**: `Content-Security-Policy` (CSP) 的內容需要根據前端實際使用的外部資源（CDN、API 端點）進行精確調整，上述只是一個範例。錯誤的 CSP 設定可能導致前端資源無法載入。

### 3.2. 輸入驗證與輸出淨化

**現狀**:
- **前端**: `index.html` 中引入了 `DOMPurify`，這表明前端有意識地在處理 HTML 淨化，防止 XSS，這是很好的做法。
- **後端**: `composer.json` 中引入了 `ezyang/htmlpurifier`，同樣用於後端 HTML 淨化。但對於非 HTML 的一般輸入（如查詢參數、JSON body），需要確保有統一的驗證機制。

**建議**:
- **統一的請求驗證層**：建議在後端引入一個驗證函式庫（例如 `respect/validation` 或 `illuminate/validation`），並建立一個中介軟體或在 Controller 的基礎類別中，對所有傳入的請求（`POST` body, `GET` 參數）進行嚴格的驗證。確保所有輸入都符合預期的格式、類型和範圍。
- **確保所有輸出都經過處理**：除了使用者提交的內容需要用 `HTMLPurifier` 淨化外，從資料庫讀取並顯示在頁面上的任何資料，都應預設進行 HTML 編碼（例如使用 `htmlspecialchars`），除非確定是安全的 HTML。

### 3.3. 權限管理的細緻度

**現狀**:
`routes.php` 中大量使用了 `jwt.authorize` 中介軟體，但沒有明確指定需要的權限。這意味著授權邏輯可能分散在各個 Controller 內部。

**建議**:
- **基於資源和操作的權限**：建議讓授權中介軟體可以接受參數，例如 `jwt.authorize:posts.update`。在中介軟體內部，檢查當前使用者是否擁有 `posts.update` 這個權限。
- **實作範例**:
  ```php
  // routes.php
  $postsUpdate->middleware(['jwt.auth', 'jwt.authorize:posts.update']);

  // JwtAuthorizeMiddleware.php
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
      // ... 取得權限參數 'posts.update'
      $requiredPermission = ...; 
      
      // ... 從 JWT token 或使用者物件中取得使用者的權限列表
      $userPermissions = ...;

      if (!in_array($requiredPermission, $userPermissions)) {
          // 拋出 403 Forbidden 錯誤
      }

      return $handler->handle($request);
  }
  ```
這樣可以讓路由定義檔本身就成為一份清晰的權限需求文件，也讓授權邏輯更集中。

### 3.4. Docker 環境安全

**現狀**:
`docker-compose.yml` 中的 `web` 服務以 `root` 使用者身份運行。

**建議**:
- **非 Root 使用者運行**：在 `docker/php/Dockerfile` 中，建立一個非 root 的使用者（例如 `www-data`），並在 Dockerfile 的最後使用 `USER www-data` 指令切換。這是一個安全最佳實踐，可以減輕容器逃逸漏洞帶來的風險。

---

## 4. 總結

AlleyNote 是一個基礎扎實、工程實踐良好的專案。

**最優先的行動建議**:
1.  **前端現代化**：開始規劃並逐步將前端遷移至 Vue.js 或 React，以應對未來的功能擴展和提升開發維護效率。
2.  **添加 HTTP 安全性標頭**：立即在 Nginx 設定中加入 `X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy` 等標頭，以低成本大幅提升前端安全性。
3.  **細化後端授權邏輯**：重構授權中介軟體，使其能夠根據路由定義來檢查具體權限，讓權限管理更清晰、更集中。

完成以上建議將使專案在可維護性、擴展性和安全性方面都達到更高水準。
