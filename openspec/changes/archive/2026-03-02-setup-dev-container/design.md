## Context

目前開發團隊成員使用不同的 OS，且主機上往往存在多個專案容器。為了避免開發過程中的 Docker 操作穿透 (Leakage) 到主機，我們需要一個 100% 獨立的 Docker 環境，確保開發容器內部的操作（如清理映像、暫停容器）與主機完全隔離。

## Goals / Non-Goals

**Goals:**
- **完全資源隔離**：容器內的 `docker ps` 與 `docker images` 必須只顯示專案內部資源。
- 整合 PHP 8.4 與 Node.js 24 LTS。
- 提供單一指令啟動的開發黑盒子。

**Non-Goals:**
- 不與主機共享 Docker 映像快取。

## Decisions

### 1. 採用 Docker-in-Docker (DinD) 架構
**決策：** 使用 VS Code Dev Containers 提供的 `docker-in-docker` 功能插件，並移除對 `/var/run/docker.sock` 的掛載。
**理由：** 只有 DinD 能實現真正的資源邊界隔離。開發容器內部的 Docker 引擎將獨立運作，所有產生的子容器均在該引擎內生成，不顯示於宿主機。

### 2. 解決 DinD 效能瓶頸
**決策：** 在 `devcontainer.json` 中配置一個具名的 Named Volume，專門掛載至容器內的 `/var/lib/docker`。
**理由：** DinD 最嚴重的效能問題來自於 OverlayFS 之上的 OverlayFS。透過映射磁碟卷到主機磁碟（繞過虛擬層），可以大幅提升映像下載與容器構建的速度。

### 3. 特權模式 (Privileged Mode) 配置
**決策：** 容器將以 `--privileged` 標籤執行。
**理由：** 這是 DinD 運作的必要條件，以允許內層 Docker 引擎存取主機系統調用。

## Risks / Trade-offs

- **[Risk] 儲存空間重複佔用** → 由於不共享主機映像，內層 Docker 會重新下載 PHP 與 Redis 映像，導致磁碟空間佔用增加。
- **[Risk] 網路雙重對映 (Double Mapping)** → 開發容器內部的服務（如 Web Server）需要先對映到 DinD Container，再從 DinD Container 對映到主機 Port。
- **[Risk] 設定複雜度** → 網路配置與 Volume 管理較為複雜。
