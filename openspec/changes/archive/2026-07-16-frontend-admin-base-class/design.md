## Context

管理後台的 5 個 class-based 頁面（profile.js、roles.js、statistics.js、tags.js、users.js）各自獨立實作了相同的生命週期模式：`constructor(props)` → `init()`（載入資料） → `render()`（渲染 HTML） → `attachEventListeners()`（綁定事件）。此外，`escapeHtml()` 這個純工具函式在 5 個 class-based 頁面（profile.js、roles.js、statistics.js、tags.js、users.js）中各自重複出現。tags.js 是目前唯一缺少專屬 API 模組（`api/modules/tags.js`）的領域。

`loadAdminPage()` wrapper 已在 router.js 中提供統一的錯誤邊界，因此基底類別不需重複處理錯誤捕捉。

## Goals / Non-Goals

**Goals:**

- 建立 `BaseAdminPage` 基底類別，封裝共用生命週期與工具方法
- 5 個管理頁面改為繼承基底類別，消除重複樣板
- 移除 5 份複製的 `escapeHtml()`（profile.js、roles.js、statistics.js、tags.js、users.js），由基底類別統一提供
- 新增 `api/modules/tags.js`，補齊 tags 領域的 API 層

**Non-Goals:**

- 不變更 functional-style 頁面（dashboard.js、posts.js、postEditor.js、settings.js）
- 不變更任何使用者可見的行為或 UI
- 不新增或修改後端 API
- 不引入 TypeScript、新框架或建構工具
- 不修改 router.js 的 `loadAdminPage()` 錯誤邊界邏輯

## Decisions

### 決定 1：基底類別提供生命週期方法而非 Mixin

- **方案 A（採用）**：ES6 class inheritance — `class ProfilePage extends BaseAdminPage`
- **方案 B（捨棄）**：Mixin / composable function
- **理由**：5 個頁面已經是 class-based，inheritance 是最自然的抽象方式，無須改寫現有結構。Mixin 在除錯與 IDE 支援上較差。

### 決定 2：escapeHtml 作為實例方法（非靜態方法）

- **方案 A（採用）**：`this.escapeHtml(str)` 實例方法
- **方案 B（捨棄）**：`BaseAdminPage.escapeHtml(str)` 靜態方法
- **理由**：現有頁面在生命週期方法中呼叫 `escapeHtml()`，實例方法可直接以 `this.escapeHtml()` 呼叫，與現有使用習慣一致且更簡潔。

### 決定 3：tags API 模組比照現有模式

- **方案 A（採用）**：新增 `frontend/js/api/modules/tags.js`，匯出 `tagsApi` 物件
- **方案 B（捨棄）**：將 tags API 寫在 tags.js 頁面內部
- **理由**：保持與其他領域（posts、users、roles 等）一致的架構，利於維護與測試。

### 決定 4：不修改 loadAdminPage 錯誤邊界

- **方案 A（採用）**：維持 router.js 的 `loadAdminPage()` 錯誤邊界，`BaseAdminPage` 不重複處理錯誤捕捉
- **方案 B（捨棄）**：在基底類別中加入 try-catch
- **理由**：`loadAdminPage()` 已提供統一錯誤處理，基底類別不需要重複。

### 決定 5：BaseAdminPage 不接受 DOM container 參數

- **方案 A（採用）**：基底類別在建構時接受 layout 實例，透過 `layout.getContentContainer()` 取得 container
- **方案 B（捨棄）**：在建構時額外傳入 container 元素
- **理由**：所有管理頁面都透過 Dashboard layout 渲染，由 layout 管理 container 是現有模式。

## Risks / Trade-offs

- **[風險] 繼承層級加深**：若未來需要更細的共用層次（如表格頁面 vs 表單頁面），單一基底類別可能不夠 → **因應**：必要時可在未來引入中層抽象類別，此變更僅建立第一層基底
- **[風險] 頁面覆寫生命週期時忘記呼叫 super**：子類別若覆寫 `init()` 但未呼叫 `super.init()` 可能導致生命週期中斷 → **因應**：基底類別在 `init()` 完成後透過內部方法觸發 `render()` 與 `attachEventListeners()`，子類別只需專注於資料載入邏輯
- **[風險] tags API 模組設計與現有後端不一致**：若 tags 路由模式與其他領域不同，API 模組可能需調整 → **因應**：實作前先確認後端 TagController 的路由模式
