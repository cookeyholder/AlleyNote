## 1. 架構基礎建設

- [ ] 1.1 定義 `ApiExceptionInterface` 與其基礎實作
- [ ] 1.2 實作 `ExceptionRegistry` 中央註冊器並配置預設映射
- [ ] 1.3 建立 `App\Shared\Http\ApiResource` 基底類別
- [ ] 1.4 實作 `PostResource` 並遷移現有的 `Post` 轉換邏輯

## 2. 核心組件重構

- [ ] 2.1 執行技術實驗 (Spike)：驗證 Swagger 能正確掃描 Interface 上的註解
- [ ] 2.2 重構 `BaseController`：移除靜態對照表，整合 `ExceptionRegistry`
- [ ] 2.3 建立 `PostApiInterface` 與 `AuthApiInterface`
- [ ] 2.4 遷移 OpenAPI 註解至上述介面，並讓 Controller 實作之
- [ ] 2.5 簡化 `PostController`：將資料轉換與統計合併邏輯移至 `PostResource`
- [ ] 2.6 遷移系統內其餘控制器至新的例外處理機制 (一次到位)

## 3. 測試基礎設施與 DSL 構建

- [ ] 3.1 建立 `Tests\Support\ApiTestCase` 並整合現有測試 Traits
- [ ] 3.2 實作流暢介面：`$this->json()`, `$this->withHeaders()`
- [ ] 3.3 實作高保真 `$this->actingAs()`：產生真實 JWT Token
- [ ] 3.4 實作資料庫斷言 DSL：`$this->assertDatabaseHas()`, `$this->assertDatabaseMissing()`
- [ ] 3.5 在基類中自動化 PSR-7 基本屬性的 Mock (getServerParams, getCookieParams)
- [ ] 3.6 為 `PostResource` 撰寫獨立的單元測試
- [ ] 3.7 遷移 `AuthControllerTest` 與 `PostControllerTest` 至新的 `ApiTestCase` 語法

## 4. 驗證與文件

- [ ] 4.1 執行完整整合測試與 E2E 測試
- [ ] 4.2 更新 `BaseController` 使用指南與測試規範文件
