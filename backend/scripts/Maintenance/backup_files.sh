#!/bin/bash

# 設定錯誤時終止腳本
set -e

# 檢查參數
if [ "$#" -ne 2 ]; then
    echo "錯誤：需要提供來源目錄和目標目錄"
    echo "用法：$0 <來源目錄> <目標目錄>"
    exit 1
fi

SOURCE_DIR="$1"
BACKUP_DIR="$2"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/files_$DATE.tar.gz"

# 確保來源目錄存在
if [ ! -d "$SOURCE_DIR" ]; then
    echo "錯誤：來源目錄不存在"
    exit 1
fi

# 確保備份目錄存在
mkdir -p "$BACKUP_DIR"

echo "開始檔案備份..."

# 建立備份
if tar -czf "$BACKUP_FILE" -C "$(dirname "$SOURCE_DIR")" "$(basename "$SOURCE_DIR")" 2>/dev/null; then
    echo "檔案備份完成！"
    echo "備份檔案: $BACKUP_FILE"
    exit 0
else
    echo "錯誤：備份失敗"
    exit 1
fi
