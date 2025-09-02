#!/bin/bash

# 設定錯誤時終止腳本
set -e

# 檢查參數
if [ "$#" -ne 2 ]; then
    echo "錯誤：需要提供備份檔案路徑和目標目錄"
    echo "用法：$0 <備份檔案路徑> <目標目錄>"
    exit 1
fi

BACKUP_FILE="$1"
TARGET_DIR="$2"

# 檢查備份檔案是否存在
if [ ! -f "$BACKUP_FILE" ]; then
    echo "錯誤：找不到備份檔案"
    exit 1
fi

# 檢查目標目錄是否可寫入
if [ ! -w "$TARGET_DIR" ]; then
    echo "錯誤：目標目錄無寫入權限"
    exit 1
fi

echo "開始還原檔案..."

# 確保目標目錄存在
mkdir -p "$TARGET_DIR"

# 解壓縮並還原備份
if tar -xzf "$BACKUP_FILE" -C "$TARGET_DIR" 2>/dev/null; then
    echo "檔案還原完成！"
    exit 0
else
    echo "錯誤：還原失敗"
    exit 1
fi
