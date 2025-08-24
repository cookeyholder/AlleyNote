# AlleyNote SSL 設定檔案

本目錄包含 SSL 相關的設定檔案和腳本。

## 檔案結構

```
docker/nginx/
├── default.conf          # HTTP 設定（含 ACME Challenge）
├── ssl.conf              # HTTPS 設定
└── nginx-production.conf  # 正式環境設定

scripts/
├── ssl-setup.sh          # SSL 自動設定腳本
└── ssl-renew.sh          # SSL 續簽腳本

logs/
├── nginx/                # Nginx 日誌
├── certbot/              # Certbot 日誌
├── mysql/                # MySQL 日誌
└── ssl-renewal.log       # SSL 續簽日誌
```

## 使用方式

### 開發環境

```bash
# 使用 localhost 開發
docker compose up -d

# 存取應用程式
http://localhost
```

### 正式環境設定

```bash
# 1. 設定環境變數
cp .env.example .env
nano .env  # 編輯 SSL_DOMAIN 和 SSL_EMAIL

# 2. 執行 SSL 設定
./scripts/ssl-setup.sh

# 3. 啟動服務
docker compose up -d

# 4. 檢查狀態
docker compose ps
curl -I https://your-domain.com
```

## 重要設定說明

### 環境變數

| 變數名 | 說明 | 預設值 |
|--------|------|--------|
| `SSL_DOMAIN` | SSL 網域名稱 | `localhost` |
| `SSL_EMAIL` | Let's Encrypt 聯絡信箱 | `admin@localhost` |
| `CERTBOT_STAGING` | 是否使用測試環境 | `true` |
| `FORCE_HTTPS` | 是否強制 HTTPS | `true` |

### Docker Compose 檔案

- `docker-compose.yml`：開發環境，支援 SSL 測試
- `docker-compose.production.yml`：正式環境，包含完整監控

### Nginx 設定

- **HTTP (Port 80)**：處理 ACME Challenge，重導向到 HTTPS
- **HTTPS (Port 443)**：主要應用程式服務，包含安全標頭

## 安全性特色

1. **強化 TLS 設定**：僅支援 TLS 1.2/1.3
2. **安全標頭**：HSTS、CSP、X-Frame-Options 等
3. **OCSP Stapling**：改善 SSL 效能
4. **自動續簽**：避免憑證過期

## 疑難排解

### 常見錯誤

1. **憑證申請失敗**
   ```bash
   # 檢查 DNS 解析
   nslookup your-domain.com
   
   # 檢查服務狀態
   docker compose ps
   
   # 查看 Certbot 日誌
   docker compose logs certbot
   ```

2. **HTTPS 無法存取**
   ```bash
   # 檢查 Nginx 設定
   docker compose exec nginx nginx -t
   
   # 查看 Nginx 日誌
   docker compose logs nginx
   
   # 檢查憑證檔案
   ls -la ssl-data/live/your-domain.com/
   ```

3. **續簽失敗**
   ```bash
   # 手動測試續簽
   docker compose run --rm certbot renew --dry-run
   
   # 檢查續簽日誌
   cat logs/ssl-renewal.log
   ```

### 除錯指令

```bash
# 重新啟動所有服務
docker compose down && docker compose up -d

# 檢查容器狀態
docker compose ps

# 查看即時日誌
docker compose logs -f nginx certbot

# 進入容器除錯
docker compose exec nginx sh
docker compose exec certbot sh

# 測試 SSL 設定
openssl s_client -connect your-domain.com:443 -servername your-domain.com
```

## 維護建議

1. **定期檢查**：每月檢查憑證有效期
2. **日誌輪轉**：定期清理舊日誌檔案
3. **備份憑證**：重要憑證定期備份
4. **監控告警**：設定憑證過期通知

---

詳細說明請參考：[SSL_DEPLOYMENT_GUIDE.md](../SSL_DEPLOYMENT_GUIDE.md)
