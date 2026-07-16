# base-admin-page Specification

## Purpose

定義管理後台 class-based 頁面的共用基底類別 `BaseAdminPage`，統一建構→init→render→attachEventListeners 生命週期，並提供共用工具方法，消除頁面之間的重複樣板。

## Requirements

### Requirement: BaseAdminPage 必須定義標準生命週期

`BaseAdminPage` SHALL 提供 `constructor`、`init()`、`render()`、`attachEventListeners()` 四個生命週期方法，子類別透過覆寫來實作特定行為。

#### Scenario: 生命週期依序執行

- **WHEN** 頁面實體化並呼叫 `init()`
- **THEN** 基底類別依序執行：載入資料 → `render()` → `attachEventListeners()`
- **THEN** 每個階段完成後才進入下一階段

#### Scenario: 子類別可覆寫任一階段

- **WHEN** 子類別覆寫 `render()` 方法
- **THEN** 基底類別在生命週期中呼叫子類別的 `render()` 而非基底實作

### Requirement: BaseAdminPage 必須提供 escapeHtml 共用方法

`BaseAdminPage` SHALL 提供靜態或實例方法 `escapeHtml(str)`，對 HTML 特殊字元進行跳脫，防止 XSS 攻擊。

#### Scenario: 所有頁面透過基底類別取得 escapeHtml

- **WHEN** 任一管理頁面需要跳脫使用者輸入
- **THEN** 頁面可使用 `this.escapeHtml(value)` 而非自行實作
- **THEN** 各頁面移除各自複製的 `escapeHtml()` 函式

#### Scenario: escapeHtml 正確跳脫特殊字元

- **WHEN** 傳入包含 `<`, `>`, `&`, `"`, `'` 的字串
- **THEN** 回傳對應的 HTML 實體編碼字串

### Requirement: BaseAdminPage 必須管理 loading 狀態

`BaseAdminPage` SHALL 提供 `showLoading()` 與 `hideLoading()` 方法，統一管理頁面載入中狀態的顯示與隱藏。

#### Scenario: 載入資料前顯示 loading

- **WHEN** `init()` 開始載入資料
- **THEN** 自動呼叫 `showLoading()` 顯示載入指示器

#### Scenario: 載入完成後隱藏 loading

- **WHEN** 資料載入完成且渲染完畢
- **THEN** 自動呼叫 `hideLoading()` 隱藏載入指示器

### Requirement: BaseAdminPage 必須提供 Dashboard 版面綁定

`BaseAdminPage` SHALL 接受 Dashboard 版面實例作為建構參數，並提供 `getLayout()` 方法供子類別存取。

#### Scenario: 版面實例在建構時注入

- **WHEN** 建立頁面實體時傳入 layout 參數
- **THEN** 頁面可透過 `this.getLayout()` 取得版面實例

### Requirement: 各管理頁面必須繼承 BaseAdminPage

profile.js、roles.js、statistics.js、tags.js、users.js SHALL 改為 `extends BaseAdminPage`，並移除各自重複的生命週期樣板。

#### Scenario: 頁面繼承後行為一致

- **WHEN** 頁面改為繼承 `BaseAdminPage`
- **THEN** 頁面功能與 UI 與重構前完全相同
- **THEN** 僅移除重複程式碼，不新增或修改行為

### Requirement: tags 必須新增專屬 API 模組

tags 領域目前缺少 `api/modules/tags.js`，SHALL 比照其他領域（posts、users、roles、statistics 等）建立對應的 API 模組。

#### Scenario: tags API 模組可用

- **WHEN** tags.js 頁面匯入 API 模組
- **THEN** 可使用 `import { tagsApi } from '../../api/modules/tags.js'` 進行 CRUD 操作
- **THEN** API 方法簽章與後端路由一致
