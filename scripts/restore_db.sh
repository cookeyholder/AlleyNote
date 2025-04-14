#!/bin/bash

# 設定錯誤時終止腳本
set -e

# 設定備份目錄
BACKUP_DIR="/var/www/alleynote/storage/backups/database"
DB_PATH="/var/www/alleynote/database/alleynote.db"

echo "開始還原資料庫..."

# 檢查是否有備份檔案
LATEST_BACKUP=$(ls -t $BACKUP_DIR/db_*.sqlite.gz 2>/dev/null | head -n1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "錯誤：找不到備份檔案"
    exit 1
fi

echo "使用最新的備份檔案: $LATEST_BACKUP"

# 停止應用程式服務
echo "停止應用程式服務..."
docker-compose down

# 備份當前資料庫（以防還原失敗）
echo "備份當前資料庫..."
TEMP_BACKUP="$DB_PATH.before_restore"
if [ -f "$DB_PATH" ]; then
    cp "$DB_PATH" "$TEMP_BACKUP"
fi

# 解壓縮並還原備份
echo "還原資料庫..."
gunzip -c "$LATEST_BACKUP" > "$DB_PATH"

# 設定適當的權限
echo "設定檔案權限..."
chown www-data:www-data "$DB_PATH"
chmod 644 "$DB_PATH"

echo "資料庫還原完成！"

# 如果需要，可以刪除臨時備份
# rm -f "$TEMP_BACKUP"
