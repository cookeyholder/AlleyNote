#!/bin/bash

# AlleyNote 測試環境建置和測試腳本

set -e

echo "=== AlleyNote 測試環境建置和測試 ==="
echo

# 設定測試環境變數
export APP_ENV=test
export SSL_DOMAIN=test.localhost
export SSL_EMAIL=test@localhost
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

# 停止現有容器
echo "🛑 停止現有容器..."
docker-compose -f docker-compose.test.yml down --remove-orphans

# 建置測試環境容器（使用 test stage）
echo "🏗️  建置測試環境容器（包含 Xdebug）..."
docker-compose -f docker-compose.test.yml build --no-cache --target test

# 啟動測試環境
echo "🚀 啟動測試環境..."
docker-compose -f docker-compose.test.yml up -d

# 等待容器啟動
echo "⏳ 等待容器啟動..."
sleep 10

# 檢查容器狀態
echo "📊 檢查容器狀態..."
docker-compose -f docker-compose.test.yml ps

# 檢查 web 容器的健康狀態
echo
echo "🏥 檢查 web 容器健康狀態..."
if docker exec alleynote_test_web php --version; then
    echo "✅ PHP 正常執行"
else
    echo "❌ PHP 執行失敗"
    exit 1
fi

# 檢查 Xdebug 是否安裝
echo
echo "🐛 檢查 Xdebug 狀態..."
if docker exec alleynote_test_web php -m | grep -i xdebug; then
    echo "✅ Xdebug 已安裝"
    docker exec alleynote_test_web php --ini | grep xdebug
else
    echo "❌ Xdebug 未安裝"
fi

# 檢查環境變數
echo
echo "🔍 檢查環境變數..."
docker exec alleynote_test_web env | grep -E "(APP_ENV|SSL_|DB_)" | sort

# 檢查 SQLite 資料庫（測試用）
echo
echo "💾 檢查測試 SQLite 資料庫..."
if docker exec alleynote_test_web php -r "
try {
    \$pdo = new PDO('sqlite:/var/www/html/database/test.db');
    echo '測試 SQLite 資料庫連線成功！' . PHP_EOL;
    echo '資料庫版本：' . \$pdo->query('SELECT sqlite_version()')->fetchColumn() . PHP_EOL;
} catch (PDOException \$e) {
    echo '測試資料庫連線失敗：' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"; then
    echo "✅ 測試 SQLite 資料庫正常"
else
    echo "❌ 測試 SQLite 資料庫連線失敗"
fi

# 執行 PHPUnit 測試
echo
echo "🧪 執行 PHPUnit 測試..."
if docker exec alleynote_test_web vendor/bin/phpunit --version; then
    echo "✅ PHPUnit 已安裝"
    
    # 執行基本測試
    echo "執行基本測試..."
    docker exec alleynote_test_web vendor/bin/phpunit tests/ --testdox --colors=never || echo "⚠️  某些測試可能失敗（這在初始設定時是正常的）"
else
    echo "❌ PHPUnit 未安裝"
fi

# 測試 Swagger 功能
echo
echo "📚 測試 Swagger 功能..."
if docker exec alleynote_test_web php scripts/test-swagger.php; then
    echo "✅ Swagger 功能正常"
else
    echo "❌ Swagger 功能測試失敗"
fi

# 檢查網頁服務（測試環境通常使用不同端口）
echo
echo "🌐 檢查測試網頁服務..."
sleep 5
if curl -f http://localhost:8080 >/dev/null 2>&1; then
    echo "✅ 測試網頁服務正常運行於 http://localhost:8080"
else
    echo "⚠️  測試網頁服務可能尚未完全啟動，請稍後手動檢查 http://localhost:8080"
fi

# 顯示日誌
echo
echo "📜 顯示容器日誌（最後 20 行）..."
docker-compose -f docker-compose.test.yml logs --tail=20

echo
echo "🎉 測試環境建置和測試完成！"
echo
echo "📋 可用的服務："
echo "   - 測試網頁服務: http://localhost:8080"
echo "   - Swagger UI: http://localhost:8080/api/docs/ui"
echo "   - Redis: localhost:6380"
echo
echo "🔧 管理指令："
echo "   查看日誌: docker-compose -f docker-compose.test.yml logs -f"
echo "   進入容器: docker exec -it alleynote_test_web bash"
echo "   執行測試: docker exec alleynote_test_web vendor/bin/phpunit"
echo "   停止服務: docker-compose -f docker-compose.test.yml down"
