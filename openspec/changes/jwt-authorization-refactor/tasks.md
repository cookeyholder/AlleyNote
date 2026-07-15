## 1. 基礎架構建立

- [ ] 1.1 在 `Domains/Auth/ValueObjects/` 建立 `AuthorizationResult.php`，從 `Application/Middleware/AuthorizationResult.php` 搬遷，更新命名空間為 `App\Domains\Auth\ValueObjects`
- [ ] 1.2 在 `Domains/Auth/Services/Authorization/` 建立 `AuthorizationContext.php` DTO，包含 `userId`、`userRole`、`userPermissions`、`resource`、`action`、`request` 屬性
- [ ] 1.3 在 `Domains/Auth/Services/Authorization/` 建立 `AuthorizationStrategyInterface.php`，定義 `evaluate(AuthorizationContext): AuthorizationResult` 方法
- [ ] 1.4 在舊位置留下 `AuthorizationResult` 的 deprecated alias 類別（指向新命名空間），觸發 `E_USER_DEPRECATED` 警告

## 2. 授權策略類別萃取

- [ ] 2.1 從 `JwtAuthorizationMiddleware::authorizeByRole()` 萃取 `RoleAuthorizationStrategy`，實作 `AuthorizationStrategyInterface`
- [ ] 2.2 從 `JwtAuthorizationMiddleware::authorizeByPermission()` 萃取 `PermissionAuthorizationStrategy`，實作 `AuthorizationStrategyInterface`
- [ ] 2.3 從 `JwtAuthorizationMiddleware::authorizeByAttributes()` 萃取 `AttributeAuthorizationStrategy`，保留時間檢查與資源擁有者檢查，去除 IP 檢查（已由 `ip-logic-unification` 處理）。同時萃取 `checkResourceOwnership()`、`isResourceOwner()`、`checkTimeBasedAccess()`、`matchesTimeRestriction()` 等輔助方法
- [ ] 2.4 從 `JwtAuthorizationMiddleware::authorizeByCustomRules()` 萃取 `CustomRuleAuthorizationStrategy`，保留規則匹配與條件式規則評估邏輯
- [ ] 2.5 從 `JwtAuthorizationMiddleware::isSuperAdmin()` 萃取超級管理員檢查邏輯，可整合至 `RoleAuthorizationStrategy` 或獨立的 `SuperAdminAuthorizationStrategy`

## 3. 授權協調服務

- [ ] 3.1 在 `Domains/Auth/Services/` 建立 `AuthorizationOrchestratorService.php`，接收策略實例陣列並依序執行
- [ ] 3.2 實作短路評估邏輯：任一策略回傳 `allowed: true` 即跳過後續策略
- [ ] 3.3 所有策略拒絕時回傳預設拒絕結果

## 4. JwtAuthorizationMiddleware 瘦身

- [ ] 4.1 將 `JwtAuthorizationMiddleware` 的建構子改為注入 `AuthorizationOrchestratorService`，同時保留精簡後的 `$config`（僅含 `skip_paths`、`auth_paths` 等路由相關設定，供 `shouldProcess()` 使用）。授權相關配置（`role_permissions`、`ownership_rules`、`time_restrictions`、`ip_restrictions`、`custom_rules`）移至 `AuthorizationOrchestratorService` 或對應策略類別
- [ ] 4.2 將 `authorize()` 方法中的策略鏈呼叫替換為 `AuthorizationOrchestratorService::authorize()` 單一呼叫
- [ ] 4.3 保留 `shouldProcess()`、`extractResource()`、`extractAction()`、`extractResourceId()`、`injectAuthorizationContext()`、`createForbiddenResponse()` 作為私有或 protected 方法
- [ ] 4.4 移除以下已萃取的方法：`authorizeByRole()`、`authorizeByPermission()`、`authorizeByAttributes()`、`authorizeByCustomRules()`、`isSuperAdmin()`、`checkTimeBasedAccess()`、`checkResourceOwnership()`、`isResourceOwner()`、`executeCustomRule()`、`evaluateConditionalRule()`、`matchesTimeRestriction()`、`matchesRuleConditions()`、`getDefaultConfig()`

## 5. DI 容器更新

- [ ] 5.1 在 `config/container.php` 註冊 `AuthorizationOrchestratorService`，注入 4 個策略實例
- [ ] 5.2 註冊 `RoleAuthorizationStrategy`、`PermissionAuthorizationStrategy`、`AttributeAuthorizationStrategy`、`CustomRuleAuthorizationStrategy`
- [ ] 5.3 更新 `JwtAuthorizationMiddleware` 的 DI 定義，注入 `AuthorizationOrchestratorService` 取代 `$config`
- [ ] 5.4 如有需要，更新 `config/routes/*` 中的中介軟體參照

## 6. 測試撰寫

- [ ] 6.1 為 `RoleAuthorizationStrategy` 撰寫單元測試（角色通配符、特定權限、不足權限情境）
- [ ] 6.2 為 `PermissionAuthorizationStrategy` 撰寫單元測試（通配符權限、特定權限、不足權限情境）
- [ ] 6.3 為 `AttributeAuthorizationStrategy` 撰寫單元測試（時間限制、資源擁有者情境）
- [ ] 6.4 為 `CustomRuleAuthorizationStrategy` 撰寫單元測試（allow/deny/conditional 規則）
- [ ] 6.5 為 `AuthorizationOrchestratorService` 撰寫單元測試（短路評估、自訂策略順序、預設拒絕）
- [ ] 6.6 為瘦身後的 `JwtAuthorizationMiddleware` 撰寫整合測試（確認行為與重構前一致）
- [ ] 6.7 更新現有測試中對 `JwtAuthorizationMiddleware` 的 mock 與斷言

## 7. 驗證與清理

- [ ] 7.1 執行 `composer analyse`（PHPStan Level 10）確認無靜態分析錯誤
- [ ] 7.2 執行 `composer test` 確認所有測試通過
- [ ] 7.3 執行 `composer cs-check` 確認符合程式碼風格標準
- [ ] 7.4 匯入 `ip-logic-unification` change 的最新狀態，確認所有 5 個 IP 方法（`checkIpBasedAccess`、`getClientIpAddress`、`isIpInList`、`ipMatches`、`matchesIpRestriction`）已從 `JwtAuthorizationMiddleware` 正確移除並萃取至 `NetworkHelper`
