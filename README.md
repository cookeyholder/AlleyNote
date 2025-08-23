# AlleyNote 公布欄網站

[![測試](https://github.com/your-org/alleynote/workflows/測試/badge.svg)](https://github.com/your-org/alleynote/actions)
[![程式碼品質](https://github.com/your-org/alleynote/workflows/程式碼品質/badge.svg)](https://github.com/your-org/alleynote/actions)
[![部署](https://github.com/your-org/alleynote/workflows/部署/badge.svg)](https://github.com/your-org/alleynote/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.4.11-blue.svg)](https://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 目錄

- [AlleyNote 公布欄網站](#alleynote-公布欄網站)
  - [目錄](#目錄)
  - [專案簡介](#專案簡介)
  - [功能特色](#功能特色)
  - [技術架構](#技術架構)
  - [專案結構說明](#專案結構說明)
  - [系統需求](#系統需求)
    - [硬體需求](#硬體需求)
    - [軟體需求](#軟體需求)
  - [安裝與快速開始](#安裝與快速開始)
    - [1. 取得專案原始碼](#1-取得專案原始碼)
    - [2. 設定環境變數](#2-設定環境變數)
    - [3. 啟動服務](#3-啟動服務)
    - [4. 安裝相依套件](#4-安裝相依套件)
    - [5. 執行資料庫遷移](#5-執行資料庫遷移)
    - [6. 預設管理員登入](#6-預設管理員登入)
  - [開發流程](#開發流程)
  - [測試流程](#測試流程)
  - [CI/CD 與自動化](#cicd-與自動化)
  - [部署說明](#部署說明)
  - [常見問題 FAQ](#常見問題-faq)
  - [貢獻指南](#貢獻指南)
  - [授權](#授權)
  - [聯絡方式](#聯絡方式)

---

## 專案簡介

AlleyNote 是一個現代化的公布欄網站系統，專為學校、社區、企業等單位設計，支援多用戶、權限控管、IP 黑白名單、附件上傳、資料自動備份等功能。  
本專案以 PHP 8.4.11 開發，採用 SQLite 資料庫，並以 Docker 容器化部署，具備完善的自動化測試與 CI/CD 流程。

---

## 功能特色

- 文章發布、編輯、刪除、置頂、封存
- 附件上傳、下載、刪除
- IP 黑白名單控管
- 使用者認證與權限管理
- 自動備份資料庫與檔案
- RESTful API 支援
- 完整測試套件（單元、整合、效能、安全）
- CI/CD 自動化流程
- 支援 Telegram 通知
- 完善的安全性防護（CSRF、XSS、SQL Injection）

---

## 技術架構

- **後端語言**：PHP 8.4.11
- **Web 伺服器**：NGINX
- **資料庫**：SQLite3
- **容器化**：Docker, Docker Compose
- **自動化測試**：PHPUnit, PHPStan, PHPCS
- **CI/CD**：GitHub Actions
- **通知整合**：Telegram Bot
- **作業系統**：Debian Linux 12

---

## 專案結構說明

```
AlleyNote/
├── src/                # 核心程式碼（控制器、服務、模型、Repository、Middleware、Helper）
├── public/             # 公開靜態資源與入口 index.php
├── database/           # 資料庫遷移檔
├── scripts/            # 備份、還原、部署、回滾等自動化腳本
├── tests/              # 測試（單元、整合、效能、安全、UI）
├── docker/             # Docker 與 NGINX、PHP 設定
├── .github/workflows/  # GitHub Actions CI/CD 設定
├── README.md           # 本文件
├── DEPLOYMENT.md       # 部署說明
├── CHANGELOG.md        # 版本更新日誌
└── ...                 # 其他設定檔
```

---

## 系統需求

### 硬體需求

- CPU: 2 核心以上
- 記憶體: 4GB 以上
- 硬碟空間: 20GB 以上

### 軟體需求

- Debian Linux 12
- Docker 24.0.0+
- Docker Compose 2.20.0+
- PHP 8.4.11
- SQLite3
- NGINX

---

## 安裝與快速開始

### 1. 取得專案原始碼

```bash
git clone https://github.com/your-org/alleynote.git
cd alleynote
```

### 2. 設定環境變數

```bash
cp .env.example .env
# 編輯 .env 檔案，設定管理員帳號、資料庫、檔案上傳、Telegram 通知等
```

### 3. 啟動服務

```bash
docker compose -f docker-compose.yml up -d
```

### 4. 安裝相依套件

```bash
docker compose -f docker-compose.yml exec php composer install
```

### 5. 執行資料庫遷移

```bash
docker compose -f docker-compose.yml exec php php /var/www/html/vendor/bin/phinx migrate
```

### 6. 預設管理員登入

- 請參考 .env 設定管理員帳號密碼

---

## 開發流程

1. Fork 本專案並建立 feature branch
2. 開發新功能或修正 bug
3. 撰寫/更新對應測試
4. 執行本地測試 `docker compose -f docker-compose.test.yml exec php vendor/bin/phpunit`
5. 提交 Pull Request，CI 會自動執行測試與靜態分析

---

## 測試流程

- **單元測試**：`tests/Unit/`
- **整合測試**：`tests/Integration/`
- **效能測試**：`tests/Unit/Repository/PostRepositoryPerformanceTest.php`
- **安全性測試**：`tests/Security/`
- **UI 測試**：`tests/UI/`

執行所有測試：

```bash
docker compose -f docker-compose.test.yml exec php vendor/bin/phpunit
```

---

## CI/CD 與自動化

- **GitHub Actions**：自動執行測試、靜態分析、程式碼風格檢查、測試覆蓋率上傳、Telegram 通知
- **CI 設定檔**：`.github/workflows/tests.yml`
- **測試覆蓋率門檻**：80%
- **自動部署腳本**：`scripts/deploy.sh`
- **自動回滾腳本**：`scripts/rollback.sh`

---

## 部署說明

詳細部署流程請參考 [DEPLOYMENT.md](DEPLOYMENT.md)。

---

## 常見問題 FAQ

**Q: 啟動服務時遇到權限問題？**  
A: 請確認 storage 目錄權限為 www-data，並執行 `chmod -R 755 storage`。

**Q: 測試覆蓋率未達標？**  
A: 請補齊單元測試，確保覆蓋率達 80% 以上。

**Q: 如何還原備份？**  
A: 執行 `./scripts/restore_db.sh` 及 `./scripts/restore_files.sh`。

**Q: 如何設定 Telegram 通知？**  
A: 於 .env 設定 `TELEGRAM_BOT_TOKEN` 與 `TELEGRAM_CHAT_ID`。

---

## 貢獻指南

1. 請先閱讀 [CONTRIBUTING.md]（如有）
2. Fork 專案並建立分支
3. 撰寫/更新測試
4. 提交 Pull Request，說明變更內容
5. 維護者審查後合併

---

## 授權

本專案採用 MIT 授權，詳見 [LICENSE](LICENSE)。

---

## 聯絡方式

- Maintainer: [cookeyholder]
- Telegram: [AlleyNote]
- Issues: [GitHub Issues](https://github.com/cookeyholder/alleynote/issues)
