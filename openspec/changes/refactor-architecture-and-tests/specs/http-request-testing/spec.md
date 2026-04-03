## ADDED Requirements

### Requirement: Enhanced API Request Builder
測試系統必須提供一個更高級的輔助方法，能一鍵建立帶有常用 Mocks（如 `getServerParams`, `getCookieParams`）的 API 請求實體。

#### Scenario: Create fully mocked API request
- **WHEN** 測試呼叫 `createApiRequest('POST', '/api/auth/logout')`
- **THEN** 系統回傳一個已經預先配置好所有 PSR-7 方法預期行為（Mock expectations）的物件，避免測試中重複 Mock

### Requirement: Database state assertions DSL
測試系統必須提供簡潔的方法來斷言資料庫中的資料狀態，而不需要開發者手動執行 SQL 查詢或實作複雜的斷言邏輯。

#### Scenario: Assert record exists in database
- **WHEN** 測試執行完建立貼文動作後，呼叫 `$this->assertDatabaseHas('posts', ['title' => 'My Post'])`
- **THEN** 系統應自動檢查資料庫中是否存在符合條件的記錄，若無則測試失敗

#### Scenario: Assert record missing from database
- **WHEN** 測試執行完刪除動作後，呼叫 `$this->assertDatabaseMissing('posts', ['id' => 1])`
- **THEN** 系統應確認資料庫中已不存在該記錄，若存在則測試失敗
