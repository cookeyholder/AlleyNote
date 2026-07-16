## MODIFIED Requirements

### Requirement: AuthorizationService 名稱澄清為 AuthorizationOrchestratorService
`AuthorizationService` 這個名稱已由 `App\Domains\Auth\Services\AuthorizationService`（角色/權限 CRUD 服務）佔用。本 change 新增的授權策略協調服務 SHALL 命名為 `AuthorizationOrchestratorService`，精確反映其協調多個授權策略的職責，避免與既有 CRUD 服務混淆。

#### Scenario: 文件使用明確命名避免混淆
- **WHEN** 團隊文件、PHPDoc、或程式碼註解提及此服務
- **THEN** 使用完整名稱 `AuthorizationOrchestratorService` 而非模糊的 `AuthorizationService`

#### Scenario: 既有 AuthorizationService 不受影響
- **WHEN** 呼叫 `AuthorizationService::assignRole()`、`hasPermission()` 等 CRUD 方法
- **THEN** 行為不應有任何改變，不因本 change 而受影響

#### Scenario: PHPStan 依賴注入解析無歧義
- **WHEN** 容器中同時存在 `AuthorizationService` 與 `AuthorizationOrchestratorService` 兩個定義
- **THEN** PHPStan Level 10 分析不應報出任何名稱解析錯誤或歧義
