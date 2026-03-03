## ADDED Requirements

### Requirement: Docker-in-Docker 整合
開發環境 SHALL 支援 Docker-in-Docker (DinD)，允許開發者在容器內部執行 Docker 指令。

#### Scenario: 驗證 Docker 指令
- **WHEN** 使用者在開發容器終端機輸入 'docker ps'
- **THEN** 系統應正確顯示主機或子容器中的進程列表
