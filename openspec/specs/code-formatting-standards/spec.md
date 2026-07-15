# code-formatting-standards Specification

## Purpose
TBD

## Requirements

### Requirement: 對整個後端執行 PHP-CS-Fixer
系統必須執行 `vendor/bin/php-cs-fixer fix` 處理 `backend/` 中所有 PHP 檔案（排除 `vendor/`），以套用一致的程式碼風格。

#### Scenario: PHP-CS-Fixer 乾淨通過
- **WHEN** 執行 `vendor/bin/php-cs-fixer fix --dry-run --diff`
- **THEN** 不得有任何檔案被回報為需要變更

### Requirement: 對整個前端執行 Prettier
系統必須執行 `npx prettier --write` 處理 `frontend/` 與 `tests/e2e/` 中所有適用的檔案，以套用一致的格式。

#### Scenario: Prettier 乾淨通過
- **WHEN** 在專案根目錄執行 `npx prettier --check .`
- **THEN** 指令必須以狀態碼 0 結束
