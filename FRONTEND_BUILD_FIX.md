# 前端應用程式建置修復記錄

## 問題描述

原本 `localhost:8080` 顯示的是靜態展示頁面（`frontend/public/index.html`），而不是實際開發的前端應用程式。

## 原因分析

1. **Vite 配置錯誤**：`vite.config.js` 中的 `rollupOptions.input` 指向 `./public/index.html`，導致建置時使用錯誤的 HTML 檔案
2. **缺少依賴套件**：多個必需的 npm 套件未安裝
3. **Import 語法錯誤**：部分檔案使用了錯誤的 import 語法（default import vs named import）
4. **Tailwind CSS 版本問題**：專案使用 Tailwind CSS 4.x，但需要降級至 3.x 以確保兼容性

## 修復內容

### 1. 安裝缺少的依賴

```bash
npm install --save web-vitals @sentry/browser
npm install --save-dev tailwindcss@^3.4.0 terser
```

### 2. 修復 Vite 配置

**檔案**：`frontend/vite.config.js`

```diff
- input: "./public/index.html",
+ input: "./index.html",
```

### 3. 修復 Import 語法錯誤

修復以下檔案的 import 語句：

- `frontend/src/pages/admin/profile.js`
- `frontend/src/pages/admin/statistics.js`
- `frontend/src/pages/admin/tags.js`
- `frontend/src/pages/admin/users.js`
- `frontend/src/pages/admin/settings.js`

**主要變更**：
- DashboardLayout：從 default import 改為 named import
- Modal：從 default import 改為 named import（`{ Modal }`）
- Loading：從 named import 改為使用 loading 實例
- apiClient：從 named import 改為 default import

### 4. 修復 DashboardLayout 使用方式

將：
```javascript
const dashboardLayout = new DashboardLayout();
dashboardLayout.render(content);
```

改為：
```javascript
const app = document.getElementById("app");
app.innerHTML = renderDashboardLayout(content);
bindDashboardLayoutEvents();
```

## 測試結果

✅ 建置成功：`npm run build` 完成無錯誤
✅ 應用程式可正常訪問：http://localhost:8080
✅ 包含以下功能頁面：
- 登入頁面（`/login`）
- 首頁（`/`）
- 文章內頁（`/posts/:id`）
- 後台管理（`/admin/*`）
  - 儀表板（`/admin/dashboard`）
  - 文章管理（`/admin/posts`）
  - 使用者管理（`/admin/users`）
  - 標籤管理（`/admin/tags`）
  - 統計分析（`/admin/statistics`）
  - 系統設定（`/admin/settings`）
  - 個人資料（`/admin/profile`）

## 專案架構

```
frontend/
├── index.html              # ✅ 正確的應用程式入口（包含 <div id="app"></div>）
├── public/
│   └── index.html         # ❌ 舊的展示頁面（已不使用）
├── src/
│   ├── main.js            # 應用程式主入口
│   ├── router/            # 路由配置
│   ├── pages/             # 頁面組件
│   ├── components/        # 可重用組件
│   ├── api/               # API 客戶端
│   ├── store/             # 全局狀態管理
│   └── utils/             # 工具函式
└── dist/                  # 建置輸出目錄（nginx 掛載此目錄）
    └── index.html         # ✅ 建置後的應用程式入口
```

## 後續建議

1. **刪除或移動展示頁面**：考慮將 `frontend/public/index.html` 移到其他位置（如 `examples/` 目錄）
2. **更新文件**：更新專案 README，說明正確的建置和部署流程
3. **CI/CD 整合**：確保 CI/CD 流程包含前端建置步驟
4. **環境變數配置**：檢查並設定正確的環境變數（API URL 等）

## 建置命令

```bash
# 開發模式
npm run dev

# 生產建置
npm run build

# 預覽建置結果
npm run preview

# 使用 Docker 建置（推薦）
docker run --rm -v "$(pwd):/app" -w /app node:20-alpine sh -c "npm install && npm run build"
```

---

**日期**：2025-01-05
**修復者**：GitHub Copilot CLI
**狀態**：✅ 已完成並測試
