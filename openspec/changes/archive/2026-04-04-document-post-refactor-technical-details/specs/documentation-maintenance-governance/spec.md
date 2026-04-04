## ADDED Requirements

### Requirement: 重大重構文件更新門檻
符合重大重構條件的變更 SHALL 在合併前同步更新技術文件，且不得僅以程式碼註解取代 canonical 文件更新。

#### Scenario: PR 涉及核心架構重構
- **WHEN** pull request 變更控制器例外處理機制、資源轉換層或測試基礎設施
- **THEN** 該 PR MUST 更新對應 canonical 技術文件，並在 PR 說明或 change 任務中標示更新路徑

### Requirement: 技術細節完整性審查清單
文件審查流程 SHALL 驗證重大重構文件是否包含決策理由、邊界條件、測試策略與維護風險四類資訊。

#### Scenario: 審查者檢查重構文件內容
- **WHEN** 審查者套用文件審查清單
- **THEN** 審查者 MUST 能逐項對應上述四類資訊，若缺項則 PR 不得視為文件完成

### Requirement: OpenSpec 文件路徑追蹤
OpenSpec 任務 SHALL 針對重大重構列出需更新的 canonical 文件實際路徑，以確保可追蹤驗證。

#### Scenario: 變更進入實作任務階段
- **WHEN** change 任務被建立或更新
- **THEN** 任務 MUST 列出至少一個實際文件路徑（例如 `README.md`、`docs/runbooks/DEVELOPMENT.md`、`docs/architecture/*`）作為完成條件
