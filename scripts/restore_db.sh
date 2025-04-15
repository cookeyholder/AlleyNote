#!/bin/bash

set -e

# 檢查參數
if [ "$#" -ne 2 ]; then
    echo "錯誤：需要提供備份檔案路徑和目標資料庫路徑"
    echo "用法：$0 <備份檔案路徑> <目標資料庫路徑>"
    exit 1
fi

BACKUP_FILE="$1"
TARGET_DB="$2"

# 檢查備份檔案是否存在
if [ ! -f "$BACKUP_FILE" ]; then
    echo "錯誤：找不到備份檔案 $BACKUP_FILE"
    exit 1
fi

# 建立目標目錄（如果不存在）
TARGET_DIR=$(dirname "$TARGET_DB")
mkdir -p "$TARGET_DIR"

# 還原 SQLite 備份
echo "還原 SQLite 資料庫..."
cp "$BACKUP_FILE" "$TARGET_DB"

# 顯示還原結果
if [ $? -eq 0 ]; then
    echo "還原完成!"
    exit 0
else
    echo "錯誤：還原失敗"
    exit 1
fi
