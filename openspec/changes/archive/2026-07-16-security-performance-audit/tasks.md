## 1. JWT Token 安全強化（jwt-token-hardening）

- [x] 1.1 建立 `JwtRoleFreshnessValidator` 類別，比對 JWT `iat` 與使用者 `role_updated_at`
- [x] 1.2 在使用者 Model/DTO 中加入 `role_updated_at` 欄位（若無則回傳 null）
- [x] 1.3 在 `JwtAuthenticationMiddleware::performSecurityChecks()` 中呼叫角色時效驗證，iat 早於 role_updated_at 時回傳 403
- [x] 1.4 移除 `FirebaseJwtProvider::validateToken()` 中所有 `file_put_contents('php://stderr', ...)` 呼叫，改用 `if (dev) { app_log('debug', ...) }`
- [x] 1.5 從 `AuthenticationService` 的 JWT payload 中移除 `email` 宣告
- [x] 1.6 將 `generateJti()` 改為 UUID v4 格式（`bin2hex(random_bytes(16))` 搭配格式或 UUID 函式庫）
- [x] 1.7 撰寫對應單元測試：角色時效驗證、移除 debug 日誌、JTI 格式

## 2. 認證 Session 安全（auth-session-security）

- [x] 2.1 `AuthController::buildCookieHeader()` 中認證 Cookie 的 `SameSite` 從 `Lax` 改為 `Strict`
- [x] 2.2 移除 `frontend/js/api/client.js` 中 `localStorage.setItem('refresh_token', ...)` 相關程式碼
- [x] 2.3 新增 `GET /csrf-token` 路由與 Controller 方法，回傳新 CSRF token 並設定 Cookie
- [x] 2.4 更新 `CsrfMiddleware` 確保 CSRF token 補發端點不受 CSRF 保護（白名單）
- [x] 2.5 撰寫對應測試：Cookie SameSite、CSRF 補發端點

## 3. 速率限制容錯降級（rate-limit-fail-closed）

- [x] 3.1 `RateLimitService::checkRateLimit()` 中 `catch (Throwable $e)` 區塊的 `return ['allowed' => true, ...]` 改為 `return ['allowed' => false, ...]`
- [x] 3.2 確認 `RateLimitMiddleware` 在收到 `allowed => false` 時正確回傳 429 或 500，而非引發未處理例外
- [x] 3.3 撰寫測試：模擬 Redis 連線失敗時速率限制拒絕請求

## 4. 查詢效能最佳化（query-performance）

- [x] 4.1 在 `PostCrudRepository` 或 `PostRepository` 中新增 `getTagsForPosts(array $postIds): array` 方法，批次查詢標籤
- [x] 4.2 修改 `paginate()` 或對應的 Service 層，在取得文章後呼叫批次標籤查詢並合併結果
- [x] 4.3 修改 `UserRepository::paginate()` 使用明確欄位清單取代 `SELECT u.*`
- [x] 4.4 確認 `UserRepository::findByIdWithRoles()` 的 `SELECT` 也不使用 `*`
- [x] 4.5 撰寫測試：批次載入標籤的查詢次數驗證
- [x] 4.6 移除 `PostCrudRepository::assignTags()` 中對 `tagsExist()` 的呼叫；若外鍵約束失敗則讓資料庫層級的事務回滾處理
- [x] 4.7 撰寫測試：模擬標籤被刪除時 `assignTags()` 正確回滾事務

## 5. CSP 與供應鏈安全（csp-supply-chain）

- [x] 5.1 計算 Tailwind CDN 的 SRI integrity hash，加入 `index.html` 的 `<script>` 標籤
- [x] 5.2 在 `SecurityHeaderService` 的 CSP 設定中，`script-src` 與 `style-src` 加入 `https://cdn.tailwindcss.com`
- [x] 5.3 將 `postEditor.js` 中 `innerHTML` 直接插入 `tag.name` 的程式碼改為使用 `escapeHtml(tag.name)`；確認已從 `../../utils/security.js` 匯入 `escapeHtml`

## 6. 驗證

- [x] 6.1 執行 `composer test` 確認所有測試通過
- [x] 6.2 執行 `composer analyse`（PHPStan Level 10）確認無新增型別錯誤
- [x] 6.3 執行 `npm run lint` 確認前端程式碼風格一致
