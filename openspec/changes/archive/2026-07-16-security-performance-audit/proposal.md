## Why

程式碼審計發現 AlleyNote 存在多項安全與效能風險：JWT 憑證在正式環境中被寫入 stderr 日誌（憑證外洩）、權限變更無法即時生效（授權混淆）、Redis 失效時速率限制靜默降級為放行（暴力破解風險）、Cookie 設定寬鬆使 CSRF 攻擊面擴大、以及貼文列表存在 N+1 查詢效能問題。本變更旨在修補這些安全漏洞並最佳化查詢效能。

## What Changes

- **移除 JWT Debug 日誌**：刪除 `FirebaseJwtProvider::validateToken()` 中寫入 `php://stderr` 的除錯輸出
- **強化 Token 角色撤銷機制**：新增 JWT `iat` 與使用者 `role_updated_at` 比對邏輯，確保角色降級後立即生效
- **收緊 Cookie 安全設定**：認證 Cookie 改為 `SameSite=Strict`、移除 `localStorage` refresh token 回退、新增 CSRF token 補發端點
- **速率限制容錯降級**：Redis 連線失敗時回傳 `allowed => false`（fail-closed）
- **修補 N+1 查詢**：批次載入文章標籤取代逐筆查詢
- **修補 TOCTOU 競態條件**：移除 `PostCrudRepository::tagsExist()` 預先檢查，改依賴外鍵約束確保標籤關聯完整性
- **CSP 與供應鏈安全**：Tailwind CDN 加上 SRI integrity hash，更新 CSP 規則
- **XSS 消毒強化**：`postEditor.js` 中 `innerHTML` 改為 `escapeHtml` 安全渲染

## Deferred (Low Priority — 本次不處理)

以下 4 項低優先級審計發現由於影響範圍小或需較大架構變更，排入 backlog 待後續處理：

- **L-1 缺少索引**：`post_tags.created_at` 未使用但自建索引，可於整理 Migration 時移除
- **L-2 硬刪除使用者**：`UserRepository::delete()` 使用 `DELETE`，需評估軟刪除或匿名化策略後再實作
- **L-3 靜態 Logger 模式**：`app_log()` 使用靜態變數與相對路徑，建議在依賴注入重構時一併處理
- **L-4 序號競態**：`getNextSeqNumber()` 使用 `MAX(seq_number) + 1` 有競態風險，需評估 `AUTOINCREMENT` 或序列表

## Capabilities

### New Capabilities
- `jwt-token-hardening`: 移除 JWT debug 日誌、角色撤銷即時生效、移除 PII、JTI 長度最佳化
- `auth-session-security`: Cookie SameSite 收緊、移除 localStorage token 回退、CSRF token 補發端點
- `rate-limit-fail-closed`: Redis 失效時速率限制降級為拒絕而非放行
- `query-performance`: 批次載入文章標籤消除 N+1、明確指定查詢欄位取代 SELECT *、修補 TOCTOU 競態條件
- `csp-supply-chain`: Tailwind CDN 加入 SRI integrity、更新 CSP 設定

### Modified Capabilities
<!-- 無既有 spec 需要修改 -->

## Impact

- `backend/app/Infrastructure/Auth/Jwt/FirebaseJwtProvider.php` — 移除 debug 日誌、角色時效檢查、JTI 最佳化
- `backend/app/Application/Middleware/JwtAuthenticationMiddleware.php` — 新增角色時效驗證
- `backend/app/Domains/Auth/Services/` — 角色時效查詢邏輯
- `backend/app/Application/Controllers/Api/V1/AuthController.php` — Cookie 設定、CSRF token 端點
- `backend/app/Infrastructure/Services/RateLimitService.php` — 容錯行為變更
- `backend/app/Domains/Post/Repositories/PostCrudRepository.php` — 批次載入標籤、移除 TOCTOU tagsExist 預檢
- `backend/app/Domains/Post/Repositories/PostRepository.php` — 批次載入標籤
- `backend/app/Domains/Auth/Repositories/UserRepository.php` — 明確查詢欄位取代 SELECT *
- `backend/app/Domains/Security/Services/Headers/SecurityHeaderService.php` — CSP 更新
- `frontend/index.html` — SRI integrity、Tailwind 本地化或 CDN 鎖定
- `frontend/js/pages/admin/postEditor.js` — XSS 消毒
- `frontend/js/api/client.js` — localStorage token 移除
