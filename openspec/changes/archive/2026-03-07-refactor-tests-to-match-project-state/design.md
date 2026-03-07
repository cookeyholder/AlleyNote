## Context

目前的測試程式碼中，建立 Request 物件的方式過於分散且依賴 Mock。我們需要一種既符合 PSR-7 實體行為，又便於快速建構測試場景的方法。

## Decisions

### 1. HttpRequestTestTrait 的核心 API
我們將提供以下方法：
- `createRequest(method, path, headers, params)`: 回傳一個帶有基本 API 標頭（Header）的 `ServerRequest` 實體。
- `withJwtAuth(request, userId, claims)`: 對 Request 注入 Bearer Token 標頭。
- `withJsonBody(request, data)`: 對 Request 注入 JSON Body 與對應的 Content-Type。

### 2. HttpResponseTestTrait 的強化
- 移除 `createJsonResponseMock`，改為 `createJsonResponse(data, status)` 回傳 `Response` 實體。
- 增加斷言：`assertJsonResponseMatches(response, array $pattern)`：支援部分匹配（Partial Match）與型別驗證。

### 3. 基底類別注入
- 在 `backend/tests/Support/BaseTestCase.php` (或更具體的 Unit/Integration) 中加入 `use` 陳述式。

## Risks / Trade-offs

- **[風險]**：從 Mock 轉向真實物件可能會導致原本「隱藏」的 Bug 爆發（測試失敗增加）。
- **[取捨]**：這正是我們想要的行為——讓測試更真實。我們將在第一階段專注於基礎設施建立，第二階段逐步遷移既有測試。
