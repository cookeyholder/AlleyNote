# SSL 部署指南

**版本**: v4.0
**更新日期**: 2025-09-03
**架構**: 前後端分離 (Vue.js 3 + PHP 8.4.12 DDD)
**系統版本**: Docker 28.3.3, Docker Compose v2.39.2

本文件說明如何為 AlleyNote 前後端分離專案部署 Let's Encrypt SSL 憑證。

## 快速開始

### 1. 設定環境變數 (前後端分離)

#### 後端環境變數 (backend/.env)
```bash
cp backend/.env.example backend/.env
```

編輯 `backend/.env` 檔案：
```env
# SSL 設定
SSL_DOMAIN=your-domain.com
SSL_EMAIL=admin@your-domain.com
CERTBOT_STAGING=false  # 生產環境設為 false

# 後端 API URL
APP_URL=https://api.your-domain.com
API_PREFIX=/api

# 資料庫設定 (SQLite3 預設推薦)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/alleynote.sqlite3

# 如需使用 PostgreSQL (大型部署)
# DB_CONNECTION=pgsql
# DB_HOST=postgres
# DB_PORT=5432
# DB_DATABASE=alleynote
# DB_USERNAME=alleynote_user
# DB_PASSWORD=secure_password
# POSTGRES_PASSWORD=postgres_password

# CORS 設定 (前後端分離)
CORS_ALLOWED_ORIGINS=https://your-domain.com
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With

# 強制 HTTPS
FORCE_HTTPS=true
```

#### 前端環境變數 (frontend/.env)
```bash
cp frontend/.env.example frontend/.env
```

編輯 `frontend/.env` 檔案：
```env
# 前端應用程式設定
VITE_APP_NAME=AlleyNote
VITE_APP_ENV=production

# API 連線設定
VITE_API_BASE_URL=https://api.your-domain.com/api
VITE_APP_URL=https://your-domain.com

# SSL 設定
VITE_FORCE_HTTPS=true
```

### 2. 執行 SSL 設定 (前後端分離)

使用自動化腳本設定 SSL：
```bash
# 給予執行權限
chmod +x scripts/ssl-setup-separated.sh

# 執行前後端分離 SSL 設定
./scripts/ssl-setup-separated.sh your-domain.com api.your-domain.com admin@your-domain.com

# 或使用環境變數
SSL_FRONTEND_DOMAIN=your-domain.com \
SSL_BACKEND_DOMAIN=api.your-domain.com \
SSL_EMAIL=admin@your-domain.com \
./scripts/ssl-setup-separated.sh
```

### 3. 初始化資料庫

#### SQLite3 (預設推薦)
```bash
# 建立資料庫目錄
mkdir -p /var/www/html/database

# 設定權限
chmod 755 /var/www/html/database

# 執行資料庫遷移
docker-compose exec web ./vendor/bin/phinx migrate
```

#### PostgreSQL (大型部署時使用)
```bash
# 啟動資料庫服務
docker-compose -f docker-compose.production.yml up -d postgres

# 等待資料庫啟動
sleep 30

# 執行資料庫遷移
docker-compose exec web ./vendor/bin/phinx migrate
```

### 4. 切換到正式環境

測試成功後，切換到正式憑證：
```bash
# 更新環境變數
sed -i 's/CERTBOT_STAGING=true/CERTBOT_STAGING=false/' .env

# 刪除測試憑證
rm -rf ssl-data/

# 重新申請正式憑證
./scripts/ssl-setup.sh
```

## 手動設定步驟

如果不使用自動化腳本，可以按照以下步驟手動設定：

### 1. 建立必要目錄
```bash
mkdir -p ssl-data certbot-data logs/nginx logs/certbot
```

### 2. 啟動基本服務
```bash
# 啟動服務（不含資料庫，因為使用 SQLite）
docker-compose up -d web nginx redis

# 初始化 SQLite 資料庫
docker-compose exec web ./scripts/init-sqlite.sh
```

### 3. 申請 SSL 憑證
```bash
# 測試環境憑證
docker-compose run --rm certbot certonly \
  --webroot \
  --webroot-path=/var/www/certbot \
  --email admin@your-domain.com \
  --agree-tos \
  --no-eff-email \
  --staging \
  -d your-domain.com

# 正式環境憑證（移除 --staging）
docker-compose run --rm certbot certonly \
  --webroot \
  --webroot-path=/var/www/certbot \
  --email admin@your-domain.com \
  --agree-tos \
  --no-eff-email \
  -d your-domain.com
```

### 4. 重啟服務
```bash
docker-compose down
docker-compose up -d
```

## 正式環境部署

正式環境使用專用的 Docker Compose 設定：

```bash
# 使用正式環境設定啟動
docker-compose -f docker-compose.production.yml up -d

# 檢查服務狀態
docker-compose -f docker-compose.production.yml ps
```

## 憑證管理

### 手動續簽憑證
```bash
# 使用內建腳本
./scripts/ssl-renew.sh

# 或直接使用 Docker
docker-compose run --rm certbot renew
docker-compose restart nginx
```

### 設定自動續簽

建立 Cron Job：
```bash
# 編輯 crontab
crontab -e

# 新增以下行（每週一凌晨 2 點檢查續簽）
0 2 * * 1 cd /path/to/alleynote && ./scripts/ssl-renew.sh >> logs/ssl-renewal.log 2>&1
```

### 檢查憑證狀態
```bash
# 檢查憑證有效期
docker-compose exec certbot openssl x509 -in /etc/letsencrypt/live/your-domain.com/fullchain.pem -noout -enddate

# 測試續簽
docker-compose run --rm certbot renew --dry-run

# 檢查 SSL 設定
curl -I https://your-domain.com
```

## 疑難排解

### 常見問題

1. **憑證申請失敗**
   - 檢查網域 DNS 解析是否正確
   - 確認防火牆開放 80 和 443 埠
   - 檢查是否有其他服務佔用埠號

2. **HTTPS 無法存取**
   - 檢查 Nginx 設定檔案語法
   - 確認憑證檔案路徑正確
   - 查看 Nginx 錯誤日誌

3. **自動續簽失敗**
   - 檢查 Cron Job 設定
   - 確認腳本路徑和權限
   - 查看續簽日誌

### 除錯指令

```bash
# 檢視服務狀態
docker-compose ps

# 檢視 Nginx 日誌
docker-compose logs nginx

# 檢視 Certbot 日誌
docker-compose logs certbot

# 進入容器除錯
docker-compose exec nginx sh
docker-compose exec certbot sh

# 測試 Nginx 設定
docker-compose exec nginx nginx -t

# 重新載入 Nginx 設定
docker-compose exec nginx nginx -s reload
```

### 日誌檔案位置

- Nginx 存取日誌：`logs/nginx/access.log`
- Nginx 錯誤日誌：`logs/nginx/error.log`
- SSL 續簽日誌：`logs/ssl-renewal.log`
- Certbot 日誌：`logs/certbot/`

## 安全性考量

### SSL 安全設定

本專案的 SSL 設定包含以下安全強化：

- **TLS 版本**：僅支援 TLS 1.2 和 1.3
- **加密演算法**：使用強加密套件
- **HSTS**：強制 HTTPS 連線
- **OCSP Stapling**：改善 SSL 效能
- **安全標頭**：防止 XSS、點擊劫持等攻擊

### 防火牆設定

確保防火牆正確設定：
```bash
# Debian/Ubuntu
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# CentOS/RHEL
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## 監控和維護

### SSL 憑證監控

可以使用以下工具監控 SSL 憑證：

1. **SSL Labs**：https://www.ssllabs.com/ssltest/
2. **SSL Checker**：https://www.sslshopper.com/ssl-checker.html
3. **內建檢查腳本**：`scripts/ssl-renew.sh`

### 效能監控

檢查 SSL 對效能的影響：
```bash
# 測試 HTTPS 回應時間
curl -w "@curl-format.txt" -o /dev/null -s https://your-domain.com

# 檢查 SSL 握手時間
openssl s_time -connect your-domain.com:443 -new -www /
```

### 備份和復原

重要檔案備份：
- SSL 憑證：`ssl-data/`
- 設定檔案：`docker/nginx/`
- 環境變數：`.env`

```bash
# 備份 SSL 憑證
tar -czf ssl-backup-$(date +%Y%m%d).tar.gz ssl-data/

# 復原 SSL 憑證
tar -xzf ssl-backup-20250823.tar.gz
```

## 進階設定

### 多網域支援

支援多個網域的設定：
```env
SSL_DOMAIN=example.com
SSL_WWW_DOMAIN=www.example.com
SSL_ADDITIONAL_DOMAINS=api.example.com,admin.example.com
```

### CDN 整合

如果使用 CDN（如 Cloudflare），需要特別設定：
```nginx
# 取得真實 IP
set_real_ip_from 0.0.0.0/0;
real_ip_header X-Forwarded-For;
real_ip_recursive on;
```

### 負載平衡

多伺服器環境下的 SSL 設定：
- 使用共享儲存同步憑證
- 設定 DNS 輪詢或負載平衡器
- 統一憑證管理策略

## 相關資源

- [Let's Encrypt 官方文件](https://letsencrypt.org/docs/)
- [Certbot 使用指南](https://certbot.eff.org/)
- [Nginx SSL 設定](https://nginx.org/en/docs/http/configuring_https_servers.html)
- [Mozilla SSL 設定產生器](https://ssl-config.mozilla.org/)

## 支援和協助

如果在設定過程中遇到問題，請：

1. 檢查本文件的疑難排解章節
2. 查看專案的 Issues 頁面
3. 參考官方文件和社群資源

---

*最後更新：2025年8月23日*
