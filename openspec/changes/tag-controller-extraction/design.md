## Context

`TagManagementController` 中 `getTagPageHtml()` 方法使用 heredoc 嵌入完整 HTML 頁面，包含 Bootstrap 5 CSS（CDN）、自訂樣式、navbar、統計卡片、標籤表格、兩個 Modal（詳細資訊 + 批量清空）、以及約 465 行 JavaScript。此為專案中唯一在 Controller 內嵌 HTML/JS 的案例。

前端已有 `frontend/js/pages/admin/tags.js`（423 行），但其處理的是**內容標籤（content tags）**，由 SPA router 動態載入。此處的**快取標籤（cache tags）** 由 PHP 直接渲染完整 HTML 頁面（非 SPA 路線），故需新增獨立模組並以 `<script type="module">` 方式載入。

## Goals

- 將 HTML 樣板從 PHP Controller 中分離至獨立視圖檔案
- 將內嵌 JavaScript 抽取為可維護的 ES6 模組（self-initializing，不依賴 SPA router）
- 維持 CSP nonce 機制完整性
- 保留所有現有功能，零行為變更

## Non-Goals

- 不重構 `TagManagementController` 的 API 方法邏輯
- 不改變路由註冊方式
- 不引入視圖引擎或模板框架——沿用 PHP 原生 `require` 模式
- 不與 `frontend/js/pages/admin/tags.js`（內容標籤）合併
- 不修改 CSP 設定或 Bootstrap CDN 來源

## Strategy

1. **視圖抽取**
   - 於 `backend/resources/views/admin/cache/` 建立 `tags.php`
   - 將 `getTagPageHtml()` 的 HTML 結構（含 `<style>` 區塊）完整搬移至新視圖檔案
   - Bootstrap CDN 的 CSS `<link>` 與 JS `<script src="...">` 保留於視圖內
   - `renderTagPage()` 改為 `require` 該視圖檔案，區域變數 `$nonce` 自動傳入視圖
   - CSP nonce 改為 PHP 變數插入（`<?= $nonce ?>`）取代 `str_replace`
   - 外部 JS 模組使用 `<script type="module" nonce="<?= $nonce ?>" src="/js/pages/admin/cacheTags.js"></script>`
   - 內嵌 `<style>` 區塊保留於視圖內

2. **前端模組抽取**
   - 於 `frontend/js/pages/admin/` 建立 `cacheTags.js`
   - 比照現有 `tags.js` 使用 `export default class CacheTagsPage { }` 模式
   - 將所有內嵌函式（`loadStatistics`, `loadTags`, `renderTagsTable`, `renderPagination`, `showTagDetails`, `flushTag`, `showBulkDeleteModal`, `executeBulkDelete`, `handleSearch`, `refreshData`, `showLoading`, `confirmAction`, `showAlert`）封裝為類別方法
   - 工具函式（`escapeHtml`, `formatBytes`, `getTypeLabel`）保持為私有方法
   - `bootstrap.Modal` 等外部 API 透過 `window.bootstrap` 存取（由 CDN 載入的 Bootstrap JS 提供）
   - 模組底部自我初始化：`new CacheTagsPage().init();`
   - 全域變數（`currentPage`, `currentSearch`, `allTags`）改為類別實例屬性
   - 事件監聽器中 `function()` 改為箭頭函式或 `bind(this)` 以維持 `this` 上下文

3. **Controller 簡化**
   - 刪除 `getTagPageHtml()` 方法（不存在任何其他呼叫者，無需保留 delegator）
   - `renderTagPage()` 改為直接載入視圖，傳入 `$nonce`

## Validation

- `TagManagementController` 行數應從 1379 降至約 690
- 視圖檔案 `tags.php` 與前端模組 `cacheTags.js` 內容應與原 `getTagPageHtml()` 內容逐行對應
- 快取標籤管理頁面 `/admin/cache/tags` 應正常渲染且功能完整
- CSP nonce 應正確套用於 `<script type="module">` 標籤
