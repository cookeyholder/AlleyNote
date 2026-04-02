## 提案動機

全方位程式碼審查發現 AlleyNote 專案存在 **63 項問題**，其中包含 13 項 P0 致命等級、15 項 P1 嚴重等級、22 項 P2 中等等級、13 項 P3 輕微等級。這些問題涵蓋資安漏洞、效能瓶頸、架構缺陷與程式碼品質等面向。

最嚴重的問題包括：

- `RichTextProcessorService` 完全未執行 HTML 淨化，XSS 攻擊向量完全開放
- `ContentModerationService` 所有審核邏輯被註解，惡意內容無法被偵測
- `PostController` 繞過所有領域邏輯，直接使用 PDO 操作資料庫
- JWT Token 可透過 URL 查詢字串傳遞，違反 OAuth 2.0 最佳實務
- 前端使用 `Math.random()` 產生密碼，密碼可被預測
- Nginx 正式環境設定存在 FastCGI 主機名稱錯誤與 SSL 憑證路徑錯誤，將導致正式環境完全失效

若不修復這些問題，系統將面臨 XSS 攻擊、SQL 注入、資訊外洩及正式環境無法運作的風險。

## 變更內容

- **修復格式化文字淨化**：讓 `RichTextProcessorService` 實際呼叫 HTMLPurifier，取代直接回傳原始內容
- **修復內容審核服務**：恢復 `ContentModerationService` 正常運作（敏感字詞過濾、XSS 偵測、垃圾內容評分）
- **消除平行程式碼路徑**：刪除直接使用 PDO 的 `PostController`，統一整合至領域服務層
- **強化 JWT 安全**：移除查詢字串 Token 支援，僅保留 Bearer Header
- **修復前端密碼產生**：使用 `crypto.getRandomValues()` 取代 `Math.random()`
- **環境化錯誤顯示**：`display_errors` 依據環境變數動態設定
- **修復前端 XSS 漏洞**：所有 `innerHTML` 注入點一致性地使用 `escapeHtml()`
- **修復 Nginx 正式環境設定**：修正 FastCGI 主機名稱（`php` → `web`）與 SSL 憑證路徑
- **實作 CSRF 中介層**：將現有的 `CsrfTokenException` 連接至實際路由保護
- **拆分上帝類別**：將 `JwtAuthorizationMiddleware`（1065 行）拆分為獨立 Policy 類別
- **修復 CI 管線**：移除 PHPStan 與 npm audit 的 `|| true` / `continue-on-error`
- **清理遺留程式碼**：移除重複 API 模組、CKEditor 包裝器、死碼、測試金鑰
- **效能最佳化**：資料庫交易保護、批次刪除端點、Tailwind 建置步驟

## 新增能力

以下為本次變更引入的新能力，每項能力將對應至獨立的規格檔案：

- **`csrf-protection`**：CSRF Token 驗證中介層與路由整合
- **`rich-text-sanitization`**：正確的 HTMLPurifier 整合與格式化文字淨化流程
- **`content-moderation`**：內容審核服務正常運作（敏感字詞、垃圾內容偵測）
- **`secure-password-generation`**：使用 CSPRNG 的密碼產生機制
- **`jwt-security-hardening`**：JWT Token 安全強化（移除查詢字串支援、HttpOnly Cookie 支援）
- **`nginx-production-config`**：正確的正式環境 Nginx 設定（FastCGI + SSL）
- **`authorization-policies`**：模組化的授權策略類別（取代單一上帝類別）
- **`batch-operations`**：批次刪除 API 端點
- **`frontend-xss-prevention`**：前端一致的 HTML 跳脫與 DOMPurify 整合

## 修改能力

以下為現有能力的規格變更：

- **`security-performance-standards`**：更新安全與效能標準，納入本次審查發現的所有規範
- **`e2e-test-strategy`**：更新 E2E 測試策略，涵蓋新修復的安全防護驗證

## 影響範圍

### 受影響的程式碼

**後端：**
`RichTextProcessorService`、`ContentModerationService`、`PostController`、`JwtAuthenticationMiddleware`、`JwtAuthorizationMiddleware`、`AuthorizationMiddleware`、`container.php`、`routes.php`、`index.php`

**前端：**
`passwordGenerator.js`、`post.js`、`posts.js`、`dashboard.js`、`statistics.js`、`DashboardLayout.js`、`main.js`、`RichTextEditor.js`

**基礎設施：**
`nginx-production.conf`、`ci.yml`、`frontend-ci.yml`、`.env.example`、`.gitignore`

**測試：**
受影響的單元測試、整合測試、E2E 測試需同步更新

### API 變更

- **新增**：`DELETE /api/v1/posts/batch` 批次刪除端點
- **移除（破壞性變更）**：JWT Token 從查詢字串傳遞的支援

### 依賴變更

無新增外部依賴，使用現有 HTMLPurifier 與 Web Crypto API。

## 開發方法

### 測試驅動開發（TDD）

本次修復全面採用 TDD 開發流程，每個任務嚴格遵循 **Red → Green → Refactor** 迴圈：

1. **Red（紅燈）**：先撰寫會失敗的測試，明確定義預期行為與邊界條件
2. **Green（綠燈）**：實作最小可行程式碼使測試通過
3. **Refactor（重構）**：在測試保護下最佳化程式碼結構，確保符合 Clean Code 標準

TDD 強制要求：

- **禁止先寫實作再補測試**：每個修復任務必須先有測試案例
- **測試涵蓋所有分支**：包含正常路徑、邊界條件、異常情境
- **測試即文件**：測試案例名稱清晰描述預期行為，作為活文件使用
- **每次提交前測試全綠**：任何提交必須確保所有測試通過

### 高頻提交策略

採用高頻提交（High-Frequency Commits）策略，確保每次提交為獨立、可驗證的最小變更單元：

**提交原則：**

- **單一職責**：每次提交僅完成一個明確目標（例如：「新增 XSS 偵測測試」、「實作敏感字詞過濾邏輯」）
- **可獨立驗證**：每次提交後所有測試必須通過
- **語意化提交訊息**：遵循 `type(scope): description` 格式
  - `test(rich-text): 新增 basic 等級淨化測試`
  - `fix(security): 移除 JWT 查詢字串支援`
  - `refactor(middleware): 拆分 JwtAuthorizationMiddleware 為 Policy 類別`

**預期提交頻率：**

| 階段           | 預估提交數 | 說明                                           |
| -------------- | ---------- | ---------------------------------------------- |
| 第一階段（P0） | 30-40 次   | 每個任務拆分為測試先行 + 實作 + 重構的多個提交 |
| 第二階段（P1） | 25-35 次   | 同上                                           |
| 第三階段（P2） | 20-30 次   | 同上                                           |
| 第四階段（P3） | 15-20 次   | 同上                                           |

**高頻提交的優勢：**

- **降低合併衝突風險**：小批量變更更容易合併
- **便於問題追蹤**：使用 `git bisect` 可快速定位引入問題的提交
- **程式碼審查友善**：每次審查的變更量小，品質更高
- **進度透明**：提交歷史即為開發進度報告
