## 為什麼需要此變更 (Why)

經 OWASP Top 10 安全審查，在 AlleyNote 程式碼庫中發現了數個安全漏洞與功能缺陷：
1. **中斷的存取控制 (Broken Access Control)**：管理網頁頁面 (`/admin/cache/tags`) 暴露在無任何驗證/授權檢查的環境下，因為全域身分驗證中間件預設只攔截 `/api/` 開頭的路徑。此外，標籤管理與快取監控的路由目前使用 Slim 框架特有的 Closure 路由定義，這與專案目前 array 格式的自訂 Router 不相容，導致未被註冊。
2. **路徑遍歷 (Path Traversal)**：靜態資源路由 (`/assets/{path}`) 內部直接串接了使用者輸入的 `$path` 變數來載入資源，未對其解析後的實際路徑進行合法性檢驗。
3. **錯誤處理不當 (Mishandling of Exceptional Conditions)**：`FileSecurityService` 允許上傳 Microsoft Office 文件（如 `.doc`、`.docx`、`.xls`、`.xlsx`），但檔案特徵簽章對照表中卻未定義這些類型的簽章，導致所有此類上傳驗證皆會失敗。
4. **安全配置錯誤 (Security Misconfiguration)**：已實作的 `SecurityHeaderService` 從未被註冊並作為全域中間件執行，導致所有 HTTP 回應皆缺少關鍵的安全性標頭。

## 變更內容 (What Changes)

- 調整身分驗證與授權中間件，擴大攔截路徑以涵蓋所有 `/admin/` 開頭的網頁路由，確保 `/admin/cache/tags` 等管理端點具備完整的權限驗證。
- 將原先 Slim 格式的快取監控與標籤管理路由，重構成專案標準的 array 路由定義並進行註冊。
- 在靜態資源路由處理器中引進路徑解析與防範路徑遍歷的安全性驗證。
- 在 `FileSecurityService` 中加入允許之 Microsoft Office 文件類型的簽章對應資訊。
- 新增並註冊全域的安全性標頭中間件 (`SecurityHeadersMiddleware`)，自動將 CSP、HSTS、X-Frame-Options、X-Content-Type-Options 等標頭附加至所有回應中。

## 系統能力 (Capabilities)

### 新增能力 (New Capabilities)
無。

### 修改能力 (Modified Capabilities)
- `security-performance-standards`：更新安全性標準需求，強制對管理頁面進行嚴格存取控制、限制資源載入器的路徑解析、校正檔案上傳簽章機制，以及強制全域載入安全性回應標頭。

## 影響範圍 (Impact)

- **受影響程式碼**：
  - `backend/config/routes/web.php` (資源路由處理器)
  - `backend/config/routes/tag-management.php` (標籤管理路由定義)
  - `backend/config/routes/cache-monitor.php` (快取監控路由定義)
  - `backend/app/Domains/Attachment/Services/FileSecurityService.php` (上傳簽章對應)
  - `backend/app/Infrastructure/Routing/Providers/RoutingServiceProvider.php` (註冊路由檔案)
  - `backend/app/Application/Middleware/JwtAuthenticationMiddleware.php` (攔截路徑調整)
  - `backend/app/Application/Middleware/JwtAuthorizationMiddleware.php` (攔截路徑調整)
  - `backend/config/container.php` (中間件註冊)
  - `backend/app/Application.php` (全域中間件載入)
- **API 影響**：存取所有 `/admin/*` 路由均需具備已驗證的管理員 (admin) 權限。
