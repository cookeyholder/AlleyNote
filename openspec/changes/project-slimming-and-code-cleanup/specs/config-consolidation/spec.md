## 新增需求

### 需求：合併 php-cs-fixer 設定檔
系統必須將兩份 php-cs-fixer 設定檔（`.php-cs-fixer.php` 與 `.php-cs-fixer.dist.php`）合併為單一的 `.php-cs-fixer.php`，並移除 `.php-cs-fixer.dist.php`。

#### 情境：僅保留單一設定檔
- **WHEN** 檢查後端目錄
- **THEN** `.php-cs-fixer.php` 必須存在，且 `.php-cs-fixer.dist.php` 不得存在

#### 情境：合併後的設定檔有效
- **WHEN** 執行 `vendor/bin/php-cs-fixer fix --dry-run --diff`
- **THEN** 指令必須以狀態碼 0 結束

### 需求：移除重複的巢狀 phpstan baseline
系統必須刪除 `backend/backend/phpstan-level-10-baseline.neon`，此為整併操作遺留在巢狀 `backend/backend/` 目錄的重複檔案。

#### 情境：巢狀 phpstan baseline 已移除
- **WHEN** 檢查 `backend/backend/`
- **THEN** `phpstan-level-10-baseline.neon` 不得存在於該目錄中

## 修改需求

### 需求：將分析報告從 storage 移至 docs/archive
系統必須將分析報告檔案從 `backend/storage/` 移至 `docs/archive/`。

#### 情境：報告已移至 docs/archive
- **WHEN** 檢查 `backend/storage/app/`
- **THEN** HTML 報告檔案不得存在於 storage 中
- **WHEN** 檢查 `docs/archive/`
- **THEN** 搬移的報告必須存在於新位置
