### **待辦清單 (To-Do List)**

---

## 📝 開發前準備

-   [x] 閱讀所有前端規劃文件
    -   [x] `FRONTEND_INTERFACE_DESIGN_SPECIFICATION.md` - 介面設計規範
    -   [x] `API_INTEGRATION_GUIDE.md` - API 整合指南
    -   [x] `STATE_MANAGEMENT_STRATEGY.md` - 狀態管理策略
    -   [x] `SECURITY_CHECKLIST.md` - 安全檢查清單
    -   [x] `TESTING_STRATEGY.md` - 測試策略
    -   [x] `DEPLOYMENT_GUIDE.md` - 部署指南
-   [x] 熟悉後端 API 文件 (`docs/guides/developer/API_DOCUMENTATION.md`)
-   [x] 設定開發環境（Node.js 18+, npm 9+）

---

## 🚧 階段一：基礎建設與環境設定 ✅

### 專案初始化
-   [x] 初始化 Vite 專案 (`npm create vite@latest frontend`)
-   [x] 安裝並配置 Tailwind CSS
-   [x] 將 `index.html` 中的 `tailwind.config` 提取到 `tailwind.config.js` 檔案中
-   [x] 設定 Prettier 與 ESLint
-   [x] 建立 `.env` 環境變數檔案（development, staging, production）

### 依賴套件安裝
-   [x] 安裝核心套件：`axios`, `navigo`（路由）
-   [x] 安裝安全套件：`dompurify`, `validator`
-   [x] 安裝開發工具：`husky`, `lint-staged`
-   [x] 安裝測試套件：`vitest`, `@playwright/test`

### 專案結構建立
-   [x] 建立完整的檔案結構：
    ```
    src/
    ├── api/              # API 相關
    ├── components/       # 可重用組件
    ├── layouts/          # 佈局
    ├── pages/            # 頁面
    ├── router/           # 路由
    ├── store/            # 狀態管理
    ├── utils/            # 工具函式
    ├── styles/           # 樣式
    └── main.js           # 入口
    ```

### API 整合架構
-   [x] 建立 API Client (`src/api/client.js`)
-   [x] 實作請求攔截器（自動加入 JWT & CSRF Token）
-   [x] 實作回應攔截器（統一錯誤處理）
-   [x] 建立 API 模組（auth, posts, attachments, users, statistics）
-   [x] 實作 Token Manager（JWT Token 管理）
-   [x] 實作 CSRF Manager（CSRF Token 管理）
-   [x] 建立 API 錯誤處理機制

### 狀態管理架構
-   [x] 實作 Store 類別（`src/store/Store.js`）
-   [x] 建立全域 Store（`src/store/globalStore.js`）
-   [x] 建立頁面級 Store（`src/store/pageStore.js`）
-   [x] 實作 Storage Manager（LocalStorage/SessionStorage 管理）
-   [x] 實作狀態持久化機制
-   [x] 實作 Event Emitter（事件系統）

### 共用工具與組件
-   [x] 建立 Form Manager（表單管理器）
-   [x] 建立驗證器（validators）
-   [x] 建立 Toast 通知組件
-   [x] 建立 Modal 組件
-   [x] 建立 Loading 組件
-   [x] 建立 Confirmation Dialog 組件

---

## 🚶 階段二：公開介面開發（訪客視角） ✅

### 佈局建立
-   [x] 建立 Public Layout（`src/layouts/PublicLayout.js`）
-   [x] 建立頂部導航列（Logo、登入按鈕）
-   [x] 建立頁腳（版權資訊）

### 首頁 / 文章列表頁
-   [x] 建立首頁路由（`/`）
-   [x] 實作文章卡片組件
-   [x] 串接文章列表 API
-   [x] 實作分頁功能
-   [x] 實作搜尋功能
-   [x] 實作分類篩選功能
-   [x] 實作 RWD（響應式設計）

### 文章內頁
-   [x] 建立文章內頁路由（`/posts/:id`）
-   [x] 串接文章詳情 API
-   [x] 使用 DOMPurify 淨化 HTML 內容
-   [x] 實作相關文章推薦
-   [x] 實作社群分享按鈕
-   [x] 實作 RWD

### 登入頁面
-   [x] 建立登入頁面路由（`/login`）
-   [x] 建立登入表單（Email、密碼）
-   [x] 實作前端驗證（Email 格式、密碼長度）
-   [x] 串接登入 API
-   [x] 處理登入成功（儲存 Token、導向後台）
-   [x] 處理登入失敗（顯示錯誤訊息）
-   [x] 實作「記住我」功能
-   [x] 實作 RWD

---

## 👨‍💼 階段三：管理員核心功能開發（Admin 視角） ✅

### 後台佈局建立
-   [x] 建立 Dashboard Layout（`src/layouts/DashboardLayout.js`）
-   [x] 建立側邊導覽列（Logo、選單、使用者資訊）
-   [x] 建立頂部標頭（頁面標題、搜尋、通知、登出）
-   [x] 實作側邊欄展開/收合功能
-   [x] 實作權限控制（根據角色顯示選單）

### 路由守衛
-   [x] 實作 `requireAuth` 中介軟體（檢查登入狀態）
-   [x] 實作角色權限檢查
-   [x] 處理未授權訪問（導向登入頁或 403 頁面）

### 儀表板頁面
-   [x] 建立儀表板路由（`/admin/dashboard`）
-   [x] 顯示統計數據卡片（文章數、瀏覽量等）
-   [x] 串接統計 API
-   [x] 顯示最近發布的文章列表
-   [x] 實作 RWD

### 文章管理頁面
-   [x] 建立文章管理路由（`/admin/posts`）
-   [x] 實作文章列表表格
-   [x] 串接文章列表 API（含篩選、排序、搜尋）
-   [x] 實作分頁功能
-   [x] 實作操作按鈕（編輯、刪除、發布/草稿切換）
-   [x] 實作刪除確認對話框
-   [x] 實作批次操作
-   [x] 實作 RWD

### 新增/編輯文章頁面
-   [x] 建立新增文章路由（`/admin/posts/create`）
-   [x] 建立編輯文章路由（`/admin/posts/:id/edit`）
-   [x] 整合 CKEditor 5（Classic Build）
-   [x] 實作標題輸入框
-   [x] 實作內容編輯器（CKEditor）
-   [x] 實作右側設定欄
    -   [x] 發布狀態選擇
    -   [x] 分類選擇
    -   [x] 標籤輸入
    -   [x] 特色圖片上傳
-   [x] 實作圖片上傳功能（CKEditor Upload Adapter）
-   [x] 實作圖片上傳進度顯示
-   [x] 實作圖片上傳錯誤處理
-   [x] 實作自動儲存草稿（每 30 秒）
-   [x] 實作離開頁面前提示（有未儲存變更）
-   [x] 串接建立文章 API
-   [x] 串接更新文章 API
-   [x] 處理驗證錯誤（顯示欄位錯誤訊息）
-   [x] 實作 RWD

### 個人資料頁面
-   [x] 建立個人資料路由（`/admin/profile`）
-   [x] 顯示使用者資訊
-   [x] 實作修改密碼功能
-   [x] 實作修改個人資訊功能
-   [x] 串接更新個人資料 API
-   [x] 實作 RWD

---

## 👑 階段四：主管理員專屬功能開發（Super Admin 視角） ✅

### 使用者管理頁面
-   [x] 建立使用者管理路由（`/admin/users`）
-   [x] 實作使用者列表表格
-   [x] 串接使用者列表 API
-   [x] 實作操作按鈕（編輯、刪除）
-   [x] 實作新增使用者模態框
-   [x] 實作編輯使用者模態框
-   [x] 實作刪除確認對話框
-   [x] 串接建立使用者 API
-   [x] 串接更新使用者 API
-   [x] 串接刪除使用者 API
-   [x] 實作 RWD

### 系統統計頁面
-   [x] 建立系統統計路由（`/admin/statistics`）
-   [x] 安裝圖表庫（Chart.js）
-   [x] 實作每日文章發布趨勢圖
-   [x] 實作熱門文章排行榜
-   [x] 實作使用者活動分析
-   [x] 串接統計 API
-   [x] 實作日期範圍篩選
-   [x] 實作匯出報表功能
-   [x] 實作 RWD

### 系統設定頁面
-   [x] 建立系統設定路由（`/admin/settings`）
-   [x] 實作網站標題設定
-   [x] 實作 Logo 上傳
-   [x] 實作維護模式開關
-   [x] 實作其他全域設定
-   [ ] 串接系統設定 API
-   [x] 實作 RWD

---

## 🛡️ 階段五：安全性強化 ✅

### XSS 防護
-   [x] 所有使用者輸入使用 `textContent` 而非 `innerHTML`
-   [x] CKEditor 內容使用 DOMPurify 淨化
-   [x] URL 參數使用 `encodeURIComponent` 編碼
-   [x] 避免使用 `eval()` 或 `Function()`

### CSRF 防護
-   [x] 所有 POST/PUT/DELETE 請求自動加入 CSRF Token
-   [x] 實作 CSRF Token 自動更新機制
-   [x] 處理 CSRF Token 遺失的情況

### 認證與授權
-   [x] JWT Token 使用 SessionStorage 儲存
-   [x] 實作 Token 過期處理（自動導向登入頁）
-   [x] 實作 Token 自動刷新機制
-   [x] 敏感操作需要二次確認

### 資料驗證
-   [x] 所有表單實作前端驗證
-   [x] 檔案上傳驗證（大小、類型、尺寸）
-   [x] 防止 SQL Injection 字元
-   [x] 使用 validator.js 驗證複雜規則

### 安全標頭
-   [x] 設定 Content-Security-Policy (CSP)
-   [x] 設定 X-Frame-Options
-   [x] 設定 X-Content-Type-Options
-   [x] 設定 X-XSS-Protection

---

## 🧪 階段六：測試 🟡 (部分完成)

### 測試環境設定
-   [x] 配置 Vitest（單元測試）
-   [x] 配置 Playwright（E2E 測試）
-   [ ] 建立 Mock Server（MSW）
-   [ ] 建立測試 Fixtures

### 單元測試
-   [x] 測試工具函式（validators, formatters 等）
-   [x] 測試 Store 類別
-   [ ] 測試 API Client
-   [ ] 測試錯誤處理邏輯
-   [x] 測試表單驗證邏輯
-   [ ] 達到 80% 以上程式碼覆蓋率

### 整合測試
-   [ ] 測試 API 整合流程
-   [ ] 測試認證流程（登入、登出、Token 刷新）
-   [ ] 測試狀態持久化機制
-   [ ] 測試事件系統

### E2E 測試
-   [x] 測試使用者登入流程
-   [ ] 測試文章建立流程
-   [ ] 測試文章編輯流程
-   [ ] 測試文章刪除流程
-   [ ] 測試圖片上傳流程
-   [ ] 測試使用者管理流程（Super Admin）
-   [ ] 測試權限控制
-   [ ] 跨瀏覽器測試（Chrome, Firefox, Safari）

### 視覺回歸測試
-   [ ] 建立關鍵頁面的截圖基準
-   [ ] 設定自動視覺比對

---

## ✨ 階段七：優化與收尾 🟡 (部分完成)

### 效能優化
-   [x] 實作 Code Splitting（路由懶加載）
-   [ ] 實作圖片懶加載
-   [x] 設定資源預載入（preload, prefetch）
-   [ ] 實作 Service Worker（PWA，可選）
-   [x] 優化 Vite 建構配置
-   [ ] 壓縮圖片與靜態資源
-   [ ] 確保 LCP < 2.5 秒

### 響應式設計（RWD）
-   [x] 全面測試手機版（< 640px）
-   [x] 全面測試平板版（640px - 1024px）
-   [x] 全面測試桌面版（> 1024px）
-   [x] 測試觸控操作（按鈕大小 ≥ 44px）
-   [ ] 測試橫向/直向切換

### 無障礙性（Accessibility）
-   [ ] 所有互動元素可用鍵盤操作
-   [x] 表單有正確的 label 關聯
-   [ ] 圖片有 alt 屬性
-   [ ] 模態框實作 focus trap
-   [x] 使用語意化 HTML
-   [ ] 通過 WCAG 2.1 AA 標準（可選）

### 錯誤處理與使用者體驗
-   [x] 統一全站的 Loading 狀態提示
-   [x] 統一全站的錯誤狀態提示
-   [x] 實作 Toast 通知系統
-   [x] 實作網路離線提示
-   [x] 實作 404 頁面
-   [x] 實作 500 錯誤頁面
-   [x] 實作 403 禁止訪問頁面

### 監控與分析
-   [ ] 整合 Sentry 錯誤追蹤
-   [ ] 整合 Google Analytics（可選）
-   [ ] 實作 Web Vitals 監控
-   [ ] 實作使用者行為追蹤（可選）

---

## 🚀 階段八：部署 🟡 (部分完成)

### 部署前準備
-   [ ] 執行完整測試套件（unit + E2E）
-   [ ] 執行 Lighthouse 測試（效能、SEO、Accessibility）
-   [x] 檢查所有環境變數設定
-   [ ] 檢查安全標頭配置
-   [ ] 檢查 CSP 政策
-   [ ] 移除 console.log 與 debugger

### 建構與部署
-   [x] 建構生產版本（`npm run build`）
-   [ ] 驗證建構產物大小（main.js < 500KB）
-   [x] 配置 Nginx（Gzip、快取、SPA 路由）
-   [x] 建立 Dockerfile
-   [x] 設定 Docker Compose
-   [ ] 設定 CI/CD（GitHub Actions）
-   [ ] 部署到測試環境（Staging）
-   [ ] 在測試環境進行完整測試
-   [ ] 部署到生產環境（Production）

### 部署後驗證
-   [ ] 網站可正常訪問（HTTPS）
-   [ ] 所有頁面都能正確顯示
-   [ ] API 請求正常運作
-   [ ] 使用者登入流程正常
-   [ ] 圖片與靜態資源載入正常
-   [ ] 手機版顯示正常
-   [ ] 跨瀏覽器測試通過
-   [ ] 效能指標符合預期
-   [ ] 錯誤追蹤系統接收資料
-   [ ] 分析工具正常運作

---

## 📚 階段九：文件撰寫 ✅

### 開發文件
-   [x] 撰寫 README（專案簡介、安裝、啟動）
-   [ ] 撰寫 CONTRIBUTING（貢獻指南）
-   [ ] 撰寫 CHANGELOG（版本記錄）
-   [ ] 撰寫元件使用說明
-   [ ] 撰寫 API 使用說明
-   [ ] 撰寫維護指南

### 使用者文件
-   [ ] 撰寫使用者手冊（Admin 功能說明）
-   [ ] 撰寫常見問題（FAQ）
-   [ ] 錄製操作示範影片（可選）

---

## ✅ 完成檢查清單

-   [ ] 所有功能已實作並測試
-   [ ] 所有測試通過（單元 + 整合 + E2E）
-   [ ] 程式碼覆蓋率 ≥ 80%
-   [ ] Lighthouse 分數 ≥ 90
-   [ ] 無安全漏洞（`npm audit` 通過）
-   [ ] 所有 TODO 註解已移除
-   [ ] 程式碼已通過 Linter 檢查
-   [ ] 所有環境變數已文件化
-   [ ] 部署流程已測試並文件化
-   [ ] 監控與錯誤追蹤已設定
-   [ ] 團隊成員已審查程式碼
-   [ ] 產品經理已驗收功能

---

## 📝 備註

- **優先順序**: 依照階段順序開發，每個階段完成後進行內部驗收
- **測試驅動**: 盡可能採用 TDD，先寫測試再寫實作
- **程式碼審查**: 每個 Pull Request 都需要至少一位團隊成員審查
- **版本控制**: 使用 Conventional Commits 規範（繁體中文）
- **分支策略**: 功能開發使用 `feature/*`，修復使用 `fix/*`
- **持續整合**: 每次 Push 都會自動執行測試與建構

**預估總開發時間**: 8-12 週（視團隊規模與經驗而定）
