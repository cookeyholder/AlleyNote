## ADDED Requirements

### Requirement: AuthService 標記為已廢棄
`AuthService` SHALL 標記 `@deprecated` PHPDoc，且在類別頂部加入 `trigger_error('...AuthService is deprecated...', E_USER_DEPRECATED)`，指引使用者改用 `AuthenticationService`。

#### Scenario: 任何方法呼叫觸發 deprecated 警告
- **WHEN** 直接實例化 `AuthService` 並呼叫 `login()` 方法
- **THEN** PHP 觸發 `E_USER_DEPRECATED` 等級的警告

#### Scenario: PHPStan 掃描時識別廢棄使用點
- **WHEN** 執行 `phpstan` 靜態分析
- **THEN** 任何直接使用 `AuthService` 的程式碼被標示為使用廢棄 API

### Requirement: AuthService 功能等效性保留
在最終移除前，`AuthService::register()`、`login()`、`logout()` SHALL 產生與之前相同的回傳結構，確保不破壞任何依賴舊格式的測試或整合程式碼。

#### Scenario: register() 有 JWT 時回傳含 tokens 的陣列
- **WHEN** 帶有有效 JWT 服務呼叫 `register()`
- **THEN** 回傳包含 `success`、`message`、`user`、`tokens` 鍵的陣列

#### Scenario: login() 無 JWT 時回傳傳統格式
- **WHEN** 不帶 JWT 服務呼叫 `login()`
- **THEN** 回傳包含 `success`、`message`、`user` 鍵的陣列（不含 `tokens`）

### Requirement: AuthenticationService.login() 步驟分離為私有方法
`AuthenticationService::login()` SHALL 將以下步驟委派給私有方法：
- 使用者狀態驗證（`validateUserStatus`）
- Token 數量限制執行（`enforceTokenLimit`）
- 使用者角色解析（`resolveUserRole`）

確保 `login()` 方法本身只包含高階流程協調邏輯，無超過 30 行的實作細節。

#### Scenario: 帳號已停用時拋出 AuthenticationException
- **WHEN** `login()` 呼叫 `validateUserStatus()` 且使用者 `deleted_at` 非空
- **THEN** 拋出 `AuthenticationException`，reason 為 `REASON_ACCOUNT_DISABLED`

#### Scenario: Token 數量超限時撤銷最舊 token
- **WHEN** `login()` 呼叫 `enforceTokenLimit()` 且活躍 token 數量 >= 50
- **THEN** 最舊的 refresh token 被撤銷，reason 為 `max_tokens_exceeded`

#### Scenario: 無角色使用者正常登入
- **WHEN** `resolveUserRole()` 取得的 `roles` 陣列為空
- **THEN** 回傳 `null`（無角色），登入流程繼續正常執行
