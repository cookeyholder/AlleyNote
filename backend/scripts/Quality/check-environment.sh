#!/bin/bash

# AlleyNote 環境配置檢查腳本
# 在應用程式啟動前驗證環境配置

set -e

echo "====================================="
echo "AlleyNote 環境配置檢查腳本"
echo "====================================="

# 檢查 PHP 是否存在
if ! command -v php &> /dev/null; then
    echo "❌ 錯誤：找不到 PHP"
    exit 1
fi

echo "✅ PHP 版本：$(php -v | head -n1)"

# 檢查 Composer 依賴
if [ ! -f "vendor/autoload.php" ]; then
    echo "❌ 錯誤：找不到 Composer autoload 檔案，請執行 'composer install'"
    exit 1
fi

echo "✅ Composer 依賴已安裝"

# 檢查環境變數和環境配置檔案
ENVIRONMENT=${APP_ENV:-development}
echo "🔍 檢查環境：$ENVIRONMENT"

# 使用 validate-config.php 腳本檢查配置
echo "📋 執行配置驗證..."
php scripts/validate-config.php "$ENVIRONMENT"
VALIDATION_RESULT=$?

if [ $VALIDATION_RESULT -ne 0 ]; then
    echo "❌ 環境配置驗證失敗，請檢查配置檔案和環境變數"
    exit 1
fi

echo "====================================="
echo "✅ 環境配置驗證通過！"
echo "====================================="
