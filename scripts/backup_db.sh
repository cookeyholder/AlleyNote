#!/bin/bash

# 設定錯誤時終止腳本
set -e

# 設定備份目錄
BACKUP_DIR="/var/www/alleynote/storage/backups/database"
DATE=$(date +%Y%m%d_%H%M%S)

# 確保備份目錄存在
mkdir -p "$BACKUP_DIR"

echo "開始資料備份..."

# 建立 SQLite 備份
echo "建立資料庫備份..."
sqlite3 /var/www/alleynote/database/alleynote.db ".backup '$BACKUP_DIR/db_$DATE.sqlite'"

# 壓縮 SQLite 備份
echo "壓縮 SQLite 備份檔案..."
gzip "$BACKUP_DIR/db_$DATE.sqlite"

# 備份 Redis 資料
echo "備份 Redis 資料..."
docker-compose exec -T redis redis-cli SAVE
docker cp $(docker-compose ps -q redis):/data/dump.rdb "$BACKUP_DIR/redis_$DATE.rdb"
gzip "$BACKUP_DIR/redis_$DATE.rdb"

# 保留最近 30 天的備份
echo "清理舊備份檔案..."
find $BACKUP_DIR -name "db_*.sqlite.gz" -mtime +30 -delete
find $BACKUP_DIR -name "redis_*.rdb.gz" -mtime +30 -delete

echo "資料庫備份完成！"
echo "SQLite 備份檔案: $BACKUP_DIR/db_$DATE.sqlite.gz"
echo "Redis 備份檔案: $BACKUP_DIR/redis_$DATE.rdb.gz"
