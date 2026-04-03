## ADDED Requirements

### Requirement: Canonical 文件拓樸
儲存庫 SHALL 提供一套 canonical 文件拓樸，包含單一主要入口，以及針對安裝、架構、維運與貢獻指引所定義的唯一可信來源文件。

#### Scenario: 貢獻者從儲存庫根目錄開始
- **WHEN** 貢獻者開啟儲存庫並需要操作指引
- **THEN** 貢獻者 MUST 能從 `README.md` 進入 canonical 文件索引，而不需依賴重複文件

### Requirement: 文件整併映射
儲存庫 SHALL 在執行清理前，維護一份明確的 legacy 文件對 canonical 目的地映射（merge、archive 或 remove）。

#### Scenario: legacy 文件被取代
- **WHEN** 文件被判定為重複或過時
- **THEN** 此 change MUST 記錄該文件是整併至 canonical 檔案、歸檔保存，或附理由移除

### Requirement: 交叉參考完整性
Canonical 文件 SHALL 使用穩定的內部連結與章節參照，讓所有關鍵流程都可由文件索引導覽到。

#### Scenario: 使用者跟隨流程連結
- **WHEN** 使用者從索引頁導覽到流程文件
- **THEN** 所有被引用的路徑與命令 MUST 對應到現有檔案與目前工具鏈
