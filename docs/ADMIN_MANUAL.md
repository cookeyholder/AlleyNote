# AlleyNote 管理員操作手冊

> 📚 **完整指南**：AlleyNote 系統的日常管理、維護和故障排除手冊

---

## 📑 目錄

- [系統概述](#系統概述)
- [用戶管理](#用戶管理)
- [內容管理](#內容管理)
- [系統配置](#系統配置)
- [安全管理](#安全管理)
- [備份與還原](#備份與還原)
- [監控與維護](#監控與維護)
- [故障排除](#故障排除)
- [效能優化](#效能優化)
- [日誌管理](#日誌管理)

---

## 🏗️ 系統概述

### AlleyNote 系統架構
AlleyNote 是基於 Docker 容器化部署的公告系統，包含以下核心組件：

- **Web 應用**：PHP 8.4.11 + SQLite 資料庫
- **Web 伺服器**：Nginx（負載均衡和 SSL 終止）
- **快取系統**：Redis（會話和應用程式快取）
- **SSL 管理**：Certbot（自動憑證管理）

### 核心功能
- 文章發布和管理
- 附件上傳和下載
- 用戶認證和權限控制
- IP 存取控制
- 自動備份和還原

---

## 👥 用戶管理

### 查看用戶列表
```bash
# 進入容器
docker-compose exec web bash

# 查看所有用戶
sqlite3 database/alleynote.db "SELECT id, email, role, created_at FROM users;"

# 查看用戶統計
sqlite3 database/alleynote.db "SELECT role, COUNT(*) as count FROM users GROUP BY role;"
```

### 創建管理員用戶
```bash
# 方法一：使用 SQLite 命令
docker-compose exec web sqlite3 database/alleynote.db
```
```sql
-- 插入新的管理員用戶（密碼需要先雜湊）
INSERT INTO users (email, password, role, created_at) 
VALUES ('admin@yourdomain.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', datetime('now'));

-- 檢查用戶是否創建成功
SELECT * FROM users WHERE email = 'admin@yourdomain.com';
```

### 密碼重設
```bash
# 進入 PHP 容器
docker-compose exec web php -r "
\$email = 'user@example.com';
\$newPassword = 'new_password';
\$hashedPassword = password_hash(\$newPassword, PASSWORD_DEFAULT);

\$pdo = new PDO('sqlite:/var/www/html/database/alleynote.db');
\$stmt = \$pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
\$result = \$stmt->execute([\$hashedPassword, \$email]);

echo \$result ? '密碼更新成功' : '密碼更新失敗';
echo \"\n\";
"
```

### 用戶權限管理
```sql
-- 將用戶設為管理員
UPDATE users SET role = 'admin' WHERE email = 'user@example.com';

-- 將用戶設為一般用戶
UPDATE users SET role = 'user' WHERE email = 'admin@example.com';

-- 停用用戶
UPDATE users SET status = 'disabled' WHERE email = 'user@example.com';

-- 啟用用戶
UPDATE users SET status = 'active' WHERE email = 'user@example.com';
```

---

## 📝 內容管理

### 文章管理
```bash
# 查看所有文章
docker-compose exec web sqlite3 database/alleynote.db "
SELECT id, title, status, created_at, user_id 
FROM posts 
ORDER BY created_at DESC 
LIMIT 20;
"

# 查看文章統計
docker-compose exec web sqlite3 database/alleynote.db "
SELECT status, COUNT(*) as count 
FROM posts 
GROUP BY status;
"
```

### 置頂文章管理
```sql
-- 設置文章置頂
UPDATE posts SET is_pinned = 1 WHERE id = 1;

-- 取消文章置頂
UPDATE posts SET is_pinned = 0 WHERE id = 1;

-- 查看置頂文章
SELECT id, title, is_pinned FROM posts WHERE is_pinned = 1;
```

### 附件管理
```bash
# 查看附件使用情況
docker-compose exec web sqlite3 database/alleynote.db "
SELECT 
    COUNT(*) as total_files,
    SUM(file_size) as total_size,
    AVG(file_size) as avg_size
FROM attachments;
"

# 查看大型附件
docker-compose exec web sqlite3 database/alleynote.db "
SELECT filename, file_size, created_at 
FROM attachments 
WHERE file_size > 1048576 
ORDER BY file_size DESC;
"

# 清理未使用的附件檔案
find storage/uploads -type f -mtime +30 -name "*.tmp" -delete
```

---

## ⚙️ 系統配置

### 環境變數管理
```bash
# 查看當前環境變數
docker-compose exec web env | grep APP_

# 更新環境變數後重啟
nano .env
docker-compose down
docker-compose up -d
```

### 重要配置項目
```env
# 應用程式設定
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# 資料庫設定
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/alleynote.db

# 快取設定
CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

# 檔案上傳設定
MAX_FILE_SIZE=10M
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,pdf,doc,docx

# 安全設定
SESSION_LIFETIME=1440
CSRF_TOKEN_LIFETIME=3600
```

### PHP 配置調整
編輯 `docker/php/php.ini`：
```ini
; 檔案上傳設定
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20

; 記憶體設定
memory_limit = 256M

; 執行時間設定
max_execution_time = 300

; 日誌設定
log_errors = On
error_log = /var/www/html/logs/php_errors.log
```

---

## 🔒 安全管理

### IP 存取控制
```bash
# 查看 IP 黑白名單
docker-compose exec web sqlite3 database/alleynote.db "
SELECT ip_address, type, description, created_at 
FROM ip_lists 
ORDER BY created_at DESC;
"

# 新增 IP 到黑名單
docker-compose exec web sqlite3 database/alleynote.db "
INSERT INTO ip_lists (ip_address, type, description, created_by, created_at) 
VALUES ('192.168.1.100', 'blacklist', '惡意行為', 1, datetime('now'));
"

# 新增 IP 到白名單
docker-compose exec web sqlite3 database/alleynote.db "
INSERT INTO ip_lists (ip_address, type, description, created_by, created_at) 
VALUES ('10.0.0.0/8', 'whitelist', '內部網路', 1, datetime('now'));
"
```

### 查看登入記錄
```bash
# 查看最近登入記錄
docker-compose exec web sqlite3 database/alleynote.db "
SELECT user_id, ip_address, user_agent, created_at 
FROM login_logs 
ORDER BY created_at DESC 
LIMIT 50;
"

# 查看失敗登入嘗試
docker-compose exec web sqlite3 database/alleynote.db "
SELECT ip_address, COUNT(*) as attempts, MAX(created_at) as last_attempt
FROM failed_login_attempts 
WHERE created_at > datetime('now', '-24 hours')
GROUP BY ip_address
HAVING attempts > 5
ORDER BY attempts DESC;
"
```

### SSL 憑證管理
```bash
# 檢查憑證有效期
docker-compose exec certbot certbot certificates

# 手動更新憑證
docker-compose exec certbot certbot renew --dry-run

# 強制更新憑證
docker-compose exec certbot certbot renew --force-renewal

# 檢查憑證檔案
ls -la ssl-data/live/yourdomain.com/
```

---

## 💾 備份與還原

### 自動備份設定
```bash
# 設定定期備份（加入 crontab）
crontab -e
```
```cron
# 每日凌晨 2 點備份資料庫
0 2 * * * /path/to/alleynote/scripts/backup_sqlite.sh

# 每週日凌晨 3 點備份檔案
0 3 * * 0 /path/to/alleynote/scripts/backup_files.sh

# 每月清理舊備份（保留 3 個月）
0 4 1 * * find /path/to/alleynote/database/backups -name "*.tar.gz" -mtime +90 -delete
```

### 手動備份
```bash
# 備份資料庫
./scripts/backup_sqlite.sh

# 備份檔案
./scripts/backup_files.sh

# 檢查備份檔案
ls -la database/backups/
```

### 還原備份
```bash
# 還原資料庫
./scripts/restore_sqlite.sh database/backups/alleynote_20231201_020000.db

# 還原檔案
./scripts/restore_files.sh database/backups/files_20231201_030000.tar.gz

# 驗證還原結果
docker-compose exec web sqlite3 database/alleynote.db ".tables"
```

### 異地備份
```bash
# 設定 rsync 異地備份
rsync -avz --delete database/backups/ backup-server:/backups/alleynote/

# 上傳到雲端儲存（需安裝 aws-cli）
aws s3 sync database/backups/ s3://your-backup-bucket/alleynote/
```

---

## 📊 監控與維護

### 系統資源監控
```bash
# 檢查容器資源使用
docker stats --no-stream

# 檢查磁碟使用
df -h

# 檢查資料庫大小
ls -lh database/alleynote.db

# 檢查日誌檔案大小
du -sh logs/
```

### 應用程式監控
```bash
# 檢查 PHP 程序狀態
docker-compose exec web ps aux | grep php

# 檢查 Nginx 狀態
docker-compose exec nginx nginx -t
curl -I http://localhost/health

# 檢查 Redis 狀態
docker-compose exec redis redis-cli ping
```

### 效能監控
```bash
# 監控資料庫查詢效能
docker-compose exec web php scripts/db-performance.php

# 監控快取效能
docker-compose exec redis redis-cli info stats

# 檢查應用程式回應時間
curl -w "@curl-format.txt" -o /dev/null -s "http://localhost/"
```

### 健康檢查腳本
建立 `scripts/health-check.sh`：
```bash
#!/bin/bash

echo "=== AlleyNote 系統健康檢查 ==="

# 檢查容器狀態
echo "1. 檢查容器狀態..."
docker-compose ps

# 檢查網站回應
echo "2. 檢查網站回應..."
curl -f http://localhost/ > /dev/null && echo "✓ 網站正常" || echo "✗ 網站異常"

# 檢查資料庫
echo "3. 檢查資料庫..."
docker-compose exec web sqlite3 database/alleynote.db "SELECT 1;" > /dev/null && echo "✓ 資料庫正常" || echo "✗ 資料庫異常"

# 檢查 Redis
echo "4. 檢查 Redis..."
docker-compose exec redis redis-cli ping > /dev/null && echo "✓ Redis 正常" || echo "✗ Redis 異常"

# 檢查磁碟空間
echo "5. 檢查磁碟空間..."
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -lt 80 ]; then
    echo "✓ 磁碟空間充足 ($DISK_USAGE%)"
else
    echo "⚠ 磁碟空間不足 ($DISK_USAGE%)"
fi

echo "=== 健康檢查完成 ==="
```

---

## 🛠️ 故障排除

### 常見問題診斷

#### 網站無法訪問
```bash
# 1. 檢查容器狀態
docker-compose ps

# 2. 檢查 Nginx 配置
docker-compose exec nginx nginx -t

# 3. 檢查端口佔用
netstat -tulpn | grep :80
netstat -tulpn | grep :443

# 4. 檢查防火牆
ufw status numbered
iptables -L

# 5. 檢查 DNS 解析
nslookup yourdomain.com
dig yourdomain.com
```

#### 資料庫連線錯誤
```bash
# 1. 檢查資料庫檔案
ls -la database/alleynote.db

# 2. 檢查檔案權限
docker-compose exec web ls -la database/alleynote.db

# 3. 修復權限
docker-compose exec web chown www-data:www-data database/alleynote.db
docker-compose exec web chmod 664 database/alleynote.db

# 4. 測試資料庫連線
docker-compose exec web sqlite3 database/alleynote.db "SELECT 1;"

# 5. 檢查資料庫完整性
docker-compose exec web sqlite3 database/alleynote.db "PRAGMA integrity_check;"
```

#### 記憶體不足
```bash
# 1. 檢查記憶體使用
free -h
docker stats --no-stream

# 2. 重啟服務釋放記憶體
docker-compose restart

# 3. 清理無用的 Docker 映像
docker system prune -f

# 4. 調整 PHP 記憶體限制
nano docker/php/php.ini
# 修改 memory_limit = 512M
docker-compose down && docker-compose up -d --build
```

#### SSL 憑證問題
```bash
# 1. 檢查憑證有效期
openssl x509 -in ssl-data/live/yourdomain.com/fullchain.pem -text -noout

# 2. 檢查憑證鏈
openssl verify -CAfile ssl-data/live/yourdomain.com/chain.pem ssl-data/live/yourdomain.com/cert.pem

# 3. 重新申請憑證
docker-compose exec certbot certbot delete --cert-name yourdomain.com
./scripts/ssl-setup.sh yourdomain.com admin@yourdomain.com

# 4. 檢查 Nginx SSL 配置
docker-compose exec nginx nginx -t
```

### 緊急恢復程序
```bash
# 1. 停止所有服務
docker-compose down

# 2. 備份當前狀態
cp -r database/ database_backup_$(date +%Y%m%d_%H%M%S)/

# 3. 恢復到最近的備份
./scripts/restore_sqlite.sh database/backups/latest_backup.db

# 4. 重啟服務
docker-compose up -d

# 5. 驗證系統狀態
./scripts/health-check.sh
```

---

## ⚡ 效能優化

### 資料庫優化
```bash
# 1. 重建資料庫索引
docker-compose exec web sqlite3 database/alleynote.db "REINDEX;"

# 2. 清理資料庫
docker-compose exec web sqlite3 database/alleynote.db "VACUUM;"

# 3. 分析查詢效能
docker-compose exec web sqlite3 database/alleynote.db "ANALYZE;"

# 4. 檢查資料庫統計
docker-compose exec web sqlite3 database/alleynote.db "
SELECT name, COUNT(*) as row_count 
FROM sqlite_master m JOIN pragma_table_info(m.name) p 
WHERE m.type='table' 
GROUP BY name;
"
```

### 快取優化
```bash
# 1. 清理應用程式快取
docker-compose exec web rm -rf storage/cache/*

# 2. 清理 Redis 快取
docker-compose exec redis redis-cli FLUSHALL

# 3. 預熱快取
docker-compose exec web php scripts/warm-cache.php

# 4. 監控快取命中率
docker-compose exec redis redis-cli info stats | grep hit
```

### 檔案系統優化
```bash
# 1. 清理臨時檔案
find storage/tmp/ -type f -mtime +7 -delete

# 2. 壓縮舊日誌
gzip logs/*.log.1

# 3. 清理未使用的附件
docker-compose exec web php scripts/cleanup-orphaned-files.php

# 4. 優化檔案權限
find storage/ -type f -exec chmod 644 {} \;
find storage/ -type d -exec chmod 755 {} \;
```

---

## 📋 日誌管理

### 日誌檔案位置
- **應用程式日誌**：`logs/app.log`
- **錯誤日誌**：`logs/error.log`
- **存取日誌**：`logs/access.log`
- **資料庫日誌**：`logs/database.log`
- **安全日誌**：`logs/security.log`

### 日誌查看和分析
```bash
# 查看即時日誌
tail -f logs/app.log

# 搜尋錯誤日誌
grep "ERROR" logs/app.log | tail -20

# 分析存取日誌
awk '{print $1}' logs/access.log | sort | uniq -c | sort -nr | head -10

# 查看容器日誌
docker-compose logs -f --tail=100 web
```

### 日誌輪轉設定
建立 `/etc/logrotate.d/alleynote`：
```
/path/to/alleynote/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
    postrotate
        docker-compose exec web php scripts/log-cleanup.php
    endscript
}
```

### 日誌監控告警
```bash
# 監控錯誤數量
ERROR_COUNT=$(grep "ERROR" logs/app.log | wc -l)
if [ $ERROR_COUNT -gt 10 ]; then
    echo "警告：錯誤日誌數量過多 ($ERROR_COUNT)" | mail -s "AlleyNote 警告" admin@yourdomain.com
fi

# 監控磁碟使用
DISK_USAGE=$(df logs/ | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "警告：日誌磁碟使用率過高 ($DISK_USAGE%)" | mail -s "AlleyNote 警告" admin@yourdomain.com
fi
```

---

## 📞 支援與維護

### 定期維護檢查清單

#### 每日檢查
- [ ] 服務狀態正常
- [ ] 網站可正常訪問
- [ ] 磁碟空間充足
- [ ] 備份執行成功

#### 每週檢查
- [ ] 查看錯誤日誌
- [ ] 檢查安全事件
- [ ] 更新系統套件
- [ ] 測試備份還原

#### 每月檢查
- [ ] 檢查 SSL 憑證有效期
- [ ] 清理舊日誌和備份
- [ ] 檢查資料庫效能
- [ ] 更新應用程式

### 緊急聯絡資訊
- **系統管理員**：admin@yourdomain.com
- **技術支援**：support@yourdomain.com
- **緊急電話**：+886-xxx-xxx-xxx

### 相關文件
- [快速入門指南](ADMIN_QUICK_START.md)
- [部署指南](DEPLOYMENT.md)
- [SSL 設定指南](SSL_DEPLOYMENT_GUIDE.md)
- [API 文件](API_DOCUMENTATION.md)

---

**📚 本手冊將隨系統更新持續維護，建議定期查看最新版本。**