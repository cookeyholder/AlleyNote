## Why

為了將全方位程式碼審查 (Code Review) 的資安與架構修復成果併入正式 `main` 分支，同時同步遠端已解決之 Guzzle 安全預警修復分支 (`origin/fix/dependabot-guzzle-security-alerts`)，需建立與發起對應的 Pull Requests (PR)。

## What Changes

1. **程式碼審查修復分支 PR**：
   - 建立修復分支 `fix/code-review-improvements`，將近期的資安修復（JWT 密鑰退避降級、`RefreshTokenService` 不可變物件漏洞修正、`TokenBlacklistRepository` 預編譯 SQL 查詢改善、`restore_db.sh` 驗證腳本）提交並推送至遠端。
   - 透過 GitHub CLI (`gh pr create`) 建立針對 `main` 的 Pull Request。
2. **Dependabot Guzzle 資安修復分支 PR**：
   - 針對遠端分支 `origin/fix/dependabot-guzzle-security-alerts`，為其發起併入 `main` 的 Pull Request。

## Capabilities

### New Capabilities
無（本變更為 Git 流程與 Pull Request 建立作業）。

### Modified Capabilities
無。

## Impact

- 專案版本控制與 CI/CD 流程。
- 遠端 GitHub 儲存庫 `main` 分支將包含最新的 Code Review 修復與安全修補套件。
