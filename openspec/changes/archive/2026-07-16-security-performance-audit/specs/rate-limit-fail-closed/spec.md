## ADDED Requirements

### Requirement: Redis 失效時限制拒絕請求
當 Redis 連線失敗或擲回例外時，`RateLimitService` 必須回傳 `allowed => false`（拒絕請求），而非 `allowed => true`（放行請求）。

#### Scenario: Redis 不可用時拒絕登入請求
- **WHEN** Redis 伺服器無法連線
- **AND** 使用者嘗試登入
- **THEN** `RateLimitService::checkRateLimit()` 擲回例外
- **AND** 中介層攔截例外
- **AND** 回傳 `allowed => false`
- **AND** 回傳 429 Too Many Requests 或 500 Internal Server Error

#### Scenario: Redis 恢復後正常運作
- **WHEN** Redis 伺服器恢復連線
- **AND** 使用者嘗試登入
- **THEN** `RateLimitService::checkRateLimit()` 正常檢查速率限制
- **AND** 若未超過限制則 `allowed => true`

#### Scenario: Redis 失效時記錄警報
- **WHEN** `RateLimitService` 偵測到 Redis 連線失敗
- **THEN** 使用 `app_log('error', ...)` 記錄錯誤
- **AND** 應可觸發監控告警
