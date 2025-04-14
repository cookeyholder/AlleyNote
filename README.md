# AlleyNote 公布欄網站

[![Build Status](https://github.com/your-org/alleynote/workflows/Tests/badge.svg)](https://github.com/your-org/alleynote/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.4.5-blue.svg)](https://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 專案簡介

AlleyNote 是一個現代化的公布欄網站系統，提供完整的文章管理、附件上傳、IP 控制等功能。系統採用 PHP 8.4.5 開發，使用 SQLite 資料庫，並透過 Docker 容器化技術進行部署。

### 主要功能

- 使用者認證與權限管理
- 文章發布與管理
- 附件上傳與管理
- IP 黑白名單控制
- 系統監控與日誌
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
# 編輯 .env 檔案，設定必要的環境變數
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
   - 密碼：請查看 .env.example 檔案

## 開發指南

### 開發環境設定

1. 安裝開發相依套件
```bash
composer install
```

2. 執行測試
```bash
./vendor/bin/phpunit
```

### 程式碼規範

- 遵循 PSR-12 編碼規範
- 使用 PHP 8.4 型別宣告
- 撰寫完整的單元測試
- 使用 Conventional Commits 規範

## 專案結構

```
alleynote/
├── app/                    # 應用程式核心程式碼
├── config/                 # 設定檔案
├── database/              # 資料庫相關
├── public/               # 公開存取目錄
├── resources/            # 前端資源
├── routes/               # 路由設定
├── storage/              # 儲存空間
├── tests/                # 測試程式碼
└── vendor/               # Composer 套件
```

## 測試

### 執行測試
```bash
# 執行所有測試
./vendor/bin/phpunit

# 執行特定測試類別
./vendor/bin/phpunit tests/Unit/PostTest.php

# 產生測試覆蓋率報告
./vendor/bin/phpunit --coverage-html coverage/
```

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
- 技術討論：請加入 Slack 頻道
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
   - 監控設定指南

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

4. **監控系統整合**
   - Prometheus 監控設定
   - Grafana 儀表板配置
   - 系統警報機制
   - 日誌管理系統

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

3. **監控系統設定**
   - 配置 Prometheus 監控指標
   - 設定 Grafana 視覺化面板
   - 整合 ELK Stack 日誌管理
   - 實作警報通知機制

#### 未來優化方向
1. 加入自動化測試到部署流程
2. 實作藍綠部署機制
3. 強化監控指標
4. 優化備份效能
5. 加入容器健康檢查
