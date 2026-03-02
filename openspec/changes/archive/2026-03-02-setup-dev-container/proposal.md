## Why

為了確保開發者能在一個與主機環境「完全隔離」且一致的環境下進行協作。這不僅能避免「在我的機器上可以執行」的問題，更能防止開發過程中的 Docker 操作（如 Build, Prune）意外影響到主機上的其他專案或容器。

## What Changes

- 新增 `.devcontainer/` 目錄及其設定檔。
- **核心架構變更**：配置 Docker-in-Docker (DinD) 支援，在開發容器內運行獨立的 Docker 引擎，實現完全的資源隔離。
- 整合 PHP 8.4 開發環境、前端工具（Node.js 24.x LTS, npm, Vite 7）以及必要的 VS Code 擴充套件。
- 配置專用的具名磁碟卷 (Named Volume)，用於存放內部 Docker 的資料與映像檔，以優化 DinD 的效能。

## Capabilities

### New Capabilities
- `isolated-dev-environment`: 提供一個與主機資源完全隔離的開發環境規格。
- `docker-in-docker-integration`: 實現在容器內部執行完整、獨立的 Docker 引擎能力。


### Modified Capabilities

## Impact

- 影響開發流程與環境部署。
- 新增對 `.devcontainer/` 目錄的維護。
- 不影響現有的 production 程式碼邏輯，但會改進本地測試與執行的方式。
