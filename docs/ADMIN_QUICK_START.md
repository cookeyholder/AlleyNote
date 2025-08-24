# AlleyNote 管理員快速入門指南

> 🚀 **目標**：讓您在 30 分鐘內快速部署並運行 AlleyNote 系統

---

## 📋 前置需求檢查

### 系統需求
- **作業系統**：Debian 12
- **硬體需求**：
  - CPU: 2 核心以上
  - RAM: 4GB 以上
  - 硬碟: 20GB 可用空間
- **軟體需求**：
  - Docker 20.10+
  - Docker Compose 2.0+
  - Git

### 檢查環境
```bash
# 檢查 Docker
docker --version
docker compose version

# 檢查可用空間
df -h

# 檢查記憶體
free -h
```

---

## ⚡ 快速部署（5分鐘）

### 1. 下載專案
```bash
git clone https://github.com/your-org/alleynote.git
cd alleynote
```

### 2. 快速啟動
```bash
# 使用管理腳本快速啟動
chmod +x alleynote.sh
./alleynote.sh start

# 或直接使用 Docker Compose
docker compose up -d
```

### 3. 初始化資料庫
```bash
# 等待容器啟動完成（約 30 秒）
sleep 30

# 初始化 SQLite 資料庫
docker compose exec web ./scripts/init-sqlite.sh
```

### 4. 檢查狀態
```bash
# 檢查服務狀態
./alleynote.sh status

# 檢查容器日誌
docker compose logs -f web
```

### 5. 訪問系統
- **主頁**：http://your-server-ip:80
- **API 文檔**：http://your-server-ip:80/docs（如已配置）

---

## 🔧 基本配置

### 環境變數設定
建立 `.env` 檔案：
```bash
cp .env.example .env
nano .env
```

**必要配置項目**：
```env
# 應用程式設定
APP_ENV=production
APP_DEBUG=false

# 資料庫設定（SQLite，無需額外配置）
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/alleynote.db

# SSL 設定（如需 HTTPS）
SSL_DOMAIN=your-domain.com
SSL_EMAIL=admin@your-domain.com
CERTBOT_STAGING=false

# 管理員設定
ADMIN_EMAIL=admin@your-domain.com
ADMIN_PASSWORD=your-secure-password
```

### 應用重啟
```bash
# 重新啟動以載入新配置
./alleynote.sh restart
```

---

## 👤 建立管理員帳號

### 方法一：使用腳本（推薦）
```bash
# 進入容器
docker compose exec web bash

# 建立管理員（如有相關腳本）
php scripts/create-admin.php
```

### 方法二：直接操作資料庫
```bash
# 進入 SQLite 命令列
docker compose exec web sqlite3 database/alleynote.db

# 查看使用者表結構
.schema users

# 插入管理員帳號（密碼需先雜湊）
INSERT INTO users (email, password, role, created_at) 
VALUES ('admin@yourdomain.com', '$2y$10$hashed_password', 'admin', datetime('now'));

# 退出 SQLite
.quit
```

---

## 📊 系統監控

### 檢查服務狀態
```bash
# 查看所有容器狀態
docker compose ps

# 查看特定服務日誌
docker compose logs web
docker compose logs nginx
docker compose logs redis

# 即時監控日誌
docker compose logs -f --tail=50
```

### 檢查資料庫
```bash
# 檢查資料庫檔案
ls -la database/alleynote.db

# 檢查資料庫表格
docker compose exec web sqlite3 database/alleynote.db ".tables"

# 檢查使用者數量
docker compose exec web sqlite3 database/alleynote.db "SELECT COUNT(*) FROM users;"
```

### 檢查系統資源
```bash
# 檢查 Docker 資源使用
docker stats

# 檢查硬碟使用
df -h

# 檢查記憶體使用
free -h
```

---

## 🔒 SSL 配置（可選）

### 自動 SSL 設定
```bash
# 設定網域名稱
export SSL_DOMAIN="your-domain.com"
export SSL_EMAIL="admin@your-domain.com"

# 執行 SSL 設定腳本
./scripts/ssl-setup.sh $SSL_DOMAIN $SSL_EMAIL

# 重啟服務以啟用 HTTPS
./alleynote.sh restart
```

### 檢查 SSL 狀態
```bash
# 檢查憑證檔案
ls -la ssl-data/live/$SSL_DOMAIN/

# 測試 HTTPS 連線
curl -I https://$SSL_DOMAIN
```

---

## 🛠️ 日常維護

### 定期備份
```bash
# 備份資料庫
./scripts/backup_sqlite.sh

# 備份檔案
./scripts/backup_files.sh

# 檢查備份檔案
ls -la database/backups/
```

### 更新系統
```bash
# 拉取最新程式碼
git pull origin main

# 重建並重啟容器
docker compose down
docker compose up -d --build

# 執行資料庫遷移（如有）
docker compose exec web ./scripts/migrate.sh
```

### 清理日誌
```bash
# 清理 Docker 日誌
docker system prune -f

# 清理舊的備份檔案（保留最近 30 天）
find database/backups/ -name "*.db" -mtime +30 -delete
```

---

## 🚨 故障排除

### 常見問題

#### 容器無法啟動
```bash
# 檢查 Docker 狀態
systemctl status docker

# 檢查埠號衝突
netstat -tulpn | grep :80
netstat -tulpn | grep :443

# 檢查磁碟空間
df -h
```

#### 資料庫連線問題
```bash
# 檢查資料庫檔案權限
ls -la database/alleynote.db

# 修復權限
docker compose exec web chown www-data:www-data database/alleynote.db
docker compose exec web chmod 664 database/alleynote.db
```

#### 網站無法訪問
```bash
# 檢查 Nginx 設定
docker compose exec nginx nginx -t

# 檢查防火牆
ufw status
```

### 日誌檢查位置
- **應用程式日誌**：`logs/app.log`
- **Nginx 日誌**：`docker compose logs nginx`
- **PHP 錯誤日誌**：`docker compose logs web`
- **系統日誌**：`/var/log/messages` 或 `/var/log/syslog`

### 緊急重啟
```bash
# 強制停止所有容器
docker compose down --remove-orphans

# 清理暫存
docker system prune -f

# 重新啟動
docker compose up -d
```

---

## 📞 支援與資源

### 快速指令參考
```bash
# 啟動服務
./alleynote.sh start

# 停止服務
./alleynote.sh stop

# 檢查狀態
./alleynote.sh status

# 查看日誌
./alleynote.sh logs

# 備份資料
./alleynote.sh backup

# 更新系統
./alleynote.sh update
```

### 重要檔案位置
- **設定檔**：`.env`
- **資料庫**：`database/alleynote.db`
- **日誌**：`logs/`
- **備份**：`database/backups/`
- **SSL 憑證**：`ssl-data/`

### 進階文件
- [完整部署指南](DEPLOYMENT.md)
- [SSL 設定詳解](SSL_DEPLOYMENT_GUIDE.md)
- [系統架構說明](ARCHITECTURE_AUDIT.md)
- [API 使用文件](API_DOCUMENTATION.md)

---

## ✅ 部署檢查清單

部署完成後，請確認以下項目：

- [ ] 服務正常運行（`docker compose ps` 全部 Up）
- [ ] 網站可正常訪問（HTTP 200 回應）
- [ ] 資料庫初始化成功（有資料表）
- [ ] 管理員帳號可正常登入
- [ ] 日誌檔案正常產生
- [ ] 備份機制已設定
- [ ] SSL 憑證正常（如適用）
- [ ] 防火牆規則正確設定

---

**🎉 恭喜！您的 AlleyNote 系統已成功部署！**

如需更詳細的配置和維護說明，請參考 [管理員操作手冊](ADMIN_MANUAL.md)