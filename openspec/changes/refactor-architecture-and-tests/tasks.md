## 1. 架構基礎建設

- [ ] 1.1 定義 `ApiExceptionInterface` 與其基礎實作
- [ ] 1.2 建立 `App\Shared\Http\ApiResource` 基底類別
- [ ] 1.3 實作 `PostResource` 並遷移現有的 `Post` 轉換邏輯

## 2. 核心組件重構

- [ ] 2.1 重構 `BaseController`：移除靜態例外對照表，改用 `ApiExceptionInterface`
- [ ] 2.2 簡化 `PostController`：將資料轉換與統計合併邏輯移至 `PostResource`
- [ ] 2.3 提取 `PostController` 的 OpenAPI 註解至專用 Docs 類別或 Interface
- [ ] 2.4 提取 `AuthController` 的 OpenAPI 註解至專用 Docs 類別或 Interface

## 3. 測試基礎設施與 DSL 構建

- [ ] 3.1 建立 `Tests\Support\ApiTestCase` 並整合現有測試 Traits
- [ ] 3.2 實作流暢介面：`$this->json()`, `$this->withHeaders()`, `$this->actingAs()`
- [ ] 3.3 實作資料庫斷言 DSL：`$this->assertDatabaseHas()`, `$this->assertDatabaseMissing()`
- [ ] 3.4 在基類中自動化 PSR-7 基本屬性的 Mock (getServerParams, getCookieParams)
- [ ] 3.5 為 `PostResource` 撰寫獨立的單元測試
- [ ] 3.6 遷移 `AuthControllerTest` 至新的 `ApiTestCase` 語法
- [ ] 3.7 遷移 `PostControllerTest` 至新的 `ApiTestCase` 語法並簡化 Mock 設置

## 4. 驗證與文件

- [ ] 4.1 執行完整整合測試與 E2E 測試
- [ ] 4.2 更新 `BaseController` 使用指南與測試規範文件
