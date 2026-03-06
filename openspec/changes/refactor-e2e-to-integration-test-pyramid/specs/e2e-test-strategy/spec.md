## ADDED Requirements

### Requirement: E2E scope must focus on critical user journeys

系統的 E2E 測試 MUST 主要覆蓋使用者關鍵流程（認證、導覽、核心 CRUD 成功路徑），而非資料規則矩陣或聚合正確性。

#### Scenario: Keep critical E2E journey

- **WHEN** 測試登入、後台導覽、文章建立與批次刪除主流程
- **THEN** 測試應使用真實瀏覽器流程驗證成功路徑與重導結果

### Requirement: Business rules should be validated in integration tests

密碼規則、時區轉換、統計聚合與設定持久化等規則型驗證 MUST 在整合測試層承擔主要覆蓋。

#### Scenario: Move rule assertions down

- **WHEN** 測試目標是資料正確性（非 UI 互動本身）
- **THEN** 應優先以 PHP 整合測試驗證，E2E 僅保留最小 smoke

### Requirement: Redundant E2E suites must be consolidated

高重複的頁面巡檢與可見性斷言 SHOULD 合併或精簡，以降低維護成本與 flaky 風險。

#### Scenario: Consolidate overlapping suites

- **WHEN** 多個 E2E 檔案重複驗證相同導覽與頁面存在性
- **THEN** 應保留單一 smoke 套件，其他重複案例降級或移除

### Requirement: Environment-dependent E2E must fail safely

當測試強依賴外部資源或不穩定基礎資料（例如設定資料表、第三方 CDN）時，E2E MUST 採用條件式 skip 或降級策略，避免產生誤導性紅燈。

#### Scenario: Handle unstable environment dependency

- **WHEN** 測試依賴項在當前環境不可用
- **THEN** 測試應標示 skip 並由整合測試承接規則驗證責任
