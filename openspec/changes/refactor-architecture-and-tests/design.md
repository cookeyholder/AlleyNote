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
- **資料耦合 (Controller Fed)**：Resource 保持純粹，不依賴 Service。所有外部合併數據（如 ViewStats）由 Controller 事先獲取，並作為上下文 (context) 傳入 Resource。這樣能確保 Resource 易於單元測試且無循環依賴。

### 2. 基於混合註冊器的例外處理
- **方案**：實作 `ExceptionRegistry` 中央註冊器結合 `ApiExceptionInterface`。
- **機制**：
    - **優先級 1**：例外實作 `ApiExceptionInterface` (自主定義 HTTP 碼，符合 DDD)。
    - **優先級 2**：查找 `ExceptionRegistry` 中央映射表 (集中管理通用錯誤)。
- **範圍 (All-in)**：重構完成後，所有控制器一次性切換至此機制，徹底移除 `BaseController` 中的硬編碼列表。

### 3. OpenAPI 註解提取至介面 (Interface)
- **方案**：建立控制器對應的介面（如 `PostControllerInterface`）。
- **做法**：將所有 `#[OA\...]` 註解移至介面方法上。
- **優點**：保持實作類別邏輯純粹，同時利用介面強制執行 API 合約，避免文件與程式碼脫節。

### 4. 現代化測試基礎設施 (ApiTestCase)
- **方案**：建立 `Tests\Support\ApiTestCase` 繼承自 `IntegrationTestCase`。
- **流暢介面 (DSL)**：實作 `$this->json()`, `$this->withHeaders()`, `$this->assertDatabaseHas()` 等方法。
- **高保真驗證 (Real JWT)**：`actingAs($user)` 將調用真實的 `JwtTokenService` 產生簽章 Token。
- **自動化 Mock**：基類自動處理 PSR-7 基本屬性 (getServerParams, getCookieParams) 的 Mock，讓開發者專注於行為測試。
- **單一連線原則 (Single PDO Source of Truth)**：
  - `ApiTestCase` 的 `$this->db`
  - 資料庫斷言 DSL (`assertDatabaseHas` / `assertDatabaseMissing`)
  - 應用容器中的 `PDO::class`
  
  三者必須指向同一個 PDO 實例，以確保 API 寫入與 DSL 斷言觀測的是同一份 SQLite in-memory 狀態。
- **事務策略 (Transaction Strategy)**：
  - 每個測試案例使用外層 transaction 保障隔離性。
  - DSL 僅負責查詢與斷言，不主動控制 transaction。
  - 對 repository 內部交易或 savepoint 的行為，以回歸測試保證不造成 DSL 假陽性/假陰性。

## Risks / Trade-offs

- **[Risk]** → 可能遺漏某些例外的映射導致意外的 500 錯誤。
- **[Mitigation]** → 在重構後執行完整的整合測試套件以驗證行為一致性。
- **[Trade-off]** → 引入 `ApiResource` 與 Interface 會增加類別數量，但在系統複雜度下，其帶來的架構清晰度與測試便利性更具價值。

### 5. 分頁數據封裝 (PaginatedResourceResponse)
- **方案**：建立 `App\Shared\Http\Responses\PaginatedResourceResponse`。
- **結構**：該類別將負責包裝 `ApiResource` 轉換後的 Data 陣列與 Meta 元數據（total, current_page 等），確保 API 回應格式統一為：
  ```json
  { "success": true, "data": [...], "meta": { "total": 100, ... } }
  ```

### 6. 目錄與命名規範 (Naming Convention)
- **Resources**: `App\Application\Resources` (如 `PostResource.php`)。
- **OpenAPI Interfaces**: `App\Application\Contracts` (如 `PostApiInterface.php`)。
- **Exceptions**: `App\Infrastructure\Http\ExceptionRegistry.php`。

## Risks / Trade-offs

- **[Risk]** → `zircote/swagger-php` 可能不支援 Interface 註解繼承。
- **[Mitigation]** → 在實作前先於 `PostController` 進行「單點實驗 (Spike)」，若失敗則改採「虛擬文件類別」方案。
- **[Risk]** → 測試 DSL 的資料庫斷言在巢狀事務下失效。
- **[Mitigation]** → 強制執行「單一連線原則」並以測試矩陣覆蓋 create/delete/rollback/巢狀交易情境。
- **[Risk]** → 測試環境變數鍵值不一致（如 `DB_PATH` vs `DB_DATABASE`）導致容器連到檔案型 SQLite，與 `:memory:` 假設不符。
- **[Mitigation]** → 統一測試配置契約，並新增「API 寫入可被 DSL 立即觀測」的整合測試作為守門條件。



