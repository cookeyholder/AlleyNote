## ADDED Requirements

### Requirement: 內容管理者使用手冊
系統 SHALL 提供一份專為非技術的內容管理者撰寫的操作指南，取代現有命名誤導的 FRONTEND_USER_GUIDE.md。

#### Scenario: 內容管理者找到操作指南
- **WHEN** 內容管理者從 `README.md` 的「你是誰？」段落選擇「內容管理者」
- **THEN** 被引導至 `docs/guides/content-creators/01-管理後台使用手冊.md`
- **AND** 文件中不包含任何程式碼、終端機指令或技術術語（如 API、DTO、路由）

#### Scenario: 操作指南涵蓋核心管理功能
- **WHEN** 內容管理者閱讀操作指南
- **THEN** 文件中 MUST 涵蓋：登入/登出、文章 CRUD、標籤管理、使用者管理、統計檢視、系統設定
- **AND** 每個操作步驟 MUST 以「點擊 → 輸入 → 確認」的格式描述

### Requirement: 目錄 README.md
內容管理者目錄 SHALL 包含 README.md，簡要說明該目錄的用途與目標讀者。

#### Scenario: 目錄概覽顯示角色說明
- **WHEN** 內容管理者瀏覽 `docs/guides/content-creators/`
- **THEN** `README.md` MUST 說明「本目錄文件給不熟悉技術的管理者使用」
