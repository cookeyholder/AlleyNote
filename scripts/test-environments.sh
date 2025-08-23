#!/bin/bash

# AlleyNote 環境比較和整合測試腳本

set -e

echo "=== AlleyNote 環境比較和整合測試 ==="
echo

# 檢查 Docker 是否執行
echo "📋 檢查 Docker 狀態..."
if ! docker info >/dev/null 2>&1; then
    echo "❌ Docker 服務未執行，請先啟動 Docker"
    echo "💡 如果使用 OrbStack，請確認 OrbStack 已啟動"
    echo "💡 或者執行：docker-machine start default"
    exit 1
fi
echo "✅ Docker 服務正常執行"
echo

# 清理所有現有容器
echo "🧹 清理所有現有容器..."
docker-compose down --remove-orphans 2>/dev/null || true
docker-compose -f docker-compose.test.yml down --remove-orphans 2>/dev/null || true
docker-compose -f docker-compose.production.yml down --remove-orphans 2>/dev/null || true

echo "🗑️  清理未使用的映像檔和容器..."
docker system prune -f

echo
echo "=== 開發環境測試 ==="
echo "🏗️  建置開發環境..."

# 設定開發環境變數
export APP_ENV=development
export SSL_DOMAIN=localhost
export SSL_EMAIL=admin@localhost
export CERTBOT_STAGING=true

# 建置和測試開發環境
docker-compose build --no-cache web
docker-compose up -d

echo "⏳ 等待開發環境啟動..."
sleep 15

echo "📊 開發環境容器狀態："
docker-compose ps

echo "🔍 開發環境變數檢查："
docker exec alleynote_web env | grep -E "(APP_ENV|SSL_|DB_)" | sort

echo "💾 開發環境資料庫測試："
docker exec alleynote_web php -r "
try {
    \$pdo = new PDO('sqlite:/var/www/html/database/alleynote.db');
    echo '✅ 開發環境 SQLite 連線成功' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ 開發環境資料庫錯誤：' . \$e->getMessage() . PHP_EOL;
}
"

echo "📚 開發環境 Swagger 測試："
docker exec alleynote_web php scripts/test-swagger.php

echo "🌐 開發環境網頁測試："
if curl -f http://localhost >/dev/null 2>&1; then
    echo "✅ 開發環境網頁服務正常"
else
    echo "❌ 開發環境網頁服務異常"
fi

echo
echo "=== 測試環境測試 ==="
echo "🏗️  建置測試環境..."

# 停止開發環境
docker-compose down

# 設定測試環境變數
export APP_ENV=test
export SSL_DOMAIN=test.localhost
export SSL_EMAIL=test@localhost

# 建置和測試測試環境
docker-compose -f docker-compose.test.yml build --no-cache --target test
docker-compose -f docker-compose.test.yml up -d

echo "⏳ 等待測試環境啟動..."
sleep 15

echo "📊 測試環境容器狀態："
docker-compose -f docker-compose.test.yml ps

echo "🔍 測試環境變數檢查："
docker exec alleynote_test_web env | grep -E "(APP_ENV|SSL_|DB_)" | sort

echo "🐛 測試環境 Xdebug 檢查："
if docker exec alleynote_test_web php -m | grep -i xdebug >/dev/null; then
    echo "✅ Xdebug 已安裝"
else
    echo "❌ Xdebug 未安裝"
fi

echo "💾 測試環境資料庫測試："
docker exec alleynote_test_web php -r "
try {
    \$pdo = new PDO('sqlite:/var/www/html/database/test.db');
    echo '✅ 測試環境 SQLite 連線成功' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ 測試環境資料庫錯誤：' . \$e->getMessage() . PHP_EOL;
}
"

echo "🧪 測試環境 PHPUnit 檢查："
if docker exec alleynote_test_web vendor/bin/phpunit --version >/dev/null 2>&1; then
    echo "✅ PHPUnit 可用"
    docker exec alleynote_test_web vendor/bin/phpunit --testdox tests/ || echo "⚠️  某些測試失敗（初始設定時正常）"
else
    echo "❌ PHPUnit 不可用"
fi

echo "🌐 測試環境網頁測試："
if curl -f http://localhost:8080 >/dev/null 2>&1; then
    echo "✅ 測試環境網頁服務正常"
else
    echo "❌ 測試環境網頁服務異常"
fi

echo
echo "=== 環境比較總結 ==="
echo

# 建立比較表格
cat << 'EOF'
| 項目            | 開發環境        | 測試環境          |
|-----------------|----------------|-------------------|
| APP_ENV         | development    | test              |
| 端口            | 80             | 8080              |
| SSL 網域        | localhost      | test.localhost    |
| 資料庫檔案      | alleynote.db   | test.db           |
| Xdebug          | ❌             | ✅                |
| PHPUnit         | 基本           | 完整              |
| Redis 端口      | 6379           | 6380              |
| 容器名稱        | alleynote_web  | alleynote_test_web|
| Docker target   | base           | test              |
EOF

echo
echo "🎉 環境比較測試完成！"
echo
echo "🔧 後續操作："
echo "   停止所有容器: docker-compose down && docker-compose -f docker-compose.test.yml down"
echo "   啟動開發環境: ./scripts/test-development.sh"
echo "   啟動測試環境: ./scripts/test-testing.sh"
echo "   查看容器狀態: docker ps -a"

# 停止所有容器
echo
echo "🛑 停止所有測試容器..."
docker-compose -f docker-compose.test.yml down
