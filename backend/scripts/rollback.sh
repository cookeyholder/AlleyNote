#!/bin/bash

# 設定錯誤時終止腳本
set -e

echo "開始回滾程序..."

# 停止服務
echo "停止現有服務..."
docker-compose down

# 還原備份
echo "還原資料備份..."
./scripts/restore_db.sh
./scripts/restore_files.sh

# 切換到上一個版本
echo "切換到上一個版本..."
git checkout HEAD^

# 安裝相依套件
echo "安裝相依套件..."
docker-compose run --rm php composer install --no-dev --optimize-autoloader

# 重啟服務
echo "重新啟動服務..."
docker-compose up -d

# 檢查服務狀態
echo "檢查服務狀態..."
docker-compose ps

echo "回滾完成！"
