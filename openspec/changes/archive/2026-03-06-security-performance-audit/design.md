## Context (背景脈絡)

GitHub Dependabot 已經回報了前端相依套件中存在多個高嚴重性 (High severity) 的漏洞，例如 `minimatch` 的 ReDoS 與 `rollup` 的路徑遍歷 (Path Traversal) 漏洞。此外，目前的程式碼庫缺乏統一的資安最佳實務和效能優化基準。我們必須針對整個應用程式（包含後端 PHP 與前端 JS/TS）進行全面的逐行審查，以確保長期的穩定性與安全性。

## Goals / Non-Goals (目標與非目標)

**Goals (目標):**
- 將有漏洞的相依套件（`minimatch`、`rollup` 等）升級至安全的版本。
- 對前端與後端進行嚴謹的逐行審查，以找出並解決安全風險（例如：XSS、SQLi、CSRF）與效能瓶頸。
- 實作自動化的資安與效能檢查（例如整合進 CI/CD 流程中）。

**Non-Goals (非目標):**
- 新增與資安或效能無關的新功能。
- 從頭徹底重寫應用程式架構。

## Decisions (技術決策)

- **TDD (測試驅動開發) 與高頻提交策略 (TDD & High-Frequency Commits):**
  - **先寫測試 (Test-First):** 針對每一個被發現的漏洞或效能瓶頸，必須先撰寫一個**會失敗的測試 (Red)**。例如：模擬 SQL Injection 攻擊的測試，或是斷言某個操作必須在 500ms 內完成的效能測試。
  - **實作修復 (Green):** 執行重構、升級相依套件或修改邏輯，直到測試通過。
  - **一任務一提交 (Commit per Task):** 每一項細微的修復任務在確認測試通過後，必須獨立進行一次 Conventional Commit（例如 `fix(security): ...` 或 `perf(backend): ...`），確保版本歷史清晰且容易追溯。
- **相依套件升級 (Dependency Upgrades):** 我們將有策略地使用 `npm audit fix` 與 `composer update`，確保不會對現有功能造成破壞性變更 (breaking changes)，並在每次重大版本更新後進行徹底測試。
- **手動審查策略 (Manual Audit Strategy):** 我們將結合靜態程式碼分析與手動的逐行審查。在後端方面，我們將嚴格要求型別標註並強制使用 Prepared Statements (預處理語句)。在前端方面，我們將審查套件的使用情況以及對 DOM 的操作方式。
- **CI/CD 整合 (CI/CD Integration):** 我們將嚴格整合 CodeQL 與 Dependabot，以阻擋任何引入新漏洞的 PR (Pull Requests)。

## Risks / Trade-offs (風險與取捨)

- **風險：相依套件升級可能帶來破壞性變更。** → **緩解措施 (Mitigation)：** 透過 TDD 策略，在升級前先確保有涵蓋核心功能的測試，升級後執行全面的單元測試與 E2E (端到端) 測試。
- **風險：效能優化可能會增加程式碼的複雜度。** → **緩解措施 (Mitigation)：** 我們將優先考量程式碼的可讀性與可維護性，只有在發現可測量的效能瓶頸（即有失敗的效能測試佐證時），才套用較複雜的優化手法。