## Context

`JwtAuthorizationMiddleware` 是一個 1023 行的 PSR-15 中介軟體，包含：
- 4 種授權機制（角色、權限、屬性、自訂規則）直接內嵌為私有方法
- 超級管理員豁免邏輯
- 時間/IP/資源擁有者檢查交織在屬性檢查中
- `shouldProcess()` 、 `extractResource()` 、 `extractAction()` 等請求預處理邏輯
- `createForbiddenResponse()` 、 `injectAuthorizationContext()` 等回應處理

`AuthorizationResult`（294 行）是一個完善的 readonly value object，但位於 `Application\Middleware` 命名空間，不屬於該層。Domain 層已有 `Domains\Auth\Services\AuthorizationService`（角色/權限 CRUD 服務），需注意名稱衝突。

變更前提：`ip-logic-unification` change 已先執行，將所有 IP 比對邏輯（`checkIpBasedAccess`、`isIpInList`、`ipMatches`、`getClientIpAddress`）萃取至 `NetworkHelper`。

## Goals / Non-Goals

**Goals:**
- 將 4 種授權策略萃取為獨立類別，遵循 Strategy 模式
- 新增 `AuthorizationOrchestratorService` 作為策略協調中心
- `AuthorizationResult` 搬遷至 `Domains\Auth\ValueObjects`
- `JwtAuthorizationMiddleware` 瘦身至約 200 行，僅保留 PSR-15 進入點與請求預處理
- `config/container.php` 新增對應的 DI 綁定
- 所有策略類別與協調器具備獨立單元測試

**Non-Goals:**
- 不改變現有 RBAC 權限模型的運作方式
- 不更動 IP 相關邏輯（已由 `ip-logic-unification` 處理）
- 不改寫現有 `Domains\Auth\Services\AuthorizationService`（角色/權限 CRUD）
- 不引入新的外部相依套件
- 不改變 API 合約與回應格式

## Decisions

### 1. 新服務命名為 AuthorizationOrchestratorService 而非 AuthorizationService
- **選擇**：`AuthorizationOrchestratorService`
- **理由**：`Domains\Auth\Services\AuthorizationService` 已是角色/權限 CRUD 服務，重用此名稱會造成混淆，也破壞現有合約。`AuthorizationOrchestratorService` 精確描述其職責：協調多個授權策略的執行順序與結果彙總。
- **替代方案**：修改現有 `AuthorizationService` 將其合併 — 拒絕，因其職責（DB CRUD）與策略協調（in-memory 邏輯鏈）截然不同。

### 2. 策略介面定位為 strategy 而非 evaluator
- **選擇**：`AuthorizationStrategyInterface` 搭配 `evaluate()` 方法
- **理由**：Strategy 模式是 GoF 經典模式，團隊熟悉度高。`evaluate()` 接受執行上下文（`AuthorizationContext` DTO）回傳 `AuthorizationResult`，清楚表達「評估條件後產出結果」的語意。
- **替代方案**：使用 chain of responsibility — 拒絕，因策略之間無前後傳遞責任的關係，而是各自獨立評估。

### 3. AuthorizationResult 搬遷至 ValueObjects
- **選擇**：`Domains\Auth\ValueObjects\AuthorizationResult`
- **理由**：`AuthorizationResult` 是不可變值物件，不攜帶任何 infrastructure 依賴，完全符合 Domain 層 ValueObject 定義。搬遷後可被 Domain 層與 Application 層共同引用。
- **替代方案**：保留於 Middleware 命名空間 — 拒絕，會持續造成分層混淆。

### 4. AuthorizationContext DTO
- **選擇**：新增 `AuthorizationContext` 作為策略評估的標準輸入
- **理由**：目前 `authorize()` 傳遞 6 個參數，且每個策略簽名略有不同。統一 DTO 可簡化介面、降低新增策略的耦合。
- **替代方案**：保留多參數簽名 — 拒絕，不利於新增策略與介面穩定。

### 5. 策略執行順序
- **選擇**：超級管理員 → 角色 → 權限 → 屬性 → 自訂規則（短路評估：任一策略允許即立即放行）
- **理由**：與現有 `JwtAuthorizationMiddleware::authorize()` 的行為完全一致，確保向後相容。超級管理員豁免在最前面是最佳化（跳過所有策略評估）。
- **替代方案**：聚合所有結果再決定 — 拒絕，會增加延遲且無實際收益。

## Risks / Trade-offs

- **[名稱衝突]** `Domains\Auth\Services\AuthorizationService` 已存在。`AuthorizationOrchestratorService` 的新命名需在團隊溝通中明確標示。
  - **緩解**：在 `AuthorizationOrchestratorService` 的 PHPDoc 中加入明確職責描述，並在提案與設計文件中記錄命名決策。

- **[向後相容]** `AuthorizationResult` 搬遷到新命名空間後，所有 `use` 陳述需一併更新。
  - **緩解**：保留舊類別作為 deprecated alias 一個過渡期（兩個版本），或在搬遷後立即執行全專案的 `use` 更新。

- **[測試覆蓋缺口]** 現有 `JwtAuthorizationMiddleware` 測試可能直接 mock 或依賴私有方法。
  - **緩解**：所有策略類別公開 `evaluate()` 方法，可獨立單元測試。`AuthorizationOrchestratorService` 可透過 mock 策略進行隔離測試。

- **[中介軟體重疊]** `AuthorizationMiddleware`（83 行）與 `JwtAuthorizationMiddleware` 有部分功能重疊（如 `extractResourceFromPath` / `extractActionFromMethod`）。
  - **緩解**：本次不處理 `AuthorizationMiddleware`，標記為後續清理候選。
