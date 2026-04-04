## ADDED Requirements

### Requirement: 重構後技術細節入口
儲存庫 SHALL 在 canonical 文件拓樸中提供重構後技術細節的固定入口，並從根目錄導覽可直接抵達該入口。

#### Scenario: 貢獻者從 README 導覽重構技術文件
- **WHEN** 貢獻者需要理解重大重構後的技術設計
- **THEN** `README.md` MUST 提供可達 canonical 技術文件的明確連結

### Requirement: 重構技術章節最小結構
重大重構技術文件 SHALL 至少涵蓋例外處理策略、資料轉換策略與測試基礎設施三類章節，並描述擴充邊界與禁止做法。

#### Scenario: 審查者驗證重構文件完整性
- **WHEN** 審查者檢視重構後文件內容
- **THEN** 文件 MUST 明確說明 `ExceptionRegistry` / `ApiExceptionInterface`、`ApiResource`、`ApiTestCase` 的設計目的與使用約束
