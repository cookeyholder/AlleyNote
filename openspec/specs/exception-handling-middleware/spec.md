# exception-handling-middleware Specification

## Purpose
TBD - created by archiving change refactor-architecture-and-tests. Update Purpose after archive.
## Requirements
### Requirement: Interface-based Exception Mapping
系統 MUST 支援基於介面或動態註冊的例外處理機制，以解耦 `BaseController` 與具體的 Domain 例外類別。

#### Scenario: Handle Custom Domain Exception
- **WHEN** 業務邏輯拋出一個實作了 `ApiExceptionInterface` 的例外
- **THEN** `BaseController` 必須正確識別其 HTTP 狀態碼並回傳標準化 JSON 錯誤回應

