# 側欄選單修復說明

## 問題描述
登入後台管理介面後，側欄中的「使用者管理」、「角色管理」、「系統統計」、「系統設定」等管理員專屬功能選單沒有顯示。

## 根本原因
側欄選單的渲染邏輯使用 `globalGetters.isAdmin()` 來判斷使用者是否為管理員。該函式會檢查：
1. 使用者的 `role` 欄位是否為 `admin`、`super_admin` 或 `超級管理員`
2. 使用者的 `roles` 陣列中是否包含管理員角色

## 已完成的修復

### 1. 更新管理員密碼
管理員帳號的密碼已重設為 `Admin@123`：
- Email: `admin@example.com`
- Password: `Admin@123`

### 2. 確認 API 回傳正確資料
登入 API (`/api/auth/login`) 現在會回傳完整的使用者資料，包括：
```json
{
  "user": {
    "id": 1,
    "email": "admin@example.com",
    "name": "admin",
    "role": "super_admin",
    "roles": [
      {
        "id": 1,
        "name": "super_admin",
        "display_name": "超級管理員"
      }
    ]
  }
}
```

### 3. 增強側欄渲染邏輯
在 `frontend/src/layouts/DashboardLayout.js` 中：
- 預先計算 `isAdmin` 狀態
- 添加 debug 輸出以便追蹤問題

### 4. 增強權限檢查邏輯
在 `frontend/src/store/globalStore.js` 中的 `isAdmin()` 函式：
- 添加詳細的 console debug 輸出
- 支援多種檢查方式（role 欄位、roles 陣列、角色 ID、角色名稱）

## 測試步驟

1. **清除瀏覽器快取和 LocalStorage**
   ```javascript
   // 在瀏覽器 Console 中執行
   localStorage.clear();
   location.reload();
   ```

2. **重新登入**
   - 前往 http://localhost:3000/login
   - 使用帳號：`admin@example.com`
   - 密碼：`Admin@123`

3. **檢查 Console 輸出**
   登入成功後，應該會看到類似以下的 debug 輸出：
   ```
   DashboardLayout Debug: {
     user: { id: 1, email: "admin@example.com", name: "admin", role: "super_admin", ... },
     isAdmin: true,
     userRole: "super_admin"
   }
   
   isAdmin() Debug: {
     user: { ... },
     hasUser: true,
     userRole: "super_admin",
     userRoles: [{ id: 1, name: "super_admin", ... }]
   }
   
   getUserRole() returned: "super_admin"
   ✅ Admin detected via role field
   ```

4. **驗證側欄選單**
   登入後應該能看到以下選單項目：
   - 📊 儀表板
   - 📝 文章管理
   - 🏷️ 標籤管理
   - 👥 使用者管理 ← 管理員專屬
   - 🔐 角色管理 ← 管理員專屬
   - 📈 系統統計 ← 管理員專屬
   - ⚙️ 系統設定 ← 管理員專屬
   - 👤 個人資料

## 如果問題仍然存在

1. **檢查 Console 錯誤訊息**
   打開瀏覽器的開發者工具 (F12)，檢查 Console 分頁是否有任何錯誤訊息。

2. **檢查 LocalStorage 中的使用者資料**
   在 Console 中執行：
   ```javascript
   console.log(JSON.parse(localStorage.getItem('alleynote_user')));
   ```
   確認使用者資料中包含 `role: "super_admin"` 和 `roles` 陣列。

3. **手動測試 isAdmin() 函式**
   在 Console 中執行：
   ```javascript
   import { globalGetters } from './store/globalStore.js';
   console.log('Is Admin?', globalGetters.isAdmin());
   console.log('User Role:', globalGetters.getUserRole());
   console.log('Current User:', globalGetters.getCurrentUser());
   ```

4. **重新建置前端**
   如果修改了程式碼，確保重新建置：
   ```bash
   cd frontend
   npm run build
   ```

## 移除 Debug 輸出

當確認問題已解決後，可以移除 debug 輸出：

1. 在 `frontend/src/layouts/DashboardLayout.js` 中移除 `console.log('DashboardLayout Debug:', ...)`
2. 在 `frontend/src/store/globalStore.js` 的 `isAdmin()` 函式中移除所有 `console.log` 語句

## 其他注意事項

- 側欄選單的顯示邏輯是在渲染時決定的，不會動態更新
- 如果使用者權限變更，需要重新登入才會生效
- 確保 Docker 容器中的資料庫檔案 (`/var/www/html/database/alleynote.sqlite3`) 與本地檔案同步
