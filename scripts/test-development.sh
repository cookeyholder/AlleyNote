#!/bin/bash

# AlleyNote 開發環境建置和測試腳本

set -e

echo "=== AlleyNote 開發環境建置和測試 ==="
echo

# 設定開發環境變數
export APP_ENV=development
export SSL_DOMAIN=localhost
export SSL_EMAIL=admin@localhost
export CERTBOT_STAGING=true

echo "🔧 環境變數設定："
echo "   APP_ENV: $APP_ENV"
echo "   SSL_DOMAIN: $SSL_DOMAIN"
echo "   SSL_EMAIL: $SSL_EMAIL"
echo "   CERTBOT_STAGING: $CERTBOT_STAGING"
echo

# 檢查 Docker 是否執行
echo "📋 檢查 Docker 狀態..."
if ! docker info >/dev/null 2>&1; then
    echo "❌ Docker 服務未執行，請先啟動 Docker"
    exit 1
fi
echo "✅ Docker 服務正常執行"
echo

# Detect compose command (prefer "docker compose" if available)
if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    COMPOSE_CMD="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
    COMPOSE_CMD="docker-compose"
else
    echo "錯誤: 需要安裝 Docker Compose (docker compose 或 docker-compose)"
    exit 1
fi

# 停止現有容器
echo "🛑 停止現有容器..."
${COMPOSE_CMD} down --remove-orphans

# 清理舊的映像檔（可選）
echo "🧹 清理舊的映像檔..."
docker system prune -f

# 建置開發環境容器
echo "🏗️  建置開發環境容器..."
${COMPOSE_CMD} build --no-cache web

# 啟動開發環境
echo "🚀 啟動開發環境..."
${COMPOSE_CMD} up -d

# 等待容器啟動
echo "⏳ 等待容器啟動..."
sleep 10

# 檢查容器狀態
echo "📊 檢查容器狀態..."
${COMPOSE_CMD} ps

# 檢查 web 容器的健康狀態
echo
echo "🏥 檢查 web 容器健康狀態..."
if docker exec alleynote_web php --version; then
    echo "✅ PHP 正常執行"
else
    echo "❌ PHP 執行失敗"
    exit 1
fi

# 檢查環境變數是否正確傳遞
echo
echo "🔍 檢查環境變數..."
docker exec alleynote_web env | grep -E "(APP_ENV|SSL_|DB_)" | sort

# 檢查 SQLite 資料庫
echo
echo "💾 檢查 SQLite 資料庫..."
if docker exec alleynote_web php -r "
try {
    \$pdo = new PDO('sqlite:/var/www/html/database/alleynote.db');
    echo 'SQLite 資料庫連線成功！' . PHP_EOL;
    echo '資料庫版本：' . \$pdo->query('SELECT sqlite_version()')->fetchColumn() . PHP_EOL;
} catch (PDOException \$e) {
    echo '資料庫連線失敗：' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"; then
    echo "✅ SQLite 資料庫正常"
else
    echo "❌ SQLite 資料庫連線失敗"
    exit 1
fi

# 檢查 Composer 套件
echo
echo "📦 檢查 Composer 套件..."
docker exec alleynote_web composer --version
docker exec alleynote_web php -m | grep -E "(sqlite|pdo)" | head -5

# 測試 Swagger UI 功能
echo
echo "📚 測試 Swagger 功能..."
if docker exec alleynote_web php scripts/test-swagger.php; then
    echo "✅ Swagger 功能正常"
else
    echo "❌ Swagger 功能測試失敗"
fi

# 檢查網頁服務
echo
echo "🌐 檢查網頁服務..."
sleep 5
if curl -f http://localhost >/dev/null 2>&1; then
    echo "✅ 網頁服務正常運行於 http://localhost"
else
    echo "⚠️  網頁服務可能尚未完全啟動，請稍後手動檢查 http://localhost"
fi

# 顯示日誌
echo
echo "📜 顯示容器日誌（最後 20 行）..."
${COMPOSE_CMD} logs --tail=20

echo
echo "🎉 開發環境建置和測試完成！"
echo
echo "📋 可用的服務："
echo "   - 網頁服務: http://localhost"
echo "   - Swagger UI: http://localhost/api/docs/ui"
echo "   - Redis: localhost:6379"
echo
echo "🔧 管理指令："
echo "   查看日誌: docker compose logs -f"
echo "   進入容器: docker exec -it alleynote_web bash"
echo "   停止服務: docker compose down"
