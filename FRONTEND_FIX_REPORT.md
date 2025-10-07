# 前端文章顯示問題修復報告

## 問題描述
前端首頁和管理後台的文章列表頁面無法顯示任何文章，始終顯示「共 0 篇文章」或「目前沒有文章」，即使後端 API 正確回傳文章資料。

## 問題原因

### 1. 資料庫欄位缺失（已修復）
- **問題**：`posts` 表缺少 `excerpt` 欄位
- **影響**：文章更新時出現 500 錯誤
- **解決方案**：執行 `ALTER TABLE posts ADD COLUMN excerpt TEXT NULL;`

### 2. 前端建置檔案缺失（主要問題）
- **問題**：`frontend/dist/` 目錄完全不存在
- **原因**：
  1. 開發依賴（devDependencies）未正確安裝
  2. Vite 建置工具缺失
  3. npm install 時未包含 dev dependencies
  
- **影響**：Nginx 無法提供前端靜態檔案，導致功能異常

### 3. Service Worker 快取問題
- **問題**：瀏覽器 Service Worker 快取了舊版本的前端程式碼
- **影響**：即使重新建置後，瀏覽器仍載入舊版本檔案

## 修復步驟

### 步驟 1：新增資料庫欄位
```bash
docker compose exec -T web sqlite3 /var/www/html/database/alleynote.sqlite3 \
  "ALTER TABLE posts ADD COLUMN excerpt TEXT NULL;"
```

### 步驟 2：清除並重新安裝依賴
```bash
cd frontend
rm -rf node_modules package-lock.json
npm install --include=dev
```

**關鍵點**：必須使用 `--include=dev` 確保安裝 `devDependencies`，包括 Vite 建置工具。

### 步驟 3：建置前端
```bash
cd frontend
./node_modules/.bin/vite build
```

建置成功後會產生：
- `frontend/dist/index.html`
- `frontend/dist/assets/*.js`
- `frontend/dist/assets/*.css`

### 步驟 4：重啟 Nginx 容器
```bash
docker compose restart nginx
```

### 步驟 5：清除瀏覽器快取
在瀏覽器中：
1. 開啟開發者工具（F12）
2. 前往 Application > Service Workers
3. 點擊「Unregister」清除 Service Worker
4. 硬性重新整理頁面（Ctrl+Shift+R 或 Cmd+Shift+R）

或使用 JavaScript：
```javascript
// 清除所有 Service Workers
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(registrations => {
    registrations.forEach(registration => registration.unregister());
  });
}
```

## 驗證結果

### API 端點測試
```bash
# 測試文章列表 API
curl "http://localhost:8000/api/posts?status=published&page=1&per_page=5"
```

**結果**：正常回傳 10 篇已發布文章

```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "title": "【已編輯】測試文章 - Playwright 自動化測試完整流程",
      ...
    },
    ...
  ],
  "pagination": {
    "total": 10,
    "page": 1,
    "per_page": 5,
    "total_pages": 2
  }
}
```

### 檔案結構驗證
```bash
# 檢查建置檔案
ls -lh frontend/dist/

# 輸出：
# index.html
# manifest.json
# offline.html
# sw.js
# assets/
#   - home-OIvLl_Ch.js
#   - posts-DWERDdYQ.js
#   - index-CHpHuxYQ.js
#   - vendor-core-DW9qjkZC.js
#   ...
```

### 容器內檔案驗證
```bash
# 檢查 Nginx 容器中的檔案
docker compose exec nginx ls -la /usr/share/nginx/html/assets/

# 確認檔案已正確掛載
```

## 根本原因分析

1. **依賴管理問題**
   - 專案使用 Vite 作為建置工具，Vite 被定義在 `devDependencies` 中
   - 執行 `npm install` 時預設不會安裝 `devDependencies`
   - 導致無法執行 `npm run build`

2. **建置流程缺失**
   - Docker Compose 配置中，Nginx 期望從 `./frontend/dist` 掛載檔案
   - 但 `dist` 目錄需要透過建置生成，專案中未自動化這個步驟
   - 開發者需要手動執行建置

3. **Service Worker 快取策略**
   - 應用程式啟用了 Service Worker 進行離線快取
   - 更新前端程式碼後，SW 繼續提供舊版本快取
   - 需要在更新時主動清除 SW 快取

## 建議改進

### 1. 自動化建置流程
建議在 `docker-compose.yml` 中增加建置步驟，或創建部署腳本：

```yaml
services:
  frontend-builder:
    build:
      context: ./frontend
      dockerfile: Dockerfile.build
    volumes:
      - ./frontend:/app
    command: npm run build
```

### 2. 更新 npm 腳本
在根目錄 `package.json` 中確保建置腳本正確：

```json
{
  "scripts": {
    "frontend:build": "cd frontend && npm install --include=dev && npm run build",
    "frontend:dev": "cd frontend && npm run dev",
    "build": "npm run frontend:build"
  }
}
```

### 3. Service Worker 版本控制
在 `sw.js` 中增加版本號，確保更新時強制刷新：

```javascript
const CACHE_VERSION = 'v1.1.0';  // 每次更新遞增
```

### 4. 開發文檔更新
更新 `README.md`，明確說明：
- 首次設置需要執行 `npm run frontend:build`
- 修改前端程式碼後需要重新建置
- 如何清除瀏覽器快取

## 結論

問題已完全解決。核心原因是前端建置檔案缺失，導致 Nginx 無法正確提供前端資源。通過重新安裝依賴（包括 devDependencies）並執行建置，問題得到解決。

**關鍵指令總結：**
```bash
# 1. 新增資料庫欄位
docker compose exec -T web sqlite3 /var/www/html/database/alleynote.sqlite3 \
  "ALTER TABLE posts ADD COLUMN excerpt TEXT NULL;"

# 2. 重新建置前端
cd frontend
rm -rf node_modules
npm install --include=dev
npm run build

# 3. 重啟服務
docker compose restart nginx

# 4. 清除瀏覽器快取（在瀏覽器中執行）
```

**驗證方式：**
- 訪問 http://localhost:8000 查看首頁是否顯示文章
- 登入後台 http://localhost:8000/admin/posts 查看文章列表
- 檢查瀏覽器開發者工具 Network 面板，確認載入正確的 JS 檔案

---

**修復日期**：2025-10-07  
**修復人員**：AI Assistant (Claude)  
**測試狀態**：✅ 已驗證
