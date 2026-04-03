## MODIFIED Requirements

### Requirement: 標準化開發環境
系統 SHALL 提供基於 Docker 容器的標準化開發環境，包含 PHP 8.4、Node.js 24+ 與 Git，並且 MUST 在開發文件中以單一 canonical 入口維護啟動、測試與除錯流程，避免多份文件描述不一致。

#### Scenario: 啟動開發容器
- **WHEN** 使用者在 VS Code 中執行 `Reopen in Container`
- **THEN** 系統應成功建置並進入包含 PHP 與 Node.js 環境的容器

#### Scenario: 查詢開發流程文件
- **WHEN** 使用者需要查詢本機啟動、測試與偵錯步驟
- **THEN** 使用者 MUST 可透過 canonical 文件入口取得與目前程式碼與工作流程一致的說明
