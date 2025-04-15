#!/bin/bash

set -e

# 檢查參數
if [ "$#" -ne 2 ]; then
    echo "錯誤：需要提供來源資料庫路徑和目標備份路徑"
    echo "用法：$0 <來源資料庫路徑> <目標備份路徑>"
    exit 1
fi

SOURCE_DB="$1"
BACKUP_FILE="$2"

# 檢查來源資料庫是否存在
if [ ! -f "$SOURCE_DB" ]; then
    echo "錯誤：找不到來源資料庫檔案 $SOURCE_DB"
    exit 1
fi

# 建立備份目錄
BACKUP_DIR=$(dirname "$BACKUP_FILE")
mkdir -p "$BACKUP_DIR"

# 備份 SQLite 資料庫
echo "備份 SQLite 資料庫..."
cp "$SOURCE_DB" "$BACKUP_FILE"

# 顯示備份結果
if [ $? -eq 0 ]; then
    echo "備份完成!"
    echo "資料庫備份檔案: $BACKUP_FILE"
    exit 0
else
    echo "錯誤：備份失敗"
    exit 1
fi
