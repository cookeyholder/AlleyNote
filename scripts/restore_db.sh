#!/bin/bash

# 設定錯誤時終止腳本
set -e

# 設定備份目錄
BACKUP_DIR="/var/www/alleynote/storage/backups/database"
DB_PATH="/var/www/alleynote/database/alleynote.db"

echo "開始還原資料..."

# 檢查是否有備份檔案
LATEST_DB_BACKUP=$(ls -t $BACKUP_DIR/db_*.sqlite.gz 2>/dev/null | head -n1)
LATEST_REDIS_BACKUP=$(ls -t $BACKUP_DIR/redis_*.rdb.gz 2>/dev/null | head -n1)

if [ -z "$LATEST_DB_BACKUP" ] || [ -z "$LATEST_REDIS_BACKUP" ]; then
    echo "錯誤：找不到完整的備份檔案"
    exit 1
fi

echo "使用最新的備份檔案:"
echo "SQLite: $LATEST_DB_BACKUP"
echo "Redis: $LATEST_REDIS_BACKUP"

# 停止應用程式服務
echo "停止應用程式服務..."
docker-compose down

# 備份當前資料庫（以防還原失敗）
echo "備份當前資料庫..."
TEMP_BACKUP="$DB_PATH.before_restore"
if [ -f "$DB_PATH" ]; then
    cp "$DB_PATH" "$TEMP_BACKUP"
fi

# 解壓縮並還原 SQLite 備份
echo "還原 SQLite 資料庫..."
gunzip -c "$LATEST_DB_BACKUP" > "$DB_PATH"

# 設定 SQLite 檔案權限
echo "設定 SQLite 檔案權限..."
chown www-data:www-data "$DB_PATH"
chmod 644 "$DB_PATH"

# 解壓縮並還原 Redis 備份
echo "還原 Redis 資料..."
gunzip -c "$LATEST_REDIS_BACKUP" > "/tmp/dump.rdb"

# 啟動 Redis 容器
echo "啟動 Redis 服務..."
docker-compose up -d redis

# 等待 Redis 啟動
echo "等待 Redis 服務啟動..."
sleep 5

# 複製 Redis 備份檔案到容器
echo "複製 Redis 備份到容器..."
docker cp "/tmp/dump.rdb" $(docker-compose ps -q redis):/data/dump.rdb
docker-compose exec -T redis redis-cli SHUTDOWN SAVE
docker-compose start redis

# 清理臨時檔案
rm -f "/tmp/dump.rdb"

echo "資料還原完成！"

# 如果需要，可以刪除臨時備份
# rm -f "$TEMP_BACKUP"
