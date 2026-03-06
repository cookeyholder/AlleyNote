## ADDED Requirements

### Requirement: Standardized request creation
測試系統應提供一個方法來建立標準化的 PSR-7 `ServerRequest` 實體，並帶有預設的 API 標頭（Accept: application/json）。

#### Scenario: Create basic GET request
- **當** 開發者呼叫 `createRequest('GET', '/api/posts')`
- **則** 系統應回傳一個 URI 為 `http://localhost/api/posts` 且帶有 `Accept: application/json` 標頭的 `ServerRequest` 實體。

### Requirement: JWT authentication injection
測試系統應提供一個方法，將有效的 JWT Bearer Token 注入到請求實體中。

#### Scenario: Authenticate a request
- **當** 開發者呼叫 `withJwtAuth($request, $userId)`
- **則** 系統應回傳一個新的請求實體，且已設定 `Authorization: Bearer <token>` 標頭。

### Requirement: JSON body injection
測試系統應提供一個方法，將陣列作為 JSON Body 注入到請求中。

#### Scenario: Inject JSON data
- **當** 開發者呼叫 `withJsonBody($request, ['title' => 'Test'])`
- **則** 系統應回傳一個帶有 `Content-Type: application/json` 且包含已編碼內容的請求實體。
