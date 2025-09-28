#!/bin/bash

# AlleyNote Docker 容器啟動腳本
# 在容器啟動時執行環境配置檢查和初始化

set -e

echo "====================================="
echo "AlleyNote Docker 容器啟動腳本"
echo "====================================="

# 檢查工作目錄
if [ ! -f "composer.json" ]; then
    echo "❌ 錯誤：不在正確的專案目錄中"
    exit 1
fi

# 設定檔案權限
echo "🔧 設定檔案權限..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/database /var/www/html/logs 2>/dev/null || true
chmod -R 755 /var/www/html/storage /var/www/html/database /var/www/html/logs 2>/dev/null || true

# 執行環境配置檢查
echo "🔍 執行環境配置檢查..."
bash scripts/check-environment.sh

# 如果是生產環境，執行額外檢查
if [ "${APP_ENV}" = "production" ]; then
    echo "🏭 生產環境額外檢查..."

    # 檢查敏感檔案權限
    if [ -f ".env.production" ]; then
        chmod 600 .env.production
        echo "✅ 生產環境配置檔案權限已設定"
    fi

    # 檢查日誌目錄
    mkdir -p logs
    chmod 755 logs
    echo "✅ 日誌目錄已準備"
fi

echo "====================================="
echo "✅ 容器啟動檢查完成！"
echo "====================================="

# 根據不同環境執行不同的啟動命令
if [ "$1" = "web" ]; then
    echo "🚀 啟動 Web 伺服器..."
    exec php-fpm
elif [ "$1" = "nginx" ]; then
    echo "🚀 啟動 Nginx..."
    exec nginx -g "daemon off;"
else
    echo "🚀 執行自訂命令：$*"
    exec "$@"
fi
