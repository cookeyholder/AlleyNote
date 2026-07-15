## 1. 廢棄程式碼移除

- [x] 1.1 移除 `Post::getViewCount()` 方法，並將 `PostTest.php` 改為使用 `getViews()`
- [x] 1.2 移除 `PostRepository::findByUserId()` 方法
- [x] 1.3 移除 `TimezoneHelper::getCommonTimezones()` 方法
- [x] 1.4 從 `functions.php` 移除 `sanitize_post_array()` 函式

## 2. 孤立檔案清理

- [x] 2.1 刪除根目錄一次性腳本（`refactor_exception.php`、`refactor_script.php`、`sort_imports.php`）
- [x] 2.2 刪除巢狀重複檔案 `backend/backend/phpstan-level-10-baseline.neon`
- [x] 2.3 刪除 `backend/scripts/Archive/` 目錄內容
- [x] 2.4 刪除 `backend/examples/` 目錄內容
- [x] 2.5 刪除前端孤立檔案（`test-ckeditor.html`、`FRONTEND_API_UPDATE.md`、`MIGRATION_NOTES.md`）
- [x] 2.6 刪除已停用的測試 `JwtTokenBlacklistIntegrationTest.php.disabled`

## 3. 設定合併

- [x] 3.1 將 `.php-cs-fixer.php` 與 `.php-cs-fixer.dist.php` 合併為單一設定檔
- [x] 3.2 刪除 `backend/backend/phpstan-level-10-baseline.neon`（重複巢狀檔案）
- [x] 3.3 將分析報告從 `backend/storage/` 移至 `docs/archive/`（storage/app 已無報告檔）

## 4. 程式碼格式化

- [ ] 4.1 對整個後端執行 PHP-CS-Fixer（排除 `vendor/`），並用 `--dry-run` 驗證
- [ ] 4.2 對整個前端與 E2E 測試執行 Prettier，並用 `--check` 驗證

## 5. 文件更新

- [ ] 5.1 更新 `README.md`：移除已刪除檔案的路徑引用，反映更新後的專案結構
- [ ] 5.2 檢視並更新 `docs/INDEX.md`：確認文件索引反映目前真實結構
- [ ] 5.3 檢視並更新 `docs/runbooks/DEVELOPMENT.md`：確認開發手冊沒有引用已刪除的測試或腳本
- [ ] 5.4 檢視 `docs/architecture/BACKEND_REFACTOR_2026-04.md`：更新任何引用已刪除檔案的路徑
- [ ] 5.5 檢視 `docs/DOCUMENTATION_GOVERNANCE.md`：確認涵蓋 `docs/archive/` 的歸檔規範
- [ ] 5.6 檢視 `docs/testing/`：更新任何引用已刪除測試檔案的路徑
- [ ] 5.7 更新 `AGENTS.md`：反映專案清理後的實際目錄結構與狀態
- [ ] 5.8 更新 `openspec/specs/documentation-maintenance-governance/spec.md`：同步新增的歸檔規範

## 6. CI 流程建立

- [ ] 6.1 建立 GitHub Actions workflow `.github/workflows/ci.yml`，在 PR 與 push 時觸發
- [ ] 6.2 在 CI 中加入 `composer install` + `composer cs-check` + `composer analyse` + `composer test`
- [ ] 6.3 在 CI 中加入 `npm ci` + `npx prettier --check`
- [ ] 6.4 確認 CI 通過後再合併

## 7. 最終驗證

- [ ] 6.1 執行完整測試套件（`vendor/bin/phpunit`）
- [ ] 6.2 執行 PHPStan 靜態分析
- [ ] 6.3 確認專案可正常編譯與運作
