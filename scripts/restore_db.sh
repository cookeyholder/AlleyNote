#!/bin/bash

set -e

BACKUP_DIR="/var/www/alleynote/storage/backups"

# 找出最新的備份檔案
LATEST_DB_BACKUP=$(ls -t $BACKUP_DIR/database_*.sqlite.gz 2>/dev/null | head -n1)

if [ -z "$LATEST_DB_BACKUP" ]; then
    echo "找不到備份檔案"
    exit 1
fi

echo "正在還原最新的備份檔案:"
echo "資料庫: $LATEST_DB_BACKUP"

# 確認是否繼續
read -p "是否繼續還原? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 0
fi

# 解壓縮並還原 SQLite 備份
echo "還原 SQLite 資料庫..."
gunzip -c "$LATEST_DB_BACKUP" >"/var/www/html/database/database.sqlite"

echo "還原完成!"
