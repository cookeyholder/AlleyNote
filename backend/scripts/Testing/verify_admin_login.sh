#!/bin/bash

# 驗證管理員登入和角色資訊腳本

echo "=========================================="
echo "驗證管理員登入和角色資訊"
echo "=========================================="
echo ""

# 設定顏色
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. 測試登入 API
echo "步驟 1: 測試登入 API..."
echo "----------------------------------------"

LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"admin123"}')

if echo "$LOGIN_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
    echo -e "${GREEN}✅ 登入成功${NC}"
    
    # 檢查是否有 roles 欄位
    if echo "$LOGIN_RESPONSE" | jq -e '.user.roles' > /dev/null 2>&1; then
        echo -e "${GREEN}✅ API 返回角色資訊${NC}"
        
        # 顯示角色資訊
        echo ""
        echo "使用者角色："
        echo "$LOGIN_RESPONSE" | jq '.user.roles[]'
        
        # 檢查是否為管理員
        ROLE_ID=$(echo "$LOGIN_RESPONSE" | jq -r '.user.roles[0].id')
        ROLE_NAME=$(echo "$LOGIN_RESPONSE" | jq -r '.user.roles[0].name')
        
        if [ "$ROLE_ID" = "1" ] || [ "$ROLE_NAME" = "super_admin" ]; then
            echo ""
            echo -e "${GREEN}✅ 使用者是管理員（ID=$ROLE_ID, NAME=$ROLE_NAME）${NC}"
            echo -e "${GREEN}✅ 側欄應該顯示使用者管理選項${NC}"
        else
            echo ""
            echo -e "${RED}❌ 使用者不是管理員${NC}"
        fi
    else
        echo -e "${RED}❌ API 沒有返回角色資訊${NC}"
        echo ""
        echo "API 回應："
        echo "$LOGIN_RESPONSE" | jq '.'
        exit 1
    fi
    
    # 取得 access token
    ACCESS_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.access_token')
    
else
    echo -e "${RED}❌ 登入失敗${NC}"
    echo ""
    echo "API 回應："
    echo "$LOGIN_RESPONSE" | jq '.'
    
    # 檢查是否需要重設密碼
    if echo "$LOGIN_RESPONSE" | grep -q "Invalid credentials"; then
        echo ""
        echo -e "${YELLOW}建議：執行以下指令重設管理員密碼${NC}"
        echo ""
        echo "docker compose exec -T web php -r \""
        echo "\\\$pdo = new PDO('sqlite:/var/www/html/database/alleynote.sqlite3');"
        echo "\\\$password = password_hash('admin123', PASSWORD_BCRYPT);"
        echo "\\\$stmt = \\\$pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');"
        echo "\\\$stmt->execute([\\\$password, 'admin@example.com']);"
        echo "echo 'Password updated successfully' . PHP_EOL;"
        echo "\""
    fi
    
    exit 1
fi

echo ""
echo "=========================================="
echo ""

# 2. 測試 /api/auth/me
echo "步驟 2: 測試 /api/auth/me..."
echo "----------------------------------------"

ME_RESPONSE=$(curl -s -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer $ACCESS_TOKEN")

if echo "$ME_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
    echo -e "${GREEN}✅ me API 請求成功${NC}"
    
    # 檢查是否有 roles 欄位
    if echo "$ME_RESPONSE" | jq -e '.data.user.roles' > /dev/null 2>&1; then
        echo -e "${GREEN}✅ me API 返回角色資訊${NC}"
        
        # 顯示角色資訊
        echo ""
        echo "使用者資訊："
        echo "$ME_RESPONSE" | jq '.data.user'
    else
        echo -e "${RED}❌ me API 沒有返回角色資訊${NC}"
        echo ""
        echo "API 回應："
        echo "$ME_RESPONSE" | jq '.'
        exit 1
    fi
else
    echo -e "${RED}❌ me API 請求失敗${NC}"
    echo ""
    echo "API 回應："
    echo "$ME_RESPONSE" | jq '.'
    exit 1
fi

echo ""
echo "=========================================="
echo ""

# 3. 總結
echo "總結"
echo "----------------------------------------"
echo -e "${GREEN}✅ 所有測試通過！${NC}"
echo ""
echo "請在瀏覽器中驗證："
echo "1. 開啟 http://localhost:3000"
echo "2. 使用 admin@example.com / admin123 登入"
echo "3. 確認側欄顯示以下選項："
echo "   - 👥 使用者管理"
echo "   - 🔐 角色管理"
echo "   - 📈 系統統計"
echo "   - ⚙️ 系統設定"
echo ""
echo "詳細測試指南請參考："
echo "  - MANUAL_TEST_INSTRUCTIONS.md"
echo "  - SIDEBAR_ADMIN_MENU_FIX_COMPLETE.md"
echo ""
echo "=========================================="
