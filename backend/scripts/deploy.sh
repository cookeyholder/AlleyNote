#!/bin/bash

# 設定錯誤時終止腳本
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "開始部署..."

# 備份資料庫
echo "備份資料庫..."
bash "$SCRIPT_DIR/backup_db.sh"

# 更新程式碼
echo "更新程式碼..."
git pull

# 安裝相依套件
echo "安裝相依套件..."
composer install --no-dev --optimize-autoloader

# 執行資料庫遷移
echo "執行資料庫遷移..."
php vendor/bin/phinx migrate

# 設定權限
echo "設定目錄權限..."
chown -R www-data:www-data storage
chmod -R 755 storage

echo "部署完成！"
