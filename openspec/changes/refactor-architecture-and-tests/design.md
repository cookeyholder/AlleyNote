## Context

目前的後端控制器架構中，`PostController` 承載了過多職責，且 `BaseController` 的例外處理機制（靜態對照表）與所有 Domain 嚴重耦合。此外，整合測試中對 PSR-7 請求的 Mock 設置極度重複，導致測試撰寫成本高且易碎。

## Goals / Non-Goals

**Goals:**
- **解耦基底類別**：將 `BaseController` 中的例外映射邏輯改為更具動態擴充性的機制。
- **標準化 API 轉換**：引入 `ApiResource` 模式，讓控制器專注於流程編排而非資料格式化。
- **優化測試體驗**：提供便捷的 HTTP 請求建立工具，減少 Mock 程式碼的比例。

**Non-Goals:**
- **重寫業務邏輯**：此設計不涉及 `PostService` 或 `AuthService` 的內部邏輯變更。
- **變更 API 格式**：確保現有的前端 API 合約不受影響。

## Decisions

### 1. 引入資源轉換層 (ApiResource)
- **方案**：建立 `App\Shared\Http\ApiResource` 基底類別。
- **原因**：目前在控制器中手動呼叫 `$post->toArray()` 並手動合併統計數據，程式碼冗長。透過資源轉換器，可以將模型與 API 輸出結構分離。
- **代選**：Laravel 風格的 `JsonResource` 或手動 DTO 轉換。最終選擇類似於 `PostResource` 的專用轉換器。

### 2. 基於介面的例外轉換機制
- **方案**：在 `BaseController` 中，如果例外實作了 `ApiExceptionInterface`，則直接調用其 `getStatusCode()`。對於其他例外，提供一個註冊式或基於設定的映射表。
- **原因**：移除 `BaseController` 中對特定 Domain 例外的靜態引用，改由例外類別自行定義其對應的 HTTP 碼，或透過中介服務查找。

### 3. 建立流暢的測試介面 (ApiTestCase)
- **方案**：建立 `Tests\Support\ApiTestCase` 繼承自 `IntegrationTestCase`。
- **功能**：
    - 封裝 `$this->json(string $method, string $path, array $data = [])`：自動處理 `json_encode`、標頭設定與 Request Mocks。
    - 封裝 `$this->actingAs(User $user)`：自動處理 JWT 標頭注入。
    - 封裝資料庫斷言：實作 `$this->assertDatabaseHas(string $table, array $data)` 與 `$this->assertDatabaseMissing(...)`，利用 `DatabaseTestTrait` 簡化資料驗證。
    - 集成 `HttpResponseTestTrait` 的斷言（如 `assertJsonResponseMatches`）。
- **原因**：目前的測試過於依賴底層 Mockerry 設定（超過 50% 是設置碼）。透過 DSL，測試代碼能專注於 API 行為斷言，提高可讀性與強健性。

## Risks / Trade-offs

- **[Risk]** → 可能遺漏某些例外的映射導致意外的 500 錯誤。
- **[Mitigation]** → 在重構後執行完整的整合測試套件以驗證行為一致性。
- **[Trade-off]** → 引入 `ApiResource` 會增加一個新的類別層級，但在大型專案中，其帶來的一致性優點遠勝過類別數量的增加。
