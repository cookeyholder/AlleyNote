## Why

專案在經歷 PHP 8.4 升級與 PSR-7 基礎設施重構後，既有的測試套件暴露了兩個主要問題：
1. **Mock 脆弱性**：單元測試過度依賴 `Mockery::mock(Interface::class)`，導致測試無法捕捉到基礎設施（如 ServerRequest）內部的實作 Bug。
2. **建構冗餘**：開發者在各個測試中重複編寫繁瑣的 Request/Response 初始化代碼，降低了開發效率且容易出錯。

## What Changes

- **建立測試輔助工具 (Traits)**：
  - `HttpRequestTestTrait`：提供標準化的方法來建立真實的 `ServerRequest` 實體，內建 Auth 與 Header 注入。
  - `HttpResponseTestTrait` (升級)：從回傳 Mock 改為回傳真實 `Response` 實體，並增加深層斷言工具。
- **重構測試基底 (Base Cases)**：
  - 將上述 Trait 注入 `UnitTestCase` 與 `IntegrationTestCase`。
- **標準化測試範式**：
  - 更新核心模組（如 Auth, Post）的測試，示範如何使用新的基礎設施進行更健壯的驗證。

## Capabilities

### New Capabilities
- `test-infrastructure-v2`: 提供一套與 PSR-7 實體完全對齊的測試工具集。

### Modified Capabilities
- `standard-api-verification`: 強化 API 測試的斷言嚴謹度。

## Impact

- 影響範圍：全案後端單元測試與整合測試。
- 收益：提升重構時的安全性，減少測試腳本的維護成本。
