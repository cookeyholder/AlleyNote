# AlleyNote 公布欄網站

[![測試](https://github.com/your-org/alleynote/workflows/測試/badge.svg)](https://github.com/your-org/alleynote/actions)
[![程式碼品質](https://github.com/your-org/alleynote/workflows/程式碼品質/badge.svg)](https://github.com/your-org/alleynote/actions)
[![部署](https://github.com/your-org/alleynote/workflows/部署/badge.svg)](https://github.com/your-org/alleynote/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.4.5-blue.svg)](https://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 專案簡介

AlleyNote 是一個現代化的公布欄網站系統，提供完整的文章管理、附件上傳、IP 控制等功能。系統採用 PHP 8.4.5 開發，使用 SQLite 資料庫，並透過 Docker 容器化技術進行部署。

### 主要功能

- 使用者認證與權限管理
- 文章發布與管理
- 附件上傳與管理
- IP 黑白名單控制
- 自動備份機制

## 系統需求

### 硬體需求
- CPU: 2 核心以上
- 記憶體: 4GB 以上
- 硬碟空間: 20GB 以上

### 軟體需求
- Debian Linux 12
- Docker 24.0.0+
- Docker Compose 2.20.0+
- PHP 8.4.5
- SQLite3
- NGINX

## 快速開始

### 安裝步驟

1. 複製專案
```bash
git clone https://github.com/your-org/alleynote.git
cd alleynote
```

2. 設定環境變數
```bash
cp .env.example .env
# 編輯 .env 檔案，設定必要的環境變數：
# - 管理員帳號密碼
# - 資料庫設定
# - 檔案上傳設定
# - Telegram 通知設定
```

3. 啟動服務
```bash
docker-compose up -d
```

4. 執行資料庫遷移
```bash
docker-compose exec php php /var/www/html/vendor/bin/phinx migrate
```

### 基本使用

1. 開啟瀏覽器訪問 `http://localhost`
2. 使用預設管理員帳號登入：
   - 帳號：admin@example.com
   - 密碼：請查看 .env.example 檔案中的 ADMIN_PASSWORD

## 開發指南

### 開發環境設定

1. 安裝開發相依套件
```bash
docker-compose exec php composer install
```

2. 執行測試
```bash
# 在 Docker 容器中執行所有測試
docker-compose exec php ./vendor/bin/phpunit

# 執行特定測試類別
docker-compose exec php ./vendor/bin/phpunit tests/Unit/PostTest.php

# 產生測試覆蓋率報告
docker-compose exec php ./vendor/bin/phpunit --coverage-html coverage/
```

### 程式碼規範

- 遵循 PSR-12 編碼規範
- 使用 PHP 8.4 型別宣告
- 撰寫完整的單元測試
- 使用 Conventional Commits 規範

## 專案結構

```
alleynote/
├── database/              # 資料庫遷移檔案
│   └── migrations/       # 資料庫遷移腳本
├── docker/               # Docker 相關設定
│   ├── nginx/           # Nginx 設定
│   └── php/            # PHP 設定
├── public/              # 公開存取目錄
├── scripts/             # 部署與維護腳本
├── src/                 # 應用程式源碼
│   ├── Controllers/    # 控制器
│   ├── Database/      # 資料庫連線
│   ├── Exceptions/    # 例外處理
│   ├── Helpers/       # 輔助函式
│   ├── Middleware/    # 中介層
│   ├── Models/        # 資料模型
│   ├── Repositories/  # 資料存取層
│   └── Services/      # 業務邏輯層
├── storage/             # 儲存空間
│   └── app/           # 應用程式檔案
└── tests/              # 測試程式碼
    ├── Integration/   # 整合測試
    ├── Security/     # 安全性測試
    ├── UI/          # 使用者介面測試
    └── Unit/        # 單元測試
```

## 持續整合與部署

本專案使用 GitHub Actions 進行持續整合與部署，包含三個主要工作流程：

### 1. 測試工作流程
- 在 Docker 容器中執行單元測試與整合測試
- 產生測試覆蓋率報告
- 檢查程式碼風格
- 執行靜態分析

### 2. 程式碼品質檢查
- 檢查程式碼風格 (PSR-12)
- 執行靜態分析 (PHPStan)
- 檢查程式碼複雜度 (PHPMD)
- 檢查重複程式碼 (PHPCPD)
- 檢查相依套件安全性
- SonarCloud 程式碼掃描

### 3. 自動部署流程
- 自動部署到生產環境
- 版本發布管理
- Telegram 通知整合
- 自動備份機制

### GitHub Actions 設定
- `.github/workflows/tests.yml`: 測試工作流程
- `.github/workflows/code-quality.yml`: 程式碼品質檢查
- `.github/workflows/deploy.yml`: 部署工作流程

### 必要的 Secrets 設定
- `ENV_PRODUCTION`: 生產環境的 .env 檔案內容
- `SSH_PRIVATE_KEY`: 部署用 SSH 私鑰
- `SSH_KNOWN_HOSTS`: 部署主機的公鑰
- `DEPLOY_PATH`: 部署目標路徑
- `DEPLOY_USER`: 部署用使用者
- `DEPLOY_HOST`: 部署主機位址
- `TELEGRAM_BOT_TOKEN`: Telegram Bot Token
- `TELEGRAM_CHAT_ID`: Telegram 通知頻道 ID
- `SONAR_TOKEN`: SonarCloud 存取令牌

## 測試

### 測試環境
所有測試都在 Docker 容器中執行，確保測試環境的一致性。

### 測試覆蓋率要求
- Repository 層：90%
- Service 層：85%
- Controller 層：80%

## 部署

詳細的部署說明請參考 [DEPLOYMENT.md](DEPLOYMENT.md)。

### 快速部署
```bash
# 執行自動化部署
./scripts/deploy.sh

# 系統回滾（如需要）
./scripts/rollback.sh
```

## 版本資訊

### 版本號說明
- 主版本號：重大更新
- 次版本號：功能更新
- 修訂號：錯誤修復

### 更新日誌
請參考 [CHANGELOG.md](CHANGELOG.md)

## 授權資訊

本專案採用 MIT 授權條款 - 詳見 [LICENSE](LICENSE) 檔案

## 技術支援

- 問題回報：請使用 GitHub Issues
- 技術討論：請加入 Telegram 群組
- 電子郵件：support@example.com

## 貢獻者

感謝所有對本專案做出貢獻的開發者。

## 分支開發記錄

### feature/deployment-preparation 分支

#### 實作重點
1. **部署文件準備**
   - 建立完整的部署指南 (DEPLOYMENT.md)
   - 系統需求說明
   - 安裝步驟說明
   - 環境設定指南
   - 部署流程說明

2. **部署腳本開發**
   - deploy.sh：自動化部署流程
   - rollback.sh：系統回滾機制
   - backup_db.sh：資料庫備份
   - backup_files.sh：檔案備份
   - restore_db.sh：資料庫還原
   - restore_files.sh：檔案還原

3. **備份機制設計**
   - 資料庫定期備份
   - 檔案系統備份
   - 自動清理過期備份
   - 備份還原機制

#### 技術實作細節
1. **部署流程自動化**
   - 使用 Shell Script 撰寫部署腳本
   - 整合 Docker Compose 指令
   - 實作錯誤處理機制
   - 加入部署狀態檢查

2. **備份策略實作**
   - SQLite 資料庫備份
   - 檔案系統備份
   - 使用 tar 和 gzip 進行壓縮
   - 實作備份輪替機制

#### 未來優化方向
1. 加入自動化測試到部署流程
2. 實作藍綠部署機制
3. 強化監控指標
4. 優化備份效能
5. 加入容器健康檢查
6. 整合 Prometheus 監控系統
7. 設定 Grafana 視覺化面板
8. 建立完整的日誌管理系統
