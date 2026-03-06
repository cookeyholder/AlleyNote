# AlleyNote 前端專案總覽

## 📋 專案資訊

- **專案名稱**: AlleyNote Frontend
- **版本**: 1.0.0
- **狀態**: ✅ 生產就緒
- **技術棧**: 原生 HTML/JavaScript/CSS + Tailwind CSS (CDN)
- **開發週期**: 4 週（2024年9月 - 2024年10月）

---

## 🎯 專案目標

建立一個現代化、安全、高效能的前端應用程式，提供：

1. **公開訪客介面** - 文章瀏覽和搜尋
2. **管理員後台** - 文章管理和發布
3. **主管理員功能** - 系統管理和統計

---

## 🏗️ 系統架構

### 技術選型

```
前端框架: Vanilla JavaScript (ES6+)
建構工具: 無構建工具（原生 ES6 Modules）
CSS 框架: Tailwind CSS 4.x
路由: Navigo 8.x
HTTP 客戶端: Fetch API 1.6
編輯器: CKEditor 5
圖表: Chart.js 4.x
```

### 架構層級

```
┌─────────────────────────────────────────┐
│             Presentation Layer          │
│         (Pages & Components)            │
├─────────────────────────────────────────┤
│           Application Layer             │
│        (Router & Store & Utils)         │
├─────────────────────────────────────────┤
│             Service Layer               │
│           (API Modules)                 │
├─────────────────────────────────────────┤
│          Infrastructure Layer           │
│      (HTTP Client & Storage)            │
└─────────────────────────────────────────┘
```

### 目錄結構

```
frontend/
├── public/                 # 靜態資源
│   ├── manifest.json      # PWA Manifest
│   ├── sw.js              # Service Worker
│   └── offline.html       # 離線頁面
│
├── src/
│   ├── api/               # API 層
│   │   ├── client.js     # HTTP 客戶端
│   │   ├── interceptors/ # 攔截器
│   │   └── modules/      # API 模組
│   │
│   ├── components/        # UI 組件
│   │   ├── CKEditorWrapper.js
│   │   ├── ConfirmationDialog.js
│   │   ├── Loading.js
│   │   └── Modal.js
│   │
│   ├── layouts/          # 佈局組件
│   │   ├── DashboardLayout.js
│   │   └── PublicLayout.js
│   │
│   ├── pages/            # 頁面組件
│   │   ├── admin/       # 後台頁面
│   │   └── ...          # 公開頁面
│   │
│   ├── router/           # 路由配置
│   ├── store/            # 狀態管理
│   ├── utils/            # 工具函式
│   ├── tests/            # 測試檔案
│   └── main.js           # 應用程式入口
│
├── .env.*                # 環境變數
├── Dockerfile            # Docker 配置
├── nginx.conf            # Nginx 配置
├── package.json          # 專案配置
├── （無需配置檔案）        # 無需配置（原生技術）
└── tailwind.config.js    # Tailwind 配置
```

---

## 🔐 安全性設計

### 1. 認證與授權

- **JWT Token 認證**
  - SessionStorage 儲存
  - 自動刷新機制
  - 過期處理

- **CSRF 防護**
  - 自動加入 CSRF Token
  - Token 自動更新
  - 失效處理

- **權限控制**
  - 路由守衛
  - 角色驗證
  - 操作權限檢查

### 2. XSS 防護

- DOMPurify HTML 淨化
- textContent 取代 innerHTML
- URL 參數編碼
- 避免 eval() 和 Function()

### 3. 資料驗證

- 前端表單驗證
- 檔案類型與大小驗證
- SQL Injection 防護
- validator.js 整合

### 4. 安全標頭

```nginx
Content-Security-Policy
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security
```

---

## ⚡ 效能優化

### 1. 建構優化

- **Code Splitting**
  - 路由懶加載
  - 第三方庫獨立打包
  - Chart.js (~60KB)
  - CKEditor (~150KB)

- **壓縮與最小化**
  - Terser 壓縮
  - 移除 console & debugger
  - CSS 最小化

### 2. 載入優化

- **圖片懶加載**
  - Intersection Observer API
  - 背景圖片懶加載
  - Placeholder 支援

- **資源預載入**
  - Critical CSS 內聯
  - Font preload
  - 依賴預優化

### 3. 快取策略

- **Service Worker**
  - Cache First（靜態資源）
  - Network First（API）
  - Stale While Revalidate

- **HTTP 快取**
  - 靜態資源快取 1 年
  - 檔案 hash 命名
  - Gzip 壓縮

### 效能指標（目標）

```
LCP (Largest Contentful Paint): < 2.5s
FID (First Input Delay): < 100ms
CLS (Cumulative Layout Shift): < 0.1
TTFB (Time to First Byte): < 800ms
Lighthouse Performance: > 95
```

---

## 🧪 測試策略

### 1. 單元測試（Jest 或瀏覽器原生測試）

```
TokenManager: 13 測試案例
FormValidator: 26 測試案例
StorageManager: 60 測試案例
Store: 50 測試案例

總計: 180+ 測試案例
覆蓋率: 85%+
```

### 2. E2E 測試（Playwright）

```
登入流程: 7 測試案例
文章管理: 30 測試案例
使用者管理: 15 測試案例
系統功能: 20 測試案例

跨瀏覽器: Chrome, Firefox, Safari
行動裝置: Pixel 5, iPhone 12
```

### 3. 測試指令

```bash
# 單元測試
npm test                 # 互動模式
npm run test:run         # 執行一次
npm run test:coverage    # 覆蓋率報告
npm run test:ui          # 測試 UI

# E2E 測試
npm run test:e2e         # 執行 E2E
npm run test:e2e:ui      # E2E UI
```

---

## 📊 監控與分析

### 1. Sentry 錯誤追蹤

```javascript
// 配置
VITE_SENTRY_DSN=https://xxx@sentry.io/xxx
VITE_SENTRY_SAMPLE_RATE=1.0

// 功能
- 自動捕獲例外
- Promise rejection 追蹤
- 麵包屑記錄
- 使用者資訊關聯
- Session Replay
```

### 2. Google Analytics

```javascript
// 配置
VITE_GA_TRACKING_ID =
  UA -
  XXXXX -
  X -
  // 追蹤項目
  頁面瀏覽 -
  使用者互動 -
  表單提交 -
  搜尋行為 -
  轉換事件;
```

### 3. Web Vitals 監控

```javascript
// 監控指標
CLS - Cumulative Layout Shift
FID - First Input Delay
FCP - First Contentful Paint
LCP - Largest Contentful Paint
TTFB - Time to First Byte
INP - Interaction to Next Paint

// 自動報告到 GA 和 Sentry
```

---

## 🚀 部署配置

> ⚠️ 備註：下方以現行「Docker + Nginx 靜態站點」流程為主。舊版 `dist/` 與 preview 描述不再適用。

### 開發環境

```bash
# 啟動開發伺服器
直接編輯文件並刷新瀏覽器

# 訪問
http://localhost:3000
```

### 建構生產版本

```bash
# 前端目前無獨立 build 產物
# 由 Docker Nginx 直接提供 frontend/ 內容
docker compose up -d
```

### Docker 部署

```dockerfile
# 目前部署由專案根目錄 docker-compose.yml 管理
# 前端服務對外端口為 3000
```

```bash
# 使用 Docker Compose
docker compose up -d
```

### Nginx 配置重點

```nginx
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;

    # Gzip 壓縮
    gzip on;
    gzip_types text/css application/javascript application/json;

    # SPA 路由支援
    location / {
        try_files $uri $uri/ /index.html;
    }

    # 靜態資源快取
    location /assets/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 安全標頭
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
}
```

---

## 🎨 UI/UX 設計

### 設計系統

- **色彩**
  - 主色: #667eea（藍紫色）
  - 輔助: #764ba2（紫色）
  - 灰階: modern-50 ~ modern-900

- **字體**
  - Inter（無襯線字體）
  - 權重: 300-900

- **間距系統**
  - Tailwind 預設間距（4px 倍數）

- **圓角**
  - 小: 0.375rem
  - 中: 0.5rem
  - 大: 1rem
  - 特大: 2rem

### 響應式斷點

```css
/* 手機版 */
< 640px (sm)

/* 平板版 */
640px - 1024px (md, lg)

/* 桌面版 */
> 1024px (xl, 2xl)
```

### 無障礙性

- WCAG 2.1 AA 標準
- 鍵盤導航支援
- 螢幕閱讀器優化
- 足夠的對比度
- 語意化 HTML

---

## 📦 依賴套件

### 生產依賴

```json
{
  "@ckeditor/ckeditor5-build-classic": "^41.4.2",
  "@ckeditor/ckeditor5-upload": "^47.0.0",
  "axios": "^1.6.0",
  "chart.js": "^4.5.0",
  "dompurify": "^3.2.7",
  "navigo": "^8.11.1",
  "validator": "^13.15.15"
}
```

### 開發依賴

```json
{
  "@playwright/test": "^1.55.1",
  "@jest 或瀏覽器原生測試/ui": "^3.2.4",
  "autoprefixer": "^10.4.21",
  "eslint": "^8.50.0",
  "prettier": "^3.6.2",
  "tailwindcss": "^4.1.14",
  （無需此依賴）,
  "jest 或瀏覽器原生測試": "^3.2.4"
}
```

---

## 📖 文件索引

### 開發文件

1. **[README.md](../../frontend/README.md)**
   - 專案簡介
   - 快速開始
   - 開發指南

2. **[API_INTEGRATION_GUIDE.md](./API_INTEGRATION_GUIDE.md)**
   - API Client 架構
   - 請求/回應攔截器
   - 錯誤處理

3. **[STATE_MANAGEMENT_STRATEGY.md](./STATE_MANAGEMENT_STRATEGY.md)**
   - Store 設計
   - 狀態流
   - 持久化

4. **[SECURITY_CHECKLIST.md](./SECURITY_CHECKLIST.md)**
   - 安全規範
   - 檢查清單
   - 最佳實踐

5. **[TESTING_STRATEGY.md](./TESTING_STRATEGY.md)**
   - 測試計劃
   - 測試工具
   - 測試腳本

6. **[DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md)**
   - 建構流程
   - Docker 部署
   - CI/CD 配置

### 規劃文件

1. **[FRONTEND_INTERFACE_DESIGN_SPECIFICATION.md](./FRONTEND_INTERFACE_DESIGN_SPECIFICATION.md)**
   - 設計哲學
   - 佈局設計
   - 組件規範

2. **[FRONTEND_TODO_LIST.md](./FRONTEND_TODO_LIST.md)**
   - 開發任務
   - 檢查清單
   - 進度追蹤

### 進度報告

1. **[DEVELOPMENT_PROGRESS.md](./DEVELOPMENT_PROGRESS.md)**
   - Week 1-4 完成狀況
   - 技術亮點
   - 待完成項目

2. **[FINAL_COMPLETION_REPORT.md](./FINAL_COMPLETION_REPORT.md)**
   - 最終成果
   - 完整統計
   - 專案總結

---

## 🔄 開發流程

### 分支策略

```
main                  # 生產環境
└── develop          # 開發環境
    └── feature/*    # 功能分支
    └── fix/*        # 修復分支
```

### Commit 規範

```
feat: 新增功能
fix: 修復錯誤
docs: 文件更新
style: 程式碼格式
refactor: 重構
test: 測試相關
chore: 建構/工具相關
perf: 效能優化
```

### Code Review 檢查項目

- [ ] 程式碼符合 ESLint 規範
- [ ] 通過 Prettier 格式化
- [ ] 所有測試通過
- [ ] 無安全漏洞
- [ ] 效能影響評估
- [ ] 文件已更新

---

## 🐛 除錯指南

### 開發工具

```javascript
// 檢視 Store 狀態
window.__STORE__ = globalStore;

// 檢視效能報告
import { getPerformanceReport } from "./utils/webVitals.js";
console.log(getPerformanceReport());

// 檢視快取
caches.keys().then(console.log);
```

### 常見問題

1. **Token 過期**
   - 檢查 `TokenManager` 配置
   - 確認刷新 API 正常

2. **CSRF Token 失效**
   - 檢查 Cookie 設定
   - 確認後端配置

3. **圖片上傳失敗**
   - 檢查檔案大小限制
   - 確認 MIME type

4. **路由不工作**
   - 檢查 Nginx 配置
   - 確認 `try_files` 設定

---

## 📞 聯絡資訊

- **專案負責人**: AlleyNote Team
- **技術支援**: GitHub Issues
- **文件問題**: 請提交 PR

---

## 📄 授權

MIT License

---

**最後更新**: 2024年10月3日
**文件版本**: 1.0.0
**專案狀態**: ✅ 生產就緒
