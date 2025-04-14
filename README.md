# AlleyNote 公布欄網站

[![測試](https://github.com/your-org/alleynote/workflows/測試/badge.svg)](https://github.com/your-org/alleynote/actions)
[![程式碼品質](https://github.com/your-org/alleynote/workflows/程式碼品質/badge.svg)](https://github.com/your-org/alleynote/actions)
[![部署](https://github.com/your-org/alleynote/workflows/部署/badge.svg)](https://github.com/your-org/alleynote/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.4.5-blue.svg)](https://www.php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 專案簡介

AlleyNote 是一個現代化的公布欄網站系統，提供完整的文章管理、附件上傳、IP 控制等功能。系統採用 PHP 8.4.5 開發，使用 SQLite 資料庫，並透過 Docker 容器化技術進行部署。

### 主要功能

- 文章發布與管理
- 附件上傳與管理
- IP 黑白名單控制
- 自動備份機制

### 開發中功能
- 使用者認證系統

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

[其餘內容保持不變...]
