## Why

PR #78 已完成大規模架構重構（例外處理機制、ApiResource、ApiTestCase 測試基礎設施），但目前 canonical 文件仍偏重操作流程，缺少重構後技術決策與維護準則的完整說明。若不立即補齊，後續開發與 code review 容易回退到舊模式，造成維護成本上升。

## What Changes

- 補強文件資訊架構，新增「重構後技術細節」的 canonical 章節與導覽規範。
- 在文件治理規範中新增「重大重構必須更新技術文件」的觸發條件與審查門檻。
- 定義重構後必須記錄的最小技術內容清單（架構決策、程式邊界、測試策略、常見風險）。
- 將 OpenSpec change 任務與具體文件路徑綁定，確保 PR 合併前可追蹤完成度。

## Capabilities

### New Capabilities
- None.

### Modified Capabilities
- `documentation-information-architecture`: 要求 canonical 文件提供重構後架構與技術細節的固定入口與導覽路徑。
- `documentation-maintenance-governance`: 要求重大重構 PR 必須同步更新技術文件，並在審查流程中驗證更新內容完整性。

## Impact

- **Affected Docs**: `README.md`, `docs/runbooks/DEVELOPMENT.md`, `docs/architecture/`（新增或補齊架構說明）。
- **Affected Process**: PR 審查清單、OpenSpec change 任務追蹤、文件維護責任歸屬。
- **Affected Code Areas (for documentation scope)**: `backend/app/Infrastructure/Http/ExceptionRegistry.php`, `backend/app/Shared/Http/ApiResource.php`, `backend/app/Application/Resources/PostResource.php`, `backend/tests/Support/ApiTestCase.php`。
- **Dependencies**: 無新增執行期相依套件；僅更新文件規格與治理流程。
