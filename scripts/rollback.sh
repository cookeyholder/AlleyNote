#!/bin/bash

# 設定錯誤時終止腳本
set -e

echo "開始回滾程序..."

# 停止服務
echo "停止現有服務..."
if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
	COMPOSE_CMD="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
	COMPOSE_CMD="docker-compose"
else
	echo "錯誤: 需要安裝 Docker Compose (docker compose 或 docker-compose)"
	exit 1
fi

$COMPOSE_CMD down

# 還原備份
echo "還原資料備份..."
./scripts/restore_db.sh
./scripts/restore_files.sh

# 切換到上一個版本
echo "切換到上一個版本..."
git checkout HEAD^

# 安裝相依套件
echo "安裝相依套件..."
${COMPOSE_CMD} run --rm php composer install --no-dev --optimize-autoloader

# 重啟服務
echo "重新啟動服務..."
${COMPOSE_CMD} up -d

# 檢查服務狀態
echo "檢查服務狀態..."
${COMPOSE_CMD} ps

echo "回滾完成！"
