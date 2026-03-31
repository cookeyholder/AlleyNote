#!/bin/bash
# AlleyNote 資料庫備份指令稿
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-./storage/backups}"
DB_PATH="${DB_PATH:-./storage/database.sqlite}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/backup_${TIMESTAMP}.sqlite"

mkdir -p "${BACKUP_DIR}"

if [ ! -f "${DB_PATH}" ]; then
    echo "錯誤：找不到資料庫檔案 ${DB_PATH}"
    exit 1
fi

cp "${DB_PATH}" "${BACKUP_FILE}"
echo "備份完成：${BACKUP_FILE}"

# 保留最近 30 天的備份
find "${BACKUP_DIR}" -name "backup_*.sqlite" -mtime +30 -delete
echo "已清理 30 天前的舊備份"
