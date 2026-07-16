## MODIFIED Requirements

### Requirement: Controller 不應內嵌 HTML 樣板與 JavaScript

Controller MUST 僅負責 HTTP 請求分派與商業邏輯委派，HTML 渲染 SHALL 交由視圖系統，前端行為 SHALL 交由 ES6 模組管理。

#### Scenario: HTML 樣板抽取

- **WHEN** 使用者造訪 `/admin/cache/tags`
- **THEN** `TagManagementController::renderTagPage()` 回傳的 HTML 應來自獨立的視圖檔案 `resources/views/admin/cache/tags.php`，而非 Controller 內嵌的 heredoc
- **AND** Bootstap CDN CSS `<link>`、Bootstrap CDN JS `<script>`、自訂 `<style>` 區塊均保留於視圖內

#### Scenario: JavaScript 抽取為 ES6 模組

- **WHEN** 使用者造訪 `/admin/cache/tags`
- **THEN** 頁面載入的 JavaScript 應來自 ES6 模組 `frontend/js/pages/admin/cacheTags.js`，以 `<script type="module" nonce="..." src="/js/pages/admin/cacheTags.js">` 載入
- **AND** 模組為 self-initializing（底部呼叫 `new CacheTagsPage().init()`），不依賴 SPA router
- **AND** 模組僅依賴 `window.bootstrap`（由 CDN 提供）與原生 DOM API

#### Scenario: CSP nonce 機制保留

- **WHEN** `SecurityHeaderService` 產生 nonce
- **THEN** 視圖中的 `<script type="module" nonce="<?= $nonce ?>">` 應正確帶入該 nonce 值
- **AND** 不再使用 `CSP_NONCE_PLACEHOLDER` 字串取代機制

#### Scenario: 視圖路徑正確性

- **WHEN** `renderTagPage()` 載入視圖
- **THEN** `require` 路徑應使用正確的相對路徑 `__DIR__ . '/../../../../resources/views/admin/cache/tags.php'`（4 層 `..`）指向 `backend/resources/views/admin/cache/tags.php`

## ADDED Requirements

### Requirement: 前端管理模組命名規則

快取標籤管理前端模組 MUST 與內容標籤管理前端模組（`tags.js`）區分命名，避免混淆。

#### Scenario: 命名區隔

- **WHEN** 開發者需要修改快取標籤管理頁面行為
- **THEN** 應在 `frontend/js/pages/admin/cacheTags.js` 而非 `tags.js` 中修改

### Requirement: JS 類別上下文正確性

抽取後的 JavaScript 類別方法 MUST 正確維持 `this` 上下文。

#### Scenario: 事件監聽器中的 this

- **WHEN** `CacheTagsPage` 類別中的事件監聽器被觸發
- **THEN** `this` 應指向類別實例，而非 DOM 元素；需使用箭頭函式或 `.bind(this)`

#### Scenario: 全域狀態遷移

- **WHEN** JS 自內嵌 `<script>` 抽取為類別
- **THEN** `currentPage`、`currentSearch`、`allTags` 等全域變數 SHALL 改為類別實例屬性
