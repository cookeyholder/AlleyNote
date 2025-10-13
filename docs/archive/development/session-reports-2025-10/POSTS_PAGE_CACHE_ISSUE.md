# 文章列表頁面顯示問題解決方案

## 問題現象

訪問 `http://localhost:8080/admin/posts` 時，頁面顯示「目前沒有文章」，但資料庫中實際有 5 篇測試文章。

## 根本原因

**瀏覽器快取了舊版本的 JavaScript 檔案**

API 後端已經修復並能正確回傳文章資料，但前端 JavaScript 檔案被瀏覽器快取，仍在使用舊的程式碼邏輯。

## API 驗證（已正常）

```bash
# 測試 API 回傳
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}' \
  | jq -r '.access_token')

curl -s "http://localhost:8080/api/posts?page=1&per_page=10" \
  -H "Authorization: Bearer $TOKEN" | jq '.'
```

**回傳結果**：
```json
{
  "success": true,
  "data": [
    {
      "id": 3,
      "title": "Test Post - Social Media",
      "content": "This post was shared on social media.",
      "status": "published",
      "user_id": 1,
      "created_at": "2025-09-03 07:10:16",
      "updated_at": null,
      "author": "admin"
    },
    // ... 共 5 篇文章
  ],
  "pagination": {
    "total": 5,
    "page": 1,
    "per_page": 10,
    "total_pages": 1
  },
  "timestamp": "2025-10-07T02:05:57+08:00"
}
```

## 解決方案

### 方法 1：硬刷新瀏覽器（推薦）

1. 在瀏覽器中按下：
   - **Mac**: `Cmd + Shift + R`
   - **Windows/Linux**: `Ctrl + Shift + R`
   
2. 或者在開發者工具中：
   - 右鍵點擊「重新載入」按鈕
   - 選擇「清空快取並強制重新載入」

### 方法 2：使用無痕視窗

1. 開啟無痕/隱私瀏覽視窗
2. 訪問 `http://localhost:8080/admin/posts`
3. 重新登入並查看文章列表

### 方法 3：清除快取（開發者工具）

1. 開啟開發者工具（F12）
2. 前往 **Application** 標籤
3. 在左側選單中：
   - 清除 **Cache Storage**
   - 清除 **Service Workers**（如果有）
   - 清除 **Local Storage** 中的舊資料（可選）
4. 重新載入頁面

### 方法 4：修改前端配置（永久解決）

在開發環境中，可以修改 Vite 配置來禁用快取：

```js
// frontend/vite.config.js
export default {
  server: {
    headers: {
      'Cache-Control': 'no-store'
    }
  }
}
```

## 已完成的修復

1. ✅ **資料庫**：添加 `refresh_tokens.revoked_reason` 欄位
2. ✅ **後端 API**：修改 `PostController.php` 從資料庫讀取真實文章
3. ✅ **路由導航**：添加 `data-navigo` 屬性和 `router.updatePageLinks()`
4. ✅ **API 回應格式**：使用 `paginatedResponse` 回傳正確的資料結構

## 驗證步驟

執行硬刷新後，你應該能看到：

1. **文章列表頁面**顯示 5 篇測試文章：
   - Test Post - Social Media
   - Test Post - Search Engine  
   - Test Post - Direct Access
   - Test Post - Legacy Empty Source
   - Test Post - Legacy Invalid Source

2. 每篇文章都有：
   - 標題
   - 狀態（已發布/草稿）
   - 作者名稱（admin 或其他）
   - 建立時間
   - 操作按鈕（編輯、發布/轉草稿、刪除）

3. **分頁資訊**顯示「第 1 頁，共 1 頁」

## 技術細節

### 瀏覽器快取策略

現代瀏覽器會快取 JavaScript 檔案以提升效能，但這在開發過程中可能造成問題。快取鍵通常包含：
- URL
- 檔案名稱
- 版本號（如果有）

### 為什麼硬刷新有效

硬刷新（Cmd+Shift+R / Ctrl+Shift+R）會：
1. 忽略所有快取
2. 強制從伺服器重新下載所有資源
3. 清除記憶體快取

### Service Worker 的影響

如果應用程式使用了 Service Worker（PWA 功能），它會額外快取資源。需要：
1. 在開發者工具中手動取消註冊
2. 或者更新 Service Worker 版本號

## 後續建議

1. **開發環境**：考慮在 nginx 配置中對 `.js` 檔案設定 `no-cache` header
2. **生產環境**：使用版本號或 hash 來管理快取失效（Vite 已內建此功能）
3. **測試**：建立 E2E 測試來驗證文章列表功能

---

**更新時間**: 2025-10-07  
**狀態**: 已修復（需要清除瀏覽器快取）
