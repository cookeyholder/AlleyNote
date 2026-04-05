# documentation-maintenance-governance Specification

## Purpose
TBD - created by archiving change consolidate-project-documentation. Update Purpose after archive.
## Requirements
### Requirement: 文件更新觸發規則
貢獻流程 SHALL 定義強制文件更新觸發條件，涵蓋行為變更、相依套件版本、CI 工作流程、部署流程或安全控制調整。

#### Scenario: PR 修改 CI 工作流程
- **WHEN** pull request 變更工作流程檔案或執行行為
- **THEN** 該 PR MUST 更新對應 canonical 文件，或明確說明不需更新文件的理由

### Requirement: 文件審查清單
儲存庫 SHALL 提供文件審查清單，並於符合觸發條件的變更在合併前套用。

#### Scenario: 審查者檢視觸發條件 PR
- **WHEN** 審查者評估符合文件觸發條件的 pull request
- **THEN** 審查者 MUST 確認範圍、正確性與連結有效性等清單項目皆已滿足

### Requirement: OpenSpec 與文件同步
OpenSpec changes SHALL 標示受影響 canonical 文件，並在 proposal/design/tasks 執行期間維持參考同步。

#### Scenario: change 包含會影響文件的任務
- **WHEN** 任務包含行為或流程更新
- **THEN** 任務 MUST 在 change 完成前明確列出需更新的 canonical 文件

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

