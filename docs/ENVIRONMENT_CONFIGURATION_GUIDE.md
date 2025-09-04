# AlleyNote 環境變數配置指南

**版本**: v4.0
**更新日期**: 2025-09-03
**架構**: 前後端分離 (Vue.js 3 + PHP 8.4.12 DDD)
**系統版本**: Docker 28.3.3, Docker Compose v2.39.2

## 📋 概述

AlleyNote 在前後端分離架構中使用環境變數來管理不同環境下的配置參數。本文件詳細說明了前端 (Vue.js 3) 和後端 (PHP 8.4.12) 的所有可用環境變數及其用途。

## 🌍 環境類型

### 支援的環境
- **development** - 開發環境
- **testing** - 測試環境
- **production** - 生產環境

### 環境配置檔案 (前後端分離)

#### 後端配置 (PHP 8.4.12)
- `backend/.env.development` - 開發環境配置
- `backend/.env.testing` - 測試環境配置
- `backend/.env.production` - 生產環境配置
- `backend/.env.example` - 後端配置範本檔案

#### 前端配置 (Vue.js 3)
- `frontend/.env.development` - 開發環境配置
- `frontend/.env.testing` - 測試環境配置
- `frontend/.env.production` - 生產環境配置
- `frontend/.env.example` - 前端配置範本檔案

## ⚙️ 後端配置項目說明 (PHP 8.4.12)

### 應用程式基本設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `APP_NAME` | 應用程式名稱 | `AlleyNote API` | `AlleyNote API` |
| `APP_ENV` | 執行環境 | `development` | `production` |
| `APP_DEBUG` | 偵錯模式 | `true` | `false` |
| `APP_URL` | 後端 API 網址 | `http://localhost:8080` | `https://api.your-domain.com` |
| `APP_KEY` | 應用程式金鑰 | - | `base64:your-key-here` |
| `APP_TIMEZONE` | 時區設定 | `Asia/Taipei` | `UTC` |
| `PHP_VERSION` | PHP 版本 | `8.4.12` | `8.4.12` |

### 資料庫設定 (SQLite3 優先推薦)

#### SQLite3 設定 (預設推薦)
| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `DB_CONNECTION` | 資料庫類型 | `sqlite` | `sqlite` |
| `DB_DATABASE` | 資料庫檔案路徑 | `/var/www/html/database/alleynote.sqlite3` | `/app/data/alleynote.sqlite3` |

#### PostgreSQL 設定 (大型部署時使用)
| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `DB_CONNECTION` | 資料庫類型 | `pgsql` | `pgsql` |
| `DB_DATABASE` | 資料庫名稱 | `alleynote` | `alleynote_prod` |
| `DB_HOST` | 資料庫主機 | `postgres` | `localhost` |
| `DB_PORT` | 資料庫連接埠 | `5432` | `5432` |
| `DB_USERNAME` | 資料庫使用者 | `alleynote` | `alleynote_user` |
| `DB_PASSWORD` | 資料庫密碼 | - | `secure_password` |
| `POSTGRES_PASSWORD` | PostgreSQL 密碼 | - | `postgres_password` |
| `DB_SCHEMA` | 資料庫結構描述 | `public` | `alleynote_schema` |

### API 與 CORS 設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `API_PREFIX` | API 路由前綴 | `/api` | `/api/v1` |
| `CORS_ALLOWED_ORIGINS` | 允許的來源 | `http://localhost:3000` | `https://your-domain.com` |
| `CORS_ALLOWED_METHODS` | 允許的 HTTP 方法 | `GET,POST,PUT,DELETE,OPTIONS` | `GET,POST,PUT,DELETE` |
| `CORS_ALLOWED_HEADERS` | 允許的標頭 | `Content-Type,Authorization,X-Requested-With` | - |

### 檔案上傳設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `UPLOAD_MAX_SIZE` | 檔案大小限制 | `50M` | `100M` |
| `UPLOAD_MAX_FILES` | 同時上傳檔案數 | `10` | `20` |
| `ALLOWED_FILE_TYPES` | 允許的檔案類型 | `jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx` | `jpg,png,pdf` |

## 🎯 前端配置項目說明 (Vue.js 3)

### 應用程式基本設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `VITE_APP_NAME` | 前端應用程式名稱 | `AlleyNote` | `AlleyNote` |
| `VITE_APP_ENV` | 前端執行環境 | `development` | `production` |
| `VITE_APP_VERSION` | 應用程式版本 | `4.0.0` | `4.0.0` |
| `VITE_BASE_URL` | 前端根路徑 | `/` | `/alleynote/` |

### API 連線設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `VITE_API_BASE_URL` | 後端 API 網址 | `http://localhost:8080/api` | `https://api.your-domain.com/api` |
| `VITE_API_TIMEOUT` | API 請求逾時 | `30000` | `60000` |
| `VITE_API_RETRY_ATTEMPTS` | 重試次數 | `3` | `5` |
| `STORAGE_PATH` | 檔案儲存路徑 | `/var/www/alleynote/storage/files` | `/custom/path` |

### 快取設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `CACHE_DRIVER` | 快取驅動程式 | `file` | `redis` |
| `CACHE_TTL` | 快取生存時間 | `3600` | `1800` |
| `CACHE_PATH` | 檔案快取路徑 | `/var/www/html/storage/cache` | `/tmp/cache` |
| `REDIS_HOST` | Redis 主機 | `redis` | `localhost` |
| `REDIS_PORT` | Redis 連接埠 | `6379` | `6379` |
| `REDIS_PASSWORD` | Redis 密碼 | `null` | `password` |

### 安全性設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `SESSION_LIFETIME` | 會話生存時間（分鐘） | `120` | `60` |
| `CSRF_TOKEN_LIFETIME` | CSRF 令牌生存時間（分鐘） | `120` | `60` |
| `RATE_LIMIT_REQUESTS` | 速率限制請求數 | `60` | `100` |
| `RATE_LIMIT_MINUTES` | 速率限制時間窗口 | `1` | `5` |

### JWT 設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `JWT_ALGORITHM` | JWT 簽名算法 | `RS256` | `HS256` |
| `JWT_ISSUER` | JWT 發行者 | `alleynote-api` | `your-api` |
| `JWT_AUDIENCE` | JWT 接收者 | `alleynote-client` | `your-client` |
| `JWT_ACCESS_TOKEN_TTL` | 存取權杖生存時間（秒） | `3600` | `7200` |
| `JWT_REFRESH_TOKEN_TTL` | 重新整理權杖生存時間（秒） | `2592000` | `604800` |
| `JWT_PRIVATE_KEY` | JWT 私鑰 | - | `-----BEGIN PRIVATE KEY-----...` |
| `JWT_PUBLIC_KEY` | JWT 公鑰 | - | `-----BEGIN PUBLIC KEY-----...` |

### SSL/HTTPS 設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `SSL_DOMAIN` | SSL 域名 | `your-domain.com` | `example.com` |
| `SSL_EMAIL` | SSL 聯絡信箱 | `admin@your-domain.com` | `admin@example.com` |
| `CERTBOT_STAGING` | 使用 Let's Encrypt 測試環境 | `true` | `false` |
| `FORCE_HTTPS` | 強制使用 HTTPS | `true` | `false` |
| `HSTS_MAX_AGE` | HSTS 最大年限 | `63072000` | `31536000` |

### 使用者活動記錄設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `ACTIVITY_LOG_LEVEL` | 記錄等級 | `info` | `debug` |
| `ACTIVITY_LOG_ENABLED` | 啟用活動記錄 | `true` | `false` |
| `ACTIVITY_LOG_RETENTION_DAYS` | 資料保留天數 | `365` | `30` |
| `ACTIVITY_LOG_CLEANUP_ENABLED` | 啟用自動清理 | `true` | `false` |
| `ACTIVITY_LOG_BATCH_SIZE` | 批次處理大小 | `100` | `50` |
| `ACTIVITY_LOG_MAX_CONTEXT_LENGTH` | 上下文最大長度 | `512` | `1024` |
| `ACTIVITY_LOG_INCLUDE_STACK_TRACE` | 包含堆疊追蹤 | `false` | `true` |
| `ACTIVITY_LOG_MONITOR_PERFORMANCE` | 監控效能 | `true` | `false` |
| `ACTIVITY_LOG_MONITOR_SUSPICIOUS_ACTIVITY` | 監控可疑活動 | `true` | `false` |
| `ACTIVITY_LOG_ALERT_THRESHOLD_PER_MINUTE` | 警告閾值（每分鐘） | `10` | `100` |

### 日誌設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `LOG_LEVEL` | 日誌等級 | `error` | `debug` |
| `LOG_PATH` | 日誌檔案路徑 | `/var/www/html/storage/logs/app.log` | `/custom/logs/app.log` |
| `LOG_CHANNEL` | 日誌頻道 | `single` | `daily` |
| `LOG_MAX_FILES` | 最大日誌檔案數 | `10` | `30` |

### 備份設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `BACKUP_RETENTION_DAYS` | 備份保留天數 | `30` | `90` |
| `BACKUP_PATH` | 備份路徑 | `/var/www/alleynote/storage/backups` | `/backup` |
| `AUTO_BACKUP_ENABLED` | 自動備份 | `false` | `true` |
| `AUTO_BACKUP_SCHEDULE` | 備份排程 | - | `0 2 * * *` |

### 管理員設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `ADMIN_EMAIL` | 管理員信箱 | `admin@example.com` | `admin@your-domain.com` |
| `ADMIN_PASSWORD` | 管理員密碼 | `Admin@123` | `StrongPassword@2024` |

### 通知設定

| 變數名稱 | 說明 | 預設值 | 範例 |
|---------|------|-------|------|
| `TELEGRAM_BOT_TOKEN` | Telegram 機器人 Token | - | `1234567890:ABC...` |
| `TELEGRAM_CHAT_ID` | Telegram 聊天室 ID | - | `-1001234567890` |

## 🔧 環境設定最佳實務

### 開發環境
- 啟用偵錯模式 (`APP_DEBUG=true`)
- 使用檔案快取 (`CACHE_DRIVER=file`)
- 詳細日誌等級 (`LOG_LEVEL=debug`)
- 放寬檔案上傳限制
- 啟用所有活動記錄功能

### 測試環境
- 使用記憶體資料庫 (`DB_DATABASE=:memory:`)
- 使用陣列快取 (`CACHE_DRIVER=array`)
- 關閉不必要的監控功能
- 停用外部服務整合
- 最小日誌記錄

### 生產環境
- 關閉偵錯模式 (`APP_DEBUG=false`)
- 使用 Redis 快取 (`CACHE_DRIVER=redis`)
- 啟用 HTTPS (`FORCE_HTTPS=true`)
- 嚴格的檔案上傳限制
- 啟用所有安全監控功能
- 定期備份設定

## 🛡️ 安全注意事項

### 敏感資訊保護
- **永遠不要**將 `.env.*` 檔案提交到版本控制
- 定期更換 JWT 金鑰對
- 使用強密碼設定管理員帳號
- 在生產環境中修改所有預設值

### 權限設定
```bash
# 設定環境檔案權限
chmod 600 .env.*

# 設定擁有者
chown www-data:www-data .env.*
```

### 金鑰管理
```bash
# 生成 JWT 金鑰對
php scripts/jwt-setup.php setup

# 生成應用程式金鑰
php scripts/generate-app-key.php
```

## 📝 配置驗證

### 使用 EnvironmentConfig 類別驗證
```php
use App\Shared\Config\EnvironmentConfig;

$config = new EnvironmentConfig('production');
$errors = $config->validate();

if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "配置錯誤: {$error}\n";
    }
}
```

### 命令列驗證
```bash
# 驗證當前環境配置
docker-compose exec web php scripts/validate-config.php

# 驗證特定環境配置
docker-compose exec web php scripts/validate-config.php --env=production
```

## 🔄 環境切換

### 手動切換
```bash
# 複製對應環境的配置
cp .env.production .env

# 重新載入配置
docker-compose down
docker-compose up -d
```

### 自動化部署
建議使用 CI/CD 工具自動複製對應環境的配置檔案，並確保敏感資訊通過安全的方式注入（如 Docker secrets 或 環境變數）。

## 🆘 常見問題

### Q: 為什麼我的配置沒有生效？
A: 確認以下幾點：
1. 環境檔案是否存在且格式正確
2. Docker 容器是否已重新啟動
3. 檔案權限是否正確
4. 環境變數是否被正確載入

### Q: 如何檢查目前載入的環境變數？
A: 使用以下命令：
```bash
docker-compose exec web env | grep APP_
docker-compose exec web php -r "echo getenv('APP_ENV');"
```

### Q: JWT 金鑰設定錯誤怎麼辦？
A: 使用 JWT 設定腳本重新生成：
```bash
docker-compose exec web php scripts/jwt-setup.php setup
```

## 📚 相關文件

- [部署指南](DEPLOYMENT.md)
- [管理員手冊](ADMIN_MANUAL.md)
- [JWT 認證規格](JWT_AUTHENTICATION_SPECIFICATION.md)
- [資料庫優化報告](DATABASE_OPTIMIZATION_REPORT.md)
- [使用者活動記錄架構](USER_ACTIVITY_LOGGING_ARCHITECTURE.md)
