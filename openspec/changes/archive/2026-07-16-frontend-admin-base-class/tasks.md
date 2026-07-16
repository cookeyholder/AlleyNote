## 1. 建立基底類別與 API 模組

- [ ] 1.1 建立 `frontend/js/components/BaseAdminPage.js`，實作建構子（items、loading 狀態、layout binding）、init→render→attachEventListeners 生命週期、showLoading/hideLoading、escapeHtml 方法
- [ ] 1.2 建立 `frontend/js/api/modules/tags.js`，參照現有 API 模組（如 posts.js、users.js）實作 tags 的 CRUD 方法

## 2. 重構各管理頁面

- [ ] 2.1 重構 `frontend/js/pages/admin/profile.js`：改為 extends BaseAdminPage，移除重複生命週期樣板與自有的 escapeHtml
- [ ] 2.2 重構 `frontend/js/pages/admin/roles.js`：改為 extends BaseAdminPage，移除重複生命週期樣板與自有的 escapeHtml
- [ ] 2.3 重構 `frontend/js/pages/admin/statistics.js`：改為 extends BaseAdminPage，移除重複生命週期樣板與自有的 escapeHtml
- [ ] 2.4 重構 `frontend/js/pages/admin/tags.js`：改為 extends BaseAdminPage，移除重複生命週期樣板與自有的 escapeHtml，改用 `api/modules/tags.js`
- [ ] 2.5 重構 `frontend/js/pages/admin/users.js`：改為 extends BaseAdminPage，移除重複生命週期樣板與自有的 escapeHtml

## 3. 驗證

- [ ] 3.1 確認所有頁面匯入路徑正確、無循環相依
- [ ] 3.2 執行前端 lint 檢查（`npm run lint`）
- [ ] 3.3 啟動開發伺服器，手動巡覽 5 個管理頁面確認功能正常
