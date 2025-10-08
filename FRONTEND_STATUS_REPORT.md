# 前端現狀報告

**建立時間**：2024-10-08

## 問題描述

用戶報告無法通過瀏覽器正確訪問 http://localhost:3000，因為有 Vite 開發伺服器佔用了該端口。

## 問題根本原因

1. **端口衝突**：本機有一個 Node.js 程序（PID 66767）正在監聽 3000 端口，這是 Vite 開發伺服器
2. **Docker 容器無法綁定**：雖然 Docker Compose 配置了 nginx 容器監聽 3000 端口，但因為端口被佔用，實際上瀏覽器訪問的是 Vite 開發伺服器
3. **HTML 注入**：Vite 開發伺服器會自動在 HTML 中注入 `<script type="module" src="/@vite/client"></script>`

## 解決方案

### 已執行的步驟

1. **終止 Vite 開發伺服器**
   ```bash
   kill 66767
   ```

2. **重啟 nginx 容器**
   ```bash
   docker-compose restart nginx
   ```

3. **驗證結果**
   - ✅ 3000 端口現在由 Docker (OrbStack) 監聽
   - ✅ HTML 中不再有 Vite 注入代碼
   - ✅ 可以正常訪問純 HTML/JS/CSS 版本的前端

## 前端架構現狀

### 目錄結構

```
frontend/
├── index.html              # 主入口文件
├── css/
│   └── main.css           # 主樣式表
├── js/
│   ├── main.js            # 主程式入口
│   ├── api/               # API 客戶端
│   │   ├── client.js
│   │   ├── auth.js
│   │   ├── posts.js
│   │   ├── users.js
│   │   └── statistics.js
│   ├── components/        # UI 組件
│   │   ├── Loading.js
│   │   └── Modal.js
│   ├── pages/             # 頁面渲染
│   │   ├── public/
│   │   │   ├── home.js
│   │   │   └── login.js
│   │   └── admin/
│   │       └── dashboard.js
│   └── utils/             # 工具函數
│       ├── router.js
│       ├── toast.js
│       └── validator.js
├── html/                  # HTML 模板（如有）
└── assets/                # 靜態資源
```

### 技術棧

- **無構建工具**：純 HTML/JS/CSS，使用 ES6 模組
- **CSS 框架**：Tailwind CSS (透過 CDN)
- **富文本編輯器**：CKEditor 5 (透過 CDN)
- **圖表庫**：Chart.js (透過 CDN)
- **安全性**：DOMPurify (透過 CDN)
- **HTTP 服務器**：nginx (Docker 容器)

### 部署配置

#### Docker Compose

- **容器名稱**：alleynote_nginx
- **端口映射**：3000:80 (前端), 8080:8080 (API), 443:443 (HTTPS)
- **掛載目錄**：`./frontend:/usr/share/nginx/html`
- **配置文件**：`./docker/nginx/frontend-backend.conf`

#### Nginx 配置

**前端服務 (Port 80)**
- 根目錄：`/usr/share/nginx/html`
- SPA 路由：`try_files $uri $uri/ /index.html`
- CSP 策略：允許 CDN 資源載入
- 靜態資源快取：1年

**API 服務 (Port 8080)**
- FastCGI 代理到 PHP 容器
- CORS 設定：允許 `http://localhost:3000` 跨域訪問
- 支援 OPTIONS 預檢請求

## 前端功能狀態

### 已實現功能

#### 公開功能
- ✅ 首頁文章列表
- ✅ 文章詳情頁
- ✅ 使用者登入頁面
- ✅ 客戶端路由（自製 Router）

#### 管理後台功能
- ✅ 管理後台儀表板
- ✅ 文章管理列表
- ✅ 側邊欄導航
- ⚠️ 文章編輯器（簡化版，功能開發中）
- ⚠️ 使用者管理（簡化版，功能開發中）
- ⚠️ 角色管理（簡化版，功能開發中）
- ⚠️ 系統統計（簡化版，功能開發中）
- ⚠️ 系統設定（簡化版，功能開發中）

### 尚未完成的功能

1. **文章編輯器**
   - 需要整合 CKEditor
   - 需要實現圖片上傳
   - 需要實現草稿自動儲存

2. **使用者管理**
   - 完整的 CRUD 介面
   - 角色權限設定
   - 使用者詳情頁面

3. **角色管理**
   - 角色列表
   - 權限分配介面
   - 角色新增/編輯/刪除

4. **系統統計**
   - 整合 Chart.js
   - 資料視覺化
   - 統計報表

5. **系統設定**
   - 系統參數設定
   - 郵件設定
   - 快取設定

## API 整合狀態

### API 基礎設定

**API 端點**：`http://localhost:8080`

**認證機制**：
- JWT Token 儲存在 `localStorage`
- 自動在請求標頭中加入 `Authorization: Bearer <token>`
- Token 過期自動跳轉登入頁面

### API 客戶端模組

#### `api/client.js`
基礎 HTTP 客戶端，處理：
- 請求攔截器（加入認證標頭）
- 回應攔截器（處理錯誤）
- 統一錯誤處理

#### `api/auth.js`
認證相關 API：
- `login(username, password)` - 登入
- `logout()` - 登出
- `getCurrentUser()` - 取得當前使用者
- `isAuthenticated()` - 檢查認證狀態

#### `api/posts.js`
文章相關 API：
- `getPublicPosts(params)` - 取得公開文章列表
- `getPublicPost(id)` - 取得公開文章詳情
- `getAll(params)` - 取得所有文章（管理）
- `getById(id)` - 取得文章詳情（管理）
- `create(data)` - 建立文章
- `update(id, data)` - 更新文章
- `delete(id)` - 刪除文章

#### `api/users.js`
使用者相關 API（基礎框架）

#### `api/statistics.js`
統計相關 API（基礎框架）

## 用戶體驗組件

### Loading（載入中）
- 全螢幕載入指示器
- 自動在頁面切換時顯示

### Toast（通知）
- 成功、錯誤、警告、資訊四種類型
- 自動消失（3秒）
- 支援多條通知堆疊

### Modal（對話框）
- 確認對話框
- 自訂內容對話框
- 背景遮罩

### Router（路由）
- SPA 路由管理
- 支援動態參數（如 `/post/:id`）
- 歷史記錄管理
- 連結攔截（`data-link` 屬性）

## 安全性措施

1. **CSP (Content Security Policy)**
   - 限制資源來源
   - 允許特定 CDN
   - 防止 XSS 攻擊

2. **其他安全標頭**
   - X-Frame-Options: SAMEORIGIN
   - X-XSS-Protection: 1; mode=block
   - X-Content-Type-Options: nosniff
   - Referrer-Policy: strict-origin-when-cross-origin

3. **DOMPurify**
   - 清理使用者輸入的 HTML
   - 防止 XSS 攻擊

## 建議的改進事項

### 短期（1-2週）

1. **完成文章編輯器**
   - 整合 CKEditor 5
   - 實現圖片上傳功能
   - 實現草稿自動儲存
   - 修復編輯文章時無法載入原內容的問題

2. **完成使用者管理**
   - 實現完整的 CRUD 介面
   - 實現角色分配功能
   - 實現使用者詳情頁面

3. **修復已知問題**
   - 未發布文章不應在首頁顯示（需要檢查 API 回應）
   - 編輯文章時帶入原內容

### 中期（3-4週）

1. **完成角色管理模組**
   - 角色列表頁面
   - 權限分配介面
   - 角色 CRUD 功能

2. **完成系統統計模組**
   - 整合 Chart.js
   - 實現各種統計圖表
   - 實現資料匯出功能

3. **完成系統設定模組**
   - 系統參數設定介面
   - 郵件設定介面
   - 快取管理介面

### 長期（1-2月）

1. **效能優化**
   - 實現程式碼分割
   - 實現懶加載
   - 優化首次載入時間

2. **PWA 支援**
   - Service Worker
   - 離線支援
   - 推送通知

3. **國際化（i18n）**
   - 多語言支援
   - 語言切換功能

4. **無障礙（a11y）**
   - ARIA 標籤
   - 鍵盤導航
   - 螢幕閱讀器支援

## 如何繼續開發

### 1. 確保 Docker 容器正常運行

```bash
cd /Users/cookeyholder/projects/AlleyNote
docker-compose up -d
```

### 2. 確認沒有 Vite 開發伺服器運行

```bash
lsof -i :3000
# 如果有 Node.js 程序，使用 kill <PID> 終止
```

### 3. 訪問前端

- 前端：http://localhost:3000
- API：http://localhost:8080

### 4. 開發流程

1. 修改 `frontend/` 目錄下的檔案
2. 由於是 bind mount，修改會立即反映在容器中
3. 重新整理瀏覽器即可看到變更
4. 不需要構建步驟

### 5. 除錯工具

- Chrome DevTools
- 瀏覽器控制台
- Network 標籤（檢查 API 請求）
- nginx 日誌：`docker-compose logs -f nginx`

## 注意事項

1. **不要使用 Vite 開發伺服器**
   - 前端已經是純 HTML/JS/CSS
   - 直接由 nginx 提供服務
   - 修改會立即生效，不需要熱更新

2. **模組載入**
   - 使用 ES6 模組 (`import`/`export`)
   - 瀏覽器原生支援
   - 需要在 script 標籤中加入 `type="module"`

3. **CORS 設定**
   - API 已設定允許 `http://localhost:3000` 跨域
   - 如果需要其他來源，修改 `docker/nginx/frontend-backend.conf`

4. **快取問題**
   - 靜態資源快取 1 年
   - 開發時可能需要強制重新整理（Cmd+Shift+R）
   - 或在 DevTools 中停用快取

## 總結

前端已經成功從 Vite 開發環境遷移到純 HTML/JS/CSS 的生產環境。目前可以正常訪問 http://localhost:3000，並且所有基礎功能都已實現。接下來需要完成管理後台的各項功能，特別是文章編輯器、使用者管理和角色管理模組。

開發體驗方面，雖然沒有 Vite 的熱更新功能，但由於是 bind mount，修改會立即反映，只需重新整理瀏覽器即可，對開發影響不大。
