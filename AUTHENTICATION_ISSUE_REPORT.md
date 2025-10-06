# 認證問題報告與修復計劃

## 🔴 當前問題

### 主要問題：登入後點擊任何功能都會跳回登入頁

**現象**：
1. ✅ 使用者可以成功登入
2. ✅ 成功跳轉到 `/admin/dashboard`
3. ❌ 點擊任何導航連結（如「文章管理」）會被重新導向到 `/login`
4. ❌ 出現 401 Unauthorized 錯誤

**錯誤日誌**：
```
Failed to load resource: the server responded with a status of 401 (Unauthorized)
```

## 🔍 根本原因分析

### 1. Token 儲存位置不一致

**問題**：
- `tokenManager.js` 使用 `sessionStorage` 儲存 Token
- `globalStore.js` 的狀態在記憶體中，頁面導航時丟失
- 路由守衛在檢查認證狀態時，記憶體中的狀態已經被清空

**程式碼位置**：
```javascript
// frontend/src/utils/tokenManager.js (line 18)
sessionStorage.setItem(this.storageKey, JSON.stringify(tokenData));

// frontend/src/store/globalStore.js
const initialState = {
  user: null,
  isAuthenticated: false,  // 頁面導航時重置為 false
  ...
};
```

### 2. API 請求未攜帶 Token

**問題**：
- Request interceptor 可能沒有正確添加 Authorization header
- Token 存在但沒有在 API 請求中發送

**需要檢查**：
```javascript
// frontend/src/api/interceptors/request.js
// 是否正確從 tokenManager 取得 Token 並加入 header?
```

### 3. 路由守衛時序問題

**問題**：
- 路由守衛執行時，globalStore 狀態尚未從 localStorage 恢復
- `isAuthenticated()` 在恢復狀態之前就被調用

**程式碼位置**：
```javascript
// frontend/src/router/index.js
export function requireAuth() {
  if (!globalGetters.isAuthenticated()) {  // 這裡可能太早執行
    router.navigate('/login');
    return false;
  }
  return true;
}
```

## 🛠️ 已嘗試的修復

### ✅ 已完成

1. **增強 globalStore 持久化**
   - 新增 `restoreUser()` 方法
   - 使用 localStorage 儲存使用者資訊
   - 改進 `isAuthenticated()` 檢查邏輯

2. **改進錯誤處理**
   - 修復 login.js 的錯誤處理
   - 支援多種後端回應格式

### ⏳ 尚未解決

1. **Token 在 API 請求中的傳遞**
2. **路由守衛的時序問題**
3. **頁面導航時的狀態恢復**

## 📋 完整修復計劃

### 階段 1：修復 Token 傳遞 ⚡ 優先

**目標**：確保所有 API 請求都攜帶有效的 Token

**步驟**：
1. 檢查 `request.js` interceptor
2. 確保從 `tokenManager.getToken()` 取得 Token
3. 正確添加到 `Authorization: Bearer {token}` header
4. 測試 API 請求是否包含 Token

**程式碼修改**：
```javascript
// frontend/src/api/interceptors/request.js
export function requestInterceptor(config) {
  const token = tokenManager.getToken();
  if (token && tokenManager.isValid()) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
}
```

### 階段 2：改進狀態恢復

**目標**：確保頁面導航時正確恢復認證狀態

**步驟**：
1. 在應用程式啟動時立即恢復狀態
2. 修改路由守衛延遲檢查
3. 確保 Token 和使用者資訊同步

**程式碼修改**：
```javascript
// frontend/src/main.js
// 在路由初始化之前恢復狀態
globalActions.restoreUser();
initRouter();
```

### 階段 3：統一儲存策略

**目標**：Token 和使用者資訊使用相同的儲存位置

**選項**：
- **選項 A**：全部使用 `localStorage`（推薦，支援跨 tab）
- **選項 B**：全部使用 `sessionStorage`（單 tab，更安全）

**建議**：使用 `sessionStorage`，避免跨 tab 狀態衝突

### 階段 4：完善 Dashboard 功能

**目標**：實作 Dashboard 頁面的所有功能

**待實作功能**：
1. ✏️ **新增文章** - 文章編輯器頁面
2. 📋 **文章管理** - 文章列表頁面（支援搜尋、篩選、分頁）
3. 🏷️ **標籤管理** - 標籤 CRUD 操作
4. 👤 **個人資料** - 使用者資料編輯
5. 👥 **使用者管理** - 管理員功能
6. 📊 **統計圖表** - 即時數據顯示
7. 🔔 **通知系統** - 系統通知和提醒

## 🧪 測試計劃

### 測試用例

#### TC-1: 基本登入流程
1. 訪問 `/login`
2. 輸入有效憑證
3. 驗證：成功跳轉到 `/admin/dashboard`
4. 驗證：Token 儲存在 sessionStorage
5. 驗證：使用者資訊儲存在 localStorage

#### TC-2: 頁面導航（核心問題）
1. 完成 TC-1
2. 點擊「文章管理」連結
3. 驗證：成功導航到 `/admin/posts`
4. 驗證：保持登入狀態
5. 驗證：API 請求包含 Authorization header

#### TC-3: 頁面刷新
1. 完成 TC-2
2. 按 F5 刷新頁面
3. 驗證：保持登入狀態
4. 驗證：使用者資訊正確顯示

#### TC-4: Tab 間同步
1. 完成 TC-1
2. 開啟新 tab 訪問 `/admin/dashboard`
3. 驗證：不需要重新登入（取決於儲存策略）

#### TC-5: Token 過期處理
1. 完成 TC-1
2. 等待 Token 過期或手動設定過期時間
3. 發起 API 請求
4. 驗證：自動跳轉到 `/login`
5. 驗證：顯示適當的錯誤訊息

## 📊 當前狀態

### ✅ 已完成功能

- [x] 登入頁面 UI
- [x] 登入表單驗證
- [x] 忘記密碼頁面
- [x] JWT Token 生成和儲存
- [x] 登入成功後跳轉
- [x] Toast 通知
- [x] Dashboard 靜態頁面

### ⏳ 進行中功能

- [ ] **認證狀態持久化**（85% 完成）
- [ ] API Token 傳遞（需要驗證）
- [ ] 路由守衛優化（需要測試）

### ❌ 未開始功能

- [ ] 文章管理頁面（CRUD）
- [ ] 文章編輯器（富文本編輯）
- [ ] 標籤管理
- [ ] 使用者管理
- [ ] 個人資料編輯
- [ ] 統計圖表整合
- [ ] 即時數據更新
- [ ] 檔案上傳功能
- [ ] 圖片管理
- [ ] 權限控制 UI

## 🚀 下一步行動

### 立即執行（P0 - 緊急）

1. **修復 request interceptor** - 確保 Token 正確傳遞
   - 檔案：`frontend/src/api/interceptors/request.js`
   - 預計時間：30 分鐘

2. **測試 API 請求** - 驗證 Authorization header
   - 使用瀏覽器開發工具檢查 Network tab
   - 預計時間：15 分鐘

3. **優化狀態恢復** - 確保路由守衛執行時狀態已恢復
   - 檔案：`frontend/src/main.js`
   - 預計時間：30 分鐘

### 短期目標（P1 - 高優先）

4. **完成文章管理頁面** - 列表、搜尋、篩選
   - 預計時間：2-3 小時

5. **實作文章編輯器** - 整合 CKEditor
   - 預計時間：3-4 小時

6. **實作個人資料頁面** - 基本資料編輯
   - 預計時間：1-2 小時

### 中期目標（P2 - 中優先）

7. **標籤管理** - CRUD 操作
8. **使用者管理** - 僅限管理員
9. **檔案上傳** - 圖片和附件

### 長期目標（P3 - 低優先）

10. **統計圖表** - Chart.js 整合
11. **即時通知** - WebSocket 或輪詢
12. **進階搜尋** - 全文搜尋
13. **多語言支援** - i18n

## 📝 結論

**核心問題**：認證狀態在頁面導航時丟失，導致路由守衛將使用者重新導向到登入頁。

**解決方向**：
1. 修復 API Token 傳遞機制
2. 優化狀態恢復時序
3. 完善路由守衛邏輯

**預計完成時間**：1-2 小時可修復核心認證問題

**後續開發**：Dashboard 功能實作預計需要 10-15 小時

---

**報告建立時間**：2025-01-06  
**狀態**：進行中  
**優先級**：🔴 P0 - 緊急
