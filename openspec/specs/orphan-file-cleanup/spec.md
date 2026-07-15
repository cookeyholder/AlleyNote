# orphan-file-cleanup Specification

## Purpose
TBD

## Requirements

### Requirement: 移除根目錄的一次性腳本
系統必須刪除以下僅在過去重構中使用過的根目錄腳本：
- `refactor_exception.php`
- `refactor_script.php`
- `sort_imports.php`

#### Scenario: 根目錄腳本已刪除
- **WHEN** 檢查專案根目錄
- **THEN** `refactor_exception.php`、`refactor_script.php`、`sort_imports.php` 不得存在

### Requirement: 移除 backend/backend/ 中的巢狀重複檔案
系統必須刪除 `backend/backend/phpstan-level-10-baseline.neon`。

#### Scenario: 巢狀重複檔案已移除
- **WHEN** 檢查 `backend/backend/`
- **THEN** `phpstan-level-10-baseline.neon` 不得存在於該目錄中

### Requirement: 移除已封存的整併腳本
系統必須刪除 `backend/scripts/Archive/` 中的所有檔案，但保留目錄結構。

#### Scenario: 封存腳本已移除
- **WHEN** 檢查 `backend/scripts/Archive/`
- **THEN** 該目錄內不得有任何檔案

### Requirement: 移除範例檔案
系統必須刪除 `backend/examples/` 中的檔案。

#### Scenario: 範例檔案已移除
- **WHEN** 檢查 `backend/examples/`
- **THEN** 該目錄內不得有任何檔案

### Requirement: 移除前端測試頁面與過時文件
系統必須刪除以下前端檔案：
- `frontend/test-ckeditor.html`
- `frontend/FRONTEND_API_UPDATE.md`
- `frontend/MIGRATION_NOTES.md`

#### Scenario: 前端孤立檔案已刪除
- **WHEN** 檢查 `frontend/`
- **THEN** `test-ckeditor.html`、`FRONTEND_API_UPDATE.md`、`MIGRATION_NOTES.md` 不得存在

### Requirement: 移除已停用的測試檔案
系統必須刪除 `backend/tests/Integration/JwtTokenBlacklistIntegrationTest.php.disabled`。

#### Scenario: 已停用測試已刪除
- **WHEN** 檢查 `backend/tests/Integration/`
- **THEN** `JwtTokenBlacklistIntegrationTest.php.disabled` 不得存在
