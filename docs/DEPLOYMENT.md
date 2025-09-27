# AlleyNote 公布欄網站部署指南

**版本**: v4.0
**更新日期**: 2025-01-20
**適用環境**: 生產環境、預備環境
**架構**: 前後端分離 (Vite + TypeScript + PHP 8.4.12 DDD 後端)

## 1. 系統需求

### 1.1 硬體需求
- CPU: 4 核心以上 (推薦 8 核心)
- 記憶體: 8GB 以上 (推薦 16GB)
- 硬碟空間: 50GB 以上 (推薦 100GB NVMe SSD)
- 網路頻寬: 1Gbps 以上

### 1.2 軟體需求
- **作業系統**: Debian 12 (強烈推薦) / Ubuntu 24.04 LTS
- **後端**: PHP 8.4.12+ (Docker 容器內自動提供)
- **前端**: Vite + TypeScript + Node.js 20.x LTS
- **資料庫**: SQLite3 (預設推薦) / PostgreSQL 16+ (大型部署)
- **Web Server**: NGINX (Docker 容器內自動提供)
- **容器平台**: Docker 28.3.3+ & Docker Compose v2.39.2+
- **測試**: PHPUnit 11.5.34 (138 檔案, 1,372 通過測試)

### 1.3 網路需求
- 固定 IP 位址或 FQDN
- 支援 HTTPS (443 埠)
- 支援 HTTP (80 埠)
- 前端服務埠 (3000)
- 後端 API 埠 (8080)
- SSL 憑證 (Let's Encrypt 或自訂)

## 2. 安裝步驟

### 2.1 基礎環境安裝
```bash
# 更新系統套件
apt update && apt upgrade -y

# 安裝必要工具
apt install -y curl git unzip jq

# 安裝 Docker 28.3.3+
curl -fsSL https://get.docker.com | sh

# 安裝 Docker Compose v2.39.2+
curl -L "https://github.com/docker/compose/releases/download/v2.39.2/docker compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker compose
chmod +x /usr/local/bin/docker compose

# 驗證版本
docker --version  # 應顯示 28.3.3+
docker compose --version  # 應顯示 v2.39.2+
```

### 2.2 🚀 專案部署 (前後端分離)

```bash
# 建立專案目錄
mkdir -p /var/www/alleynote
cd /var/www/alleynote

# 複製專案檔案
git clone https://github.com/your-org/alleynote.git .

# 設定後端環境變數
cp backend/.env.example backend/.env
# 編輯 backend/.env 檔案，設定必要的環境變數：
# - APP_ENV=production
# - 管理員帳號密碼
# - 資料庫設定
# - JWT 密鑰
# - Telegram 通知設定

# 設定前端環境變數
cp frontend/.env.example frontend/.env
# 編輯 frontend/.env 檔案：
# - VITE_API_BASE_URL=https://your-domain.com/api
# - VITE_APP_ENV=production

# 啟動 Docker 容器
docker compose up -d

# 後端初始化
cd backend
docker compose exec web composer install --optimize-autoloader --no-dev
docker compose exec web ./vendor/bin/phinx migrate
docker compose exec web php -r "opcache_reset();"

# 前端建構和部署
cd ../frontend
npm ci
npm run build
```

### 2.3 驗證部署

```bash
# 檢查容器狀態
docker compose ps

# 驗證後端 API
curl -i http://localhost:8080/api/health

# 執行後端測試 (1,372 個測試)
docker compose exec web ./vendor/bin/phpunit

# 檢查前端建構
ls -la frontend/dist/

# 驗證前端服務
curl -i http://localhost:3000
```

### 2.4 SSL 憑證設定

```bash
# 安裝 Certbot
apt install -y certbot python3-certbot-nginx

# 取得 SSL 憑證
certbot --nginx -d your-domain.com

# 設定自動續約
echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
```

## 3. 環境設定

### 3.1 NGINX 設定 (前後端分離)
```nginx
# 前端 (Vite + TypeScript)
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    root /var/www/alleynote/frontend/dist;
    index index.html;

    # 前端路由
    location / {
        try_files $uri $uri/ /index.html;
    }

    # 後端 API 代理
    location /api/ {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # 安全性標頭
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # 靜態檔案快取
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 3.2 PHP 8.4.12 設定
```ini
; php.ini 設定 (針對 PHP 8.4.12)
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
date.timezone = Asia/Taipei

; PHP 8.4.12 特定設定
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.save_comments=1
opcache.enable_file_override=1

; 新特性支援
jit_buffer_size=256M
realpath_cache_size=4096k
realpath_cache_ttl=600
```

### 3.3 Docker Compose v2.39.2 設定
```yaml
# docker compose.production.yml
version: '3.8'

services:
  # 後端 API 服務
  web:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: alleynote_backend
    ports:
      - "8080:80"
    volumes:
      - ./backend:/var/www/html
      - ./backend/storage:/var/www/html/storage
    environment:
      - APP_ENV=production
      - PHP_VERSION=8.4.12
    networks:
      - alleynote_network

  # 前端服務
  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    container_name: alleynote_frontend
    ports:
      - "3000:3000"
    volumes:
      - ./frontend/dist:/usr/share/nginx/html
    depends_on:
      - web
    networks:
      - alleynote_network

  # 資料庫服務 (生產環境使用 PostgreSQL)
  db:
    image: postgres:16-alpine
    container_name: alleynote_db
    environment:
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - alleynote_network

volumes:
  postgres_data:

networks:
  alleynote_network:
    driver: bridge
```

## 4. 部署流程 (前後端分離)

### 4.1 自動化部署腳本 (v4.0)
```bash
#!/bin/bash
# deploy.sh - 前後端分離部署腳本

set -e

echo "🚀 開始部署 AlleyNote v4.0 (前後端分離架構)"

# 停止現有服務
echo "停止現有服務..."
docker compose down

# 備份資料
echo "備份資料..."
./scripts/backup_database.sh
./scripts/backup_uploads.sh

# 更新程式碼
echo "更新程式碼..."
git pull origin main

# 後端部署
echo "部署後端 (PHP 8.4.12 DDD)..."
cd backend
docker compose run --rm web composer install --no-dev --optimize-autoloader
docker compose run --rm web ./vendor/bin/phinx migrate
docker compose run --rm web php -r "opcache_reset();"

# 前端部署
echo "部署前端 (Vite + TypeScript)..."
cd ../frontend
npm ci --production
npm run build
npm run test:unit  # 執行前端測試

# 啟動服務
echo "啟動服務..."
cd ..
docker compose -f docker compose.production.yml up -d

# 健康檢查
echo "執行健康檢查..."
sleep 10
curl -f http://localhost:8080/api/health || exit 1
curl -f http://localhost:3000 || exit 1

# 執行後端測試 (1,372 個測試)
echo "執行後端測試..."
docker compose exec web ./vendor/bin/phpunit

echo "✅ 部署完成！"
echo "後端 API: http://localhost:8080"
echo "前端介面: http://localhost:3000"
```

### 4.2 回滾程序 (v4.0)
```bash
#!/bin/bash
# rollback.sh - 前後端分離回滾腳本

set -e

echo "🔄 開始回滾 AlleyNote 到上一個版本"

# 停止現有服務
echo "停止服務..."
docker compose -f docker compose.production.yml down

# 後端回滾
echo "回滾後端..."
cd backend
git checkout HEAD^
docker compose run --rm web composer install --no-dev --optimize-autoloader

# 前端回滾
echo "回滾前端..."
cd ../frontend
git checkout HEAD^
npm ci --production
npm run build

# 還原資料庫 (如需要)
echo "還原資料庫..."
./scripts/restore_database.sh

# 重新啟動服務
echo "重新啟動服務..."
cd ..
docker compose -f docker compose.production.yml up -d

# 驗證回滾
echo "驗證回滾..."
sleep 15
curl -f http://localhost:8080/api/health || echo "⚠️ 後端健康檢查失敗"
curl -f http://localhost:3000 || echo "⚠️ 前端健康檢查失敗"

echo "✅ 回滾完成！"
```

### 4.3 藍綠部署 (Zero Downtime)
```bash
#!/bin/bash
# blue-green-deploy.sh - 零停機部署

CURRENT_ENV=$(docker compose ps --filter "status=running" | grep -q "blue" && echo "blue" || echo "green")
TARGET_ENV=$([ "$CURRENT_ENV" = "blue" ] && echo "green" || echo "blue")

echo "🔄 藍綠部署: $CURRENT_ENV → $TARGET_ENV"

# 準備目標環境
docker compose -f docker compose.$TARGET_ENV.yml up -d --build

# 等待服務啟動
sleep 30

# 健康檢查
if curl -f http://localhost:8080/api/health && curl -f http://localhost:3000; then
    echo "✅ 目標環境健康檢查通過"

    # 切換流量
    ./scripts/switch-traffic.sh $TARGET_ENV

    # 停止舊環境
    docker compose -f docker compose.$CURRENT_ENV.yml down

    echo "✅ 部署完成，流量已切換到 $TARGET_ENV"
else
    echo "❌ 健康檢查失敗，回滾到 $CURRENT_ENV"
    docker compose -f docker compose.$TARGET_ENV.yml down
    exit 1
fi
```

## 5. 維護與監控 (v4.0)

### 5.1 定期維護工作
```bash
# 每日維護腳本
#!/bin/bash
# daily-maintenance.sh

echo "📅 執行每日維護作業..."

# 1. 資料庫備份
./scripts/backup_database.sh

# 2. 日誌輪轉
docker compose exec web php -c "opcache_reset();"
find /var/log/alleynote -name "*.log" -mtime +7 -delete

# 3. 清理暫存檔案
docker system prune -f

# 4. 系統健康檢查
./scripts/health-check.sh

# 5. 效能指標收集
./scripts/collect-metrics.sh

echo "✅ 每日維護完成"
```

### 5.2 系統監控
```bash
# monitor.sh - 系統監控腳本
#!/bin/bash

# Docker 容器狀態
echo "=== 容器狀態 ==="
docker compose ps

# 系統資源使用率
echo "=== 系統資源 ==="
docker stats --no-stream

# API 健康檢查
echo "=== API 健康檢查 ==="
curl -s http://localhost:8080/api/health | jq .

# 前端可用性
echo "=== 前端可用性 ==="
curl -s -o /dev/null -w "%{http_code}" http://localhost:3000

# 測試執行狀態
echo "=== 測試覆蓋率 ==="
docker compose exec web ./vendor/bin/phpunit --coverage-text | tail -10

# 資料庫狀態
echo "=== 資料庫狀態 ==="
docker compose exec db psql -U ${DB_USERNAME} -d ${DB_DATABASE} -c "SELECT * FROM pg_stat_activity;"
```

### 5.3 效能優化 (PHP 8.4.12)
```bash
# performance-tuning.sh
#!/bin/bash

echo "🚀 執行效能優化..."

# 1. PHP OPcache 預熱
docker compose exec web php -r "
\$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('/var/www/html')
);
foreach (\$iterator as \$file) {
    if (\$file->getExtension() === 'php') {
        opcache_compile_file(\$file->getPathname());
    }
}
echo 'OPcache 預熱完成\\n';
"

# 2. 資料庫查詢優化
docker compose exec web php -r "
require '/var/www/html/vendor/autoload.php';
\$pdo = new PDO('sqlite:/var/www/html/storage/database.sqlite');
\$pdo->exec('VACUUM;');
\$pdo->exec('ANALYZE;');
echo '資料庫優化完成\\n';
"

# 3. 前端資產優化
cd frontend
npm run build:optimize

echo "✅ 效能優化完成"
```

### 5.4 故障排除指南

#### 5.4.1 常見問題診斷
```bash
# troubleshoot.sh - 故障診斷腳本
#!/bin/bash

echo "🔍 開始系統診斷..."

# 檢查 Docker 服務
echo "=== Docker 服務狀態 ==="
systemctl is-active docker
docker version --format '{{.Server.Version}}'

# 檢查容器狀態
echo "=== 容器運行狀態 ==="
docker compose ps
docker compose logs --tail=50 web
docker compose logs --tail=50 frontend

# 檢查網路連線
echo "=== 網路連線測試 ==="
curl -I http://localhost:8080/api/health
curl -I http://localhost:3000

# 檢查系統資源
echo "=== 系統資源使用 ==="
df -h
free -h
top -bn1 | head -10

# 檢查 PHP 配置
echo "=== PHP 8.4.12 狀態 ==="
docker compose exec web php -v
docker compose exec web php -m | grep -E "(opcache|xdebug)"

# 檢查測試狀態
echo "=== 測試執行狀態 ==="
docker compose exec web ./vendor/bin/phpunit --testdox | head -20

echo "✅ 診斷完成"
```

#### 5.4.2 效能問題排查
```bash
# performance-debug.sh
#!/bin/bash

echo "🚀 效能問題排查..."

# PHP 效能分析
docker compose exec web php -r "
echo 'OPcache 狀態:' . PHP_EOL;
print_r(opcache_get_status());

echo 'Memory 使用量:' . PHP_EOL;
echo 'Peak: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB' . PHP_EOL;
echo 'Current: ' . memory_get_usage(true) / 1024 / 1024 . ' MB' . PHP_EOL;
"

# 資料庫效能
docker compose exec db psql -U ${DB_USERNAME} -d ${DB_DATABASE} -c "
SELECT * FROM pg_stat_activity;
SELECT * FROM pg_stat_database;
SELECT schemaname,tablename,attname,n_distinct,correlation FROM pg_stats;
"

# 前端效能檢查
cd frontend
npm run analyze
```

### 5.5 安全性維護

#### 5.5.1 安全檢查腳本
```bash
# security-check.sh
#!/bin/bash

echo "🔒 執行安全檢查..."

# 檢查 Docker 容器安全
echo "=== Docker 安全掃描 ==="
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
  aquasec/trivy image alleynote_backend:latest

# 檢查 PHP 套件漏洞
echo "=== PHP 套件安全掃描 ==="
docker compose exec web composer audit

# 檢查前端套件漏洞
echo "=== 前端套件安全掃描 ==="
cd frontend
npm audit --audit-level moderate

# 檢查系統更新
echo "=== 系統安全更新 ==="
apt list --upgradable | grep -i security

# SSL 憑證檢查
echo "=== SSL 憑證狀態 ==="
openssl x509 -in /etc/letsencrypt/live/your-domain.com/cert.pem -text -noout | grep -A2 "Not After"

echo "✅ 安全檢查完成"
```

#### 5.5.2 防火牆設定
```bash
# 設定 UFW 防火牆
ufw --force reset
ufw default deny incoming
ufw default allow outgoing

# 允許必要連接埠
ufw allow 22/tcp      # SSH
ufw allow 80/tcp      # HTTP
ufw allow 443/tcp     # HTTPS
ufw allow 3000/tcp    # 前端服務
ufw allow 8080/tcp    # 後端 API

# 啟用防火牆
ufw --force enable
ufw status verbose
```

## 6. 擴展與升級

### 6.1 水平擴展 (Load Balancing)
```yaml
# docker compose.scale.yml
version: '3.8'

services:
  # 負載均衡器
  nginx-lb:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/load-balancer.conf:/etc/nginx/nginx.conf
    depends_on:
      - web-1
      - web-2
      - web-3

  # 後端 API 服務 (多實例)
  web-1:
    extends:
      file: docker compose.production.yml
      service: web
    container_name: alleynote_backend_1

  web-2:
    extends:
      file: docker compose.production.yml
      service: web
    container_name: alleynote_backend_2

  web-3:
    extends:
      file: docker compose.production.yml
      service: web
    container_name: alleynote_backend_3

  # Redis (Session 共享)
  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data

volumes:
  redis_data:
```

### 6.2 升級程序
```bash
# upgrade.sh - 系統升級腳本
#!/bin/bash

echo "⬆️ 開始系統升級..."

# 1. 備份整個系統
./scripts/full-backup.sh

# 2. 更新 Docker
curl -fsSL https://get.docker.com | sh

# 3. 更新 Docker Compose
COMPOSE_VERSION="v2.39.2"
curl -L "https://github.com/docker/compose/releases/download/$COMPOSE_VERSION/docker compose-$(uname -s)-$(uname -m)" \
  -o /usr/local/bin/docker compose
chmod +x /usr/local/bin/docker compose

# 4. 升級 PHP 版本 (如需要)
# 重新建構 Docker 映像檔包含 PHP 8.4.12

# 5. 升級前端依賴
cd frontend
npm update
npm audit fix

# 6. 升級後端依賴
cd ../backend
docker compose exec web composer update

# 7. 執行測試確保相容性
docker compose exec web ./vendor/bin/phpunit

echo "✅ 升級完成"
```

## 7. 備份與還原

### 7.1 完整備份策略
```bash
# full-backup.sh
#!/bin/bash

BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/alleynote_$BACKUP_DATE"

echo "📦 開始完整備份到 $BACKUP_DIR"

mkdir -p $BACKUP_DIR

# 1. 程式碼備份
git bundle create $BACKUP_DIR/code.bundle --all

# 2. 資料庫備份
docker compose exec db pg_dump -U ${DB_USERNAME} -d ${DB_DATABASE} \
  --clean --if-exists --no-owner --no-privileges > $BACKUP_DIR/database.sql

# 3. 上傳檔案備份
tar -czf $BACKUP_DIR/uploads.tar.gz backend/storage/uploads/

# 4. 組態檔備份
cp -r docker/ $BACKUP_DIR/
cp -r nginx/ $BACKUP_DIR/
cp backend/.env $BACKUP_DIR/backend.env
cp frontend/.env $BACKUP_DIR/frontend.env

# 5. SSL 憑證備份
cp -r /etc/letsencrypt/ $BACKUP_DIR/ssl/

# 6. 建立檢查檔
echo "Backup created at: $(date)" > $BACKUP_DIR/backup.info
echo "PHP Version: $(docker compose exec web php -v | head -1)" >> $BACKUP_DIR/backup.info
echo "Test Status: $(docker compose exec web ./vendor/bin/phpunit --testdox | grep -c 'Test')" >> $BACKUP_DIR/backup.info

echo "✅ 備份完成: $BACKUP_DIR"
```

### 7.2 還原程序
```bash
# restore.sh
#!/bin/bash

BACKUP_DIR=$1
if [ -z "$BACKUP_DIR" ]; then
    echo "使用方式: ./restore.sh /path/to/backup"
    exit 1
fi

echo "🔄 從 $BACKUP_DIR 還原系統"

# 1. 停止服務
docker compose down

# 2. 還原程式碼
git clone $BACKUP_DIR/code.bundle .

# 3. 還原組態檔
cp -r $BACKUP_DIR/docker/ .
cp -r $BACKUP_DIR/nginx/ .
cp $BACKUP_DIR/backend.env backend/.env
cp $BACKUP_DIR/frontend.env frontend/.env

# 4. 還原資料庫
docker compose up -d db
sleep 15
docker compose exec db psql -U ${DB_USERNAME} -d ${DB_DATABASE} < $BACKUP_DIR/database.sql

# 5. 還原上傳檔案
tar -xzf $BACKUP_DIR/uploads.tar.gz -C backend/storage/

# 6. 還原 SSL 憑證
cp -r $BACKUP_DIR/ssl/* /etc/letsencrypt/

# 7. 重新啟動服務
docker compose up -d

echo "✅ 還原完成"
```

---

## 📝 部署檢查清單

### 部署前檢查
- [ ] 系統需求確認 (Docker 28.3.3+, Docker Compose v2.39.2+)
- [ ] 網域名稱設定完成
- [ ] SSL 憑證準備就緒
- [ ] 環境變數檔案設定完成
- [ ] 資料庫設定確認
- [ ] 備份策略制定完成

### 部署過程檢查
- [ ] 後端容器正常啟動
- [ ] 前端容器正常啟動
- [ ] 資料庫遷移成功
- [ ] API 健康檢查通過
- [ ] 前端頁面可正常存取
- [ ] 所有測試通過 (1,372 個測試)

### 部署後檢查
- [ ] 使用者可正常登入
- [ ] 公告功能正常運作
- [ ] 檔案上傳功能正常
- [ ] HTTPS 存取正常
- [ ] 效能指標符合預期
- [ ] 監控系統設定完成

---

**維護聯絡資訊**:
- 技術支援: [your-email@domain.com]
- 緊急聯絡: [emergency-contact]
- 專案文件: `/docs/` 目錄
- 部署日誌: `/var/log/alleynote/`

**最後更新**: 2025-01-20
**文件版本**: v4.0
**適用系統**: AlleyNote 前後端分離架構
