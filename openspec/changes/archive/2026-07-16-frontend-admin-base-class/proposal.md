## Why

五個 class-based 管理後台頁面（profile.js、roles.js、statistics.js、tags.js、users.js）存在大量重複的建構→init→render→attachEventListeners 生命週期樣板，且 `escapeHtml()` 工具函式在 5 個 class-based 頁面中各自複製貼上。這導致維護成本增加，新增管理頁面時也缺少一致的模式可遵循。

## What Changes

- 建立 `BaseAdminPage` 抽象基底類別，封裝共用生命週期與工具方法
- 5 個 class-based 管理頁面改為繼承 `BaseAdminPage`，移除重複樣板
- 移除 5 份複製的 `escapeHtml()`（profile.js、roles.js、statistics.js、tags.js、users.js），統一由基底類別提供
- 新增 `api/modules/tags.js` API 模組（tags 是目前唯一缺少專屬 API 模組的領域）
- **純重構**：不變更任何行為或 UI

## Capabilities

### New Capabilities

- `base-admin-page`: 管理後台頁面的共用基底類別，定義建構、載入、渲染、事件綁定的標準生命週期與共用工具方法

### Modified Capabilities

（無 — 此變更為純重構，不涉及 spec 層級的行為變更）

## Impact

- **新增檔案**：`frontend/js/components/BaseAdminPage.js`、`frontend/js/api/modules/tags.js`
- **修改檔案**：`frontend/js/pages/admin/profile.js`、`frontend/js/pages/admin/roles.js`、`frontend/js/pages/admin/statistics.js`、`frontend/js/pages/admin/tags.js`、`frontend/js/pages/admin/users.js`
- **不受影響**：4 個 functional-style 頁面（dashboard.js、posts.js、postEditor.js、settings.js）不在範圍內
- **不影響後端**：僅前端重構，無 API 合約變更
