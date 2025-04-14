# AlleyNote 公布欄網站部署指南

## 1. 系統需求

### 1.1 硬體需求
- CPU: 2 核心以上
- 記憶體: 4GB 以上
- 硬碟空間: 20GB 以上

### 1.2 軟體需求
- Debian Linux 12
- PHP 8.4.5
- SQLite3
- Redis 7.0+
- NGINX
- Docker 24.0.0 以上
- Docker Compose 2.20.0 以上

### 1.3 網路需求
- 固定 IP 位址
- 支援 HTTPS (443 埠)
- 支援 HTTP (80 埠)

## 2. 安裝步驟

### 2.1 基礎環境安裝
```bash
# 更新系統套件
apt update && apt upgrade -y

# 安裝必要工具
apt install -y curl git unzip

# 安裝 Docker
curl -fsSL https://get.docker.com | sh

# 安裝 Docker Compose
curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
```

### 2.2 專案部署
```bash
# 建立專案目錄
mkdir -p /var/www/alleynote
cd /var/www/alleynote

# 複製專案檔案
git clone https://github.com/your-org/alleynote.git .

# 設定環境變數
cp .env.example .env
# 編輯 .env 檔案，設定必要的環境變數：
# - 管理員帳號密碼
# - 資料庫設定
# - Redis 連線設定
# - 檔案上傳設定
# - Telegram 通知設定

# 啟動容器
docker-compose up -d

# 執行資料庫遷移
docker-compose exec php php /var/www/html/vendor/bin/phinx migrate

# 設定目錄權限
chown -R www-data:www-data storage
chmod -R 755 storage
```

## 3. 環境設定

### 3.1 NGINX 設定
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;

    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 安全性標頭
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # 靜態檔案快取
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 7d;
        add_header Cache-Control "public, no-transform";
    }
}
```

### 3.2 PHP 設定
```ini
; php.ini 設定
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
date.timezone = Asia/Taipei
```

### 3.3 Redis 設定
```yaml
# docker-compose.yml Redis 服務設定
services:
  redis:
    image: redis:alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    command: redis-server --appendonly yes
    networks:
      - app-network
```

### 3.4 資料庫設定
```sql
-- 建立資料庫目錄
mkdir -p /var/www/alleynote/database
chmod 755 /var/www/alleynote/database

-- 設定 SQLite 權限
chown -R www-data:www-data /var/www/alleynote/database
```

## 4. 部署流程

### 4.1 自動化部署腳本
```bash
#!/bin/bash
# deploy.sh

# 停止服務
docker-compose down

# 備份資料
./scripts/backup_db.sh
./scripts/backup_files.sh

# 更新程式碼
git pull origin main

# 安裝相依套件
docker-compose run --rm composer install --no-dev --optimize-autoloader

# 更新資料庫
docker-compose run --rm php php /var/www/html/vendor/bin/phinx migrate

# 清除 Redis 快取
docker-compose exec redis redis-cli FLUSHALL

# 重啟服務
docker-compose up -d

# 檢查服務狀態
docker-compose ps
```

### 4.2 回滾程序
```bash
#!/bin/bash
# rollback.sh

# 停止服務
docker-compose down

# 還原備份
./scripts/restore_db.sh
./scripts/restore_files.sh

# 切換到上一個版本
git checkout HEAD^

# 清除 Redis 快取
docker-compose exec redis redis-cli FLUSHALL

# 重啟服務
docker-compose up -d
```

## 5. 維護指南

### 5.1 定期維護工作
1. 資料庫備份 (每日)
2. 檔案系統備份 (每週)
3. 系統更新 (每月)
4. 安全性掃描 (每月)
5. SSL 憑證更新 (每 90 天)
6. Redis 快取清理 (視需要)

### 5.2 效能調校
1. NGINX 工作程序數量
2. PHP-FPM 工作程序數量
3. SQLite 快取大小
4. Redis 記憶體設定
5. 檔案系統快取設定
6. 網路緩衝區大小

### 5.3 故障排除
1. 檢查系統日誌
2. 檢查應用程式日誌
3. 檢查資料庫狀態
4. 檢查 Redis 狀態
5. 檢查網路連線
6. 檢查硬碟空間

### 5.4 安全性維護
1. 定期更新系統套件
2. 檢查安全性更新
3. 掃描漏洞
4. 檢查存取日誌
5. 更新防火牆規則
6. 監控 Redis 連線
