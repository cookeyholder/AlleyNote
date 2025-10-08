# 手動測試說明

## 目的

驗證管理員登入後，側欄是否正確顯示「使用者管理」等管理員專用選項。

## 測試步驟

### 1. 確認服務正在運行

```bash
# 檢查後端
docker compose ps

# 檢查前端（應該在 http://localhost:3000）
ps aux | grep vite
```

### 2. 清除瀏覽器快取

為了確保測試結果準確，請先清除瀏覽器的快取和 localStorage：

1. 開啟瀏覽器開發者工具（F12）
2. 前往 Application (Chrome) 或 Storage (Firefox) 標籤
3. 清除所有 Local Storage
4. 清除所有 Cookies
5. 重新整理頁面

### 3. 進行登入

1. 開啟 http://localhost:3000
2. 應該會自動導向到登入頁面
3. 輸入測試帳號：
   - Email: `admin@example.com`
   - Password: `admin123`
4. 點擊「登入」按鈕

### 4. 檢查側欄

登入成功後，應該會看到後台管理頁面。在左側側欄中，應該會看到以下選項：

**一般使用者可見：**
- 📊 儀表板
- 📝 文章管理
- 🏷️ 標籤管理
- 👤 個人資料

**管理員專用（應該顯示）：**
- 👥 使用者管理
- 🔐 角色管理
- 📈 系統統計
- ⚙️ 系統設定

### 5. 驗證角色資訊

在瀏覽器開發者工具的 Console 中執行以下程式碼：

```javascript
// 查看儲存的使用者資訊
const user = JSON.parse(localStorage.getItem('alleynote_user'));
console.log('User:', user);
console.log('Roles:', user?.roles);

// 測試 isAdmin 函數
console.log('Is Admin:', 
  user?.roles?.some(r => 
    r.id === 1 || 
    r.name === 'super_admin' || 
    r.name === 'admin' || 
    r.name === '超級管理員'
  )
);
```

預期輸出：
```
User: {id: 1, email: "admin@example.com", roles: Array(1), ...}
Roles: [{id: 1, name: "super_admin", display_name: "超級管理員"}]
Is Admin: true
```

### 6. 測試使用者管理功能

1. 點擊側欄中的「👥 使用者管理」
2. 應該會導向到使用者列表頁面
3. 應該能看到所有使用者的列表
4. 可以進行新增、編輯、刪除等操作

### 7. 測試角色管理功能

1. 點擊側欄中的「🔐 角色管理」
2. 應該會導向到角色列表頁面
3. 應該能看到所有角色的列表
4. 可以進行新增、編輯、刪除等操作

## 預期結果

✅ 登入成功後，側欄顯示所有管理員選項
✅ 點擊「使用者管理」能正確導向
✅ 點擊「角色管理」能正確導向
✅ LocalStorage 中儲存了正確的使用者角色資訊
✅ 開發者工具 Console 沒有錯誤訊息

## 如果仍然沒有顯示

### 檢查清單

1. **檢查 API 回應**
   
   在開發者工具的 Network 標籤中，查看登入 API (`/api/auth/login`) 的回應：
   
   ```json
   {
     "success": true,
     "user": {
       "id": 1,
       "email": "admin@example.com",
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
   
   確認 `user.roles` 欄位存在且包含正確的角色資訊。

2. **檢查 localStorage**
   
   在開發者工具的 Application 標籤中，查看 localStorage：
   
   - `alleynote_user` 應該包含使用者資訊和角色
   - `alleynote_token` 應該包含有效的 JWT token

3. **檢查 Console 錯誤**
   
   如果 Console 有任何 JavaScript 錯誤，請記錄下來並回報。

4. **強制重新載入**
   
   按 Ctrl+Shift+R (Windows/Linux) 或 Cmd+Shift+R (Mac) 強制重新載入頁面，繞過快取。

5. **檢查前端程式碼**
   
   在 Console 中執行：
   
   ```javascript
   import { globalGetters } from '/src/store/globalStore.js';
   console.log('Is Admin:', globalGetters.isAdmin());
   ```
   
   應該返回 `true`。

## 故障排除

### 問題：登入時顯示「Invalid credentials」

**解決方案：**

重設管理員密碼：

```bash
cd /Users/cookeyholder/projects/AlleyNote
docker compose exec -T web php -r "
\$pdo = new PDO('sqlite:/var/www/html/database/alleynote.sqlite3');
\$password = password_hash('admin123', PASSWORD_BCRYPT);
\$stmt = \$pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
\$stmt->execute([\$password, 'admin@example.com']);
echo 'Password updated successfully' . PHP_EOL;
"
```

### 問題：側欄仍然沒有顯示管理員選項

**解決方案：**

1. 完全清除 localStorage：
   
   ```javascript
   localStorage.clear();
   location.reload();
   ```

2. 重新登入

3. 檢查 globalStore 狀態：
   
   ```javascript
   import { globalStore, globalGetters } from '/src/store/globalStore.js';
   console.log('User:', globalGetters.getCurrentUser());
   console.log('Is Admin:', globalGetters.isAdmin());
   console.log('User Role:', globalGetters.getUserRole());
   ```

### 問題：API 沒有返回角色資訊

**解決方案：**

重啟後端服務：

```bash
cd /Users/cookeyholder/projects/AlleyNote
docker compose restart web
```

然後等待幾秒讓服務完全啟動。

## 聯絡資訊

如果仍然有問題，請提供：

1. 瀏覽器開發者工具 Console 的錯誤訊息
2. Network 標籤中 `/api/auth/login` 的完整回應
3. localStorage 中 `alleynote_user` 的內容
4. 瀏覽器版本和作業系統

這些資訊將幫助我們更快地診斷和解決問題。
