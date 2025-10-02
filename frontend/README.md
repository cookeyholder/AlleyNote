# AlleyNote 前端應用程式

> 基於 Vite + Tailwind CSS + 原生 JavaScript 的現代化前端應用

## 📋 功能特色

✅ **已完成的核心功能**:
- API 整合架構（Axios + 攔截器）
- JWT + CSRF 認證機制
- 輕量級狀態管理
- 路由系統（Navigo）
- Toast 通知系統
- 響應式後台佈局
- 登入頁面
- 儀表板
- 文章列表管理
- 文章編輯器（基礎版）

🚧 **計畫中的功能**:
- CKEditor 5 富文本編輯器
- 圖片上傳功能
- 使用者管理
- 完整測試覆蓋
- PWA 支援

## 🚀 快速開始

### 前置需求

- Node.js 18+
- npm 9+

### 安裝

```bash
# 安裝依賴（使用 --no-workspaces 避免 monorepo 衝突）
npm install --no-workspaces

# 或者使用 legacy peer deps
npm install --legacy-peer-deps
```

### 開發

```bash
# 啟動開發伺服器
npm run dev

# 或使用 npx
npx vite
```

應用程式會在 `http://localhost:5173` 啟動

### 建構

```bash
# 建構生產版本
npm run build

# 預覽建構結果
npm run preview
```

## 📁 專案結構

```
frontend/
├── src/
│   ├── api/                 # API 相關
│   │   ├── client.js        # Axios 客戶端
│   │   ├── config.js        # API 配置
│   │   ├── errors.js        # 錯誤處理
│   │   ├── interceptors/    # 請求/回應攔截器
│   │   └── modules/         # API 模組（auth, posts）
│   ├── components/          # 可重用組件
│   ├── layouts/             # 佈局
│   │   └── DashboardLayout.js
│   ├── pages/               # 頁面
│   │   ├── home.js
│   │   ├── login.js
│   │   ├── notFound.js
│   │   └── admin/           # 後台頁面
│   ├── router/              # 路由
│   │   └── index.js
│   ├── store/               # 狀態管理
│   │   ├── Store.js
│   │   └── globalStore.js
│   ├── utils/               # 工具函式
│   │   ├── tokenManager.js
│   │   ├── csrfManager.js
│   │   └── toast.js
│   ├── styles/              # 樣式
│   │   └── main.css
│   └── main.js              # 入口
├── public/
├── index.html
├── vite.config.js
├── tailwind.config.js
└── package.json
```

## 🎨 技術棧

- **建構工具**: Vite 5.x
- **CSS 框架**: Tailwind CSS 4.x
- **HTTP 客戶端**: Axios
- **路由**: Navigo
- **安全**: DOMPurify, Validator.js
- **測試**: Vitest, Playwright

## 🔧 配置

### 環境變數

複製 `.env.example` 並建立以下檔案：

- `.env.development` - 開發環境
- `.env.staging` - 測試環境
- `.env.production` - 生產環境

### API 配置

在 `.env` 中設定：

```env
VITE_API_BASE_URL=http://localhost:8080/api
VITE_API_TIMEOUT=30000
VITE_ENABLE_API_LOGGER=true
```

## 📝 開發指南

### 程式碼風格

```bash
# 執行 Linter
npm run lint

# 自動修復
npm run lint:fix

# 格式化程式碼
npm run format
```

### 測試

```bash
# 執行單元測試
npm run test

# 執行 E2E 測試
npm run test:e2e

# 測試覆蓋率
npm run test:coverage
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
const posts = await postsAPI.list({ status: 'published' });

// 建立文章
const newPost = await postsAPI.create({
  title: '文章標題',
  content: '文章內容',
  status: 'published'
});
```

### 錯誤處理

```javascript
try {
  await postsAPI.create(data);
} catch (error) {
  if (error.isValidationError()) {
    // 處理驗證錯誤
    const errors = error.getValidationErrors();
  } else if (error.isAuthError()) {
    // 處理認證錯誤
    router.navigate('/login');
  } else {
    // 其他錯誤
    toast.error(error.getUserMessage());
  }
}
```

## 🛡️ 安全機制

### JWT 認證

- Token 儲存在 SessionStorage
- 自動加入 Authorization Header
- Token 過期自動導向登入頁

### CSRF 防護

- 從 Cookie 讀取 CSRF Token
- 自動加入 POST/PUT/PATCH/DELETE 請求

### XSS 防護

- 使用 DOMPurify 淨化 HTML
- 避免使用 innerHTML
- URL 參數編碼

## 📚 文件

完整文件請參考：

- [前端開發文件](/docs/frontend/)
- [API 整合指南](/docs/frontend/API_INTEGRATION_GUIDE.md)
- [安全檢查清單](/docs/frontend/SECURITY_CHECKLIST.md)
- [測試策略](/docs/frontend/TESTING_STRATEGY.md)

## 🐛 故障排除

### 問題：vite: command not found

```bash
# 解決方案 1: 使用 npx
npx vite

# 解決方案 2: 重新安裝依賴
rm -rf node_modules package-lock.json
npm install --no-workspaces
```

### 問題：Tailwind CSS 樣式未套用

```bash
# 確認 Tailwind CSS 配置正確
npm run build

# 檢查 tailwind.config.js 的 content 路徑
```

### 問題：API 請求失敗

```bash
# 確認後端 API 正在運行
# 檢查 .env 中的 VITE_API_BASE_URL

# 檢查瀏覽器 Console 的錯誤訊息
```

## 📄 授權

MIT License

## 👥 貢獻

歡迎提交 Pull Request！

請遵循專案的程式碼風格與提交規範（Conventional Commits）。

---

**AlleyNote Frontend** - 現代化公布欄系統前端應用
