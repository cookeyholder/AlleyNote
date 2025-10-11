#!/bin/bash

# E2E 測試環境安裝腳本

set -e

echo "🎭 AlleyNote E2E 測試環境安裝"
echo "================================"
echo ""

# 檢查 Node.js
if ! command -v node &> /dev/null; then
    echo "❌ 錯誤：未安裝 Node.js"
    echo "請先安裝 Node.js 16.x 或更高版本"
    echo "下載：https://nodejs.org/"
    exit 1
fi

NODE_VERSION=$(node -v)
echo "✅ Node.js 版本：$NODE_VERSION"

# 檢查 npm
if ! command -v npm &> /dev/null; then
    echo "❌ 錯誤：未安裝 npm"
    exit 1
fi

NPM_VERSION=$(npm -v)
echo "✅ npm 版本：$NPM_VERSION"
echo ""

# 安裝依賴
echo "📦 安裝 npm 依賴..."
npm install

if [ $? -eq 0 ]; then
    echo "✅ 依賴安裝完成"
else
    echo "❌ 依賴安裝失敗"
    exit 1
fi

echo ""

# 安裝 Playwright 瀏覽器
echo "🌐 安裝 Playwright 瀏覽器..."
npx playwright install chromium

if [ $? -eq 0 ]; then
    echo "✅ Playwright 瀏覽器安裝完成"
else
    echo "❌ Playwright 瀏覽器安裝失敗"
    exit 1
fi

echo ""
echo "================================"
echo "✅ 安裝完成！"
echo ""
echo "下一步："
echo "1. 啟動應用程式："
echo "   cd ../.. && docker compose up -d"
echo ""
echo "2. 執行測試："
echo "   npm test"
echo ""
echo "或使用執行腳本："
echo "   ./run-tests.sh"
echo ""
echo "查看更多資訊："
echo "   cat README.md"
echo "   cat QUICK_START.md"
