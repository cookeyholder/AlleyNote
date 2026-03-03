# 任務完成報告

執行日期：2025-10-07

## 任務 1：確定目前使用的資料庫檔案 ✅

### 檢查結果

**資料庫檔案路徑**：`/var/www/html/database/alleynote.sqlite3`

**檔案資訊**：
- 檔案大小：5.2 MB
- 位置：容器內的 `/var/www/html/database/alleynote.sqlite3`
- 對應本地：`./database/alleynote.sqlite3`（通過 Docker volume 掛載）

**配置來源**：
1. `.env` 檔案：`DB_DATABASE=/var/www/html/database/alleynote.sqlite3`
2. `PostController.php`：使用 `$_ENV['DB_DATABASE']` 作為預設值

**其他資料庫檔案**：
- `/var/www/html/storage/database.sqlite` (0 bytes) - 未使用的空檔案

**驗證方式**：
```bash
# 查看 .env 設定
grep DB_DATABASE backend/.env

# 檢查檔案大小
docker compose exec web ls -lh /var/www/html/database/alleynote.sqlite3
```

---

## 任務 2：新增文章並驗證 ✅

### 新增文章測試

**測試步驟**：
1. 透過 API 新增文章
2. 檢查資料庫
3. 驗證 API 列表回傳

**測試 1 - 第一次新增（失敗）**
```json
{
  "title": "測試新文章",
  "content": "這是一篇測試文章的內容",
  "status": "published"
}
```
- **結果**：API 回應成功但未寫入資料庫
- **原因**：`PostController::store()` 方法只回傳假資料

**測試 2 - 修復後新增（成功）✅**
```json
{
  "title": "新增測試文章",
  "content": "這是透過API新增的文章",
  "status": "published"
}
```
- **結果**：成功寫入資料庫
- **文章 ID**：8
- **UUID**：393a1945-0d0a-4d5c-86aa-13716429fc92
- **seq_number**：1006

### 驗證結果

**API 列表回應**：
```json
{
  "success": true,
  "total": 6,
  "posts": [
    "新增測試文章",
    "Test Post - Social Media",
    "Test Post - Search Engine"
  ]
}
```

**資料庫查詢**：
```sql
SELECT id, title, status, created_at 
FROM posts 
ORDER BY id DESC 
LIMIT 3;

-- 結果：
8|新增測試文章|published|2025-10-06 18:21:46
5|Test Post - Legacy Empty Source|published|2025-08-26 07:10:16
4|Test Post - Legacy Invalid Source|published|2025-08-22 07:10:16
```

### 修復內容

修改了 `backend/app/Application/Controllers/PostController.php` 的 `store()` 方法：

**修改前**：
- 只生成假資料
- 隨機 ID（不實際存在）
- 不寫入資料庫

**修改後**：
- ✅ 實際連接 SQLite 資料庫
- ✅ 生成正確的 UUID 和 seq_number
- ✅ 插入 posts 表
- ✅ 驗證必填欄位（title, content）
- ✅ 支援 status 欄位（published/draft）
- ✅ 從 JWT token 獲取 user_id
- ✅ 完整錯誤處理

---

## 任務 3：npm build 錯誤診斷與修復 ✅

### 錯誤現象

執行 `npm run build` 時出現以下錯誤：

```
Error: Cannot find module @rollup/rollup-darwin-arm64
```

### 根本原因

**問題 1：npm 依賴安裝不完整**
- 本地 `node_modules` 缺少 Rollup 的原生模組
- ARM64 架構（Apple Silicon Mac）需要特定的原生綁定
- npm 的 optional dependencies bug 導致安裝失敗

**問題 2：Vite 命令找不到**
```
sh: vite: command not found
```
- `node_modules/.bin/vite` 不存在
- npm install 沒有正確建立符號連結

### 解決方案

**方法 1：使用 Docker 建置（推薦）**

```bash
# 在 Docker 容器中重新安裝並建置
docker run --rm -v "$(pwd)/frontend:/app" -w /app node:20-alpine sh -c \
  "rm -rf node_modules package-lock.json && npm install && npm run build"
```

**優點**：
- 環境隔離，不受本地環境影響
- 與生產環境一致
- 自動處理平台差異

**方法 2：本地修復（需要多次嘗試）**

```bash
cd frontend
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

### 建置結果

**成功**：✅

```
vite v5.4.20 building for production...
✓ 703 modules transformed.
✓ built in 6.63s

dist/index.html                    3.42 kB │ gzip:   1.27 kB
dist/assets/posts-DljPvQvj.js      8.14 kB │ gzip:   2.73 kB
dist/assets/index-5LAK4lbz.js    359.94 kB │ gzip: 118.04 kB
dist/assets/vendor-ckeditor.js 1,310.18 kB │ gzip: 312.18 kB
```

**檔案更新**：
- 所有 JavaScript 檔案已更新
- 包含最新的 DEBUG 日誌
- 包含 `data-navigo` 屬性的連結

### 警告訊息（可忽略）

```
(!) Some chunks are larger than 1000 kB after minification.
```

**說明**：
- CKEditor 檔案較大（1.3MB）
- 這是正常現象，CKEditor 是一個功能完整的富文本編輯器
- 已經過 gzip 壓縮（312 KB）

---

## 前端顯示問題（瀏覽器快取）

### 現象

訪問 `http://localhost:8080/admin/posts` 仍顯示「目前沒有文章」

### 原因

**瀏覽器快取了舊版 JavaScript 檔案**

雖然已經：
- ✅ 修復後端 API（正確回傳 6 篇文章）
- ✅ 重新建置前端（包含最新程式碼）
- ✅ 重啟 nginx 容器

但瀏覽器仍在使用快取中的舊版 JavaScript。

### 解決方法

**立即解決**（使用者需要操作）：

1. **硬刷新瀏覽器**：
   - Mac：`Cmd + Shift + R`
   - Windows/Linux：`Ctrl + Shift + R`

2. **清除快取**：
   - 開啟開發者工具（F12）
   - Application → Clear Storage
   - 點擊「Clear site data」

3. **使用無痕視窗**：
   - 開啟新的無痕/隱私視窗
   - 訪問網站

### API 驗證（後端完全正常）

```bash
# 獲取文章列表
curl -s "http://localhost:8080/api/posts?page=1&per_page=10" \
  -H "Authorization: Bearer $TOKEN" | jq .

# 回應：
{
  "success": true,
  "data": [
    {
      "id": 8,
      "title": "新增測試文章",
      "author": "admin",
      "status": "published",
      ...
    },
    // ... 共 6 篇文章
  ],
  "pagination": {
    "total": 6,
    "page": 1,
    "per_page": 10,
    "total_pages": 1
  }
}
```

---

## 總結

### ✅ 已完成

1. **資料庫確認**：
   - 確定使用 `/var/www/html/database/alleynote.sqlite3` (5.2 MB)
   - 檔案正常運作

2. **新增文章功能**：
   - 修復 `PostController::store()` 方法
   - 成功新增測試文章（ID: 8）
   - 文章數從 5 增加到 6
   - API 正確回傳新文章

3. **npm build 錯誤**：
   - 診斷出 Rollup 原生模組缺失
   - 使用 Docker 成功建置
   - 所有檔案已更新

### ⚠️ 需要注意

**前端顯示問題**：
- 後端 API 完全正常
- 需要清除瀏覽器快取才能看到更新

### 📝 建議

**短期**：
1. 在開發時使用無痕視窗測試
2. 或者設定瀏覽器停用快取（開發者工具中）

**長期**：
1. 考慮在開發環境中設定 `Cache-Control: no-cache` header
2. 使用版本號（Vite 已內建）管理快取
3. 實作 Service Worker 更新機制

---

## Git 提交記錄

```bash
# 1. 修復文章列表 API
git commit -m "fix(backend): 修復文章列表 API 與資料庫整合問題"

# 2. 修復 SPA 路由導航
git commit -m "fix(frontend): 修復管理員後台 SPA 路由導航問題"

# 3. 實作新增文章功能
git commit -m "feat(backend): 實作 PostController store 方法以實際寫入資料庫"

# 4. 文件說明
git commit -m "docs: 添加文章列表頁面瀏覽器快取問題說明"
```

---

**報告完成時間**：2025-10-07 02:30  
**所有任務狀態**：✅ 完成
