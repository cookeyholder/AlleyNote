# ADR-4: JWT 授權策略模式

**狀態**: 已完成
**日期**: 2026-07-15

## 背景

`JwtAuthorizationMiddleware` 原始實作約 600 行，一口氣包含多種授權機制的判斷邏輯：

1. 角色授權（`authorizeByRole`）
2. 權限授權（`authorizeByPermission`）
3. 屬性授權（`authorizeByAttributes`：時間限制、資源擁有者、IP 限制）
4. 自訂規則授權（`authorizeByCustomRules`）
5. 超級管理員檢查（`isSuperAdmin`）

這些邏輯緊密耦合在同一個 Middleware 中，無法獨立測試，也無法調整策略執行順序。Middleware 同時需載入所有授權相關的配置資料（角色權限映射、擁有者規則、時間限制等），違反單一職責。

## 決策

採用 Strategy 模式，萃取 5 個授權策略類別 + 1 個 Orchestrator：

- `AuthorizationStrategyInterface`：定義 `evaluate(AuthorizationContext): AuthorizationResult` 契約
- `RoleAuthorizationStrategy`：角色→權限映射查詢
- `PermissionAuthorizationStrategy`：直接授予權限查詢
- `AttributeAuthorizationStrategy`：時間限制 + 資源擁有者檢查
- `CustomRuleAuthorizationStrategy`：自訂規則評估（allow/deny/conditional）
- `SuperAdminAuthorizationStrategy`：超級管理員略過檢查
- `AuthorizationOrchestratorService`：依序執行策略，短路評估（任一策略允許即回傳）

同步建立：
- `AuthorizationResult` ValueObject：從 `Application/Middleware/AuthorizationResult` 搬遷至 `Domains/Auth/ValueObjects`
- `AuthorizationContext` DTO：封裝 `userId`、`userRole`、`userPermissions`、`resource`、`action`、`request`

## 結果

**變更檔案**：
- 新增：`Domains/Auth/Services/Authorization/AuthorizationStrategyInterface.php`
- 新增：`Domains/Auth/Services/Authorization/RoleAuthorizationStrategy.php`
- 新增：`Domains/Auth/Services/Authorization/PermissionAuthorizationStrategy.php`
- 新增：`Domains/Auth/Services/Authorization/AttributeAuthorizationStrategy.php`
- 新增：`Domains/Auth/Services/Authorization/CustomRuleAuthorizationStrategy.php`
- 新增：`Domains/Auth/Services/Authorization/SuperAdminAuthorizationStrategy.php`
- 新增：`Domains/Auth/Services/Authorization/AuthorizationOrchestratorService.php`
- 新增：`Domains/Auth/Services/Authorization/AuthorizationContext.php`
- 搬遷：`AuthorizationResult` ValueObject（舊位置留 deprecated alias）
- 修改：`JwtAuthorizationMiddleware` — 從 ~600 行瘦身至 ~100 行（僅保留路由判斷與請求處理）
- 修改：`config/container.php` — 註冊所有策略與 Orchestrator

**對比**：
- **Before**：1 個 Middleware 600 行，5 種授權機制耦合，無法單獨測試
- **After**：8 個類別（~500 行總計），每個策略可獨立測試，可任意組合順序

**測試**：每個策略獨立單元測試 + Orchestrator 短路評估測試 + Middleware 整合測試。

## 替代方案

1. **維持單一 Middleware**：不萃取 — 拒絕原因：違反 SRP，類別過大，難以維護與測試。
2. **Decorator 模式**：撰寫多個 Middleware 各自檢查不同維度 — 拒絕原因：授權判斷需共享上下文（同一 Request），分散至多個 Middleware 會重複解析。
3. **AOP 切入**：使用 PHP 屬性實作宣告式授權 — 拒絕原因：專案無 AOP 基礎設施，引入成本過高。

## 取捨

- 檔案數量增加，但新增策略或調整執行順序無需修改既有策略類別
- `AuthorizationOrchestratorService` 為同步執行，若未來需非同步授權（如外部服務檢查）需實作 `AuthorizationStrategyInterface` 的非同步版本
