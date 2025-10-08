# 使用者管理功能演示

## 前置條件
1. 後端服務正在運行：http://localhost:8000
2. 前端服務正在運行：http://localhost:3000
3. 管理員帳號已設定：
   - Email: admin@example.com
   - Password: password

## 演示步驟

### 1. 登入管理後台

1. 打開瀏覽器訪問：http://localhost:3000/login
2. 輸入管理員憑證：
   - Email: `admin@example.com`
   - Password: `password`
3. 點擊「登入」按鈕
4. 應該會自動跳轉到管理後台儀表板

### 2. 檢查側欄功能

登入成功後，側欄應該顯示以下管理功能：
- 📊 儀表板
- 📝 文章管理
- 🏷️ 標籤管理
- 👥 **使用者管理** ← 新功能
- 🔐 **角色管理** ← 新功能
- 📈 系統統計
- ⚙️ 系統設定
- 👤 個人資料

### 3. 使用者管理功能

#### 3.1 檢視使用者列表
1. 點擊側欄的「使用者管理」
2. 應該會顯示所有使用者列表
3. 列表應該包含以下欄位：
   - ID
   - 使用者名稱
   - Email
   - 角色
   - 建立時間
   - 操作按鈕（編輯、刪除）

#### 3.2 新增使用者
1. 點擊「新增使用者」按鈕
2. 填寫使用者資訊：
   - 使用者名稱
   - Email
   - 密碼
   - 確認密碼
   - 選擇角色
3. 點擊「儲存」按鈕
4. 應該會返回列表並顯示新增的使用者

#### 3.3 編輯使用者
1. 在使用者列表中點擊「編輯」按鈕
2. 修改使用者資訊
3. 點擊「儲存」按鈕
4. 應該會更新使用者資訊

#### 3.4 刪除使用者
1. 在使用者列表中點擊「刪除」按鈕
2. 確認刪除操作
3. 應該會從列表中移除該使用者

### 4. 角色管理功能

#### 4.1 檢視角色列表
1. 點擊側欄的「角色管理」
2. 應該會顯示所有角色列表
3. 列表應該包含以下欄位：
   - ID
   - 角色名稱
   - 顯示名稱
   - 描述
   - 操作按鈕（編輯、刪除）

#### 4.2 新增角色
1. 點擊「新增角色」按鈕
2. 填寫角色資訊：
   - 角色名稱（英文，如：editor）
   - 顯示名稱（中文，如：編輯者）
   - 描述
3. 點擊「儲存」按鈕
4. 應該會返回列表並顯示新增的角色

#### 4.3 編輯角色
1. 在角色列表中點擊「編輯」按鈕
2. 修改角色資訊
3. 點擊「儲存」按鈕
4. 應該會更新角色資訊

#### 4.4 刪除角色
1. 在角色列表中點擊「刪除」按鈕
2. 確認刪除操作
3. 應該會從列表中移除該角色

## 驗證 API 端點

### 使用者管理 API
```bash
# 獲取使用者列表
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer YOUR_TOKEN"

# 獲取單個使用者
curl -X GET http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer YOUR_TOKEN"

# 建立使用者
curl -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "password123",
    "role_ids": [2]
  }'

# 更新使用者
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "updateduser",
    "email": "updated@example.com",
    "role_ids": [2]
  }'

# 刪除使用者
curl -X DELETE http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 角色管理 API
```bash
# 獲取角色列表
curl -X GET http://localhost:8000/api/roles \
  -H "Authorization: Bearer YOUR_TOKEN"

# 獲取單個角色
curl -X GET http://localhost:8000/api/roles/1 \
  -H "Authorization: Bearer YOUR_TOKEN"

# 建立角色
curl -X POST http://localhost:8000/api/roles \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "editor",
    "display_name": "編輯者",
    "description": "可以編輯文章"
  }'

# 更新角色
curl -X PUT http://localhost:8000/api/roles/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "editor",
    "display_name": "編輯者",
    "description": "可以編輯文章"
  }'

# 刪除角色
curl -X DELETE http://localhost:8000/api/roles/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 常見問題

### Q1: 側欄沒有顯示使用者管理功能
**A**: 檢查以下項目：
1. 確認已使用管理員帳號登入
2. 清除瀏覽器的 LocalStorage 並重新登入
3. 檢查瀏覽器控制台是否有錯誤
4. 確認後端 API 返回的 user 對象包含正確的 role 和 roles 欄位

### Q2: 無法登入管理後台
**A**: 檢查以下項目：
1. 確認後端服務正在運行
2. 確認資料庫中的密碼雜湊正確
3. 檢查網路請求是否成功
4. 查看後端日誌

### Q3: API 返回 401 Unauthorized
**A**: 檢查以下項目：
1. 確認 Token 沒有過期
2. 確認 Authorization header 格式正確
3. 重新登入獲取新的 Token

## 技術細節

### 前端路由
- 使用者列表：`/admin/users`
- 新增使用者：`/admin/users/new`
- 編輯使用者：`/admin/users/:id/edit`
- 角色列表：`/admin/roles`
- 新增角色：`/admin/roles/new`
- 編輯角色：`/admin/roles/:id/edit`

### 後端路由
- 使用者管理：`/api/users`
- 角色管理：`/api/roles`

### 權限控制
- 只有 `super_admin` 角色可以訪問使用者管理和角色管理功能
- 前端通過 `globalGetters.isAdmin()` 檢查權限
- 後端通過 JWT 中間件和角色檢查來保護 API 端點

## 下一步
1. 添加權限管理功能
2. 實現批量操作（批量刪除、批量分配角色等）
3. 添加使用者搜索和篩選功能
4. 實現使用者匯入/匯出功能
5. 添加使用者活動日誌
