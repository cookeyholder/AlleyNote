## Context

目前工作區 (working directory) 包含全方位 Code Review 修復變更（已驗證通過 `PHPStan Level 10` 與 `PHPUnit` 測試），基於 `main` 分支。遠端 `origin` 亦存在已有修改分支 `origin/fix/dependabot-guzzle-security-alerts`。

參見 `proposal.md` 了解發起 PR 的動機。

## Goals / Non-Goals

**Goals:**
1. 建立本地分支 `fix/code-review-improvements`，將目前修復（`RefreshTokenService.php`、`TokenBlacklistRepository.php`、`JwtConfig.php`、`restore_db.sh`、`phpunit.xml` 與 `docs/CODE_REVIEW_REPORT.md`）暫存、Commit 並 Push 至遠端。
2. 使用 GitHub CLI (`gh pr create`) 發起併入 `main` 的 PR。
3. 針對遠端分支 `origin/fix/dependabot-guzzle-security-alerts`，建立併入 `main` 的 PR。

**Non-Goals:**
- 不重寫或增刪非關聯的業務邏輯程式碼。

## Decisions

1. **分開為兩個獨立 PR**：
   - PR 1 (`fix/code-review-improvements` $\to$ `main`)：專注於 code review 產出的資安、效能與架構性重構修復。
   - PR 2 (`fix/dependabot-guzzle-security-alerts` $\to$ `main`)：專注於 Guzzle 安全預警的套件更新修復。
   - **Rationale**: 職責分離 (Separation of Concerns)，便於團隊進行獨立 Code Review 與 CI 驗證。

2. **使用 GitHub CLI (`gh`) 建立 PR**：
   - 包含適當的 Title 與詳細的 Markdown Body 說明，利於代碼審查與稽核。

## Risks / Trade-offs

- [Risk] GitHub CLI 未登入或網路限制 → [Mitigation] 檢查 `gh auth status` 或提供手動建立 PR 的二進制指令與網址連結作為 Fallback。
