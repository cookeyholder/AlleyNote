## 1. 核心測試工具實作 (Core Test Traits)

- [ ] 1.1 實作 `HttpRequestTestTrait.php`
  - **AC**: 提供 `createRequest()` 可產出帶有 Host 的實體 `ServerRequest`。
  - **AC**: `withJwtAuth()` 能正確注入 `Authorization: Bearer` 標頭且不影響原 Request (具備不可變性)。
  - **AC**: 所有的標頭（Header）鍵名在建構時必須自動轉為小寫。
- [ ] 1.2 實作 `DatabaseSnapshotTrait.php`
  - **AC**: `captureRow()` 失敗時（如 ID 不存在）應拋出明確的例外（Exception）。
  - **AC**: `assertRowUnchanged()` 在資料完全一致時通過，任何一欄（含型別）不符時失敗。
  - **AC**: `assertRowChangedOnly()` 必須驗證「除了指定欄位外，其餘皆未變動」。
- [ ] 1.3 強化 `HttpResponseTestTrait.php`
  - **AC**: 實作 `createJsonResponse()` 回傳 `App\Infrastructure\Http\Response` 實體。
  - **AC**: `assertJsonResponseMatches()` 支援巢狀陣列的部分比對（Partial Match）。

## 2. 測試基底重構 (Base Case Refactoring)

- [ ] 2.1 更新 `BaseTestCase.php`
  - **AC**: 成功注入上述所有 Traits，且不與現有 PHPUnit 方法衝突。
- [ ] 2.2 同步 `IntegrationTestCase` 與 `UnitTestCase`
  - **AC**: 執行一個空測試，確認所有新工具皆可透過 `$this->` 正常呼叫。

## 3. 數據工廠升級 (Enhanced Factories)

- [ ] 3.1 升級 `PostFactory.php`
  - **AC**: `PostFactory::create()` 執行後，資料庫 `posts` 資料表必須多出一筆記錄。
  - **AC**: 回傳的物件或陣列必須包含資料庫生成的實體 ID。
- [ ] 3.2 建立 `UserFactory.php`
  - **AC**: 預設產出的密碼必須經過 `password_hash` 加密，確保能通過真實的登入驗證。

## 4. 示範重構與驗證 (Demonstration & Verification)

- [ ] 4.1 **[DEMO]** 重構 `PostControllerTest.php`
  - **AC**: 移除所有手動模擬（Mock）`ServerRequestInterface` 的程式碼。
  - **AC**: 測試案例數量不減少，且所有測試皆綠燈通過。
- [ ] 4.2 **[DEMO]** 重構 `PostActivityLoggingTest.php`
  - **AC**: 使用語義化斷言（如 `assertActivityLogged`）取代複雜的 Mockery 閉包比對。
- [ ] 4.3 執行全案測試驗證
  - **AC**: `composer check-all` 執行通過，且錯誤（Errors）保持為 0。
