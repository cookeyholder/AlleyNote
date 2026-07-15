## Why

專案經過多次迭代與重構後累積了大量已廢棄的程式碼、重複的設定檔、孤立的腳本與範例檔案，以及超過 300MB 的專案體積。這些殘留物增加維護成本、干擾靜態分析、並使新開發者難以判斷哪些程式碼是有效的。本次變更旨在系統性地清理無用程式碼、統一設定、並建立程式碼格式化標準作業流程。

## What Changes

- 移除已無呼叫端的 `@deprecated` 方法（`Post::getViewCount()`、`PostRepository::findByUserId()`、`TimezoneHelper::getCommonTimezones()`、`sanitize_post_array()`），並更新相依測試
- 移除根目錄一次性腳本（`refactor_exception.php`、`refactor_script.php`、`sort_imports.php`）
- 移除巢狀重複檔案 `backend/backend/phpstan-level-10-baseline.neon`
- 移除 `backend/scripts/Archive/` 中的 10 個舊版整併腳本
- 移除 `backend/examples/` 中的 3 個範例檔案
- 移除 `frontend/test-ckeditor.html` 測試頁面
- 移除 `frontend/FRONTEND_API_UPDATE.md` 與 `frontend/MIGRATION_NOTES.md` 過時文件
- 移除已停用的測試 `JwtTokenBlacklistIntegrationTest.php.disabled`
- 合併兩份 php-cs-fixer 設定檔為一份
- 將 `backend/storage/` 中已追蹤的分析報告移至 `docs/archive/`
- 全專案執行 PHP-CS-Fixer 格式化
- 全專案執行 Prettier 格式化
- 建立 GitHub Actions CI workflow 自動執行檢查

## Capabilities

### New Capabilities
- `deprecated-code-removal`: 系統性識別並移除無呼叫端的廢棄程式碼，含對應測試更新
- `orphan-file-cleanup`: 清除根目錄、備份目錄與範例目錄中的孤立檔案
- `config-consolidation`: 合併重複的設定檔（php-cs-fixer 與 phpstan 多份 baseline）
- `code-formatting-standards`: 對全專案執行統一的格式化工具並確保 CI 一致性

### Modified Capabilities
- `documentation-maintenance-governance`: 將儲存在 `backend/storage/` 的技術分析報告正規化歸檔至 `docs/archive/`

## Impact

- **影響範圍**: backend/app/、backend/tests/、frontend/、根目錄腳本
- **API 變更**: 無。所有移除的程式碼均已確認無呼叫端
- **測試影響**: `PostTest.php` 需更新一行（`getViewCount()` → `getViews()`）
- **設定影響**: 合併 php-cs-fixer 設定後可能需要微調排除規則
- **CI 影響**: 建立 GitHub Actions CI workflow，包含格式化檢查、PHPStan 靜態分析與 PHPUnit 測試
