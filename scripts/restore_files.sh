#!/bin/bash

# 設定錯誤時終止腳本
set -e

# 設定備份目錄
BACKUP_DIR="/var/www/alleynote/storage/backups/files"
STORAGE_DIR="/var/www/alleynote/storage/app"
PUBLIC_DIR="/var/www/alleynote/storage/app/public"

echo "開始還原檔案..."

# 檢查是否有備份檔案
LATEST_BACKUP=$(ls -t $BACKUP_DIR/files_*.tar.gz 2>/dev/null | head -n1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "錯誤：找不到備份檔案"
    exit 1
fi

echo "使用最新的備份檔案: $LATEST_BACKUP"

# 停止應用程式服務
echo "停止應用程式服務..."
docker-compose down

# 備份當前檔案（以防還原失敗）
echo "備份當前檔案..."
TEMP_BACKUP_DIR="/tmp/files_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$TEMP_BACKUP_DIR"
if [ -d "$STORAGE_DIR" ]; then
    cp -r "$STORAGE_DIR" "$TEMP_BACKUP_DIR/"
fi

# 清空目標目錄
echo "清空目標目錄..."
rm -rf "$STORAGE_DIR"
mkdir -p "$STORAGE_DIR" "$PUBLIC_DIR"

# 解壓縮並還原備份
echo "還原檔案..."
tar -xzf "$LATEST_BACKUP" -C /var/www/alleynote/storage

# 設定適當的權限
echo "設定檔案權限..."
chown -R www-data:www-data "$STORAGE_DIR"
chmod -R 755 "$STORAGE_DIR"

echo "檔案還原完成！"

# 如果需要，可以刪除臨時備份
# rm -rf "$TEMP_BACKUP_DIR"
