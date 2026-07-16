# AlleyNote 管理員操作手冊

> 📚 **完整指南**：AlleyNote 系統的日常管理、維護與統計模組運維參考

**版本**: v4.2
**最後更新**: 2025-09-27
**適用版本**: PHP 8.4.12 + Docker 28.3.3 + Docker Compose v2.39.2

---

## 📑 目錄

- [系統概述](#系統概述)
- [用戶管理](#用戶管理)
- [內容管理](#內容管理)
- [系統配置](#系統配置)
- [安全管理](#安全管理)
- [備份與還原](#備份與還原)
- [監控與維護](#監控與維護)
- [統計模組](#統計模組)
- [故障排除](#故障排除)
- [效能優化](#效能優化)
- [日誌管理](#日誌管理)

---

## 🏗️ 系統概述

### AlleyNote 系統架構 (前後端分離)

AlleyNote 採用 Docker 容器化部署並遵循 DDD 原則，主要組成如下：

- **後端**: PHP 8.4.12、SQLite、RESTful API、分層式 DDD 模組
- **前端**: 無構建工具 + TypeScript + Tailwind CSS（以 Fetch API 與後端溝通）
- **Web 伺服器**: Nginx（反向代理與 SSL 終止）
- **容器化**: Docker 28.3.3 + Docker Compose v2.39.2
- **快取系統**: Redis（快取、佇列與暫存）
- **統計模組**: 多層快取、快照儲存、趨勢分析、儀表板資料匯出
- **SSL 管理**: Certbot（自動申請與續約）

### 當前系統狀態

- **PHP 環境**: PHP 8.4.12（Xdebug 3.4.5、Zend OPcache 啟用）
- **測試與品質**: PHPUnit 11、PHPStan Level 10、PHP CS Fixer、自動化 CI 流程
- **架構模式**: Domain-Driven Design (DDD)
- **API 標準**: RESTful API + JSON 回應
- **統計現況**: 每日快照、趨勢曲線、儀表板小工具均已啟用

### 核心功能

- 文章與附件管理（含內容審核與審計紀錄）
- 使用者認證、權限、IP 控制
- 統計儀表板（快照、熱門趨勢、批次回填）
- 自動備份、還原與多層快取
- SSL 憑證自動化與安全性監控

---

## 👥 用戶管理

### 查看用戶列表

```bash
# 進入後端容器
docker compose exec web bash

# 查看所有用戶
sqlite3 database/alleynote.sqlite3 "SELECT id, email, role, created_at FROM users;"

# 查看用戶統計
sqlite3 database/alleynote.sqlite3 "SELECT role, COUNT(*) as count FROM users GROUP BY role;"
```

### 創建管理員用戶

```bash
# 使用 PHP 8.4.12 建立雜湊密碼
docker compose exec web php -r "
\$email = 'admin@yourdomain.com';
\$password = 'Example#Pass123!';
\$hashedPassword = password_hash(\$password, PASSWORD_ARGON2ID);

\$pdo = new PDO('sqlite:database/alleynote.sqlite3');
\$stmt = \$pdo->prepare('INSERT INTO users (email, password, role, created_at) VALUES (?, ?, ?, datetime(\"now\"))');
\$result = \$stmt->execute([\$email, \$hashedPassword, 'admin']);

echo \$result ? '管理員創建成功' : '管理員創建失敗';
echo \"\n\";
"
```

### 密碼重設

```bash
# 使用 PHP 8.4 的 readonly 屬性和新語法
docker compose exec web php -r "
\$email = 'user@example.com';
\$newPassword = 'Example#Pass123!';
\$hashedPassword = password_hash(\$newPassword, PASSWORD_ARGON2ID);

\$pdo = new PDO('sqlite:database/alleynote.sqlite3');
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
docker compose exec web sqlite3 database/alleynote.sqlite3 "
SELECT id, title, status, created_at, user_id
FROM posts
ORDER BY created_at DESC
LIMIT 20;
"

# 查看文章統計
docker compose exec web sqlite3 database/alleynote.sqlite3 "
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
docker compose exec web sqlite3 database/alleynote.sqlite3 "
SELECT
    COUNT(*) as total_files,
    SUM(file_size) as total_size,
    AVG(file_size) as avg_size
FROM attachments;
"

# 查看大型附件
docker compose exec web sqlite3 database/alleynote.sqlite3 "
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
docker compose exec web env | grep APP_

# 更新環境變數後重啟
nano .env
docker compose down
docker compose up -d
```

### 重要配置項目

```env
# 應用程式設定
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# 資料庫設定
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/alleynote.sqlite3

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
docker compose exec web sqlite3 database/alleynote.sqlite3 "
SELECT ip_address, type, description, created_at
FROM ip_lists
ORDER BY created_at DESC;
"

# 新增 IP 到黑名單
docker compose exec web sqlite3 database/alleynote.sqlite3 "
INSERT INTO ip_lists (ip_address, type, description, created_by, created_at)
VALUES ('192.168.1.100', 'blacklist', '惡意行為', 1, datetime('now'));
"

# 新增 IP 到白名單
docker compose exec web sqlite3 database/alleynote.sqlite3 "
INSERT INTO ip_lists (ip_address, type, description, created_by, created_at)
VALUES ('10.0.0.0/8', 'whitelist', '內部網路', 1, datetime('now'));
"
```

### 查看登入記錄

```bash
# 查看最近登入記錄
docker compose exec web sqlite3 database/alleynote.sqlite3 "
SELECT user_id, ip_address, user_agent, created_at
FROM login_logs
ORDER BY created_at DESC
LIMIT 50;
"

# 查看失敗登入嘗試
docker compose exec web sqlite3 database/alleynote.sqlite3 "
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
docker compose exec certbot certbot certificates

# 手動更新憑證
docker compose exec certbot certbot renew --dry-run

# 強制更新憑證
docker compose exec certbot certbot renew --force-renewal

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
0 2 * * * /path/to/alleynote/backend/scripts/backup_sqlite.sh

# 每週日凌晨 3 點備份檔案
0 3 * * 0 /path/to/alleynote/backend/scripts/backup_files.sh

# 每月清理舊備份（保留 3 個月）
0 4 1 * * find /path/to/alleynote/database/backups -name "*.tar.gz" -mtime +90 -delete
```

### 手動備份

```bash
# 備份資料庫
docker compose exec web bash -lc "./scripts/backup_sqlite.sh"

# 備份檔案
docker compose exec web bash -lc "./scripts/backup_files.sh"

# 檢查備份檔案
ls -la database/backups/
```

### 還原備份

```bash
# 還原資料庫
docker compose exec web bash -lc "./scripts/restore_sqlite.sh database/backups/alleynote_20231201_020000.sqlite3"

# 還原檔案
docker compose exec web bash -lc "./scripts/restore_files.sh database/backups/files_20231201_030000.tar.gz"

# 驗證還原結果
docker compose exec web sqlite3 database/alleynote.sqlite3 ".tables"
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
 docker compose exec web ps aux | grep php

# 檢查磁碟使用
 docker compose exec nginx nginx -t

# 檢查資料庫大小
 docker compose exec redis redis-cli ping

# 檢查日誌檔案大小
du -sh logs/
```

### 應用程式監控

```bash
# 檢查 PHP 程序狀態
docker compose exec web ps aux | grep php

# 檢查 Nginx 狀態
docker compose exec nginx nginx -t
curl -I http://localhost/health

# 檢查 Redis 狀態
docker compose exec redis redis-cli ping
```

### 效能監控

```bash
# 監控資料庫查詢效能
docker compose exec web php scripts/db-performance.php

# 監控快取效能
docker compose exec redis redis-cli info stats

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
docker compose ps

# 檢查網站回應
echo "2. 檢查網站回應..."
curl -f http://localhost/ > /dev/null && echo "✓ 網站正常" || echo "✗ 網站異常"

# 檢查資料庫
echo "3. 檢查資料庫..."
docker compose exec web sqlite3 database/alleynote.sqlite3 "SELECT 1;" > /dev/null && echo "✓ 資料庫正常" || echo "✗ 資料庫異常"

# 檢查 Redis
echo "4. 檢查 Redis..."
docker compose exec redis redis-cli ping > /dev/null && echo "✓ Redis 正常" || echo "✗ Redis 異常"

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

## � 統計模組

### 儀表板與快照檢視

- 後台路徑：管理後台 → 「統計儀表板」可檢視總覽、熱門內容與趨勢圖。
- 快速確認資料：
  ```bash
  docker compose exec web sqlite3 database/alleynote.sqlite3 "
  SELECT snapshot_type, period_type, snapshot_date, total_posts, total_users
  FROM statistics_snapshots
  ORDER BY snapshot_date DESC
  LIMIT 10;
  "
  ```
- 若儀表板無法載入，請先清除瀏覽器快取並確認後端 `statistics_snapshots` 資料是否存在。

### 手動回填與重新計算

- 推薦於部署後或大量資料匯入後執行以下指令重新整理統計：
  ```bash
  docker compose exec web php ./scripts/statistics-calculation.php --periods=daily,weekly --force
  ```
- 指令選項說明：
  - `--periods` 可指定 `daily`、`weekly`、`monthly` 多種週期（以逗號分隔）。
  - `--force` 會覆蓋既有快照，可搭配 `--max-retries` 控制重試次數。
- 若無法使用容器，可在主機端執行 `./backend/scripts/statistics-calculation.php`，需先載入 Composer 相依套件。

### 快取與排程建議

- 建議每天定時執行統計計算腳本，可加入 crontab：
  ```cron
  15 1 * * * docker compose exec web php ./scripts/statistics-calculation.php --periods=daily
  ```
- 若要強制刷新統計快取，可刪除快照快取並重新計算：
  ```bash
  docker compose exec web rm -rf storage/cache/statistics || true
  docker compose exec web php ./scripts/statistics-calculation.php --force
  ```
- 重新部署後建議再執行 `docker compose exec web php ./scripts/warm-cache.php`，確保 DI 與統計相依服務快取已就緒。

---

## �🛠️ 故障排除

### 常見問題診斷

#### 網站無法訪問

```bash
# 1. 檢查容器狀態
docker compose ps

# 2. 檢查 Nginx 配置
docker compose exec nginx nginx -t

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
ls -la database/alleynote.sqlite3

# 2. 檢查檔案權限
docker compose exec web ls -la database/alleynote.sqlite3

# 3. 修復權限
docker compose exec web chown www-data:www-data database/alleynote.sqlite3
docker compose exec web chmod 664 database/alleynote.sqlite3

# 4. 測試資料庫連線
docker compose exec web sqlite3 database/alleynote.sqlite3 "SELECT 1;"

# 5. 檢查資料庫完整性
docker compose exec web sqlite3 database/alleynote.sqlite3 "PRAGMA integrity_check;"
```

#### 記憶體不足

```bash
# 1. 檢查記憶體使用
free -h
docker stats --no-stream

# 2. 重啟服務釋放記憶體
docker compose restart

# 3. 清理無用的 Docker 映像
docker system prune -f

# 4. 調整 PHP 記憶體限制
nano docker/php/php.ini
# 修改 memory_limit = 512M
docker compose down && docker compose up -d --build
```

#### SSL 憑證問題

```bash
# 1. 檢查憑證有效期
openssl x509 -in ssl-data/live/yourdomain.com/fullchain.pem -text -noout

# 2. 檢查憑證鏈
openssl verify -CAfile ssl-data/live/yourdomain.com/chain.pem ssl-data/live/yourdomain.com/cert.pem

# 3. 重新申請憑證
docker compose exec certbot certbot delete --cert-name yourdomain.com
./scripts/ssl-setup.sh yourdomain.com admin@yourdomain.com

# 4. 檢查 Nginx SSL 配置
docker compose exec nginx nginx -t
```

### 緊急恢復程序

```bash
# 1. 停止所有服務
docker compose down

# 2. 備份當前狀態
cp -r database/ database_backup_$(date +%Y%m%d_%H%M%S)/

# 3. 恢復到最近的備份
./scripts/restore_sqlite.sh database/backups/latest_backup.db

# 4. 重啟服務
docker compose up -d

# 5. 驗證系統狀態
./scripts/health-check.sh
```

---

## ⚡ 效能優化

### 資料庫優化

```bash
# 1. 重建資料庫索引
docker compose exec web sqlite3 database/alleynote.sqlite3 "REINDEX;"

# 2. 清理資料庫
docker compose exec web sqlite3 database/alleynote.sqlite3 "VACUUM;"

# 3. 分析查詢效能
docker compose exec web sqlite3 database/alleynote.sqlite3 "ANALYZE;"

# 4. 檢查資料庫統計
docker compose exec web sqlite3 database/alleynote.sqlite3 "
SELECT name, COUNT(*) as row_count
FROM sqlite_master m JOIN pragma_table_info(m.name) p
WHERE m.type='table'
GROUP BY name;
"
```

### 快取優化

```bash
# 1. 清理應用程式快取
docker compose exec web rm -rf storage/cache/*

# 2. 清理 Redis 快取
docker compose exec redis redis-cli FLUSHALL

# 3. 預熱快取
docker compose exec web php scripts/warm-cache.php

# 4. 監控快取命中率
docker compose exec redis redis-cli info stats | grep hit
```

### 檔案系統優化

```bash
# 1. 清理臨時檔案
find storage/tmp/ -type f -mtime +7 -delete

# 2. 壓縮舊日誌
gzip logs/*.log.1

# 3. 清理未使用的附件
docker compose exec web php scripts/cleanup-orphaned-files.php

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
docker compose logs -f --tail=100 web
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
        docker compose exec web php scripts/log-cleanup.php
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
