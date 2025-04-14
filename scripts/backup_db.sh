#!/bin/bash

set -e

# 設定變數
DATE=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="/var/www/alleynote/storage/backups"

# 建立備份目錄
mkdir -p "$BACKUP_DIR"

# 備份 SQLite 資料庫
echo "備份 SQLite 資料庫..."
cp /var/www/html/database/database.sqlite "$BACKUP_DIR/database_$DATE.sqlite"
gzip "$BACKUP_DIR/database_$DATE.sqlite"

# 刪除超過 30 天的備份
echo "清理舊備份檔案..."
find $BACKUP_DIR -name "database_*.sqlite.gz" -mtime +30 -delete

# 顯示備份結果
echo "備份完成!"
echo "資料庫備份檔案: $BACKUP_DIR/database_$DATE.sqlite.gz"
