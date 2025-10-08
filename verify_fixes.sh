#!/bin/bash

echo "========================================"
echo "驗證所有修復"
echo "========================================"

# 登入並取得 Token
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' | jq -r '.access_token')

if [ -z "$TOKEN" ] || [ "$TOKEN" == "null" ]; then
  echo "❌ 登入失敗"
  exit 1
fi

echo "✅ 登入成功"
echo "Token: ${TOKEN:0:50}..."

# 測試 1: 驗證 password_hash 已移除
echo -e "\n【測試 1】驗證 API 不回傳 password_hash"
HAS_PASSWORD=$(curl -s -H "Authorization: Bearer $TOKEN" "http://localhost:8000/api/users" | jq '.data[0] | has("password_hash")')
if [ "$HAS_PASSWORD" == "false" ]; then
  echo "✅ password_hash 已正確移除"
else
  echo "❌ password_hash 仍然存在"
fi

# 測試 2: 取得單一使用者（修復路由參數問題）
echo -e "\n【測試 2】測試取得單一使用者 API"
SHOW_SUCCESS=$(curl -s -H "Authorization: Bearer $TOKEN" "http://localhost:8000/api/users/1" | jq -r '.success')
if [ "$SHOW_SUCCESS" == "true" ]; then
  echo "✅ 取得單一使用者成功（路由參數問題已修復）"
else
  echo "❌ 取得單一使用者失敗"
fi

# 測試 3: 完整 CRUD 測試
echo -e "\n【測試 3】完整 CRUD 流程測試"

# 建立
CREATE_RESULT=$(curl -s -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"username":"verify_user","email":"verify@example.com","password":"test123456","role_ids":[3]}')

CREATE_SUCCESS=$(echo "$CREATE_RESULT" | jq -r '.success')
USER_ID=$(echo "$CREATE_RESULT" | jq -r '.data.id')

if [ "$CREATE_SUCCESS" == "true" ] && [ -n "$USER_ID" ] && [ "$USER_ID" != "null" ]; then
  echo "✅ 建立使用者成功 (ID: $USER_ID)"
  
  # 更新
  UPDATE_SUCCESS=$(curl -s -X PUT "http://localhost:8000/api/users/$USER_ID" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"username":"verify_updated","email":"verify_updated@example.com"}' | jq -r '.success')
  
  if [ "$UPDATE_SUCCESS" == "true" ]; then
    echo "✅ 更新使用者成功"
  else
    echo "❌ 更新使用者失敗"
  fi
  
  # 分配角色
  ASSIGN_SUCCESS=$(curl -s -X PUT "http://localhost:8000/api/users/$USER_ID/roles" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"role_ids":[3,4]}' | jq -r '.success')
  
  if [ "$ASSIGN_SUCCESS" == "true" ]; then
    echo "✅ 分配角色成功"
  else
    echo "❌ 分配角色失敗"
  fi
  
  # 刪除
  DELETE_SUCCESS=$(curl -s -X DELETE "http://localhost:8000/api/users/$USER_ID" \
    -H "Authorization: Bearer $TOKEN" | jq -r '.success')
  
  if [ "$DELETE_SUCCESS" == "true" ]; then
    echo "✅ 刪除使用者成功"
  else
    echo "❌ 刪除使用者失敗"
  fi
else
  echo "❌ 建立使用者失敗，無法測試 CRUD"
fi

# 測試 4: 角色 API
echo -e "\n【測試 4】測試角色管理 API"
ROLE_SHOW=$(curl -s -H "Authorization: Bearer $TOKEN" "http://localhost:8000/api/roles/1" | jq -r '.success')
if [ "$ROLE_SHOW" == "true" ]; then
  echo "✅ 角色 API 正常運作"
else
  echo "❌ 角色 API 失敗"
fi

echo -e "\n========================================"
echo "驗證完成！"
echo "========================================"
