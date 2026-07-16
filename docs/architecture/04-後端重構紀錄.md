# Backend Refactor 2026-04

## 目的

本文件是 PR #78（merge commit `685ec41`）後端重構的 canonical 技術細節說明，涵蓋設計決策、擴充邊界、測試策略與維護風險。

## 變更範圍摘要

- 例外處理：`ExceptionRegistry` + `ApiExceptionInterface` 取代控制器內硬編碼映射。
- 資料轉換：導入 `ApiResource` 基底與 `PostResource`，讓控制器專注流程編排。
- 測試基礎設施：導入 `ApiTestCase` DSL（`json` / `withHeaders` / `actingAs` / DB assertions）。

## Task 1.1 盤點結果（code-reviewer + 實際程式入口）

### code-reviewer 執行紀錄

- 已執行：`pr_analyzer.py .`
- 已執行：`code_quality_checker.py .`
- 已執行：`review_report_generator.py .`

註：上述三支腳本目前僅輸出通用骨架報告；本文件未將其輸出視為可驗證 findings，實際重構影響面以下列程式碼盤點為準。

### 核心入口與相依檔案

- 例外處理入口：
  - `backend/app/Infrastructure/Http/ExceptionRegistry.php`
  - `backend/app/Shared/Exceptions/ApiExceptionInterface.php`
  - `backend/app/Application/Controllers/BaseController.php`
- 資料轉換入口：
  - `backend/app/Shared/Http/ApiResource.php`
  - `backend/app/Application/Resources/PostResource.php`
  - `backend/app/Application/Controllers/Api/V1/PostController.php`
- 測試 DSL 入口：
  - `backend/tests/Support/ApiTestCase.php`
  - `backend/tests/Integration/Http/PostControllerTest.php`
  - `backend/tests/Integration/AuthControllerTest.php`

## 設計決策與擴充邊界

### 1. 例外處理策略

- 優先順序：`ApiExceptionInterface` > `ExceptionRegistry` 類別映射 > 介面映射。
- 擴充方式：
  - 可修改例外類別時，優先實作 `ApiExceptionInterface`。
  - 無法修改時，集中於 `ExceptionRegistry::createDefault()` 註冊。
- 禁止做法：
  - 不可在個別 controller 建立私有例外對照表。

### 2. 資料轉換策略

- 所有 API 輸出轉換由 `ApiResource` 系列處理，controller 不負責欄位重組。
- 跨來源資料（例如 views/unique visitors）由 controller 先組 context，再交給 resource。
- 禁止做法：
  - 在 controller 內手刻回應結構，導致重複與格式漂移。

### 3. 測試策略（ApiTestCase）

- API 測試一律優先使用 `ApiTestCase` 提供的 DSL。
- `actingAs()` 使用真實 JWT 簽章流程，避免測試與正式行為偏離。
- `assertDatabaseHas` / `assertDatabaseMissing` 依賴單一 PDO 來源原則，確保可觀測一致性。
- 驗證機制的測試（如 `AuthenticationService` 等）必須獨立拆解過於龐大的副程式，並逐一覆蓋。

### 4. Value Object 作為防腐層 (Anti-Corruption Layer)
- 所有 `Email`, `Password`, `IPAddress`, `UUID` 等敏感或具固定格式之資料，皆須直接呼叫對應的 Value Object 確保正確性，禁止散落原生的 `filter_var` 或 `password_hash` 於 Controller / Service 中。

### 5. 權責收斂與服務移除
- 已將多餘的 `AuthService` 完全移除。系統驗證機制一律統一交由 `AuthenticationService` (JWT邏輯與認證) 以及 `UserManagementService` (註冊與使用者管理) 處理。
- 禁止做法：
  - 直接手工組假的 Bearer token。
  - 混用不同 PDO 實例導致 `:memory:` 判斷失真。

## 風險與取捨

- 取捨：類別數量增加（Resource / Contracts / Registry），但降低控制器耦合與測試脆弱度。
- 風險：若漏登記例外映射，可能回落為 500。
- 緩解：新增例外時同步檢查 `ApiExceptionInterface` 或 `ExceptionRegistry` 是否已覆蓋。

## Task 1.2 文件更新範圍對照表

| 文件路徑 | 角色 | 本次更新重點 |
|---|---|---|
| `README.md` | 根目錄 canonical 入口 | 新增重構技術文件入口 |
| `docs/runbooks/DEVELOPMENT.md` | 開發流程 canonical | 補齊例外/Resource/測試 DSL 邊界與反模式 |
| `docs/architecture/BACKEND_REFACTOR_2026-04.md` | 重構技術細節 canonical | 記錄決策、邊界、測試策略與風險 |
| `.github/pull_request_template.md` | PR 審查入口 | 新增重大重構技術文件完整性核對項 |
| `docs/DOCUMENTATION_GOVERNANCE.md` | 文件治理規範 | 定義重大重構觸發條件與審查要件 |
