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

## Risks / Trade-offs

- **[Risk]** → 可能遺漏某些例外的映射導致意外的 500 錯誤。
- **[Mitigation]** → 在重構後執行完整的整合測試套件以驗證行為一致性。
- **[Trade-off]** → 引入 `ApiResource` 與 Interface 會增加類別數量，但在系統複雜度下，其帶來的架構清晰度與測試便利性更具價值。

## Open Questions (Phase 2: Implementation Details)

### 1. OpenAPI 註解繼承的技術相容性
- **問題**：`zircote/swagger-php` 對於 Interface 上的註解掃描是否有特定限制？
- **風險**：若掃描器無法自動識別介面註解，重構後 Swagger UI 可能會變空白。
- **思考**：是否需要先在 `PostController` 的其中一個方法進行「單點實驗 (Spike)」？

### 2. ApiResource 如何封裝分頁元數據 (Pagination Meta)
- **問題**：`ApiResource` 專注於轉換 Data，但 API 輸出的 `total`, `per_page` 等資訊應該放在哪裡？
    - **方案 A**：建立 `PaginatedResourceResponse` 類別包裝 Data 與 Meta。
    - **方案 B**：由 `BaseController` 的 `paginatedResponse` 方法接收 Resource 轉換後的陣列再進行手動包裝。
- **思考**：您希望 API 回應是扁平結構還是帶有顯式的 `meta` 欄位？

### 3. ExceptionRegistry 的定義媒介
- **問題**：例外映射表（Exception -> HttpCode）存放在哪裡最直觀？
    - **方案 A：設定檔** (`config/exceptions.php`)：易於查找與修改。
    - **方案 B：DI 容器註冊** (`container.php`)：符合目前專案的架構風格，但映射表變大時會讓容器定義變得擁擠。

### 4. 測試 DSL 的資料庫事務 (Database Transactions)
- **問題**：當呼叫 `$this->json()` 或 `$this->assertDatabaseHas()` 時，如何確保數據的一致性？
- **思考**：是否需要在 `ApiTestCase` 內部自動開啟事務？如果測試涉及多個請求（例如：先建立再查詢），事務的處理邊界該如何定義？

### 5. 命名空間與目錄結構定調
- **ApiResource** 位置：`App\Application\Resources` 還是 `App\Http\Resources`？
- **OpenAPI 介面** 命名：`PostApi` 還是 `PostControllerInterface`？
- **ExceptionRegistry** 位置：`App\Infrastructure\Exceptions` 還是 `App\Shared\Http`？



