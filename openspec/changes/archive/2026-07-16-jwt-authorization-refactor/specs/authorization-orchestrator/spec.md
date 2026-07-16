## ADDED Requirements

### Requirement: AuthorizationOrchestratorService 策略協調
系統 SHALL 新增 `AuthorizationOrchestratorService`，負責載入已註冊的授權策略、依序執行並彙總結果。執行順序為：超級管理員豁免 → `RoleAuthorizationStrategy` → `PermissionAuthorizationStrategy` → `AttributeAuthorizationStrategy` → `CustomRuleAuthorizationStrategy`，任一策略允許即短路回傳。

#### Scenario: 超級管理員跳過其餘策略
- **WHEN** 使用者角色為 `admin` 且存在多個拒絕規則
- **THEN** `AuthorizationOrchestratorService` 立即回傳 `allowed: true`，不執行其他策略

#### Scenario: 角色策略允許後跳過權限策略
- **WHEN** `RoleAuthorizationStrategy` 回傳 `allowed: true`
- **THEN** `AuthorizationOrchestratorService` 不繼續執行 `PermissionAuthorizationStrategy`

#### Scenario: 所有策略拒絕時回傳預設拒絕
- **WHEN** 所有 4 個策略皆回傳 `allowed: false`
- **THEN** `AuthorizationOrchestratorService` 回傳 `allowed: false`，code 為 `INSUFFICIENT_PERMISSIONS`，`appliedRules` 包含 `['default_deny']`

#### Scenario: 可注入自訂策略清單
- **WHEN** 透過建構子傳入策略實例陣列
- **THEN** `AuthorizationOrchestratorService` 依照陣列順序執行策略

### Requirement: JwtAuthorizationMiddleware 瘦身
`JwtAuthorizationMiddleware` SHALL 將其 `process()` 方法中的授權邏輯委派給 `AuthorizationOrchestratorService`，僅保留請求預處理（`shouldProcess`、`extractResource`、`extractAction`）與回應處理（`createForbiddenResponse`、`injectAuthorizationContext`）。

#### Scenario: 中介軟體委派授權給協調器
- **WHEN** `JwtAuthorizationMiddleware::process()` 執行授權
- **THEN** 調用 `AuthorizationOrchestratorService::authorize()`，而非直接呼叫私有策略方法

#### Scenario: 策略評估失敗回傳 403
- **WHEN** `AuthorizationOrchestratorService::authorize()` 回傳 `allowed: false`
- **THEN** `JwtAuthorizationMiddleware` 回傳 HTTP 403 回應，內容包含 `success: false`、`error`、`code`、`timestamp`

### Requirement: AuthorizationResult 搬遷
`AuthorizationResult` SHALL 從 `App\Application\Middleware` 命名空間搬遷至 `App\Domains\Auth\ValueObjects`，保留所有既有方法與 `JsonSerializable` 實作。

#### Scenario: 新命名空間可正常 autoload
- **WHEN** 任何類別引用 `App\Domains\Auth\ValueObjects\AuthorizationResult`
- **THEN** PHP autoloader 正確載入

#### Scenario: 向後相容保留 alias（選擇性）
- **WHEN** 舊命名空間 `App\Application\Middleware\AuthorizationResult` 被引用
- **THEN** 如實作 deprecated alias，觸發 `E_USER_DEPRECATED` 警告
