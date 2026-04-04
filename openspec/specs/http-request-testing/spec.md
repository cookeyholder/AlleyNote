# http-request-testing Specification

## Purpose

TBD - created by archiving change refactor-tests-to-match-project-state. Update Purpose after archive.
## Requirements
### Requirement: Standardized request creation

測試系統 MUST 提供一個方法來建立標準化的 PSR-7 `ServerRequest` 實體，並帶有預設的 API 標頭（Accept: application/json）。

#### Scenario: Create basic GET request

- **當** 開發者呼叫 `createRequest('GET', '/api/posts')`
- **則** 系統應回傳一個 URI 為 `http://localhost/api/posts` 且帶有 `Accept: application/json` 標頭的 `ServerRequest` 實體。

### Requirement: JWT authentication injection

測試系統 MUST 提供一個方法，將有效的 JWT Bearer Token 注入到請求實體中。

#### Scenario: Authenticate a request

- **當** 開發者呼叫 `withJwtAuth($request, $userId)`
- **則** 系統應回傳一個新的請求實體，且已設定 `Authorization: Bearer <token>` 標頭。

### Requirement: JSON body injection

測試系統 MUST 提供一個方法，將陣列作為 JSON Body 注入到請求中。

#### Scenario: Inject JSON data

- **當** 開發者呼叫 `withJsonBody($request, ['title' => 'Test'])`
- **則** 系統應回傳一個帶有 `Content-Type: application/json` 且包含已編碼內容的請求實體。

### Requirement: Enhanced API Request Builder
測試系統 MUST 提供一個更高級的輔助方法，能一鍵建立帶有常用 Mocks（如 `getServerParams`, `getCookieParams`）的 API 請求實體。

#### Scenario: Create fully mocked API request
- **WHEN** 測試呼叫 `createApiRequest('POST', '/api/auth/logout')`
- **THEN** 系統回傳一個已經預先配置好所有 PSR-7 方法預期行為（Mock expectations）的物件，避免測試中重複 Mock

### Requirement: Database state assertions DSL
測試系統 MUST 提供簡潔的方法來斷言資料庫中的資料狀態，而不需要開發者手動執行 SQL 查詢或實作複雜的斷言邏輯。

此外，DSL 斷言必須與應用程式實際寫入共用同一個資料庫連線上下文；在 SQLite in-memory 測試環境中，`ApiTestCase`、DSL、DI 容器 `PDO::class` 需觀測同一份資料狀態。

#### Scenario: Assert record exists in database
- **WHEN** 測試執行完建立貼文動作後，呼叫 `$this->assertDatabaseHas('posts', ['title' => 'My Post'])`
- **THEN** 系統應自動檢查資料庫中是否存在符合條件的記錄，若無則測試失敗

#### Scenario: Assert record missing from database
- **WHEN** 測試執行完刪除動作後，呼叫 `$this->assertDatabaseMissing('posts', ['id' => 1])`
- **THEN** 系統應確認資料庫中已不存在該記錄，若存在則測試失敗

#### Scenario: Observe API write from DSL in sqlite in-memory
- **GIVEN** 測試執行環境使用 SQLite in-memory
- **AND** `ApiTestCase`、DSL 與 DI 容器共用同一 PDO 連線
- **WHEN** 測試透過 API 端點建立一筆 `posts` 記錄
- **THEN** 立刻呼叫 `$this->assertDatabaseHas('posts', [...])` 應成功

#### Scenario: Nested transaction does not break assertion correctness
- **GIVEN** 測試案例包含外層交易，且應用層內部可能使用巢狀交易或 savepoint
- **WHEN** 內部交易 rollback 或 commit 後執行資料庫 DSL 斷言
- **THEN** `assertDatabaseHas()` / `assertDatabaseMissing()` 的結果必須與最終可見資料狀態一致

