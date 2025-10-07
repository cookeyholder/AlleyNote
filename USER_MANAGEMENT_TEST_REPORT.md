# 使用者管理功能測試報告

## 測試日期
2025-10-08

## 測試環境
- Backend URL: http://localhost:8000
- 測試帳號: admin@example.com / password
- 測試工具: Playwright Browser Automation + curl API Testing

---

## 測試結果總覽

### 後端 API 測試

| 功能 | API 端點 | 方法 | 狀態 | 說明 |
|------|---------|------|------|------|
| 使用者列表 | `/api/users` | GET | ✅ 成功 | 正常回傳使用者列表、分頁資訊 |
| 取得單一使用者 | `/api/users/{id}` | GET | ❌ 失敗 | 認證失敗錯誤 |
| 建立使用者 | `/api/users` | POST | ⏸️ 未測試 | 因上一步失敗未測試 |
| 更新使用者 | `/api/users/{id}` | PUT | ⏸️ 未測試 | 因前置失敗未測試 |
| 刪除使用者 | `/api/users/{id}` | DELETE | ⏸️ 未測試 | 因前置失敗未測試 |
| 分配角色 | `/api/users/{id}/roles` | PUT | ⏸️ 未測試 | 因前置失敗未測試 |
| 角色列表 | `/api/roles` | GET | ✅ 成功 | 回傳 5 個角色 |
| 權限列表 | `/api/permissions` | GET | ✅ 成功 | 回傳 21 個權限 |

### 前端頁面測試

| 功能 | 路由 | 狀態 | 說明 |
|------|------|------|------|
| 登入 | `/login` | ✅ 成功 | 登入正常，Token 正常生成 |
| 使用者管理頁面 | `/admin/users` | ⚠️ 部分 | 頁面載入成功，但顯示「尚無使用者資料」 |
| 角色管理頁面 | `/admin/roles` | ⏸️ 未測試 | 待後續測試 |
| 側邊欄導航 | - | ❌ 失敗 | 使用者管理和角色管理連結未顯示 |

---

## 詳細測試記錄

### 1. 使用者列表 API 測試 ✅

**請求**：
```bash
GET /api/users?page=1&per_page=10
Authorization: Bearer {token}
```

**回應**：
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com",
      "password_hash": "$2y$12$3yw.Aa5I6f/yhbMuKDOqouoecSGEmejxqGuXMBi6iY67GnXy/SXx.",
      "created_at": "2025-10-05 11:59:35",
      "updated_at": "2025-10-05 11:59:35",
      "last_login": "2025-10-08T07:42:00+08:00",
      "roles": [
        {
          "id": 1,
          "name": "超級管理員"
        }
      ]
    }
  ],
  "pagination": {
    "total": 1,
    "page": 1,
    "per_page": 10,
    "last_page": 1
  }
}
```

**問題**：
- ❌ 回應包含 `password_hash`，這是安全問題（應該在 Service 層過濾）

**建議**：
- UserManagementService 應該過濾掉敏感欄位

---

### 2. 取得單一使用者 API 測試 ❌

**請求**：
```bash
GET /api/users/1
Authorization: Bearer {token}
```

**回應**：
```json
{
  "success": false,
  "error": "認證驗證失敗",
  "code": "AUTH_FAILED",
  "timestamp": "2025-10-08T07:42:00+08:00"
}
```

**問題**：
- ❌ API 認證失敗，但使用者列表 API 使用相同 Token 卻成功
- 可能是路由配置或中間件問題

**需要修復**：
- 檢查路由配置中的 `users.show` 路由
- 檢查中間件設定

---

### 3. 前端使用者管理頁面測試 ⚠️

**狀態**：頁面載入成功，但顯示「尚無使用者資料」

**問題分析**：
1. ❌ Token 未正確儲存到 localStorage
   - localStorage 只有 `alleynote_user` key
   - 內容沒有 `access_token` 欄位
   - 只有 `user` 和 `token_info` 欄位

2. ⚠️ API 調用使用錯誤的 header 格式
   - 前端可能使用錯誤的 Token 來源

3. ❌ 側邊欄未顯示使用者管理連結
   - `globalGetters.isAdmin()` 可能回傳 false
   - 需要檢查權限判斷邏輯

**localStorage 內容**：
```json
{
  "alleynote_user": {
    "user": {
      "id": 1,
      "email": "admin@example.com",
      "name": null
    },
    "token_info": {
      "issued_at": 1759880435,
      "expires_at": 1759884035
    }
  }
}
```

**期望內容**：
```json
{
  "alleynote_user": {
    "user": {...},
    "access_token": "eyJ0...",
    "token_info": {...}
  }
}
```

---

## 發現的問題

### 🔴 高優先級（阻塞功能）

1. **取得單一使用者 API 認證失敗**
   - 影響：無法查看使用者詳情、編輯、刪除
   - 位置：`backend/config/routes/api.php` 或中間件
   - 修復：檢查路由定義和中間件配置

2. **前端 Token 未正確儲存**
   - 影響：所有 API 調用失敗
   - 位置：前端登入邏輯
   - 修復：確保登入後 Token 寫入 localStorage

3. **側邊欄未顯示管理連結**
   - 影響：使用者無法導航到管理頁面
   - 位置：`frontend/src/layouts/DashboardLayout.js`
   - 修復：檢查 `isAdmin()` 邏輯

### 🟡 中優先級（安全性）

4. **API 回傳密碼雜湊值**
   - 影響：安全風險
   - 位置：`UserManagementService::listUsers()`
   - 修復：過濾 `password_hash` 欄位

### 🟢 低優先級（優化）

5. **前端錯誤處理**
   - 建議：API 失敗時顯示更友善的錯誤訊息

---

## 修復建議

### 1. 修復取得單一使用者 API

檢查 `backend/config/routes/api.php`：
```php
'users.show' => [
    'methods' => ['GET'],
    'path' => '/api/users/{id}',
    'handler' => [UserController::class, 'show'],
    'middleware' => ['auth'],  // 確認這行存在
    'name' => 'users.show'
],
```

### 2. 修復 Token 儲存

檢查前端登入處理（可能在 `frontend/src/api/modules/auth.js`）：
```javascript
// 登入成功後
localStorage.setItem('alleynote_user', JSON.stringify({
  user: response.user,
  access_token: response.access_token,  // 確保有這行
  token_info: response.token_info
}));
```

### 3. 過濾敏感欄位

在 `UserRepository::paginate()` 或 Service 層：
```php
// 在回傳前移除密碼
unset($user['password_hash']);
unset($user['password']);
```

### 4. 修復側邊欄顯示

檢查 `globalGetters.isAdmin()` 的實作。

---

## 後續測試計劃

### 待測試功能

1. ✅ 使用者列表（已測試）
2. ❌ 取得單一使用者（待修復後測試）
3. ⏸️ 建立新使用者
4. ⏸️ 更新使用者資料
5. ⏸️ 刪除使用者
6. ⏸️ 分配角色給使用者
7. ⏸️ 角色管理頁面
8. ⏸️ 權限管理

### 測試場景

當 API 修復後，需要測試：

1. **建立使用者**
   - 正常建立
   - 重複使用者名稱（應失敗）
   - 重複 Email（應失敗）
   - 分配多個角色

2. **編輯使用者**
   - 修改使用者名稱
   - 修改 Email
   - 修改密碼
   - 修改角色

3. **刪除使用者**
   - 刪除一般使用者
   - 嘗試刪除 admin（應拒絕）

4. **搜尋功能**
   - 按使用者名稱搜尋
   - 按 Email 搜尋

5. **分頁功能**
   - 建立 20+ 使用者測試分頁

---

## 結論

使用者管理模組的基礎架構已經完成，但存在以下關鍵問題需要修復：

1. **必須修復**：單一使用者 API 認證問題
2. **必須修復**：前端 Token 儲存問題
3. **必須修復**：側邊欄導航顯示問題
4. **建議修復**：過濾敏感欄位

修復這些問題後，系統將可以正常使用。

---

**測試人員**：AI Assistant (Claude)  
**測試日期**：2025-10-08  
**狀態**：⚠️ 發現關鍵問題，需要修復
