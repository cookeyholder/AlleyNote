# AlleyNote 管理員快速入門指南

> 🚀 **目標**：讓您在 30 分鐘內快速部署並運行 AlleyNote 統計系統

**版本**: v2.1
**最後更新**: 2025-09-27
**適用版本**: Docker 28.3.3 + Docker Compose v2.39.2

---

## 📋 前置需求檢查

### 系統需求
- **作業系統**：Linux (推薦 Debian 12+ 或 Ubuntu 20.04+)
- **硬體需求**：
  - CPU: 2 核心以上
  - RAM: 4GB 以上
  - 硬碟: 20GB 可用空間
- **軟體需求**：
  - Docker 28.3.3+
  - Docker Compose v2.39.2+
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
git clone https://github.com/cookeyholder/AlleyNote.git
cd alleynote
```

### 2. 快速啟動
```bash
# 使用 Docker Compose 啟動服務
docker compose up -d

# 檢查服務啟動狀態
docker compose ps
```

### 3. 初始化資料庫
```bash
# 等待容器啟動完成（約 30 秒）
sleep 30

# 初始化 SQLite 資料庫
docker compose exec web bash -c "./backend/scripts/init-sqlite.sh"

# 或直接執行初始化
docker compose exec web php backend/scripts/init-sqlite.sh
```

### 4. 檢查狀態
```bash
# 檢查服務狀態
docker compose ps

# 檢查容器日誌
docker compose logs -f web

# 測試 API 端點
curl -I http://localhost/health
```

### 5. 訪問系統
- **主頁**：http://localhost (或您的伺服器 IP)
- **API 健康檢查**：http://localhost/health
- **統計儀表板**：http://localhost/admin/statistics （需登入）

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

# 資料庫設定（SQLite）
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/alleynote.sqlite3

# Redis 快取設定
CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

# 統計模組設定
STATISTICS_CACHE_TTL=3600
STATISTICS_ENABLED=true

# SSL 設定（如需 HTTPS）
SSL_DOMAIN=your-domain.com
SSL_EMAIL=admin@your-domain.com
```
### 創建管理員帳號

#### 方法一：使用 PHP 指令（推薦）
```bash
# 進入容器
docker compose exec web bash

# 使用 PHP 建立管理員帳號
docker compose exec web php -r "
\$email = 'admin@yourdomain.com';
\$password = 'your-secure-password';
\$hashedPassword = password_hash(\$password, PASSWORD_ARGON2ID);

\$pdo = new PDO('sqlite:database/alleynote.sqlite3');
\$stmt = \$pdo->prepare('INSERT INTO users (email, password, role, created_at) VALUES (?, ?, ?, datetime(\"now\"))');
\$result = \$stmt->execute([\$email, \$hashedPassword, 'admin']);

echo \$result ? '管理員建立成功' : '管理員建立失敗';
echo PHP_EOL;
"
```

#### 方法二：直接操作資料庫
```bash
# 進入 SQLite 命令列
docker compose exec web sqlite3 database/alleynote.sqlite3

# 查看使用者表結構
.schema users

# 插入管理員帳號（密碼需先雜湊）
INSERT INTO users (email, password, role, created_at)
VALUES ('admin@yourdomain.com', '$2y$10$hashed_password', 'admin', datetime('now'));

# 退出 SQLite
.quit
```

### 統計系統初始化
```bash
# 初始化統計模組
docker compose exec web php backend/scripts/statistics-calculation.php --periods=daily --force

# 檢查統計資料
docker compose exec web sqlite3 database/alleynote.sqlite3 "
SELECT COUNT(*) as snapshot_count, snapshot_type
FROM statistics_snapshots
GROUP BY snapshot_type;
"
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
ls -la database/alleynote.sqlite3

# 檢查資料庫表格
docker compose exec web sqlite3 database/alleynote.sqlite3 ".tables"

# 檢查使用者數量
docker compose exec web sqlite3 database/alleynote.sqlite3 "SELECT COUNT(*) FROM users;"

# 檢查統計快照
docker compose exec web sqlite3 database/alleynote.sqlite3 "
SELECT snapshot_type, COUNT(*) as count, MAX(snapshot_date) as latest
FROM statistics_snapshots
GROUP BY snapshot_type;
"
```

### 檢查系統資源
```bash
# 檢查 Docker 資源使用
docker stats --no-stream

# 檢查硬碟使用
df -h

# 檢查記憶體使用
free -h
```

---

## 🔧 常用管理操作

### 服務管理
```bash
# 停止所有服務
docker compose down

# 啟動服務
docker compose up -d

# 重新啟動特定服務
docker compose restart web

# 檢視服務配置
docker compose config
```

### 統計管理
```bash
# 手動生成統計
docker compose exec web php backend/scripts/statistics-calculation.php

# 清除統計快取
docker compose exec web rm -rf storage/cache/statistics/

# 統計數據維護
docker compose exec web php backend/scripts/statistics-cleanup.php --days=90
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
docker compose restart nginx
```

### 檢查 SSL 狀態
```bash
# 檢查憑證檔案
ls -la ssl-data/live/$SSL_DOMAIN/

# 測試 HTTPS 連線
curl -I https://$SSL_DOMAIN

# 檢查憑證有效期
docker compose exec certbot certbot certificates
```

---

## 🛠️ 日常維護

### 定期備份
```bash
# 備份資料庫
docker compose exec web bash -c "./backend/scripts/backup_sqlite.sh"

# 備份檔案
docker compose exec web bash -c "./backend/scripts/backup_files.sh"

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
docker compose exec web php backend/scripts/migrate.sh
```

### 統計系統維護
```bash
# 定期重新計算統計
docker compose exec web php backend/scripts/statistics-calculation.php --periods=daily

# 清理過期統計快取
docker compose exec web rm -rf storage/cache/statistics/

# 檢查統計系統狀態
curl -s http://localhost/api/admin/statistics/overview | jq
```

### 清理系統
```bash
# 清理 Docker 日誌和未使用資源
docker system prune -f

# 清理舊的備份檔案（保留最近 30 天）
find database/backups/ -name "*.sqlite3" -mtime +30 -delete
find database/backups/ -name "*.tar.gz" -mtime +30 -delete
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

# 清理 Docker 資源
docker compose down
docker system prune -a -f
```

#### 資料庫連線問題
```bash
# 檢查資料庫檔案權限
ls -la database/alleynote.sqlite3

# 修復權限
docker compose exec web chown www-data:www-data database/alleynote.sqlite3
docker compose exec web chmod 664 database/alleynote.sqlite3

# 測試資料庫連線
docker compose exec web sqlite3 database/alleynote.sqlite3 "SELECT 1;"
```

#### 網站無法訪問
```bash
# 檢查 Nginx 設定
docker compose exec nginx nginx -t

# 檢查防火牆
ufw status
firewall-cmd --list-all

# 檢查服務狀態
docker compose ps
curl -I http://localhost/health
```

#### 統計功能異常
```bash
# 檢查統計資料表
docker compose exec web sqlite3 database/alleynote.sqlite3 "
SELECT COUNT(*) as total_snapshots FROM statistics_snapshots;
"

# 重新生成統計
docker compose exec web php backend/scripts/statistics-calculation.php --force

# 檢查統計快取
docker compose exec web ls -la storage/cache/statistics/
```

---

## 📞 取得支援

### 日誌收集
當遇到問題時，請收集以下日誌：
```bash
# 收集所有容器日誌
docker compose logs > alleynote_logs.txt

# 收集系統資訊
docker compose ps > system_status.txt
docker stats --no-stream >> system_status.txt

# 收集環境資訊
docker --version >> environment_info.txt
docker compose version >> environment_info.txt
```

### 支援管道
- **GitHub Issues**: [提交問題報告](https://github.com/cookeyholder/AlleyNote/issues)
- **文件查詢**: 詳細文件位於 [docs/](../docs/) 資料夾
- **社群討論**: [GitHub Discussions](https://github.com/cookeyholder/AlleyNote/discussions)

---

**🎉 恭喜！您已成功完成 AlleyNote 的快速部署。**

**下一步建議**：
- 閱讀 [管理員手冊](ADMIN_MANUAL.md) 了解詳細功能
- 設定 [定期備份](../scripts/README.md) 保護數據安全
- 查看 [統計功能總覽](STATISTICS_FEATURE_OVERVIEW.md) 使用統計分析

**🎯 部署狀態**: ✅ 快速就緒 | 📈 統計啟用 | 🔒 SSL 可選
getenforce
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

如需更詳細的配置和維護說明，請參考 [管理員操作手冊](ADMIN_MANUAL.md)。
