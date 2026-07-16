## ADDED Requirements

### Requirement: 為每個 Bounded Context 建立 OVERVIEW.md
系統 SHALL 為以下 6 個 Bounded Context 建立 `docs/domains/<context>/OVERVIEW.md`：

1. Post — 含 Repository 拆分說明
2. Auth — 含 JWT 策略模式說明
3. Statistics — 含 Analyzer 模式說明
4. Security — 速率限制、CSP、安全審計
5. Attachment — 檔案上傳管理
6. Setting — 系統設定

#### Scenario: 開發者查看領域概述
- **WHEN** 開發者開啟 `docs/domains/post/OVERVIEW.md`
- **THEN** 文件中包含：BC 職責說明、關鍵檔案對照表（Models/Services/Repositories）、依賴方向、設計決策摘要

#### Scenario: OVERVIEW.md 列出閱讀對象
- **WHEN** 開發者開啟任一份 OVERVIEW.md
- **THEN** 文件頂部標示 **適用讀者**（如：後端開發者、新進成員）

### Requirement: 領域文件附檔案對照表
每份 OVERVIEW.md SHALL 包含該 BC 的核心檔案結構對照，讓開發者能從文件快速定位到程式碼。

#### Scenario: 對照表包含主要類別路徑
- **WHEN** 開發者查閱 OVERVIEW.md 的檔案對照表
- **THEN** 表格包含 Models、Services、Repositories、DTOs、Enums 等類別的路徑
