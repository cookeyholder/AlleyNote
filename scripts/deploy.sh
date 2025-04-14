#!/bin/bash

# 設定錯誤時終止腳本
set -e

echo "開始部署程序..."

# 停止服務
echo "停止現有服務..."
docker-compose down

# 備份資料
echo "執行資料備份..."
./scripts/backup_db.sh
./scripts/backup_files.sh

# 更新程式碼
echo "更新程式碼..."
git pull origin main

# 安裝相依套件
echo "安裝相依套件..."
docker-compose run --rm php composer install --no-dev --optimize-autoloader

# 更新資料庫
echo "執行資料庫遷移..."
docker-compose run --rm php php /var/www/html/vendor/bin/phinx migrate

# 清除快取
echo "清除系統快取..."
docker-compose exec redis redis-cli FLUSHALL

# 重啟服務
echo "重新啟動服務..."
docker-compose up -d

# 檢查服務狀態
echo "檢查服務狀態..."
docker-compose ps

echo "部署完成！"
