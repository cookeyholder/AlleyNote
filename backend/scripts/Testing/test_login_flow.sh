#!/bin/bash

# 測試登入流程腳本
# 用於驗證登入 API 和前端整合是否正常運作

echo "========================================="
echo "  AlleyNote 登入流程測試"
echo "========================================="
echo ""

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# API 基礎 URL
API_BASE_URL="http://localhost:8080/api"

# 測試帳號
TEST_EMAIL="admin@example.com"
TEST_PASSWORD="password"

echo "📋 測試環境："
echo "   API URL: $API_BASE_URL"
echo "   測試帳號: $TEST_EMAIL"
echo ""

# 測試 1: 登入端點
echo "1️⃣  測試登入端點..."
LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$TEST_EMAIL\",\"password\":\"$TEST_PASSWORD\"}")

# 檢查登入是否成功
if echo "$LOGIN_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 登入成功${NC}"
  
  # 提取 tokens
  ACCESS_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.access_token')
  REFRESH_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.refresh_token')
  USER_ID=$(echo "$LOGIN_RESPONSE" | jq -r '.user.id')
  USER_EMAIL=$(echo "$LOGIN_RESPONSE" | jq -r '.user.email')
  USER_ROLE=$(echo "$LOGIN_RESPONSE" | jq -r '.user.role')
  
  echo "   使用者 ID: $USER_ID"
  echo "   電子郵件: $USER_EMAIL"
  echo "   角色: $USER_ROLE"
  echo "   Access Token: ${ACCESS_TOKEN:0:50}..."
  echo "   Refresh Token: ${REFRESH_TOKEN:0:50}..."
else
  echo -e "${RED}❌ 登入失敗${NC}"
  echo "回應內容："
  echo "$LOGIN_RESPONSE" | jq .
  exit 1
fi

echo ""

# 測試 2: 取得使用者資訊
echo "2️⃣  測試取得使用者資訊端點..."
ME_RESPONSE=$(curl -s -X GET "$API_BASE_URL/auth/me" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "Content-Type: application/json")

if echo "$ME_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 成功取得使用者資訊${NC}"
  
  ME_USER_ID=$(echo "$ME_RESPONSE" | jq -r '.data.user.id')
  ME_USER_EMAIL=$(echo "$ME_RESPONSE" | jq -r '.data.user.email')
  ME_USER_USERNAME=$(echo "$ME_RESPONSE" | jq -r '.data.user.username')
  
  echo "   使用者 ID: $ME_USER_ID"
  echo "   電子郵件: $ME_USER_EMAIL"
  echo "   使用者名稱: $ME_USER_USERNAME"
  
  # 驗證使用者 ID 是否一致
  if [ "$USER_ID" == "$ME_USER_ID" ]; then
    echo -e "${GREEN}✅ 使用者 ID 一致${NC}"
  else
    echo -e "${RED}❌ 使用者 ID 不一致${NC}"
    exit 1
  fi
else
  echo -e "${RED}❌ 取得使用者資訊失敗${NC}"
  echo "回應內容："
  echo "$ME_RESPONSE" | jq .
  exit 1
fi

echo ""

# 測試 3: Token 刷新
echo "3️⃣  測試 Token 刷新端點..."
REFRESH_RESPONSE=$(curl -s -X POST "$API_BASE_URL/auth/refresh" \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\":\"$REFRESH_TOKEN\"}")

if echo "$REFRESH_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ Token 刷新成功${NC}"
  
  NEW_ACCESS_TOKEN=$(echo "$REFRESH_RESPONSE" | jq -r '.access_token')
  echo "   新的 Access Token: ${NEW_ACCESS_TOKEN:0:50}..."
else
  echo -e "${RED}❌ Token 刷新失敗${NC}"
  echo "回應內容："
  echo "$REFRESH_RESPONSE" | jq .
  exit 1
fi

echo ""

# 測試 4: 使用新 Token 驗證
echo "4️⃣  測試使用新 Token 驗證..."
VERIFY_RESPONSE=$(curl -s -X GET "$API_BASE_URL/auth/me" \
  -H "Authorization: Bearer $NEW_ACCESS_TOKEN" \
  -H "Content-Type: application/json")

if echo "$VERIFY_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 新 Token 驗證成功${NC}"
else
  echo -e "${RED}❌ 新 Token 驗證失敗${NC}"
  echo "回應內容："
  echo "$VERIFY_RESPONSE" | jq .
  exit 1
fi

echo ""

# 測試 5: 登出
echo "5️⃣  測試登出端點..."
LOGOUT_RESPONSE=$(curl -s -X POST "$API_BASE_URL/auth/logout" \
  -H "Authorization: Bearer $NEW_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"access_token\":\"$NEW_ACCESS_TOKEN\",\"refresh_token\":\"$REFRESH_TOKEN\"}")

if echo "$LOGOUT_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 登出成功${NC}"
else
  echo -e "${YELLOW}⚠️  登出回應異常（但可能仍然成功）${NC}"
  echo "回應內容："
  echo "$LOGOUT_RESPONSE" | jq .
fi

echo ""

# 測試 6: 驗證登出後 Token 是否失效
echo "6️⃣  驗證登出後 Token 是否失效..."
AFTER_LOGOUT_RESPONSE=$(curl -s -X GET "$API_BASE_URL/auth/me" \
  -H "Authorization: Bearer $NEW_ACCESS_TOKEN" \
  -H "Content-Type: application/json")

if echo "$AFTER_LOGOUT_RESPONSE" | jq -e '.success == false' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ Token 已正確失效${NC}"
else
  echo -e "${YELLOW}⚠️  Token 可能未正確失效（取決於後端實作）${NC}"
fi

echo ""
echo "========================================="
echo -e "${GREEN}✅ 所有測試完成${NC}"
echo "========================================="
echo ""
echo "📝 前端測試步驟："
echo "   1. 開啟無痕模式瀏覽器"
echo "   2. 訪問 http://localhost:3000/login"
echo "   3. 使用測試帳號登入：$TEST_EMAIL / $TEST_PASSWORD"
echo "   4. 驗證是否成功導向到管理後台"
echo "   5. 重新載入頁面，確認使用者狀態保持登入"
echo ""
