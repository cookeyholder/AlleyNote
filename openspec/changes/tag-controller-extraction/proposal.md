## Why

`TagManagementController` 是目前專案最大的 PHP 檔案（1379 行），其中約 690 行為 `getTagPageHtml()` 方法內的內嵌 HTML 樣板、CSS 與 JavaScript。這違反關注點分離原則（SoC），使 Controller 難以閱讀與維護，也無法利用前端 ES6 模組化工具鏈。`CacheMonitorController` 僅 167 行且為純 JSON API，故此問題僅存在於 `TagManagementController`。

## What Changes

- 自 `TagManagementController::getTagPageHtml()` 抽取 HTML 樣板至獨立視圖檔案 `resources/views/admin/cache/tags.php`
- 自內嵌 `<script>` 區塊抽取約 465 行 JavaScript 至前端 ES6 模組 `frontend/js/pages/admin/cacheTags.js`，視圖以 `<script type="module" nonce="...">` 載入
- 簡化 `renderTagPage()` 方法，改為 `require` 獨立視圖檔案
- CSP nonce 機制維持不變：從 `str_replace` 改為視圖直接輸出 `<?= $nonce ?>`
- Bootstrap CDN 依賴（CSS/JS）保留於視圖檔案內，不對現有 CSP 設定做任何變更
- 無任何行為或邏輯變更——純粹的抽取與搬移

## Capabilities

### Modified Capabilities

- `cache-tag-management`: Controller 職責由「渲染 HTML + 提供 API」縮減為「僅提供 API 端點與路由分派」，HTML 樣板交由視圖系統處理，前端行為交由 ES6 模組負責。

## Impact

- 影響範圍：
  - 修改：`backend/app/Application/Controllers/Admin/TagManagementController.php`（刪除 ~690 行）
  - 新增：`backend/resources/views/admin/cache/tags.php`（HTML 樣板，Bootstrap CDN、navbar、統計卡片、表格、Modals）
  - 新增：`frontend/js/pages/admin/cacheTags.js`（ES6 模組，self-initializing class）
  - 注意：視圖使用 PHP `require` 載入，**無需修改** `composer.json` autoload
- 成果目標：`TagManagementController` 行數降至 ~690 行，前端管理介面符合現有 ES6 模組規範。
