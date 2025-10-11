# AlleyNote 前端開發文件總覽

> **AlleyNote** 是一個基於 DDD（領域驅動設計）的現代化公布欄網站，採用前後端分離架構。本目錄包含前端開發的完整規劃與指南。

---

## 📚 文件索引

### 🎨 核心規劃文件

#### 1. [介面設計規範](./FRONTEND_INTERFACE_DESIGN_SPECIFICATION.md)
**必讀 ⭐⭐⭐**

定義 AlleyNote 前端的整體設計哲學、技術選型、佈局規劃與各角色介面設計。

**包含內容**:
- 設計風格與色彩系統
- 技術棧選擇（原生 HTML/JavaScript/CSS + Tailwind CSS CDN）
- 公開佈局 vs 管理後台佈局
- 訪客、管理員、主管理員的介面規劃
- CKEditor 5 整合計畫（透過 CDN）

**適合對象**: 所有前端開發人員、UI/UX 設計師

---

#### 2. [待辦清單](./FRONTEND_TODO_LIST.md)
**必讀 ⭐⭐⭐**

完整的開發任務清單，從環境設定到部署上線的所有步驟。

**包含內容**:
- 9 個開發階段（基礎建設 → 優化收尾 → 文件撰寫）
- 每個階段的詳細任務項目
- 檢查清單與驗收標準
- 預估開發時間

**適合對象**: 專案經理、前端開發人員

---

### 🔧 技術實作指南

#### 3. [API 整合指南](./API_INTEGRATION_GUIDE.md)
**必讀 ⭐⭐⭐**

前端與後端 API 整合的完整架構與實作細節。

**包含內容**:
- API Client 架構設計
- JWT Token 管理
- CSRF Token 管理
- 請求/回應攔截器
- 統一錯誤處理
- API 模組化設計（auth, posts, attachments 等）
- 使用範例與最佳實踐

**適合對象**: 前端開發人員（必讀）

---

#### 4. [狀態管理策略](./STATE_MANAGEMENT_STRATEGY.md)
**必讀 ⭐⭐**

輕量級狀態管理方案，不使用 Vuex/Redux 等大型庫。

**包含內容**:
- 狀態分類（全域、頁面、組件、暫存）
- 自訂 Store 實作
- 本地儲存策略（LocalStorage/SessionStorage）
- 事件系統（EventEmitter）
- 表單狀態管理（FormManager）
- 最佳實踐

**適合對象**: 前端開發人員

---

### 🛡️ 安全與品質保證

#### 5. [安全檢查清單](./SECURITY_CHECKLIST.md)
**必讀 ⭐⭐⭐**

前端安全規範與檢查項目，確保應用程式免受 Web 安全威脅。

**包含內容**:
- XSS 防護（DOMPurify、輸出編碼）
- CSRF 防護（CSRF Token、SameSite Cookie）
- 認證與授權（JWT 安全儲存、權限檢查）
- 資料驗證（表單驗證、檔案上傳驗證）
- 安全標頭（CSP、X-Frame-Options 等）
- 第三方套件安全
- 部署前後檢查清單

**適合對象**: 前端開發人員、安全審查人員（必讀）

---

#### 6. [測試策略](./TESTING_STRATEGY.md)
**必讀 ⭐⭐**

完整的測試方案，包含 E2E、整合、單元測試。

**包含內容**:
- 測試金字塔架構
- E2E 測試（Playwright）
- 整合測試（瀏覽器原生測試）
- 單元測試（Jest 或瀏覽器原生）
- 視覺回歸測試
- Mock Server 設定
- CI/CD 整合
- 最佳實踐

**適合對象**: 前端開發人員、QA 測試人員

---

### 🚀 部署與維運

#### 7. [部署指南](./DEPLOYMENT_GUIDE.md)
**必讀 ⭐⭐**

從構建到部署的完整流程與最佳實踐。

**包含內容**:
- Docker + Nginx 部署配置
- 環境變數管理
- 部署方案（Docker + Nginx 靜態文件服務）
- 效能優化（圖片懶加載、CDN 使用）
- 監控與日誌（錯誤追蹤、訪問統計）
- CI/CD 流程（GitHub Actions）
- 故障排除

**適合對象**: DevOps 工程師、前端開發人員

---

## 🎯 快速開始

### 新手上路

如果你是第一次接觸 AlleyNote 前端專案，建議按照以下順序閱讀：

1. **第一天**: 
   - ✅ [介面設計規範](./FRONTEND_INTERFACE_DESIGN_SPECIFICATION.md) - 了解整體設計
   - ✅ [待辦清單](./FRONTEND_TODO_LIST.md) - 了解開發流程

2. **第二天**:
   - ✅ [API 整合指南](./API_INTEGRATION_GUIDE.md) - 學習 API 整合
   - ✅ [狀態管理策略](./STATE_MANAGEMENT_STRATEGY.md) - 學習狀態管理

3. **第三天**:
   - ✅ [安全檢查清單](./SECURITY_CHECKLIST.md) - 了解安全規範
   - ✅ [測試策略](./TESTING_STRATEGY.md) - 了解測試方法

4. **開始開發前**:
   - ✅ 閱讀後端 API 文件：`docs/guides/developer/API_DOCUMENTATION.md`
   - ✅ 確認 Docker 已安裝並運行
   - ✅ Clone 專案並執行 `docker-compose up -d`
   - ✅ 閱讀前端遷移記錄：`frontend/MIGRATION_NOTES.md`

---

### 經驗開發者

如果你已經熟悉前端開發，可以快速瀏覽：

1. **技術棧**: 原生 HTML/JavaScript/CSS + Tailwind CSS (CDN) + CKEditor 5 (CDN)
2. **狀態管理**: 輕量級自訂 Store（無 React/Vue/框架）
3. **API 整合**: Fetch API + JWT + CSRF Token
4. **測試**: Playwright (E2E) + 瀏覽器原生測試
5. **部署**: Docker + Nginx（無需構建）

然後直接開始：
```bash
# 啟動 Docker 服務
docker-compose up -d

# 訪問前端
open http://localhost:3000

# 直接編輯 frontend/ 目錄的文件，刷新瀏覽器即可看到變更
```

---

## 🏗️ 技術架構

```
┌─────────────────────────────────────────────────┐
│                  Frontend Layer                 │
├─────────────────────────────────────────────────┤
│                                                 │
│  ┌──────────────┐    ┌──────────────┐          │
│  │  Tailwind    │    │  CKEditor 5  │          │
│  │  CSS (CDN)   │    │    (CDN)     │          │
│  └──────────────┘    └──────────────┘          │
│                                                 │
│  ┌──────────────┐    ┌──────────────┐          │
│  │   Native JS  │    │  Chart.js    │          │
│  │  ES6 Modules │    │    (CDN)     │          │
│  └──────────────┘    └──────────────┘          │
│                                                 │
│  ┌─────────────────────────────────────────┐   │
│  │         State Management                │   │
│  │  (Custom Store + Event System)          │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
│  ┌─────────────────────────────────────────┐   │
│  │          API Integration                │   │
│  │  (Fetch API + JWT + CSRF Token)         │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
└─────────────────────────────────────────────────┘
                        │
                        │ RESTful API
                        ▼
┌─────────────────────────────────────────────────┐
│              Backend Layer (PHP)                │
│           (DDD Architecture + JWT)              │
└─────────────────────────────────────────────────┘
```

---

## 📊 開發流程

```
┌────────────┐
│ 需求確認    │
└─────┬──────┘
      │
      ▼
┌────────────┐
│ 閱讀文件    │ ← 你在這裡
└─────┬──────┘
      │
      ▼
┌────────────┐
│ 環境設定    │
└─────┬──────┘
      │
      ▼
┌────────────┐
│ 撰寫測試    │ (TDD)
└─────┬──────┘
      │
      ▼
┌────────────┐
│ 實作功能    │
└─────┬──────┘
      │
      ▼
┌────────────┐
│ 執行測試    │
└─────┬──────┘
      │
      ▼
┌────────────┐
│ 程式碼審查  │
└─────┬──────┘
      │
      ▼
┌────────────┐
│ 合併程式碼  │
└─────┬──────┘
      │
      ▼
┌────────────┐
│ 部署        │
└────────────┘
```

---

## 🔑 關鍵決策

### 為什麼選擇原生 HTML/JavaScript/CSS？

✅ **優點**:
- 零構建時間，修改即時生效
- 無需 Node.js 環境和 npm 套件管理
- 學習曲線低，對初學者友善
- 部署簡單，無需構建步驟
- 維護成本低，不受框架版本束縛
- 直接使用瀏覽器原生特性，效能優異

❌ **不選擇 Vite/React/Vue 的原因**:
- 專案規模適中，不需要複雜的構建流程
- 後端已有成熟的 DDD 架構
- 團隊希望保持技術棧簡單
- 避免構建工具的學習成本和維護負擔

### 為什麼不使用 Vuex/Redux？

✅ **輕量級狀態管理的優勢**:
- 學習曲線低
- 程式碼量少
- 易於除錯
- 滿足專案需求

✅ **自訂 Store 的特點**:
- 簡單的訂閱/通知機制
- 支援持久化到 LocalStorage/SessionStorage
- 配合事件系統實現組件通訊

---

## 📝 開發規範

### Commit Message 規範

使用 **Conventional Commits**（繁體中文）：

```
feat: 新增文章管理頁面
fix: 修復登入 Token 過期問題
docs: 更新 API 整合指南
style: 調整按鈕樣式
refactor: 重構 Store 類別
test: 新增文章建立流程測試
chore: 更新依賴套件版本
```

### 程式碼風格

- ✅ 使用 ES6+ 模組語法（import/export）
- ✅ 使用 JSDoc 提供型別提示
- ✅ 變數/函式使用 camelCase
- ✅ 類別使用 PascalCase
- ✅ 常數使用 UPPER_SNAKE_CASE
- ✅ 保持程式碼簡潔清晰，善用註解

### 目錄結構

```
frontend/
├── index.html            # 主入口頁面
├── js/                   # JavaScript 模組
│   ├── main.js          # 應用程式入口
│   ├── api/             # API 相關
│   │   ├── client.js
│   │   ├── auth.js
│   │   └── posts.js
│   ├── components/      # 可重用組件
│   │   ├── Modal.js
│   │   ├── Toast.js
│   │   └── Loading.js
│   ├── pages/           # 頁面模組
│   │   │   ├── home.js
│   │   │   └── login.js
│   │   └── admin/      # 管理頁面
│   │       ├── dashboard.js
│   │       └── posts.js
│   ├── utils/           # 工具函式
│   │   ├── router.js
│   │   ├── toast.js
│   │   └── validator.js
│   └── store/           # 狀態管理（可選）
├── css/                 # 樣式表
│   └── main.css
├── assets/              # 靜態資源
│   ├── images/
│   └── icons/
└── README.md
```

**注意**: 不再需要以下文件/目錄：
- ❌ `package.json` (無需 npm)
- ❌ `（無需配置檔案）` (無構建工具)
- ❌ `node_modules/` (無依賴目錄)
- ❌ `.env.*` (配置直接在程式碼中或透過 Docker)

---

## 🤝 貢獻指南

### 開發流程

1. **Fork 專案**
2. **建立功能分支**: `git checkout -b feature/新功能名稱`
3. **撰寫測試**: 先寫測試（TDD）
4. **實作功能**: 實作並通過測試
5. **提交變更**: `git commit -m "feat: 新增某某功能"`
6. **Push 分支**: `git push origin feature/新功能名稱`
7. **建立 Pull Request**

### Pull Request 檢查清單

- [ ] 所有功能正常運作
- [ ] 程式碼清晰易讀，有適當註解
- [ ] 已撰寫相關文件
- [ ] Commit Message 符合規範
- [ ] 已測試 RWD（手機/平板/桌面）
- [ ] 已通過安全檢查清單
- [ ] 已通過程式碼審查
- [ ] 在多個瀏覽器測試（Chrome、Firefox、Safari）

---

## 📞 聯絡資訊

- **專案倉庫**: https://github.com/cookeyholder/AlleyNote
- **問題回報**: 請開 GitHub Issue
- **技術討論**: 請使用 GitHub Discussions

---

## 📜 授權

本專案採用 MIT 授權條款。

---

## 🎓 學習資源

### 推薦閱讀

- [MDN Web Docs - JavaScript](https://developer.mozilla.org/zh-TW/docs/Web/JavaScript)
- [MDN Web Docs - ES6 Modules](https://developer.mozilla.org/zh-TW/docs/Web/JavaScript/Guide/Modules)
- [Tailwind CSS 官方文件](https://tailwindcss.com/)
- [CKEditor 5 官方文件](https://ckeditor.com/docs/ckeditor5/latest/)
- [Playwright 官方文件](https://playwright.dev/)
- [Web.dev - Web 效能優化](https://web.dev/learn-web-vitals/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

### 相關技術

- **JavaScript**: ES6+ Modules, Async/Await, Promises, Fetch API
- **HTML5**: Semantic HTML, Web Components（可選）
- **CSS3**: Flexbox, Grid, CSS Variables
- **HTTP**: RESTful API, JWT, CORS
- **安全**: XSS, CSRF, CSP
- **測試**: E2E Testing, Browser DevTools
- **DevOps**: Docker, Nginx, CI/CD

---

## ✅ 總結

AlleyNote 前端專案採用**原生技術、簡單直接**的開發方式，確保：

1. ✅ **開發效率**: 零構建時間、修改即生效
2. ✅ **程式碼品質**: 清晰的結構、完整測試
3. ✅ **安全性**: XSS/CSRF 防護、安全標頭
4. ✅ **效能**: 輕量化、CDN 加速
5. ✅ **可維護性**: 無框架依賴、易於理解

**開始開發前，請務必閱讀本目錄下的所有文件。祝開發順利！** 🚀

---

**📌 重要提醒**: 

前端已從 Vite 改為純原生技術，請參考 [frontend/MIGRATION_NOTES.md](../../frontend/MIGRATION_NOTES.md) 了解遷移詳情。
3. ✅ **安全性**: XSS/CSRF 防護、安全標頭
4. ✅ **效能**: Code Splitting、懶加載、快取策略
5. ✅ **可維護性**: 清晰的架構、完整的文件

**開始開發前，請務必閱讀本目錄下的所有文件。祝開發順利！** 🚀
