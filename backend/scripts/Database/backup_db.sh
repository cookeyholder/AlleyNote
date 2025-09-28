#!/bin/bash

# 資料庫備份腳本
# 用途：建立 SQLite 資料庫的備份

set -e

# 預設設定
DB_PATH="${DB_PATH:-./database/alleynote.sqlite3}"
BACKUP_DIR="${BACKUP_DIR:-./database/backups}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_$TIMESTAMP.db"

# 檢查資料庫檔案是否存在
if [ ! -f "$DB_PATH" ]; then
    echo "錯誤: 資料庫檔案不存在: $DB_PATH" >&2
    exit 1
fi

# 建立備份目錄
mkdir -p "$BACKUP_DIR"

# 執行備份
echo "正在備份資料庫: $DB_PATH -> $BACKUP_FILE"
cp "$DB_PATH" "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "資料庫備份完成: $BACKUP_FILE"
    
    # 清理舊的備份檔案 (保留最新10個)
    cd "$BACKUP_DIR" && ls -t backup_*.db | tail -n +11 | xargs -r rm --
    
    echo "備份檔案清理完成"
else
    echo "錯誤: 資料庫備份失敗" >&2
    exit 1
fi