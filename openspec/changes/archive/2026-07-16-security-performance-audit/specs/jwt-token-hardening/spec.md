## ADDED Requirements

### Requirement: 移除 JWT Debug 日誌
系統在正式環境中不得將 JWT token、金鑰資訊或除錯狀態寫入 `php://stderr`。僅在 `APP_ENV=development` 時可輸出除錯資訊。

#### Scenario: 正式環境無 JWT 日誌輸出
- **WHEN** `APP_ENV=production` 且任一 API 請求包含 JWT token
- **THEN** `FirebaseJwtProvider::validateToken()` 不寫入任何 `file_put_contents('php://stderr', ...)` 呼叫

#### Scenario: 開發環境保留日誌
- **WHEN** `APP_ENV=development` 且任一 API 請求包含 JWT token
- **THEN** `FirebaseJwtProvider::validateToken()` 仍輸出除錯資訊至 `php://stderr`

### Requirement: 角色撤銷即時生效
系統必須在使用者角色被變更後的合理時間內（小於 access token TTL），使該使用者的 JWT 失效。實作方式為比對 JWT 的 `iat` 宣告與使用者紀錄中的 `role_updated_at` 時間戳。

#### Scenario: 角色降級後拒絕存取
- **WHEN** 使用者的角色在資料庫中被從 `admin` 降級為 `editor`
- **AND** 該使用者使用降級前簽發的 JWT token 呼叫管理端點
- **AND** JWT 的 `iat` 早於 `role_updated_at`
- **THEN** 系統拒絕請求，回傳 403 Forbidden，並提示需要重新登入

#### Scenario: 角色未變更時正常存取
- **WHEN** 使用者的角色未曾變更
- **AND** 該使用者使用有效 JWT token 呼叫端點
- **AND** JWT 的 `iat` 晚於 `role_updated_at`
- **THEN** 系統正常處理請求，不中斷

#### Scenario: 新註冊使用者無 `role_updated_at` 時視為通過
- **WHEN** 使用者無 `role_updated_at` 欄位（舊資料或新註冊）
- **THEN** 系統視為角色時效檢查通過，不拒絕請求

### Requirement: JWT 不包含 PII
JWT payload 不得包含 email 等個人識別資訊（PII）。email 應從使用者儲存庫依需求取得，而非嵌入 token 中。

#### Scenario: JWT 無 email 宣告
- **WHEN** `AuthenticationService` 建立 JWT token
- **THEN** 產生的 JWT payload 不包含 `email` 欄位

### Requirement: JTI 長度最佳化
`generateJti()` 產生的 JTI 長度不得超過 36 個字元（UUID v4 格式），以減少儲存空間與索引大小。

#### Scenario: JTI 使用 UUID v4 格式
- **WHEN** `FirebaseJwtProvider::generateJti()` 被呼叫
- **THEN** 回傳值符合 UUID v4 格式（`xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`）
