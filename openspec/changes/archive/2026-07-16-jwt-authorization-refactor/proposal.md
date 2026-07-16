## Why

`JwtAuthorizationMiddleware`（1023 行、34 個方法）已成長為一個巨型類別，將 RBAC、ABAC、IP 基礎、時間基礎、資源擁有者檢查、自訂規則和配置管理全部塞進單一 PSR-15 中介軟體，違反單一職責原則，導致難以測試、維護與擴展。此外 `AuthorizationResult` 這個乾淨的值物件位於 Application 層而非 Domain 層，產生分層混淆。

## What Changes

- **Strategy 模式**：將 4 種授權策略萃取為獨立類別（角色、權限、屬性、自訂規則），各自實作共通的 `AuthorizationStrategyInterface`
- **授權協調器**：新增 `AuthorizationOrchestratorService` 作為策略執行協調中心，取代 `JwtAuthorizationMiddleware::authorize()` 中的大量條件鏈
- **值物件搬遷**：將 `AuthorizationResult` 從 `Application\Middleware` 移至 `Domains\Auth\ValueObjects`
- **中介軟體瘦身**：`JwtAuthorizationMiddleware` 從 ~1023 行縮減至 ~200 行，僅保留 PSR-15 進入點與請求預處理
- **可測試性**：每個策略可獨立單元測試，無需模擬整個中介軟體
- **注意**：IP 相關方法（`checkIpBasedAccess`、`isIpInList`、`ipMatches`）已由先前的 `ip-logic-unification` change 萃取至 `NetworkHelper`，本 change 不處理
- **注意**：`Domains\Auth\Services\AuthorizationService`（角色/權限 CRUD）已存在，新授權協調器另命名為 `AuthorizationOrchestratorService` 以避免類別名稱衝突

## Capabilities

### New Capabilities
- `authorization-strategies`: Strategy 模式實作，4 種授權策略類別與共通的策略介面
- `authorization-orchestrator`: 授權協調服務，負責載入、排序與執行策略鏈，產出 `AuthorizationResult`

### Modified Capabilities
- `auth-service-consolidation`: 規格中的 `AuthorizationService` 名稱需調整為 `AuthorizationOrchestratorService`，以反映其真正的協調職責

## Impact

- **檔案搬遷**：`AuthorizationResult.php` 從 `Application/Middleware/` 移至 `Domains/Auth/ValueObjects/`（**BREAKING**）
- **中介軟體重構**：`JwtAuthorizationMiddleware.php` 大幅縮減，授權邏輯移至新策略類別
- **DI 容器**：`config/container.php` 需註冊新策略類別與協調器
- **路由配置**：`config/routes/*` 中的中介軟體參照可能需更新
- **測試**：現有測試若直接依賴 `JwtAuthorizationMiddleware` 的內部方法（如 `authorizeByRole`）需一併更新
