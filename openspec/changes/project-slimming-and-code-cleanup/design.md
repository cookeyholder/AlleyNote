## Context

AlleyNote 後端為 PHP 專案，前端為 JavaScript/TypeScript 專案，兩者各自有程式碼格式化工具。專案歷經多次重構後累積了超過 300MB 的無用資產：

- 後端已在方法上標記 `@deprecated` 但從未移除
- 根目錄殘留一次性重構腳本
- `backend/scripts/Archive/` 有 10 個舊版工作的整併腳本（68KB）已無用途
- `backend/examples/` 有 3 個範例檔（24KB）僅供參考但已過時
- `backend/backend/` 目錄因先前整併操作遺留重複的 phpstan baseline
- `frontend/` 殘留測試頁面與過時文件
- `backend/storage/` 混雜了程式碼分析報告（非執行期產物）
- 兩份 php-cs-fixer 設定檔（`.php-cs-fixer.php` 與 `.php-cs-fixer.dist.php`）內容不一致

這些廢棄項目不會造成錯誤，但會：
- 使程式碼搜尋結果雜亂（`DatabaseConnection` 有 4 處 import 但無人使用 PDO 實例以外的功能）
- 讓新開發者難以判別哪些檔案是有效的
- 浪費 CI 與 IDE 索引時間

## Goals / Non-Goals

**Goals:**
- 移除無任何呼叫端的 `@deprecated` 方法（已由 codegraph 靜態分析確認）
- 清除根目錄、備份目錄、範例目錄中的孤立檔案
- 合併重複設定檔並統一整份
- 全專案執行格式化，確保符合統一的程式碼風格
- 將分析報告從 `backend/storage/` 移至 `docs/archive/`

**Non-Goals:**
- 不重構 `DatabaseConnection` 類別（仍需在 container.php 與測試中使用 PDO singleton）
- 不修改業務邏輯行為
- 不變更 API 簽章或資料庫 schema
- 不引入新的 lint/format 工具（僅沿用既有工具）
- 不重構測試基礎設施

## Decisions

### 1. 使用 codegraph 而非 grep 驗證呼叫端
**決策**: 使用 `codegraph callers` 靜態分析確認每個 `@deprecated` 符號的真實呼叫端。
**理由**: grep 會產生 false positive（例如註解中的文字、同名但不同 namespace 的方法）。codegraph 解析 PHP AST 與 use statements，只回傳實際呼叫路徑。
**替代方案**: PHPStan 的 `checkDeprecated` 規則 — 但專案尚未全面啟用此規則。

### 2. 不處理 `DatabaseConnection` 的移除
**決策**: 保留 `DatabaseConnection` 類別及其 `@deprecated` 標記。
**理由**: 該類別雖標記為 `@deprecated`，但仍在 `container.php` 與 5 個測試檔案中使用。完全移除需要整個測試基礎設施從 PDO singleton 遷移到 DI 容器，這是一個獨立的重構工作。
**替代方案**: PHPStan baseline 中忽略此類別直到重構完成。

### 3. phpstan 多份 baseline 暫不合併
**決策**: 保留 `backend/phpstan-baseline.neon` 與 `backend/phpstan-level-10-baseline.neon` 兩份 baseline 不處理。
**理由**: 不同 baseline 分別記錄不同 analysis level 的已知錯誤。`phpstan-level-10-baseline.neon` 在根目錄與 `backend/backend/` 各有一份，後者為重複檔案（由孤立檔案清理任務移除）。但 `backend/` 根目錄的兩份 baseline 各有用途，暫不整併。
**替代方案**: 待未來決定統一的 PHPStan level 後再合併為單一 baseline。

### 4. 保留 `.php-cs-fixer.php` 作為正式設定，移除 `.php-cs-fixer.dist.php`
**決策**: 保留 `.php-cs-fixer.php`（預設載入），刪除 `.php-cs-fixer.dist.php`。
**理由**: PHP-CS-Fixer 3.x 優先讀取 `.php-cs-fixer.php`。`.dist` 版本應為分發用的範本，但實際上兩份檔案都在專案根目錄，造成混淆。檢查內容後，保留較完整的那份。

### 5. 建立 CI pipeline 確保格式與測試一致性
**決策**: 建立 GitHub Actions CI workflow，在每次 PR 與 push 時自動執行格式化檢查、靜態分析與測試。
**理由**: 專案目前無 CI/CD（`.github/` 為空），格式化後若無自動檢查會隨時間走樣。趁本次清理一併建立基礎 CI，確保後續變更維持品質。
**替代方案**: 手動執行 — 但可靠度低，不適合多人協作。

### 6. 分析報告搬遷至 `docs/archive/` 而非直接刪除
**決策**: 將 `backend/storage/app/report-*.html` 類型檔案移至 `docs/archive/`。
**理由**: 這些報告是 PHPStan 分析結果的 HTML 輸出，屬於技術文件而非執行期儲存資料。搬遷到 docs 目錄更符合其性質，同時縮小 storage 目錄大小。

### 7. `backend/scripts/Archive/` 目錄保留但清空內容
**決策**: 刪除 `backend/scripts/Archive/` 內所有檔案，但保留目錄結構。
**理由**: 目錄本身為專案的一部分，日後可能再次用於封存腳本。刪除內容而非目錄可避免 git 追蹤空目錄的困擾（git 不追蹤空目錄）。

### 8. 格式化不修改 git history，僅 clean working tree
**決策**: 格式化工具僅處理當前檔案內容，不 rebase 或改寫歷史。
**理由**: 此類別淨的工作不應污染 git blame。建議開發者提交前先執行格式化。

## Risks / Trade-offs

- **格式化後的 git blame 偏移** → 建議在格式化提交的 commit message 中加入 `[skip ci]` 或特殊標記，後續 blame 時可跳過此 commit
- **php-cs-fixer 設定合併可能漏掉規則** → 合併後執行 `vendor/bin/php-cs-fixer fix --dry-run --diff` 檢查無 error
- **刪除檔案後若有遺漏的引用會在 CI 報錯** → 所有移除項目均經過 codegraph 確認無呼叫端，風險極低
- **Prettier 格式化可能改動較大（前端）** → 建議先執行 `--check` 模式，確認改動範圍後再執行 `--write`
- **CI 首次設定可能因環境差異失敗** → 先在專案根目錄手動驗證 `composer check-all` 與 `npm run lint` 通過後再設定 workflow
