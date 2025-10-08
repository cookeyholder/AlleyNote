# AlleyNote 前端應用程式

> 基於 Vite + Tailwind CSS + 原生 JavaScript 的現代化前端應用

![開發狀態](https://img.shields.io/badge/開發狀態-85%25完成-brightgreen)
![測試覆蓋率](https://img.shields.io/badge/測試覆蓋率-75%25-green)
![Node.js](https://img.shields.io/badge/Node.js-18%2B-blue)
![License](https://img.shields.io/badge/license-MIT-blue)

## 📋 功能特色

### ✅ 已完成的核心功能（85%）

#### 🏗️ 基礎架構
- ✅ Vite + Tailwind CSS 技術棧
- ✅ 模組化程式碼組織
- ✅ API Client 統一管理
- ✅ 路由系統與守衛（Navigo）
- ✅ 輕量級狀態管理
- ✅ Toast 通知系統

#### 🔐 安全性機制
- ✅ JWT Token 自動刷新（Promise 鎖機制）
- ✅ CSRF Token 自動加入
- ✅ XSS 防護（DOMPurify）
- ✅ 安全標頭配置（CSP、X-Frame-Options 等）

#### 🎨 使用者介面
- ✅ 響應式後台佈局（RWD）
- ✅ 公開頁面（首頁、文章內頁、登入）
- ✅ 管理後台（7 個功能頁面）
- ✅ 統一的設計風格

#### 📝 內容管理
- ✅ 文章管理（CRUD、分頁、搜尋、篩選）
- ✅ CKEditor 5 富文本編輯器
- ✅ 圖片上傳功能（附件管理）
- ✅ 標籤管理（CRUD、自動配色）
- ✅ 文章內頁（DOMPurify 淨化）

#### 👥 使用者管理
- ✅ 使用者 CRUD 操作
- ✅ 角色管理（管理員/主管理員）
- ✅ 權限控制
- ✅ 個人資料管理（修改密碼、個人資訊）

#### 📊 系統統計
- ✅ Chart.js 圖表整合
- ✅ 文章發布趨勢（折線圖）
- ✅ 文章狀態分佈（圓餅圖）
- ✅ 熱門文章排行榜
- ✅ 時間範圍篩選

#### 🧪 測試與品質
- ✅ Vitest 單元測試框架（39 個測試案例）
- ✅ Playwright E2E 測試（7 個測試案例）
- ✅ ESLint + Prettier
- ✅ 測試覆蓋率 75%

#### ⚡ 效能優化
- ✅ Code Splitting（第三方套件獨立打包）
- ✅ 路由懶加載
- ✅ 資源壓縮與最小化（Terser）
- ✅ Gzip 壓縮
- ✅ 靜態資源快取（1 年）

#### 🚀 部署配置
- ✅ Docker 容器化（多階段建構）
- ✅ Nginx 優化配置
- ✅ GitHub Actions CI/CD（7 個工作階段）
- ✅ 自動化測試流程

### 🚧 待完成功能（15%）

#### 🔍 監控與分析
- ⏳ Sentry 錯誤追蹤整合
- ⏳ Google Analytics 整合
- ⏳ Web Vitals 監控

#### 📱 PWA 支援
- ⏳ Service Worker 實作
- ⏳ 離線功能支援
- ⏳ 桌面安裝提示

#### 📚 文件完善
- ⏳ 元件使用說明
- ⏳ API 使用範例
- ⏳ 維護指南

## 🚀 快速開始

### 前置需求

- Node.js 18+
- npm 9+
- Docker（可選，用於容器化部署）

### 本地開發

```bash
# 1. 安裝依賴
cd frontend
npm install

# 2. 啟動開發伺服器
npm run dev

# 3. 開啟瀏覽器
# http://localhost:5173
```
```

### 測試

```bash
# 執行單元測試
npm run test

# 測試覆蓋率報告
npm run test:coverage

# 執行 E2E 測試
npm run test:e2e

# E2E 測試 UI 模式
npm run test:e2e:ui
```

### 程式碼品質

```bash
# 執行 ESLint
npm run lint

# 自動修復 ESLint 錯誤
npm run lint:fix

# 執行 Prettier
npm run format

# 檢查格式
npm run format:check
```

### Docker 部署

```bash
# 建構 Docker 映像
docker build -t alleynote-frontend .

# 執行容器
docker run -p 80:80 alleynote-frontend

# 使用 Docker Compose
docker compose up -d
```

## 📁 專案結構

```
frontend/
├── src/
│   ├── api/                    # API 相關
│   │   ├── client.js           # Axios 客戶端
│   │   ├── config.js           # API 配置
│   │   ├── errors.js           # 錯誤處理
│   │   ├── interceptors/       # 請求/回應攔截器
│   │   └── modules/            # API 模組
│   │       ├── auth.js         # 認證 API
│   │       ├── posts.js        # 文章 API
│   │       ├── users.js        # 使用者 API
│   │       ├── attachments.js  # 附件 API
│   │       └── statistics.js   # 統計 API
│   ├── components/             # 可重用組件
│   │   ├── Modal.js            # 模態框
│   │   ├── Loading.js          # 載入動畫
│   │   └── CKEditorWrapper.js  # CKEditor 包裝器
│   ├── layouts/                # 佈局
│   │   ├── PublicLayout.js     # 公開佈局
│   │   └── DashboardLayout.js  # 後台佈局
│   ├── pages/                  # 頁面
│   │   ├── home.js             # 首頁
│   │   ├── post.js             # 文章內頁
│   │   ├── login.js            # 登入頁
│   │   ├── notFound.js         # 404 頁面
│   │   └── admin/              # 後台頁面
│   │       ├── dashboard.js    # 儀表板
│   │       ├── posts.js        # 文章列表
│   │       ├── postEditor.js   # 文章編輯器
│   │       ├── users.js        # 使用者管理
│   │       ├── tags.js         # 標籤管理
│   │       ├── statistics.js   # 系統統計
│   │       └── profile.js      # 個人資料
│   ├── router/                 # 路由
│   │   └── index.js            # 路由配置
│   ├── store/                  # 狀態管理
│   │   ├── Store.js            # Store 類別
│   │   └── globalStore.js      # 全域 Store
│   ├── utils/                  # 工具函式
│   │   ├── tokenManager.js     # JWT Token 管理
│   │   ├── csrfManager.js      # CSRF Token 管理
│   │   ├── storageManager.js   # Storage 管理
│   │   ├── formValidator.js    # 表單驗證
│   │   └── toast.js            # Toast 通知
│   ├── styles/                 # 樣式
│   ├── tests/                  # 測試
│   │   ├── setup.js            # 測試設定
│   │   ├── unit/               # 單元測試
│   │   ├── integration/        # 整合測試
│   │   └── e2e/                # E2E 測試
│   ├── main.js                 # 應用程式入口
│   └── style.css               # 全域樣式
├── public/                     # 靜態資源
│   └── index.html              # HTML 模板
├── dist/                       # 建構產物
├── .env.development            # 開發環境變數
├── .env.production             # 生產環境變數
├── vite.config.js              # Vite 配置
├── vitest.config.js            # Vitest 配置
├── playwright.config.js        # Playwright 配置
├── tailwind.config.js          # Tailwind 配置
├── Dockerfile                  # Docker 配置
├── nginx.conf                  # Nginx 配置
├── package.json                # 專案配置
└── README.md                   # 說明文件
```

## 🎨 技術棧

### 核心框架
- **建構工具**: Vite 5.x（極速的開發體驗）
- **CSS 框架**: Tailwind CSS 4.x（實用優先的 CSS）
- **JavaScript**: 原生 ES6+（輕量、快速）

### 主要依賴
- **HTTP 客戶端**: Axios 1.6+（HTTP 請求）
- **路由**: Navigo 8.x（SPA 路由）
- **編輯器**: CKEditor 5（富文本編輯）
- **圖表**: Chart.js 4.x（資料視覺化）
- **安全**: DOMPurify、Validator.js

### 開發工具
- **測試**: Vitest 3.x（單元測試）、Playwright 1.x（E2E 測試）
- **程式碼品質**: ESLint、Prettier
- **Git Hooks**: Husky、Lint-staged

## 🔧 配置

### 環境變數

複製 `.env.example` 並建立以下檔案：

- `.env.development` - 開發環境
- `.env.staging` - 測試環境
- `.env.production` - 生產環境

### API 配置

在 `.env` 中設定：

```env
ALLEYNOTE_API_BASE_URL=http://localhost:8080/api
ALLEYNOTE_API_TIMEOUT=30000
ALLEYNOTE_ENABLE_API_LOGGER=true
```

## 📊 效能指標

### 建構產物

```
dist/
├── index.html          : ~2 KB
├── assets/
│   ├── index-[hash].js  : ~180 KB (gzip: ~60 KB)
│   ├── index-[hash].css : ~5 KB (gzip: ~2 KB)
│   ├── vendor-core-[hash].js    : ~150 KB (gzip: ~50 KB)
│   ├── vendor-chart-[hash].js   : ~200 KB (gzip: ~70 KB)
│   └── vendor-ckeditor-[hash].js: ~300 KB (gzip: ~100 KB)

總計：~835 KB (未壓縮)
總計：~282 KB (Gzip 壓縮) ✅
```

### 載入效能目標

```
✅ 首次內容繪製 (FCP)    : < 1.5 秒
✅ 最大內容繪製 (LCP)    : < 2.5 秒
✅ 首次輸入延遲 (FID)    : < 100 毫秒
✅ 累積佈局偏移 (CLS)    : < 0.1
✅ 互動準備時間 (TTI)    : < 3 秒
```

## 📝 開發指南

### 程式碼風格

本專案使用 ESLint 與 Prettier 確保程式碼品質。

```bash
# 執行 Linter
npm run lint

# 自動修復
npm run lint:fix

# 格式化程式碼
npm run format

# 檢查格式
npm run format:check
```

### Git Commit 規範

遵循 Conventional Commits 規範（繁體中文）：

```
feat: 新增使用者管理功能
fix: 修正登入頁面驗證錯誤
docs: 更新 API 文件
style: 調整按鈕樣式
refactor: 重構 API Client
test: 新增單元測試
chore: 更新依賴套件
```

## 🌐 API 整合

### 使用範例

```javascript
import { authAPI } from './api/modules/auth.js';
import { postsAPI } from './api/modules/posts.js';

// 登入
const result = await authAPI.login({
  email: 'admin@example.com',
  password: 'password'
});

// 取得文章列表
const posts = await postsAPI.list({ 
  status: 'published',
  page: 1,
  perPage: 10
});

// 建立文章
const newPost = await postsAPI.create({
  title: '文章標題',
  content: '<p>文章內容</p>',
  status: 'published',
  tags: ['前端', '開發']
});
```

### 錯誤處理

```javascript
import { toast } from './utils/toast.js';

try {
  await postsAPI.create(data);
  toast.success('文章建立成功');
} catch (error) {
  if (error.isValidationError?.()) {
    // 處理驗證錯誤
    const errors = error.getValidationErrors?.();
    Object.entries(errors).forEach(([field, message]) => {
      toast.error(`${field}: ${message}`);
    });
  } else if (error.isAuthError?.()) {
    // 處理認證錯誤
    toast.error('請重新登入');
    router.navigate('/login');
  } else {
    // 其他錯誤
    toast.error(error.message || '操作失敗');
  }
}
```

## 🛡️ 安全機制

### JWT 認證

- ✅ Token 儲存在 SessionStorage
- ✅ 自動加入 Authorization Header
- ✅ Token 過期自動導向登入頁
- ✅ Token 自動刷新（無感刷新）

### CSRF 防護

- ✅ 從 Cookie 或 API 取得 CSRF Token
- ✅ 自動加入 POST/PUT/PATCH/DELETE 請求
- ✅ Token 遺失時自動處理

### XSS 防護

- ✅ 使用 DOMPurify 淨化 HTML 內容
- ✅ 優先使用 textContent 而非 innerHTML
- ✅ URL 參數使用 encodeURIComponent 編碼
- ✅ 避免使用 eval() 或 Function()

### 安全標頭

```nginx
# Nginx 配置（nginx.conf）
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self'; ..." always;
```

## 🧪 測試

### 單元測試

```bash
# 執行所有單元測試
npm run test

# Watch 模式（開發時使用）
npm run test:watch

# 測試覆蓋率
npm run test:coverage

# UI 模式
npm run test:ui
```

### E2E 測試

```bash
# 執行 E2E 測試
npm run test:e2e

# UI 模式（除錯使用）
npm run test:e2e:ui

# 指定瀏覽器
npx playwright test --project=chromium
npx playwright test --project=firefox
```

### 測試覆蓋率目標

```
目標覆蓋率：
- Statements  : ≥ 80%
- Branches    : ≥ 75%
- Functions   : ≥ 80%
- Lines       : ≥ 80%

目前覆蓋率：
- Statements  : 75% 🔄
- Branches    : 70% 🔄
- Functions   : 80% ✅
- Lines       : 75% 🔄
```

## 📚 文件

完整文件請參考：

- [前端開發文件](/docs/frontend/)
- [介面設計規範](/docs/frontend/FRONTEND_INTERFACE_DESIGN_SPECIFICATION.md)
- [API 整合指南](/docs/frontend/API_INTEGRATION_GUIDE.md)
- [狀態管理策略](/docs/frontend/STATE_MANAGEMENT_STRATEGY.md)
- [安全檢查清單](/docs/frontend/SECURITY_CHECKLIST.md)
- [測試策略](/docs/frontend/TESTING_STRATEGY.md)
- [部署指南](/docs/frontend/DEPLOYMENT_GUIDE.md)
- [開發進度報告](/docs/frontend/DEVELOPMENT_PROGRESS.md)
- [最終總結](/docs/frontend/FINAL_SUMMARY.md)

## 🐛 故障排除

### 問題：npm install 失敗

```bash
# 清除快取並重新安裝
rm -rf node_modules package-lock.json
npm cache clean --force
npm install

# 或使用 legacy-peer-deps
npm install --legacy-peer-deps
```

### 問題：Tailwind CSS 樣式未套用

```bash
# 重新建構
npm run build

# 檢查 tailwind.config.js 的 content 路徑
# 確保包含所有使用 Tailwind 的檔案
```

### 問題：API 請求失敗 (CORS)

```bash
# 確認後端 API 正在運行
docker compose ps

# 檢查 .env 中的 API_BASE_URL
# 確保後端有正確的 CORS 設定
```

### 問題：測試失敗

```bash
# 清除測試快取
npm run test:clean

# 重新執行測試
npm run test:run

# 檢查測試環境設定
cat src/tests/setup.js
```

## 🚀 部署

### 生產環境部署步驟

1. **建構應用程式**
   ```bash
   npm run build
   ```

2. **建構 Docker 映像**
   ```bash
   docker build -t alleynote-frontend:latest .
   ```

3. **執行容器**
   ```bash
   docker run -d -p 80:80 --name alleynote-frontend alleynote-frontend:latest
   ```

4. **使用 Docker Compose**
   ```bash
   docker compose up -d frontend
   ```

### CI/CD

專案已配置 GitHub Actions 自動化流程：

- ✅ 程式碼品質檢查（ESLint + Prettier）
- ✅ 單元測試（Vitest）
- ✅ E2E 測試（Playwright）
- ✅ 建構驗證
- ✅ 安全性掃描（npm audit）
- ✅ Docker 映像建構與推送

## 📈 效能優化建議

### 已實作

- ✅ Code Splitting（第三方套件獨立打包）
- ✅ 路由懶加載
- ✅ Gzip 壓縮
- ✅ 靜態資源快取（1 年）
- ✅ 資源壓縮與最小化

### 待實作

- ⏳ 圖片懶加載
- ⏳ 預載入關鍵資源
- ⏳ Service Worker（PWA）
- ⏳ Critical CSS 內聯

## 📄 授權

MIT License © 2025 AlleyNote Team

## 👥 貢獻

歡迎提交 Pull Request！

### 貢獻流程

1. Fork 本專案
2. 建立功能分支（`git checkout -b feature/amazing-feature`）
3. 提交變更（`git commit -m 'feat: 新增驚人的功能'`）
4. 推送到分支（`git push origin feature/amazing-feature`）
5. 開啟 Pull Request

### 貢獻規範

- 遵循 Conventional Commits 規範
- 撰寫測試（測試覆蓋率 ≥ 80%）
- 通過所有 CI 檢查
- 更新相關文件

## 🙏 致謝

感謝所有開源專案的貢獻者：

- [Vite](https://vitejs.dev/) - 極速的建構工具
- [Tailwind CSS](https://tailwindcss.com/) - 實用優先的 CSS 框架
- [Chart.js](https://www.chartjs.org/) - 簡單而靈活的圖表庫
- [CKEditor](https://ckeditor.com/) - 強大的富文本編輯器
- [Vitest](https://vitest.dev/) - 快速的單元測試框架
- [Playwright](https://playwright.dev/) - 可靠的 E2E 測試工具

## 📞 聯絡資訊

- **專案首頁**: [GitHub Repository](https://github.com/cookeyholder/AlleyNote)
- **問題回報**: [GitHub Issues](https://github.com/cookeyholder/AlleyNote/issues)
- **文件**: [/docs/frontend/](/docs/frontend/)

---

**AlleyNote Frontend** v1.0.0 - 現代化公布欄系統前端應用  
**開發狀態**: ✅ 85% 完成（核心功能已完成）  
**最後更新**: 2025-01-XX
