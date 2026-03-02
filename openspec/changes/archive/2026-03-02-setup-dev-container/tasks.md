## 1. 配置 Dev Container 基礎架構

- [x] 1.1 建立 `.devcontainer/` 目錄與基礎 `devcontainer.json`。
- [x] 1.2 設定 `docker-in-docker` 特性 (Feature) 配置。
- [x] 1.3 移除對 `/var/run/docker.sock` 的所有路徑映射設定。

## 2. 優化 Docker 儲存與效能

- [x] 2.1 建立具名的 `dind-var-lib-docker` 磁碟卷 (Volume)。
- [x] 2.2 在 `devcontainer.json` 中配置將該磁碟卷映射至 `/var/lib/docker`。
- [x] 2.3 設定 `runArgs` 以包含 `--privileged` 權限。

## 3. 整合開發工具與擴充套件

- [x] 3.1 確保 `Dockerfile` 或 `devcontainer.json` 已正確安裝 PHP 8.4。
- [x] 3.2 整合 Node.js 24 與 npm 環境。
- [x] 3.3 設定 VS Code 擴充套件自動安裝清單（PHP, ESLint, Tailwind, Prettier）。

## 4. 驗證完全隔離性

- [x] 4.1 進入容器內執行 `docker images`，確認不會顯示宿主機的映像。
- [x] 4.2 進入容器內執行 `docker ps`，確認不會看到宿主機正在執行的其他容器。
- [x] 4.3 在容器內執行 `docker build` 並測試 `alleynote` 的內部 Docker 運作。
