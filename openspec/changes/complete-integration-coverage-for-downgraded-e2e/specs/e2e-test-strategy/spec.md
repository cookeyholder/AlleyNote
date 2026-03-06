## MODIFIED Requirements

### Requirement: Business rules should be validated in integration tests

系統 MUST 對每個被降級的 E2E 能力提供對應整合測試，且測試應覆蓋該能力的核心正確性規則。

#### Scenario: Backfill downgraded E2E responsibilities

- **WHEN** 任一 E2E 套件因環境依賴或重複性而被降級
- **THEN** 對應的整合測試必須在同一變更中補齊，至少涵蓋一條成功路徑與一條邊界/錯誤路徑

### Requirement: Mapping between downgraded E2E and integration suites must be explicit

測試策略 MUST 維持可追蹤對應關係，明確記錄「哪個降級 E2E」由「哪個整合測試」承接。

#### Scenario: Traceability for maintenance

- **WHEN** 開發者調整 E2E skip/smoke 策略
- **THEN** 應可從 OpenSpec tasks 與整合測試檔案快速定位承接覆蓋
