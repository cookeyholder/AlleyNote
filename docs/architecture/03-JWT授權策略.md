# JWT 授權策略模式

## 設計動機

`JwtAuthorizationMiddleware` 原始設計將 5 種授權機制緊密耦合：

1. 角色授權（RBAC）
2. 直接權限授權
3. 屬性授權（時間 + 資源擁有者）
4. 自訂規則授權
5. 超級管理員略過

這些機制共用 Middleware 的生命週期與請求處理，但邏輯彼此獨立。當需要調整策略順序、新增策略、或獨立測試某一策略時，必須修改整個 Middleware。

## 策略模式架構

```
JwtAuthorizationMiddleware（瘦身）
  │
  └── AuthorizationOrchestratorService（協調器）
        │
        ├── SuperAdminAuthorizationStrategy
        │     └── 超級管理員（super_admin）→ 直接允許
        │
        ├── RoleAuthorizationStrategy
        │     └── RBAC：角色→權限映射表查詢
        │
        ├── PermissionAuthorizationStrategy
        │     └── 直接權限：user_permissions 查詢
        │
        ├── AttributeAuthorizationStrategy
        │     └── 時間限制 + 資源擁有者檢查
        │
        └── CustomRuleAuthorizationStrategy
              └── 自訂規則（allow/deny/conditional）
```

### 短路評估

策略依序執行，任一策略回傳 `allowed: true` 即立即跳過後續策略：

```
foreach ($this->strategies as $strategy) {
    $result = $strategy->evaluate($context);
    if ($result->isAllowed()) {
        return $result;  // 短路
    }
}
return AuthorizationResult::deny('all_strategies_rejected');
```

## 核心類別

### AuthorizationResult（ValueObject）

```php
AuthorizationResult::allow(string $strategy, ?string $reason = null): self
AuthorizationResult::deny(string $reason): self
// 方法：isAllowed(), getStrategy(), getReason(), getContext()
```

### AuthorizationContext（DTO）

```php
new AuthorizationContext(
    userId: ?int,
    userRole: string,
    userPermissions: array,
    resource: string,      // 如 'posts', 'users'
    action: string,         // 如 'create', 'edit'
    request: ?ServerRequestInterface,
);
```

### AuthorizationStrategyInterface

```php
interface AuthorizationStrategyInterface {
    public function evaluate(AuthorizationContext $context): AuthorizationResult;
}
```

## 各策略說明

| 策略 | 職責 | 查詢來源 | 適用場景 |
|------|------|----------|----------|
| `SuperAdminAuthorizationStrategy` | 超級管理員略過所有檢查 | `$context->userRole` | `super_admin` 角色 |
| `RoleAuthorizationStrategy` | 根據角色權限映射表判斷 | `$rolePermissions` 設定檔 | RBAC 基本授權 |
| `PermissionAuthorizationStrategy` | 根據直接指派權限判斷 | `user_permissions` 資料表 | 角色權限例外 |
| `AttributeAuthorizationStrategy` | 時間範圍 + 資源擁有者 | `$timeRestrictions` + 資料庫 | 限時公告、只能編輯自己的公告 |
| `CustomRuleAuthorizationStrategy` | 自訂條件規則 | `$customRules` 設定檔 | 複雜授權情境 |

## 授權模型：RBAC + 直接權限混合

AlleyNote 使用混合授權模型，在 `AuthorizationOrchestratorService` 中依序執行策略：

1. **角色權限（Role-based）**：`RoleAuthorizationStrategy` 查詢角色→權限映射表（RBAC）
2. **直接權限（Direct Permission）**：`PermissionAuthorizationStrategy` 查詢使用者直接指派的權限

兩者在 `AuthorizationOrchestratorService` 中依序執行，任一策略允許即短路回傳。這讓管理員可以對特定使用者「額外開放」某項權限，而不需要變更其角色。

## JwtAuthorizationMiddleware 瘦身前後

**Before**（~600 行）：
- `authorize()` → 內嵌策略鏈邏輯
- `authorizeByRole()` / `authorizeByPermission()` / `authorizeByAttributes()` / `authorizeByCustomRules()` / `isSuperAdmin()`
- 輔助方法：`checkTimeBasedAccess()`、`checkResourceOwnership()`、`isResourceOwner()`、`executeCustomRule()`、`evaluateConditionalRule()`、`matchesIpRestriction()`
- 配置資料與路由邏輯夾雜

**After**（~100 行）：
- `authorize()` → `$orchestrator->authorize($context)`
- 保留：`shouldProcess()`、`extractResource()`、`extractAction()`、`extractResourceId()`、`injectAuthorizationContext()`、`createForbiddenResponse()`
- 配置僅含 `skip_paths`、`auth_paths` 等路由相關設定

## 相關檔案

```
backend/app/Domains/Auth/Services/Authorization/
├── AuthorizationStrategyInterface.php
├── AuthorizationContext.php
├── AuthorizationOrchestratorService.php
├── SuperAdminAuthorizationStrategy.php
├── RoleAuthorizationStrategy.php
├── PermissionAuthorizationStrategy.php
├── AttributeAuthorizationStrategy.php
└── CustomRuleAuthorizationStrategy.php

backend/app/Domains/Auth/ValueObjects/
└── AuthorizationResult.php

backend/app/Application/Middleware/
└── JwtAuthorizationMiddleware.php

backend/config/
└── container.php
```
