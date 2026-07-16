## 1. 建立視圖目錄與樣板

- [ ] 1.1 建立 `backend/resources/views/admin/cache/tags.php`
  - AC: 包含完整 HTML 結構（DOCTYPE、head、Bootstrap CSS CDN `<link>`、自訂 `<style>`、navbar、統計卡片、表格、Modals）
  - AC: Bootstrap CDN 的 CSS 與 JS 維持不變（`bootstrap.min.css`, `bootstrap-icons.css`, `bootstrap.bundle.min.js`）
  - AC: CSP nonce 使用 `<?= $nonce ?>` 輸出
  - AC: 外部 JS 模組以 `<script type="module" nonce="<?= $nonce ?>" src="/js/pages/admin/cacheTags.js"></script>` 載入（取代內嵌 `<script>` 區塊）
  - AC: 內嵌 `<style>` 區塊保留於視圖內

## 2. 建立前端 ES6 模組

- [ ] 2.1 建立 `frontend/js/pages/admin/cacheTags.js`
  - AC: 以 `export default class CacheTagsPage { }` 封裝
  - AC: 包含所有內嵌 JS 功能：載入統計、載入標籤列表、渲染表格、分頁、搜尋、標籤詳細資訊 Modal、單一/批量清空、確認對話框、alert 通知
  - AC: 全域變數（`currentPage`, `currentSearch`, `allTags`）改為類別實例屬性
  - AC: 事件監聽器使用箭頭函式或 `bind(this)`，確保 `this` 指向類別實例
  - AC: 工具函式（`escapeHtml`, `formatBytes`, `getTypeLabel`）作為私有方法
  - AC: `confirmAction` 與 `showAlert` 封裝為類別方法
  - AC: `bootstrap.Modal` 透過 `window.bootstrap` 全域存取
  - AC: 模組底部自我初始化：`new CacheTagsPage().init();`
  - AC: 無外部相依（純原生 DOM API + `window.bootstrap`）

## 3. 簡化 Controller

- [ ] 3.1 修改 `TagManagementController.php`
  - AC: 刪除 `getTagPageHtml()` 方法（行 705–1378）
  - AC: `renderTagPage()` 改為：
    ```php
    $nonce = $this->headerService->generateNonce();
    require __DIR__ . '/../../../../resources/views/admin/cache/tags.php';
    ```
    （注意：需 **4 層** `..` 從 Controller 回到 `backend/` 根目錄）
  - AC: 移除 `str_replace('CSP_NONCE_PLACEHOLDER', $nonce, $html)`，改為直接將 `$nonce` 傳入視圖
  - AC: 保留 `withHeader('Content-Type', 'text/html; charset=utf-8')` 設定

## 4. 驗證

- [ ] 4.1 執行後端靜態分析
  - AC: `composer analyse` 無新增錯誤
- [ ] 4.2 執行 PHP-CS-Fixer
  - AC: `composer cs-check` 通過
- [ ] 4.3 瀏覽器驗證（手動）
  - AC: `/admin/cache/tags` 頁面正常渲染
  - AC: 統計卡片載入、標籤列表顯示、搜尋、分頁、Modal 皆正常運作
  - AC: 瀏覽器開發者工具 Network 面板確認 `cacheTags.js` 模組正確載入
  - AC: CSP nonce 正確套用，無 CSP 違規回報
