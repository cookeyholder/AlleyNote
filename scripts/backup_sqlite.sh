#!/bin/bash

# SQLite 資料庫備份腳本
# 建立完整的資料庫備份

set -e

# 設定變數
DB_FILE="${DB_DATABASE:-/var/www/html/database/alleynote.db}"
BACKUP_DIR="/var/www/html/database/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/alleynote_backup_$TIMESTAMP.db"

echo "正在備份 SQLite 資料庫..."

# 建立備份目錄
mkdir -p "$BACKUP_DIR"

# 檢查資料庫檔案是否存在
if [ ! -f "$DB_FILE" ]; then
    echo "錯誤：資料庫檔案不存在: $DB_FILE"
    exit 1
fi

# 執行備份
echo "從 $DB_FILE 備份到 $BACKUP_FILE"
sqlite3 "$DB_FILE" ".backup '$BACKUP_FILE'"

# 壓縮備份檔案
echo "壓縮備份檔案..."
gzip "$BACKUP_FILE"
COMPRESSED_FILE="${BACKUP_FILE}.gz"

# 設定權限
chmod 644 "$COMPRESSED_FILE"
chown www-data:www-data "$COMPRESSED_FILE"

# 顯示備份資訊
echo "備份完成！"
echo "備份檔案: $COMPRESSED_FILE"
echo "備份大小: $(ls -lh "$COMPRESSED_FILE" | awk '{print $5}')"

# 清理舊備份（保留最近 7 天的備份）
echo "清理超過 7 天的舊備份..."
find "$BACKUP_DIR" -name "alleynote_backup_*.db.gz" -mtime +7 -delete

# 顯示現有備份列表
echo ""
echo "現有備份檔案:"
ls -lah "$BACKUP_DIR"/alleynote_backup_*.db.gz 2>/dev/null || echo "無備份檔案"

echo "SQLite 備份腳本執行完成！"
