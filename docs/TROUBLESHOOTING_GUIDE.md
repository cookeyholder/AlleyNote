# AlleyNote 故障排除和維護指南

**版本**: v4.0
**更新日期**: 2025-09-03
**適用環境**: 前後端分離架構 (Vue.js 3 + PHP 8.4.12 DDD)
**系統版本**: Docker 28.3.3, Docker Compose v2.39.2

> 🔧 **目標**：為管理員提供系統故障排除的完整解決方案和維護最佳實踐

---

## 📑 目錄

- [緊急故障處理](#緊急故障處理)
- [常見問題診斷](#常見問題診斷)
- [前後端分離問題](#前後端分離問題)
- [系統監控告警](#系統監控告警)
- [日常維護任務](#日常維護任務)
- [效能問題處理](#效能問題處理)
- [安全事件處理](#安全事件處理)
- [資料恢復程序](#資料恢復程序)
- [預防性維護](#預防性維護)

---

## 🚨 緊急故障處理

### 系統完全無法訪問

#### 診斷步驟
```bash
# 1. 檢查系統是否活動
ping your-server-ip

# 2. 檢查 SSH 連線
ssh user@your-server-ip

# 3. 檢查前後端容器狀態
docker compose ps

# 4. 檢查系統資源
top
df -h
free -h

# 5. 檢查前端服務 (Vue.js 3)
curl -I http://localhost:3000

# 6. 檢查後端 API (PHP 8.4.12)
curl -I http://localhost:8080/api/health
```

#### 緊急恢復程序
```bash
# 1. 強制重啟所有容器 (前後端分離)
docker compose -f docker compose.production.yml down --remove-orphans
docker compose -f docker compose.production.yml up -d

# 2. 如果容器無法啟動，檢查日誌
docker compose logs --tail=100 web          # 後端日誌
docker compose logs --tail=100 frontend     # 前端日誌
docker compose logs --tail=100 db           # 資料庫日誌

# 3. 檢查系統日誌
sudo journalctl -u docker.service --since "1 hour ago"

# 4. 緊急模式啟動（僅後端服務）
docker compose up -d web

# 5. 檢查前端建構狀態
cd frontend && npm run build

# 6. 最後手段：重啟整個系統
sudo reboot
```

### 資料庫連線問題

#### SQLite3 故障排除 (預設推薦)
```bash
# 1. 檢查 SQLite3 檔案權限
ls -la /var/www/html/database/alleynote.sqlite3

# 2. 檢查檔案是否存在且可寫入
test -w /var/www/html/database/alleynote.sqlite3 && echo "可寫入" || echo "權限問題"

# 3. 備份資料庫
cp /var/www/html/database/alleynote.sqlite3 \
   /var/www/html/storage/backups/alleynote_$(date +%Y%m%d_%H%M%S).sqlite3

# 4. 檢查資料庫完整性
sqlite3 /var/www/html/database/alleynote.sqlite3 "PRAGMA integrity_check;"
```

#### PostgreSQL 故障排除 (大型部署)
```bash
# 1. 停止所有服務
docker compose -f docker compose.production.yml down

# 2. 檢查資料庫容器狀態
docker compose ps db

# 3. 備份當前資料庫
docker compose exec db pg_dump -U ${DB_USERNAME} -d ${DB_DATABASE} \
  --clean --if-exists > backup_$(date +%Y%m%d_%H%M%S).sql

# 4. 檢查資料庫日誌
docker compose logs db

# 5. 重新啟動資料庫服務
docker compose up -d db

# 6. 等待資料庫啟動完成
sleep 30

# 7. 測試連線
docker compose exec db psql -U ${DB_USERNAME} -d ${DB_DATABASE} -c "SELECT 1;"

# 8. 如果仍有問題，檢查資料庫完整性
docker compose exec db psql -U ${DB_USERNAME} -d ${DB_DATABASE} -c "SELECT version();"
```

### SSL 憑證過期

#### 快速修復
```bash
# 1. 檢查憑證狀態
openssl x509 -in ssl-data/live/yourdomain.com/fullchain.pem -text -noout | grep "Not After"

# 2. 強制更新憑證
docker compose exec certbot certbot renew --force-renewal

# 3. 重新載入 Nginx
docker compose exec nginx nginx -s reload

# 4. 如果更新失敗，重新申請憑證
./scripts/ssl-setup.sh yourdomain.com admin@yourdomain.com

# 5. 驗證憑證更新
curl -I https://yourdomain.com
```

---

## 🔍 常見問題診斷

### 網站回應緩慢

#### 診斷流程
```bash
# 1. 檢查系統負載
uptime
top -bn1 | head -20

# 2. 檢查記憶體使用 (前後端分離需更多記憶體)
free -h
ps aux --sort=-%mem | head -10

# 3. 檢查磁碟 I/O
iostat -x 1 5

# 4. 檢查網路連線
netstat -i
ss -tuln

# 5. 檢查 Docker 容器資源
docker stats --no-stream

# 6. 檢查前端效能
curl -w "@/dev/stdin" -o /dev/null -s http://localhost:3000 <<< "
time_namelookup:  %{time_namelookup}\n
time_connect:     %{time_connect}\n
time_appconnect:  %{time_appconnect}\n
time_pretransfer: %{time_pretransfer}\n
time_redirect:    %{time_redirect}\n
time_starttransfer: %{time_starttransfer}\n
time_total:       %{time_total}\n"

# 7. 檢查後端 API 效能
curl -w "@/dev/stdin" -o /dev/null -s http://localhost:8080/api/health <<< "
time_total: %{time_total}\n"
```

#### 解決方案
```bash
# 清理系統快取
sudo sync && sudo sysctl vm.drop_caches=3

# 重啟緩慢的容器
docker compose restart web frontend

# 清理 PHP OPcache (PHP 8.4.12)
docker compose exec web php -r "opcache_reset();"

# 重建前端資產
cd frontend
npm run build

# 優化資料庫
# SQLite3 (預設)
sqlite3 /var/www/html/database/alleynote.sqlite3 "VACUUM; ANALYZE;"

# PostgreSQL (大型部署時)
docker compose exec db psql -U ${DB_USERNAME} -d ${DB_DATABASE} -c "VACUUM ANALYZE;"
docker compose exec db psql -U ${DB_USERNAME} -d ${DB_DATABASE} -c "REINDEX DATABASE ${DB_DATABASE};"
```

### 404 錯誤頻發

#### 檢查項目
```bash
# 1. 檢查 Nginx 配置
docker compose exec nginx nginx -t

# 2. 檢查 Nginx 錯誤日誌
docker compose logs nginx | grep "404"

# 3. 檢查檔案權限
ls -la public/
ls -la storage/

# 4. 檢查路由配置
docker compose exec web php -r "
include 'vendor/autoload.php';
// 檢查路由設定
"
```

#### 修復步驟
```bash
# 1. 修復檔案權限
docker compose exec web chown -R www-data:www-data /var/www/html
docker compose exec web chmod -R 755 public/
docker compose exec web chmod -R 775 storage/

# 2. 清理並重建快取
docker compose exec web php artisan cache:clear
docker compose exec web php artisan route:clear

# 3. 重新載入 Nginx 配置
docker compose exec nginx nginx -s reload
```

### 500 內部伺服器錯誤

#### 日誌檢查
```bash
# 1. 檢查 PHP 錯誤日誌
docker compose logs web | tail -50

# 2. 檢查應用程式日誌
tail -50 logs/app.log

# 3. 檢查 Nginx 錯誤日誌
docker compose exec nginx tail -50 /var/log/nginx/error.log

# 4. 檢查系統日誌
sudo journalctl -u docker.service --since "1 hour ago"
```

#### 常見原因和修復
```bash
# PHP 記憶體不足
# 編輯 docker/php/php.ini
memory_limit = 512M

# 檔案權限問題
docker compose exec web chown -R www-data:www-data /var/www/html
docker compose exec web chmod -R 755 /var/www/html

# PHP 擴展缺失
docker compose exec web php -m | grep -i needed_extension

# 重建容器
docker compose down
docker compose up -d --build
```

### 資料庫連線失敗

#### 診斷步驟
```bash
# 1. 檢查資料庫檔案
ls -la database/alleynote.db

# 2. 檢查檔案權限
docker compose exec web ls -la database/alleynote.db

# 3. 測試資料庫連線
docker compose exec web sqlite3 database/alleynote.db "SELECT 1;"

# 4. 檢查資料庫鎖定
lsof database/alleynote.db
```

#### 修復方法
```bash
# 1. 修復檔案權限
docker compose exec web chown www-data:www-data database/alleynote.db
docker compose exec web chmod 664 database/alleynote.db

# 2. 檢查並修復資料庫
docker compose exec web sqlite3 database/alleynote.db "PRAGMA integrity_check;"

# 3. 重建資料庫索引
docker compose exec web sqlite3 database/alleynote.db "REINDEX;"

# 4. 如果資料庫損壞，恢復備份
./scripts/restore_sqlite.sh database/backups/latest_backup.db
```

---

## 📊 系統監控告警

### 建立監控腳本

#### 系統健康檢查腳本
```bash
#!/bin/bash
# /usr/local/bin/alleynote-health-check.sh

LOG_FILE="/var/log/alleynote-health.log"
ALERT_EMAIL="admin@yourdomain.com"
DOMAIN="yourdomain.com"

# 記錄函數
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}

# 發送警告
send_alert() {
    echo "$1" | mail -s "AlleyNote 系統警告" $ALERT_EMAIL
    log_message "ALERT: $1"
}

# 檢查網站回應
check_website() {
    if ! curl -f -s http://$DOMAIN/ > /dev/null; then
        send_alert "網站無法訪問: http://$DOMAIN/"
        return 1
    fi
    return 0
}

# 檢查容器狀態
check_containers() {
    local failed_containers=$(docker compose ps | grep -v "Up" | grep -v "Name" | wc -l)
    if [ $failed_containers -gt 0 ]; then
        send_alert "發現 $failed_containers 個容器狀態異常"
        return 1
    fi
    return 0
}

# 檢查磁碟空間
check_disk_space() {
    local usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ $usage -gt 85 ]; then
        send_alert "磁碟使用率過高: ${usage}%"
        return 1
    fi
    return 0
}

# 檢查記憶體使用
check_memory() {
    local mem_usage=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    if [ $mem_usage -gt 90 ]; then
        send_alert "記憶體使用率過高: ${mem_usage}%"
        return 1
    fi
    return 0
}

# 檢查資料庫
check_database() {
    if ! docker compose exec -T web sqlite3 database/alleynote.db "SELECT 1;" > /dev/null 2>&1; then
        send_alert "資料庫連線失敗"
        return 1
    fi
    return 0
}

# 執行所有檢查
main() {
    log_message "開始健康檢查"

    local errors=0

    check_website || ((errors++))
    check_containers || ((errors++))
    check_disk_space || ((errors++))
    check_memory || ((errors++))
    check_database || ((errors++))

    if [ $errors -eq 0 ]; then
        log_message "所有檢查通過"
    else
        log_message "檢查完成，發現 $errors 個問題"
    fi
}

main "$@"
```

#### 設定定期檢查
```bash
# 加入 crontab
crontab -e

# 每 5 分鐘檢查一次
*/5 * * * * /usr/local/bin/alleynote-health-check.sh

# 每小時生成狀態報告
0 * * * * /usr/local/bin/alleynote-status-report.sh
```

### 效能監控

#### CPU 和記憶體監控
```bash
#!/bin/bash
# 效能監控腳本

# 檢查 CPU 使用率
cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | sed 's/%us,//')
if (( $(echo "$cpu_usage > 80" | bc -l) )); then
    echo "警告：CPU 使用率過高 (${cpu_usage}%)"
fi

# 檢查記憶體使用率
mem_usage=$(free | awk 'NR==2{printf "%.1f", $3*100/$2}')
if (( $(echo "$mem_usage > 85" | bc -l) )); then
    echo "警告：記憶體使用率過高 (${mem_usage}%)"
fi

# 檢查負載平均
load_avg=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
cpu_count=$(nproc)
if (( $(echo "$load_avg > $cpu_count" | bc -l) )); then
    echo "警告：系統負載過高 (${load_avg})"
fi
```

---

## 🛠️ 日常維護任務

### 每日維護清單

```bash
#!/bin/bash
# 每日維護腳本

echo "=== AlleyNote 每日維護 $(date) ==="

# 1. 檢查系統狀態
echo "1. 檢查系統狀態..."
./scripts/health-check.sh

# 2. 清理臨時檔案
echo "2. 清理臨時檔案..."
find storage/tmp/ -type f -mtime +1 -delete
find storage/cache/ -name "*.tmp" -mtime +1 -delete

# 3. 檢查磁碟空間
echo "3. 檢查磁碟空間..."
df -h

# 4. 檢查日誌大小
echo "4. 檢查日誌大小..."
du -sh logs/

# 5. 檢查備份狀態
echo "5. 檢查備份狀態..."
ls -la database/backups/ | tail -5

# 6. 檢查 SSL 憑證
echo "6. 檢查 SSL 憑證..."
if [ -f ssl-data/live/yourdomain.com/fullchain.pem ]; then
    openssl x509 -in ssl-data/live/yourdomain.com/fullchain.pem -text -noout | grep "Not After"
fi

echo "=== 每日維護完成 ==="
```

### 每週維護清單

```bash
#!/bin/bash
# 每週維護腳本

echo "=== AlleyNote 每週維護 $(date) ==="

# 1. 更新系統套件
echo "1. 檢查系統更新..."
apt list --upgradable

# 2. 清理 Docker 資源
echo "2. 清理 Docker 資源..."
docker system prune -f
docker volume prune -f

# 3. 優化資料庫
echo "3. 優化資料庫..."
docker compose exec web sqlite3 database/alleynote.db "VACUUM;"
docker compose exec web sqlite3 database/alleynote.db "ANALYZE;"

# 4. 備份驗證
echo "4. 驗證備份完整性..."
latest_backup=$(ls -t database/backups/*.db | head -1)
if [ -f "$latest_backup" ]; then
    sqlite3 "$latest_backup" "PRAGMA integrity_check;"
fi

# 5. 檢查安全日誌
echo "5. 檢查安全事件..."
grep -i "failed\|error\|attack" logs/security.log | tail -10

# 6. 效能分析
echo "6. 效能分析..."
docker compose exec web php scripts/db-performance.php

echo "=== 每週維護完成 ==="
```

### 每月維護清單

```bash
#!/bin/bash
# 每月維護腳本

echo "=== AlleyNote 每月維護 $(date) ==="

# 1. 完整系統更新
echo "1. 執行系統更新..."
sudo apt update && sudo apt upgrade -y

# 2. 重新整理容器映像
echo "2. 更新容器映像..."
docker compose pull
docker compose down
docker compose up -d --build

# 3. 清理舊備份
echo "3. 清理舊備份..."
find database/backups/ -name "*.db" -mtime +90 -delete
find database/backups/ -name "*.tar.gz" -mtime +90 -delete

# 4. 檢查 SSL 憑證續簽
echo "4. 檢查 SSL 憑證..."
docker compose exec certbot certbot certificates

# 5. 安全掃描
echo "5. 執行安全掃描..."
docker run --rm -v $(pwd):/app clamav/clamav clamscan -r /app/storage/uploads/

# 6. 效能基準測試
echo "6. 效能基準測試..."
ab -n 100 -c 10 http://localhost/ > performance_report_$(date +%Y%m).txt

echo "=== 每月維護完成 ==="
```

---

## ⚡ 效能問題處理

### 網站回應緩慢

#### 診斷工具
```bash
# 1. 檢查回應時間
curl -w "@curl-format.txt" -o /dev/null -s "http://yourdomain.com/"

# curl-format.txt 內容：
cat > curl-format.txt << 'EOF'
     time_namelookup:  %{time_namelookup}\n
        time_connect:  %{time_connect}\n
     time_appconnect:  %{time_appconnect}\n
    time_pretransfer:  %{time_pretransfer}\n
       time_redirect:  %{time_redirect}\n
  time_starttransfer:  %{time_starttransfer}\n
                     ----------\n
          time_total:  %{time_total}\n
EOF

# 2. 分析慢查詢
docker compose exec web php scripts/slow-query-analyzer.php

# 3. 檢查快取命中率
docker compose exec redis redis-cli info stats | grep hit
```

#### 優化策略
```bash
# 1. 啟用 OPcache
# 編輯 docker/php/php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000

# 2. 調整 PHP-FPM 設定
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35

# 3. 優化 Nginx 設定
# 編輯 docker/nginx/default.conf
gzip on;
gzip_types text/plain text/css application/json application/javascript;
client_max_body_size 10M;

# 4. 資料庫索引優化
docker compose exec web sqlite3 database/alleynote.db "
CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at);
CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);
CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
"
```

### 記憶體洩漏問題

#### 檢測方法
```bash
# 1. 監控記憶體使用趨勢
while true; do
    echo "$(date): $(docker stats --no-stream --format 'table {{.Name}}\t{{.MemUsage}}' | grep alleynote)"
    sleep 300
done >> memory_usage.log

# 2. 分析記憶體使用模式
docker compose exec web php -r "
echo 'Memory usage: ' . memory_get_usage(true) / 1024 / 1024 . ' MB' . PHP_EOL;
echo 'Peak usage: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB' . PHP_EOL;
"

# 3. 檢查 PHP 記憶體限制
docker compose exec web php -i | grep memory_limit
```

#### 解決方案
```bash
# 1. 重啟容器釋放記憶體
docker compose restart web

# 2. 調整 PHP 記憶體限制
# 編輯 docker/php/php.ini
memory_limit = 256M

# 3. 啟用記憶體垃圾回收
# 在 PHP 程式碼中
gc_enable();
gc_collect_cycles();
```

---

## 🔒 安全事件處理

### 可疑活動檢測

#### 監控指標
```bash
# 1. 檢查失敗登入
docker compose exec web sqlite3 database/alleynote.db "
SELECT ip_address, COUNT(*) as attempts, MAX(created_at) as last_attempt
FROM failed_login_attempts
WHERE created_at > datetime('now', '-24 hours')
GROUP BY ip_address
HAVING attempts > 10
ORDER BY attempts DESC;
"

# 2. 檢查異常 IP 存取
tail -1000 logs/access.log | awk '{print $1}' | sort | uniq -c | sort -nr | head -20

# 3. 檢查大量檔案上傳
docker compose exec web sqlite3 database/alleynote.db "
SELECT user_id, COUNT(*) as uploads, SUM(file_size) as total_size
FROM attachments
WHERE created_at > datetime('now', '-24 hours')
GROUP BY user_id
HAVING uploads > 50 OR total_size > 104857600
ORDER BY uploads DESC;
"
```

#### 自動封鎖腳本
```bash
#!/bin/bash
# 自動封鎖可疑 IP

BLOCK_THRESHOLD=50
LOG_FILE="/var/log/alleynote-security.log"

# 分析存取日誌
awk '{print $1}' logs/access.log | sort | uniq -c | sort -nr | while read count ip; do
    if [ $count -gt $BLOCK_THRESHOLD ]; then
        # 檢查是否已封鎖
        if ! iptables -L INPUT | grep -q $ip; then
            # 加入防火牆規則
            iptables -A INPUT -s $ip -j DROP
            echo "$(date): 封鎖 IP $ip (請求數: $count)" >> $LOG_FILE

            # 記錄到資料庫
            docker compose exec web sqlite3 database/alleynote.db "
            INSERT INTO ip_lists (ip_address, type, description, created_by, created_at)
            VALUES ('$ip', 'blacklist', '自動封鎖 - 請求數過多 ($count)', 0, datetime('now'));
            "
        fi
    fi
done
```

### 惡意檔案檢測

#### 掃描腳本
```bash
#!/bin/bash
# 惡意檔案掃描

UPLOAD_DIR="storage/uploads"
QUARANTINE_DIR="storage/quarantine"

# 建立隔離目錄
mkdir -p $QUARANTINE_DIR

# 掃描可疑檔案類型
find $UPLOAD_DIR -type f \( -name "*.php" -o -name "*.exe" -o -name "*.bat" -o -name "*.sh" \) | while read file; do
    echo "發現可疑檔案: $file"
    mv "$file" "$QUARANTINE_DIR/"
    echo "$(date): 隔離檔案 $file" >> /var/log/alleynote-security.log
done

# 檢查檔案大小異常
find $UPLOAD_DIR -type f -size +50M | while read file; do
    echo "發現大型檔案: $file ($(du -h "$file" | cut -f1))"
done
```

---

## 💾 資料恢復程序

### 資料庫恢復

#### 完整恢復程序
```bash
#!/bin/bash
# 資料庫恢復腳本

BACKUP_DIR="database/backups"
RESTORE_POINT="$1"

if [ -z "$RESTORE_POINT" ]; then
    echo "使用方法: $0 <backup_file>"
    echo "可用備份:"
    ls -la $BACKUP_DIR/
    exit 1
fi

echo "=== 開始資料恢復程序 ==="

# 1. 停止服務
echo "停止服務..."
docker compose down

# 2. 備份當前資料庫
echo "備份當前資料庫..."
cp database/alleynote.db database/alleynote_before_restore_$(date +%Y%m%d_%H%M%S).db

# 3. 恢復資料庫
echo "恢復資料庫..."
cp "$RESTORE_POINT" database/alleynote.db

# 4. 檢查資料庫完整性
echo "檢查資料庫完整性..."
sqlite3 database/alleynote.db "PRAGMA integrity_check;"

# 5. 修復權限
echo "修復權限..."
chown www-data:www-data database/alleynote.db
chmod 664 database/alleynote.db

# 6. 重啟服務
echo "重啟服務..."
docker compose up -d

# 7. 驗證恢復
echo "驗證恢復..."
sleep 10
curl -f http://localhost/ && echo "恢復成功" || echo "恢復失敗"

echo "=== 資料恢復程序完成 ==="
```

### 檔案恢復

#### 檔案恢復腳本
```bash
#!/bin/bash
# 檔案恢復腳本

BACKUP_FILE="$1"
RESTORE_DIR="storage"

if [ -z "$BACKUP_FILE" ]; then
    echo "使用方法: $0 <backup_tar_file>"
    exit 1
fi

echo "=== 開始檔案恢復程序 ==="

# 1. 備份當前檔案
echo "備份當前檔案..."
tar -czf "storage_backup_$(date +%Y%m%d_%H%M%S).tar.gz" storage/

# 2. 恢復檔案
echo "恢復檔案..."
tar -xzf "$BACKUP_FILE" -C ./

# 3. 修復權限
echo "修復權限..."
chown -R www-data:www-data storage/
chmod -R 755 storage/

# 4. 驗證檔案
echo "驗證檔案..."
ls -la storage/

echo "=== 檔案恢復程序完成 ==="
```

---

## 🔧 預防性維護

### 系統加固

#### 安全設定檢查清單
```bash
#!/bin/bash
# 安全設定檢查

echo "=== AlleyNote 安全設定檢查 ==="

# 1. 檢查檔案權限
echo "1. 檢查檔案權限..."
find . -type f -perm /o+w -ls | grep -v ".git"

# 2. 檢查 SSH 設定
echo "2. 檢查 SSH 設定..."
grep "PermitRootLogin\|PasswordAuthentication" /etc/ssh/sshd_config

# 3. 檢查防火牆狀態
echo "3. 檢查防火牆狀態..."
ufw status

# 4. 檢查開放端口
echo "4. 檢查開放端口..."
netstat -tulpn | grep LISTEN

# 5. 檢查最近登入
echo "5. 檢查最近登入..."
last -10

# 6. 檢查系統更新
echo "6. 檢查系統更新..."
apt list --upgradable | head -10

echo "=== 安全檢查完成 ==="
```

### 效能監控儀表板

#### 建立監控腳本
```bash
#!/bin/bash
# 效能監控儀表板

clear
echo "=== AlleyNote 系統監控儀表板 ==="
echo "更新時間: $(date)"
echo

# 系統資源
echo "📊 系統資源使用："
echo "CPU: $(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | sed 's/%us,//')%"
echo "記憶體: $(free | awk 'NR==2{printf "%.1f", $3*100/$2}')%"
echo "磁碟: $(df / | awk 'NR==2 {print $5}')"
echo

# 容器狀態
echo "🐳 容器狀態："
docker compose ps --format "table {{.Name}}\t{{.State}}\t{{.Status}}"
echo

# 網站狀態
echo "🌐 網站狀態："
if curl -f -s http://localhost/ > /dev/null; then
    echo "✅ 網站正常"
else
    echo "❌ 網站異常"
fi
echo

# 資料庫狀態
echo "💾 資料庫狀態："
if docker compose exec -T web sqlite3 database/alleynote.db "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ 資料庫正常"
    echo "大小: $(ls -lh database/alleynote.db | awk '{print $5}')"
else
    echo "❌ 資料庫異常"
fi
echo

# 最新日誌
echo "📝 最新錯誤（最近10筆）："
tail -10 logs/error.log 2>/dev/null || echo "無錯誤日誌"
```

### 自動化監控設定

#### 建立 Systemd 服務
```bash
# /etc/systemd/system/alleynote-monitor.service
[Unit]
Description=AlleyNote Monitoring Service
After=network.target

[Service]
Type=simple
User=alleynote
WorkingDirectory=/var/alleynote
ExecStart=/var/alleynote/scripts/monitor.sh
Restart=always
RestartSec=300

[Install]
WantedBy=multi-user.target
```

```bash
# 啟用監控服務
sudo systemctl enable alleynote-monitor.service
sudo systemctl start alleynote-monitor.service
```

---

## 📞 支援與聯絡

### 問題回報模板

```
### 問題描述
[簡述問題現象]

### 環境資訊
- 作業系統：
- Docker 版本：
- AlleyNote 版本：

### 重現步驟
1.
2.
3.

### 錯誤訊息
```
[貼上完整錯誤訊息]
```

### 已嘗試的解決方案
[列出已經嘗試過的修復方法]

### 系統日誌
[貼上相關的系統日誌]
```

### 緊急聯絡方式

- **系統管理員**：admin@yourdomain.com
- **技術支援**：support@yourdomain.com
- **緊急電話**：+886-xxx-xxx-xxx

### 相關文件

- [管理員快速入門](ADMIN_QUICK_START.md)
- [管理員操作手冊](ADMIN_MANUAL.md)
- [系統需求說明](SYSTEM_REQUIREMENTS.md)
- [部署指南](DEPLOYMENT.md)

---

**🔧 本指南應定期更新，確保內容與系統實際狀況一致。**
