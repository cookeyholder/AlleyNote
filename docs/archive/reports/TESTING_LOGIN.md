# 登入功能測試指南

## 快速測試

### 自動化測試（後端 API）
```bash
./scripts/test_login_flow.sh
```

這個腳本會自動測試：
- ✅ 登入端點
- ✅ 取得使用者資訊
- ✅ Token 刷新
- ✅ Token 驗證
- ✅ 登出功能

### 手動測試（前端完整流程）

#### 1. 開啟無痕模式瀏覽器
- Chrome: `Cmd+Shift+N` (Mac) 或 `Ctrl+Shift+N` (Windows)
- Firefox: `Cmd+Shift+P` (Mac) 或 `Ctrl+Shift+P` (Windows)
- Safari: `Cmd+Shift+N` (Mac)

#### 2. 訪問登入頁面
```
http://localhost:3000/login
```

#### 3. 使用測試帳號登入
```
電子郵件：admin@example.com
密碼：password
```

#### 4. 驗證登入成功
預期結果：
- ✅ 顯示「登入成功」訊息
- ✅ 自動導向到 `/admin/dashboard`
- ✅ 在 localStorage 中可以看到：
  - `alleynote_access_token`
  - `alleynote_refresh_token`
  - `alleynote_user`

#### 5. 驗證狀態持久化
- 重新載入頁面（`F5` 或 `Cmd+R`）
- 預期結果：使用者仍然保持登入狀態

#### 6. 測試登出功能
- 點擊「登出」按鈕
- 預期結果：
  - ✅ 導向到首頁或登入頁面
  - ✅ localStorage 已清除所有認證資料

## 檢查 localStorage（開發者工具）

### Chrome / Firefox
1. 按 `F12` 開啟開發者工具
2. 選擇「Application」或「Storage」標籤
3. 左側選單選擇「Local Storage」
4. 查看 `http://localhost:3000` 下的資料

### 應該看到的資料
```
alleynote_access_token: "eyJ0eXAi..."
alleynote_refresh_token: "eyJ0eXAi..."
alleynote_user: {"id":1,"email":"admin@example.com",...}
```

## 常見問題排查

### 問題 1: 登入後沒有導向
**可能原因：**
- 前端路由設定錯誤
- JavaScript 錯誤

**檢查方式：**
```javascript
// 在瀏覽器 Console 執行
console.log(localStorage.getItem('alleynote_access_token'));
console.log(localStorage.getItem('alleynote_user'));
```

### 問題 2: Token 沒有儲存
**可能原因：**
- API 回應格式錯誤
- LocalStorage 被禁用

**檢查方式：**
1. 開啟 Network 標籤
2. 執行登入
3. 查看 `/api/auth/login` 的回應
4. 確認回應包含 `access_token` 和 `refresh_token`

### 問題 3: 重新載入後登出
**可能原因：**
- Token 沒有正確儲存
- Token 驗證失敗

**檢查方式：**
```bash
# 測試 Token 是否有效
TOKEN="你的_access_token"
curl -H "Authorization: Bearer $TOKEN" http://localhost:8080/api/auth/me
```

### 問題 4: CORS 錯誤
**解決方式：**
- 確認後端 CORS 設定正確
- 確認使用正確的 API URL

**檢查 API 設定：**
```javascript
// frontend/js/api/config.js
console.log(API_CONFIG.baseURL); // 應該是 http://localhost:8080/api
```

## 測試帳號列表

### 管理員帳號
```
電子郵件: admin@example.com
密碼: password
角色: super_admin
```

### 一般使用者帳號
```
電子郵件: user@example.com
密碼: password
角色: user
```

## API 端點測試

### 手動測試登入 API
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

### 手動測試使用者資訊 API
```bash
# 先取得 token
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' | jq -r '.access_token')

# 使用 token 取得使用者資訊
curl -H "Authorization: Bearer $TOKEN" http://localhost:8080/api/auth/me
```

### 手動測試 Token 刷新
```bash
# 先取得 refresh token
REFRESH_TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' | jq -r '.refresh_token')

# 使用 refresh token 取得新的 access token
curl -X POST http://localhost:8080/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d "{\"refresh_token\":\"$REFRESH_TOKEN\"}"
```

## 瀏覽器 Console 測試

### 測試登入流程
```javascript
// 開啟瀏覽器 Console (F12)，執行以下程式碼

// 1. 測試登入
const testLogin = async () => {
  const response = await fetch('http://localhost:8080/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      email: 'admin@example.com',
      password: 'password'
    })
  });
  const data = await response.json();
  console.log('登入回應:', data);
  
  // 儲存 token
  if (data.access_token) {
    localStorage.setItem('alleynote_access_token', JSON.stringify(data.access_token));
    localStorage.setItem('alleynote_user', JSON.stringify(data.user));
    console.log('✅ Token 已儲存');
  }
  
  return data;
};

// 執行測試
await testLogin();
```

### 測試取得使用者資訊
```javascript
const testMe = async () => {
  const token = JSON.parse(localStorage.getItem('alleynote_access_token'));
  const response = await fetch('http://localhost:8080/api/auth/me', {
    headers: { 
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  const data = await response.json();
  console.log('使用者資訊:', data);
  return data;
};

// 執行測試
await testMe();
```

## 相關文件

- [登入問題修復摘要](./LOGIN_FIX_SUMMARY.md)
- [API 文件](http://localhost:8080/api/docs/ui)
- [前端架構文件](./docs/frontend-architecture.md)

## 需要協助？

如果遇到問題，請按照以下步驟：

1. 檢查 Docker 容器是否正常運行
   ```bash
   docker compose ps
   ```

2. 查看後端日誌
   ```bash
   docker compose logs web --tail=50
   ```

3. 查看前端 Console 錯誤訊息（瀏覽器開發者工具）

4. 執行自動化測試腳本
   ```bash
   ./scripts/test_login_flow.sh
   ```

5. 如果以上步驟都無法解決，請建立 Issue 並附上：
   - 錯誤訊息
   - Console 日誌
   - 測試步驟
   - 預期結果 vs 實際結果
