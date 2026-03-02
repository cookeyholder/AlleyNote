# Capability: Docker Integration

## Purpose
確保開發環境具備操作 Docker 資源的能力，特別是在容器內部執行 Docker 指令 (Docker-in-Docker)，以支援完整的功能測試與容器管理。

## Requirements

### Requirement: Docker-in-Docker 整合
開發環境 SHALL 支援 Docker-in-Docker (DinD)，允許開發者在容器內部執行 Docker 指令。

#### Scenario: 驗證 Docker 指令
- **WHEN** 使用者在開發容器終端機輸入 'docker ps'
- **THEN** 系統應正確顯示主機或子容器中的進程列表
