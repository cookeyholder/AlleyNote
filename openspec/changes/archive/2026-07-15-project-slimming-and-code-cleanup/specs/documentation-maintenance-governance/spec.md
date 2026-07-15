## MODIFIED Requirements

### Requirement: 文件類型與歸檔位置
**FROM:** 文件僅區分 canonical 文件與一般文件
**TO:** 文件系統 SHALL 區分三層：專案根文件（`README.md`、`AGENTS.md`）、`docs/` 目錄下的使用中文件、以及 `docs/archive/` 下的歸檔文件

#### Scenario: 歸檔文件不影響搜尋
- **WHEN** 搜尋使用中文件
- **THEN** `docs/archive/` 的內容不應被當作有效技術文件

### Requirement: 分析報告歸檔規範
文件治理 SHALL 規範將非執行期產生的技術分析報告（如 PHPStan HTML report）存放於 `docs/archive/`。

#### Scenario: 分析報告歸檔
- **WHEN** 產生靜態分析 HTML 報告
- **THEN** 報告 SHALL 存放於 `docs/archive/` 而非 `backend/storage/`
