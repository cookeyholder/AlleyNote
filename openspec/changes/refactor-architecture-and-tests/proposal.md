## Why

AlleyNote 的後端架構目前面臨三大問題：`PostController` 職責過重（超過 1300 行）、`BaseController` 的例外處理與 Domain 層強耦合、以及整合測試中的 Mock 設置極其冗餘。這些問題導致了系統難以維護、代碼可讀性差且開發效率低下。現在進行重構是為了建立一個更具擴充性且易於測試的基礎架構。

## What Changes

- **Controller 瘦身**：將 OpenAPI 註解、分頁處理與資料轉換邏輯從控制器中分離。
- **解耦例外處理**：重構 `BaseController` 的例外映射機制，改為基於類別映射或介面註冊。
- **現代化測試架構**：建立專用的 `ApiTestCase` 與流暢的測試 DSL，將測試重心從「Mock 實作」轉向「行為驗證」。
- **引入 Resource/Transformer 模式**：標準化 API 輸出的轉換邏輯，並為其建立獨立的單元測試。

## Capabilities

### New Capabilities
- `api-resource-transformers`: 提供統一的資料轉換層，分離模型與 API 輸出格式。
- `exception-handling-middleware`: 實作全域或分層的例外轉換機制，解耦控制器與特定錯誤類別。

### Modified Capabilities
- `http-request-testing`: 擴充測試工具，簡化 PSR-7 請求的 Mock 與實體建立流程。

## Impact

- **Affected Code**: `BaseController`, `AuthController`, `PostController`, `IntegrationTestCase`。
- **APIs**: 無破壞性 API 變更，但內部實作將大幅簡化。
- **Dependencies**: 無新增外部依賴，僅優化內部架構。
