## MODIFIED Requirements

### Requirement: Canonical 文件拓樸
**變更**: 加入 reader persona 分流欄位；每個子目錄使用 README.md 作為概覽入口；所有文件使用繁體中文檔名 + 數字 prefix

#### Scenario: 貢獻者從儲存庫根目錄開始
- **WHEN** 貢獻者開啟儲存庫並需要操作指引
- **THEN** 貢獻者 MUST 能從 `README.md` 進入 canonical 文件索引
- **AND** INDEX.md 的表格中 MUST 包含「適合對象」欄位，涵蓋 5 種讀者（後端開發者、前端開發者、內容管理者、系統管理員、API 整合者）

#### Scenario: 開發者尋找 ADR
- **WHEN** 開發者需要了解某次重構的決策背景
- **THEN** `docs/INDEX.md` MUST 提供 `docs/decisions/` 的連結
- **AND** `docs/decisions/README.md` MUST 列出所有 ADR 的摘要與適用情境

#### Scenario: 瀏覽子目錄時看到概覽
- **WHEN** 開發者在 GitHub 或檔案系統中瀏覽 `docs/architecture/`
- **THEN** `docs/architecture/README.md` MUST 自動顯示為目錄概覽
- **AND** 其他子目錄（decisions、domains、frontend、api、runbooks、guides）SHALL 遵循相同模式

#### Scenario: 文件按閱讀順序排列
- **WHEN** 開發者在終端機執行 `ls docs/architecture/`
- **THEN** 檔案 MUST 以數字 prefix（01-、02- 等）排序
- **AND** 數字順序 SHALL 對應建議閱讀順序

### Requirement: 重構後技術細節入口
**變更**: 將 architecture/ 與 domains/ 納入重構技術細節的入口

#### Scenario: 貢獻者從 README 導覽重構技術文件
- **WHEN** 貢獻者從 `README.md` → `docs/INDEX.md` 導覽
- **THEN** MUST 可抵達 `docs/architecture/` 與 `docs/domains/` 下的設計文件

### Requirement: 繁體中文命名規則
**新增**: 所有文件使用繁體中文檔名，英文術語保留原文

#### Scenario: 檔案命名一致
- **WHEN** 開發者檢視 `docs/` 下的任一檔案
- **THEN** 檔名 MUST 使用繁體中文描述（如 `01-統計分析器模式.md`）
- **AND** 專業術語（如 ADR、DTO、API）SHALL 保留英文
- **AND** 數字 prefix SHALL 使用兩位數（01-99）
