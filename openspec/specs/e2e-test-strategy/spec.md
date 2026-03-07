# e2e-test-strategy Specification

## Purpose
TBD - created by archiving change refactor-e2e-to-integration-test-pyramid. Update Purpose after archive.
## Requirements
### Requirement: E2E scope must focus on critical user journeys

系統的 E2E 測試 MUST 主要覆蓋使用者關鍵流程（認證、導覽、核心 CRUD 成功路徑），而非資料規則矩陣或聚合正確性。

#### Scenario: Keep critical E2E journey

- **WHEN** 測試登入、後台導覽、文章建立與批次刪除主流程
- **THEN** 測試應使用真實瀏覽器流程驗證成功路徑與重導結果

### Requirement: Business rules should be validated in integration tests

系統 MUST 對每個被降級的 E2E 能力提供對應整合測試，且測試應覆蓋該能力的核心正確性規則。

#### Scenario: Backfill downgraded E2E responsibilities

- **WHEN** 任一 E2E 套件因環境依賴或重複性而被降級
- **THEN** 對應的整合測試必須在同一變更中補齊，至少涵蓋一條成功路徑與一條邊界/錯誤路徑

### Requirement: Redundant E2E suites must be consolidated

高重複的頁面巡檢與可見性斷言 MUST 合併或精簡，以降低維護成本與 flaky 風險。

#### Scenario: Consolidate overlapping suites

- **WHEN** 多個 E2E 檔案重複驗證相同導覽與頁面存在性
- **THEN** 應保留單一 smoke 套件，其他重複案例降級或移除

### Requirement: Environment-dependent E2E must fail safely

當測試強依賴外部資源或不穩定基礎資料（例如設定資料表、第三方 CDN）時，E2E MUST 採用條件式 skip 或降級策略，避免產生誤導性紅燈。

#### Scenario: Handle unstable environment dependency

- **WHEN** 測試依賴項在當前環境不可用
- **THEN** 測試應標示 skip 並由整合測試承接規則驗證責任

### Requirement: Mapping between downgraded E2E and integration suites must be explicit

測試策略 MUST 維持可追蹤對應關係，明確記錄「哪個降級 E2E」由「哪個整合測試」承接。

#### Scenario: Traceability for maintenance

- **WHEN** 開發者調整 E2E skip/smoke 策略
- **THEN** 應可從 OpenSpec tasks 與整合測試檔案快速定位承接覆蓋

