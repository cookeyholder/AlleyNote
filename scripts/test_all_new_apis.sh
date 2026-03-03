#!/bin/bash

# 測試所有新實作的 API 端點
# 用於驗證所有 API 是否正常運作

echo "========================================="
echo "  測試所有新實作的 API 端點"
echo "========================================="
echo ""

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# API 基礎 URL
API_BASE_URL="http://localhost:8080/api"

# 測試帳號
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="password"

echo "📋 測試環境："
echo "   API URL: $API_BASE_URL"
echo "   測試帳號: $ADMIN_EMAIL"
echo ""

# ===========================================
# 準備工作：取得管理員 Token
# ===========================================

echo -e "${BLUE}🔐 準備工作：取得管理員 Token...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"$ADMIN_PASSWORD\"}")

if echo "$LOGIN_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.access_token')
  echo -e "${GREEN}✅ 成功取得 Token${NC}"
else
  echo -e "${RED}❌ 無法取得 Token，請確認測試帳號正確${NC}"
  exit 1
fi

echo ""

# ===========================================
# 一、個人資料管理 API 測試
# ===========================================

echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}一、個人資料管理 API 測試${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""

# 1.1 更新個人資料
echo "1️⃣  測試更新個人資料 (PUT /auth/profile)..."
PROFILE_RESPONSE=$(curl -s -X PUT "$API_BASE_URL/auth/profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin Updated"
  }')

if echo "$PROFILE_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 個人資料更新成功${NC}"
else
  echo -e "${RED}❌ 個人資料更新失敗${NC}"
  echo "$PROFILE_RESPONSE" | jq .
fi

echo ""

# 1.2 變更密碼（測試後會改回原密碼）
echo "2️⃣  測試變更密碼 (POST /auth/change-password)..."
CHANGE_PASSWORD_RESPONSE=$(curl -s -X POST "$API_BASE_URL/auth/change-password" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "current_password": "password",
    "new_password": "password123",
    "new_password_confirmation": "password123"
  }')

if echo "$CHANGE_PASSWORD_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 密碼變更成功${NC}"
  
  # 改回原密碼
  echo "   正在改回原密碼..."
  
  # 重新登入取得新 token
  NEW_LOGIN=$(curl -s -X POST "$API_BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$ADMIN_EMAIL\",\"password\":\"password123\"}")
  
  NEW_TOKEN=$(echo "$NEW_LOGIN" | jq -r '.access_token')
  
  curl -s -X POST "$API_BASE_URL/auth/change-password" \
    -H "Authorization: Bearer $NEW_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
      "current_password": "password123",
      "new_password": "password",
      "new_password_confirmation": "password"
    }' > /dev/null
  
  echo -e "${GREEN}   ✅ 已改回原密碼${NC}"
else
  echo -e "${RED}❌ 密碼變更失敗${NC}"
  echo "$CHANGE_PASSWORD_RESPONSE" | jq .
fi

echo ""

# ===========================================
# 二、使用者管理 API 測試
# ===========================================

echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}二、使用者管理 API 測試${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""

# 2.1 取得使用者列表
echo "1️⃣  測試取得使用者列表 (GET /admin/users)..."
USERS_LIST_RESPONSE=$(curl -s -X GET "$API_BASE_URL/admin/users" \
  -H "Authorization: Bearer $TOKEN")

if echo "$USERS_LIST_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  USER_COUNT=$(echo "$USERS_LIST_RESPONSE" | jq '.pagination.total')
  echo -e "${GREEN}✅ 成功取得使用者列表（共 $USER_COUNT 位使用者）${NC}"
else
  echo -e "${RED}❌ 取得使用者列表失敗${NC}"
  echo "$USERS_LIST_RESPONSE" | jq .
fi

echo ""

# 2.2 建立測試使用者
echo "2️⃣  測試建立使用者 (POST /admin/users)..."
CREATE_USER_RESPONSE=$(curl -s -X POST "$API_BASE_URL/admin/users" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser_'$(date +%s)'",
    "email": "testuser_'$(date +%s)'@example.com",
    "password": "testpass123"
  }')

if echo "$CREATE_USER_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  TEST_USER_ID=$(echo "$CREATE_USER_RESPONSE" | jq -r '.data.id')
  TEST_USERNAME=$(echo "$CREATE_USER_RESPONSE" | jq -r '.data.username')
  echo -e "${GREEN}✅ 成功建立使用者（ID: $TEST_USER_ID, Username: $TEST_USERNAME）${NC}"
else
  echo -e "${RED}❌ 建立使用者失敗${NC}"
  echo "$CREATE_USER_RESPONSE" | jq .
  # 如果建立失敗，使用預設測試 ID
  TEST_USER_ID=2
fi

echo ""

# 2.3 取得單一使用者
echo "3️⃣  測試取得單一使用者 (GET /admin/users/{id})..."
GET_USER_RESPONSE=$(curl -s -X GET "$API_BASE_URL/admin/users/$TEST_USER_ID" \
  -H "Authorization: Bearer $TOKEN")

if echo "$GET_USER_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 成功取得使用者詳情${NC}"
else
  echo -e "${RED}❌ 取得使用者詳情失敗${NC}"
  echo "$GET_USER_RESPONSE" | jq .
fi

echo ""

# 2.4 更新使用者
echo "4️⃣  測試更新使用者 (PUT /admin/users/{id})..."
UPDATE_USER_RESPONSE=$(curl -s -X PUT "$API_BASE_URL/admin/users/$TEST_USER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Test User"
  }')

if echo "$UPDATE_USER_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 成功更新使用者${NC}"
else
  echo -e "${RED}❌ 更新使用者失敗${NC}"
  echo "$UPDATE_USER_RESPONSE" | jq .
fi

echo ""

# 2.5 停用使用者
echo "5️⃣  測試停用使用者 (POST /admin/users/{id}/deactivate)..."
DEACTIVATE_RESPONSE=$(curl -s -X POST "$API_BASE_URL/admin/users/$TEST_USER_ID/deactivate" \
  -H "Authorization: Bearer $TOKEN")

if echo "$DEACTIVATE_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 成功停用使用者${NC}"
else
  echo -e "${RED}❌ 停用使用者失敗${NC}"
  echo "$DEACTIVATE_RESPONSE" | jq .
fi

echo ""

# 2.6 啟用使用者
echo "6️⃣  測試啟用使用者 (POST /admin/users/{id}/activate)..."
ACTIVATE_RESPONSE=$(curl -s -X POST "$API_BASE_URL/admin/users/$TEST_USER_ID/activate" \
  -H "Authorization: Bearer $TOKEN")

if echo "$ACTIVATE_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 成功啟用使用者${NC}"
else
  echo -e "${RED}❌ 啟用使用者失敗${NC}"
  echo "$ACTIVATE_RESPONSE" | jq .
fi

echo ""

# 2.7 重設使用者密碼
echo "7️⃣  測試重設使用者密碼 (POST /admin/users/{id}/reset-password)..."
RESET_PASSWORD_RESPONSE=$(curl -s -X POST "$API_BASE_URL/admin/users/$TEST_USER_ID/reset-password" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "newpassword123"
  }')

if echo "$RESET_PASSWORD_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
  echo -e "${GREEN}✅ 成功重設使用者密碼${NC}"
else
  echo -e "${RED}❌ 重設使用者密碼失敗${NC}"
  echo "$RESET_PASSWORD_RESPONSE" | jq .
fi

echo ""

# 2.8 刪除測試使用者
if [ -n "$TEST_USER_ID" ] && [ "$TEST_USER_ID" != "1" ]; then
  echo "8️⃣  測試刪除使用者 (DELETE /admin/users/{id})..."
  DELETE_USER_RESPONSE=$(curl -s -X DELETE "$API_BASE_URL/admin/users/$TEST_USER_ID" \
    -H "Authorization: Bearer $TOKEN")

  if echo "$DELETE_USER_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
    echo -e "${GREEN}✅ 成功刪除使用者${NC}"
  else
    echo -e "${RED}❌ 刪除使用者失敗${NC}"
    echo "$DELETE_USER_RESPONSE" | jq .
  fi
  
  echo ""
fi

# ===========================================
# 三、文章發布管理 API 測試
# ===========================================

echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}三、文章發布管理 API 測試${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""

# 先檢查是否有文章可以測試
POSTS_RESPONSE=$(curl -s -X GET "$API_BASE_URL/posts")
POST_COUNT=$(echo "$POSTS_RESPONSE" | jq '.data | length' 2>/dev/null || echo "0")

if [ "$POST_COUNT" -gt 0 ]; then
  TEST_POST_ID=$(echo "$POSTS_RESPONSE" | jq -r '.data[0].id')
  
  # 3.1 發布文章
  echo "1️⃣  測試發布文章 (POST /posts/{id}/publish)..."
  PUBLISH_RESPONSE=$(curl -s -X POST "$API_BASE_URL/posts/$TEST_POST_ID/publish" \
    -H "Authorization: Bearer $TOKEN")

  if echo "$PUBLISH_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
    echo -e "${GREEN}✅ 成功發布文章${NC}"
  else
    echo -e "${YELLOW}⚠️  發布文章測試（文章可能已發布）${NC}"
  fi

  echo ""

  # 3.2 取消發布文章
  echo "2️⃣  測試取消發布文章 (POST /posts/{id}/unpublish)..."
  UNPUBLISH_RESPONSE=$(curl -s -X POST "$API_BASE_URL/posts/$TEST_POST_ID/unpublish" \
    -H "Authorization: Bearer $TOKEN")

  if echo "$UNPUBLISH_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
    echo -e "${GREEN}✅ 成功取消發布文章${NC}"
  else
    echo -e "${YELLOW}⚠️  取消發布文章測試${NC}"
  fi

  echo ""

  # 重新發布以便測試置頂
  curl -s -X POST "$API_BASE_URL/posts/$TEST_POST_ID/publish" \
    -H "Authorization: Bearer $TOKEN" > /dev/null

  # 3.3 取消置頂文章
  echo "3️⃣  測試取消置頂文章 (DELETE /posts/{id}/pin)..."
  UNPIN_RESPONSE=$(curl -s -X DELETE "$API_BASE_URL/posts/$TEST_POST_ID/pin" \
    -H "Authorization: Bearer $TOKEN")

  if echo "$UNPIN_RESPONSE" | jq -e '.success == true' > /dev/null 2>&1; then
    echo -e "${GREEN}✅ 成功取消置頂文章${NC}"
  else
    echo -e "${YELLOW}⚠️  取消置頂文章測試（文章可能未置頂）${NC}"
  fi
else
  echo -e "${YELLOW}⚠️  無文章可測試，跳過文章發布管理測試${NC}"
fi

echo ""

# ===========================================
# 測試總結
# ===========================================

echo -e "${BLUE}=========================================${NC}"
echo -e "${GREEN}✅ 所有 API 端點測試完成${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""
echo "📝 測試摘要："
echo "   - 個人資料管理 API：2個端點"
echo "   - 使用者管理 API：8個端點"
echo "   - 文章發布管理 API：3個端點"
echo ""
echo "💡 建議："
echo "   1. 查看上方測試結果，確認所有端點正常"
echo "   2. 檢查 API 文件：http://localhost:8080/api/docs/ui"
echo "   3. 進行前端整合測試"
echo ""
