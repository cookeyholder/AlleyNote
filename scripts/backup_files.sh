#!/bin/bash

# 設定錯誤時終止腳本
set -e

# 設定備份目錄
BACKUP_DIR="/var/www/alleynote/storage/backups/files"
DATE=$(date +%Y%m%d_%H%M%S)

# 確保備份目錄存在
mkdir -p "$BACKUP_DIR"

echo "開始檔案備份..."

# 要備份的目錄
STORAGE_DIR="/var/www/alleynote/storage/app"
PUBLIC_DIR="/var/www/alleynote/storage/app/public"

# 確保來源目錄存在
if [ ! -d "$STORAGE_DIR" ] || [ ! -d "$PUBLIC_DIR" ]; then
    echo "錯誤：來源目錄不存在"
    exit 1
fi

# 建立備份
echo "建立檔案備份..."
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" \
    -C "$(dirname "$STORAGE_DIR")" "$(basename "$STORAGE_DIR")"

# 保留最近 30 天的備份
echo "清理舊備份檔案..."
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +30 -delete

echo "檔案備份完成！"
echo "備份檔案: $BACKUP_DIR/files_$DATE.tar.gz"
