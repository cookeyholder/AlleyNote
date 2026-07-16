## ADDED Requirements

### Requirement: AuthorizationStrategyInterface 定義
系統 SHALL 定義 `AuthorizationStrategyInterface` 作為所有授權策略的共通契約，包含單一 `evaluate()` 方法，接受 `AuthorizationContext` DTO 並回傳 `AuthorizationResult`。

#### Scenario: 實作介面的策略類別可被協調器一致呼叫
- **WHEN** 任何實作 `AuthorizationStrategyInterface` 的策略類別被傳入 `AuthorizationOrchestratorService`
- **THEN** 協調器可透過多型呼叫其 `evaluate()` 方法

### Requirement: RoleAuthorizationStrategy 萃取
系統 SHALL 從 `JwtAuthorizationMiddleware::authorizeByRole()` 萃取 `RoleAuthorizationStrategy`，負責基於角色的授權檢查（RBAC），包含角色通配符權限與特定權限比對。

#### Scenario: 超級管理員角色通過檢查
- **WHEN** 使用者角色為 `admin`
- **THEN** 回傳 `allowed: true`，code 為 `SUPER_ADMIN_ACCESS`

#### Scenario: 角色擁有資源通配符權限
- **WHEN** 使用者角色為 `moderator`，角色權限包含 `posts.*`
- **THEN** 對 `posts` 資源的任何操作回傳 `allowed: true`，code 為 `ROLE_WILDCARD_ACCESS`

#### Scenario: 角色缺乏權限
- **WHEN** 使用者角色為 `user`，嘗試對 `posts` 執行 `delete` 操作
- **THEN** 回傳 `allowed: false`，code 為 `ROLE_INSUFFICIENT`

### Requirement: PermissionAuthorizationStrategy 萃取
系統 SHALL 從 `JwtAuthorizationMiddleware::authorizeByPermission()` 萃取 `PermissionAuthorizationStrategy`，負責基於權限的授權檢查。

#### Scenario: 使用者擁有通配符權限
- **WHEN** 使用者權限包含 `*`
- **THEN** 所有資源與操作皆回傳 `allowed: true`，code 為 `PERMISSION_WILDCARD_ACCESS`

#### Scenario: 使用者擁有特定權限
- **WHEN** 使用者權限包含 `posts.create`
- **THEN** 對 `posts.create` 操作回傳 `allowed: true`，code 為 `PERMISSION_SPECIFIC_ACCESS`

#### Scenario: 使用者缺乏所需權限
- **WHEN** 使用者沒有 `posts.delete` 權限
- **THEN** 回傳 `allowed: false`，code 為 `PERMISSION_INSUFFICIENT`

### Requirement: AttributeAuthorizationStrategy 萃取
系統 SHALL 從 `JwtAuthorizationMiddleware::authorizeByAttributes()` 萃取 `AttributeAuthorizationStrategy`，負責基於屬性的授權檢查，包含時間基礎與資源擁有者檢查。IP 檢查已由 `ip-logic-unification` 處理，本策略不包含。

#### Scenario: 時間限制違規
- **WHEN** 當前時間不在允許操作的時間範圍內
- **THEN** 回傳 `allowed: false`，code 為 `TIME_RESTRICTION_VIOLATED`

#### Scenario: 時間檢查通過
- **WHEN** 無時間限制配置或當前時間在允許範圍內
- **THEN** 繼續評估後續屬性條件

#### Scenario: 資源擁有者檢查通過
- **WHEN** 操作為 `update` 或 `delete`，且使用者為資源擁有者
- **THEN** 回傳 `allowed: true`，code 為 `RESOURCE_OWNER_ACCESS`

### Requirement: CustomRuleAuthorizationStrategy 萃取
系統 SHALL 從 `JwtAuthorizationMiddleware::authorizeByCustomRules()` 萃取 `CustomRuleAuthorizationStrategy`，負責評估自訂規則。

#### Scenario: 自訂規則允許存取
- **WHEN** 請求符合自訂規則的條件，且規則類型為 `allow`
- **THEN** 回傳 `allowed: true`，code 為 `CUSTOM_RULE_ALLOW`

#### Scenario: 自訂規則拒絕存取
- **WHEN** 請求符合自訂規則的條件，且規則類型為 `deny`
- **THEN** 回傳 `allowed: false`，code 為 `CUSTOM_RULE_DENY`

#### Scenario: 無匹配的自訂規則
- **WHEN** 沒有任何自訂規則的條件與請求匹配
- **THEN** 回傳 `allowed: false`，code 為 `NO_CUSTOM_RULE`

### Requirement: AuthorizationContext DTO
系統 SHALL 新增 `AuthorizationContext` 作為策略評估的標準化輸入 DTO，包含 `userId`、`userRole`、`userPermissions`、`resource`、`action`、`request` 等欄位。

#### Scenario: AuthorizationContext 包含所有必要欄位
- **WHEN** 建立 `AuthorizationContext` 實例並傳入完整參數
- **THEN** 可透過 getter 取得 `userId`、`userRole`、`userPermissions`、`resource`、`action` 與 `request`
