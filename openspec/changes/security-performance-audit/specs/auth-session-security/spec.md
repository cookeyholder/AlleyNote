## ADDED Requirements

### Requirement: 認證 Cookie SameSite=Strict
所有認證相關 Cookie（`access_token`、`refresh_token`）的 `SameSite` 屬性必須設為 `Strict`，防止跨站請求挾帶認證 Cookie。

#### Scenario: 認證 Cookie 使用 SameSite=Strict
- **WHEN** `AuthController::buildCookieHeader()` 建立認證 Cookie
- **THEN** Cookie 的 `SameSite` 屬性為 `Strict`

#### Scenario: 第三方站點無法挾帶認證 Cookie
- **WHEN** 使用者已登入且從外部網站導覽至 AlleyNote
- **THEN** 瀏覽器不傳送認證 Cookie

### Requirement: 移除 localStorage Refresh Token
前端不得將 refresh token 儲存在 `localStorage` 中。refresh token 應僅透過 HttpOnly Cookie 傳遞與儲存。

#### Scenario: 無 refresh token 寫入 localStorage
- **WHEN** `api/client.js` 處理認證回應
- **THEN** 不寫入 `localStorage.setItem('refresh_token', ...)` 或類似操作

#### Scenario: 使用 HttpOnly Cookie 儲存 refresh token
- **WHEN** 後端簽發 refresh token
- **THEN** refresh token 僅以 `HttpOnly`、`Secure`、`SameSite=Strict` Cookie 形式回傳

### Requirement: CSRF Token 補發端點
系統必須提供 `GET /csrf-token` 端點，讓前端在 CSRF Cookie 遺失時能夠重新取得。

#### Scenario: CSRF Cookie 遺失時可重新取得
- **WHEN** 前端呼叫 `GET /csrf-token`
- **THEN** 伺服器回傳新的 CSRF token 並設定新的 CSRF Cookie
- **AND** 回應狀態碼為 200

#### Scenario: CSRF Token 補發端點不需認證
- **WHEN** 未登入使用者呼叫 `GET /csrf-token`
- **THEN** 伺服器仍回傳 CSRF token（因登入頁面的 CSRF 保護也需要 token）
