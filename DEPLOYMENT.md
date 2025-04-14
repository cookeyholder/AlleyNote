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
# 編輯 .env 檔案，設定必要的環境變數

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

### 3.3 資料庫設定
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

# 清除快取
docker-compose run --rm php php artisan cache:clear

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

# 重啟服務
docker-compose up -d
```

## 5. 監控設定

### 5.1 系統監控
```yaml
# docker-compose.yml 監控設定
services:
  prometheus:
    image: prom/prometheus
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
    ports:
      - "9090:9090"

  grafana:
    image: grafana/grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=your-secure-password
```

### 5.2 日誌管理
```yaml
# docker-compose.yml 日誌設定
services:
  filebeat:
    image: docker.elastic.co/beats/filebeat:8.0.0
    volumes:
      - ./filebeat.yml:/usr/share/filebeat/filebeat.yml
      - /var/log:/var/log:ro
      - /var/lib/docker/containers:/var/lib/docker/containers:ro
```

### 5.3 效能監控
```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'nginx'
    static_configs:
      - targets: ['nginx-exporter:9113']
  
  - job_name: 'php-fpm'
    static_configs:
      - targets: ['php-fpm-exporter:9253']
```

### 5.4 警報設定
```yaml
# alertmanager.yml
route:
  group_by: ['alertname']
  receiver: 'email-notifications'
  
receivers:
- name: 'email-notifications'
  email_configs:
  - to: 'admin@your-domain.com'
    from: 'alertmanager@your-domain.com'
    smarthost: 'smtp.your-domain.com:587'
```

## 6. 維護指南

### 6.1 定期維護工作
1. 資料庫備份 (每日)
2. 檔案系統備份 (每週)
3. 系統更新 (每月)
4. 安全性掃描 (每月)
5. SSL 憑證更新 (每 90 天)

### 6.2 效能調校
1. NGINX 工作程序數量
2. PHP-FPM 工作程序數量
3. SQLite 快取大小
4. 檔案系統快取設定
5. 網路緩衝區大小

### 6.3 故障排除
1. 檢查系統日誌
2. 檢查應用程式日誌
3. 檢查資料庫狀態
4. 檢查網路連線
5. 檢查硬碟空間

### 6.4 安全性維護
1. 定期更新系統套件
2. 檢查安全性更新
3. 掃描漏洞
4. 檢查存取日誌
5. 更新防火牆規則
