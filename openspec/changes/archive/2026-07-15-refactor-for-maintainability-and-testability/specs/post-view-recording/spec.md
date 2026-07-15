## ADDED Requirements

### Requirement: IP 位址驗證為純函數
`PostValidator` SHALL 提供 `public static function isValidIp(string $ip): bool` 純函數，封裝 IPv4/IPv6 的格式驗證邏輯，使之可在不實例化 validator 的情況下被單獨測試與呼叫。

#### Scenario: 有效 IPv4 通過驗證
- **WHEN** 以 `'192.168.1.1'` 呼叫 `PostValidator::isValidIp()`
- **THEN** 回傳 `true`

#### Scenario: 有效 IPv6 通過驗證
- **WHEN** 以 `'::1'` 呼叫 `PostValidator::isValidIp()`
- **THEN** 回傳 `true`

#### Scenario: 無效 IP 字串回傳 false
- **WHEN** 以 `'not-an-ip'` 呼叫 `PostValidator::isValidIp()`
- **THEN** 回傳 `false`

#### Scenario: 空字串回傳 false
- **WHEN** 以 `''` 呼叫 `PostValidator::isValidIp()`
- **THEN** 回傳 `false`

### Requirement: PostService.recordView() 委派 IP 驗證
`PostService::recordView()` SHALL 委派 IP 格式驗證給 `PostValidator::isValidIp()`，而非內嵌 `filter_var` 呼叫，確保驗證邏輯只有一個來源。

#### Scenario: 無效 IP 觸發 ValidationException
- **WHEN** 以無效 IP 呼叫 `recordView()`
- **THEN** 拋出 `ValidationException`，且錯誤欄位為 `user_ip`

#### Scenario: 有效 IP 允許繼續業務流程
- **WHEN** 以有效 IP 呼叫 `recordView()`
- **THEN** 繼續執行 post 狀態檢查與 view count 遞增流程

### Requirement: PostValidator 規則方法為可測試的純函數
`PostValidator` 中的驗證邏輯 SHALL 提取為 `private static` 具名方法（`validatePostStatus`、`validateRfc3339Datetime`、`validatePostTitle`、`validatePostContent`、`validateUserId`、`validateIpAddress`、`validatePublishDateFuture`），讓自動化測試可透過測試子類別或 reflection 直接呼叫個別規則。

#### Scenario: 單一規則邊界案例可獨立測試
- **WHEN** 測試以 null 輸入 `validatePostStatus` 規則方法
- **THEN** 回傳 `true`（允許 null，由 required 規則處理）

#### Scenario: 規則方法對非字串輸入回傳 false
- **WHEN** 以整數 `123` 呼叫 `validatePostStatus` 規則方法
- **THEN** 回傳 `false`
