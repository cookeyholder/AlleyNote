## ADDED Requirements

### Requirement: ADR 涵蓋 7 次重構
系統 SHALL 為以下 7 次重構各建立一份 Architecture Decision Record，存放在 `docs/decisions/`：

1. Statistics DTO Analyzer 萃取
2. PostRepository 拆分為三職責
3. IP 邏輯統一至 NetworkHelper
4. JWT Auth Strategy 模式
5. TagController 內嵌頁面萃取
6. Frontend Admin BasePage
7. 安全與效能審計修復

#### Scenario: 每份 ADR 包含必要章節
- **WHEN** 開發者開啟 `docs/decisions/ADR-*.md`
- **THEN** 文件中必須包含：標題、狀態、背景、決策、結果
- **AND** 可選包含：替代方案、取捨、相關連結

### Requirement: ADR 格式一致性
所有 ADR SHALL 遵循相同的結構模板，確保可讀性與可搜尋性。

#### Scenario: ADR 使用輕量模板
- **WHEN** 開發者檢視任一份 ADR
- **THEN** 文件開頭包含 `# ADR-N: 標題` 與 `**狀態**: 已完成` 的元資料區塊
- **AND** 章節順序為：背景 → 決策 → 結果 → 替代方案（可選）
