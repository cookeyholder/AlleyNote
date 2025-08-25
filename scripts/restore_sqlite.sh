#!/bin/bash

# SQLite 資料庫還原腳本
# 從備份檔案還原資料庫

set -e

# 檢查參數
if [ $# -lt 1 ]; then
    echo "使用方法: $0 <備份檔案路徑> [目標資料庫路徑]"
    echo ""
    echo "範例:"
    echo "  $0 /var/www/html/database/backups/alleynote_backup_20250823_120000.db.gz"
    echo "  $0 backup.db.gz /var/www/html/database/alleynote_restored.db"
    echo ""
    echo "可用的備份檔案:"
    ls -la /var/www/html/database/backups/alleynote_backup_*.db.gz 2>/dev/null || echo "  無備份檔案"
    exit 1
fi

# 設定變數
BACKUP_FILE="$1"
TARGET_DB="${2:-${DB_DATABASE:-/var/www/html/database/alleynote.sqlite3}}"
TEMP_DIR="/tmp/sqlite_restore_$$"

echo "正在還原 SQLite 資料庫..."
echo "備份檔案: $BACKUP_FILE"
echo "目標資料庫: $TARGET_DB"

# 檢查備份檔案是否存在
if [ ! -f "$BACKUP_FILE" ]; then
    echo "錯誤：備份檔案不存在: $BACKUP_FILE"
    exit 1
fi

# 建立臨時目錄
mkdir -p "$TEMP_DIR"

# 解壓縮備份檔案（如果是 .gz 格式）
if [[ "$BACKUP_FILE" == *.gz ]]; then
    echo "解壓縮備份檔案..."
    UNCOMPRESSED_FILE="$TEMP_DIR/$(basename "${BACKUP_FILE%.gz}")"
    gunzip -c "$BACKUP_FILE" > "$UNCOMPRESSED_FILE"
    SOURCE_FILE="$UNCOMPRESSED_FILE"
else
    SOURCE_FILE="$BACKUP_FILE"
fi

# 檢查目標資料庫是否存在，如果存在則備份
if [ -f "$TARGET_DB" ]; then
    CURRENT_BACKUP="$TARGET_DB.backup.$(date +%Y%m%d_%H%M%S)"
    echo "目標資料庫已存在，建立備份: $CURRENT_BACKUP"
    cp "$TARGET_DB" "$CURRENT_BACKUP"
fi

# 建立目標資料庫目錄
mkdir -p "$(dirname "$TARGET_DB")"

# 執行還原
echo "還原資料庫..."
cp "$SOURCE_FILE" "$TARGET_DB"

# 設定權限
chmod 664 "$TARGET_DB"
chown www-data:www-data "$TARGET_DB"

# 驗證還原的資料庫
echo "驗證還原的資料庫..."
sqlite3 "$TARGET_DB" "PRAGMA integrity_check;" > /dev/null

if [ $? -eq 0 ]; then
    echo "資料庫完整性檢查通過"
else
    echo "錯誤：資料庫完整性檢查失敗"
    exit 1
fi

# 顯示資料庫資訊
echo ""
echo "還原完成！"
echo "資料庫檔案: $TARGET_DB"
echo "資料庫大小: $(ls -lh "$TARGET_DB" | awk '{print $5}')"
echo ""
echo "資料庫表格:"
sqlite3 "$TARGET_DB" ".tables"

# 清理臨時檔案
rm -rf "$TEMP_DIR"

echo ""
echo "SQLite 資料庫還原完成！"
