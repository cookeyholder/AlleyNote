# CI/CD Runbook

## 範圍

本文件為 CI 行為、本地推送前驗證與失敗排查的 canonical 指南。

## 目前使用中的流程

- `.github/workflows/ci.yml`（PHP 測試、程式碼風格、靜態分析）
- `.github/workflows/e2e-tests.yml`
- `.github/workflows/security.yml`
- `.github/workflows/frontend-ci.yml`
- CodeQL（PR 安全分析）

## 本地推送前檢查清單

1. 後端檢查須在本機通過。
2. E2E 檢查須在本機通過（或僅跑受影響範圍並附理由）。
3. 相依套件 lockfile 更新需有明確意圖。
4. 若流程或行為變更，需同步更新文件。

## 建議指令

```bash
# 後端品質關卡
docker compose exec -T web sh -lc 'cd /var/www/html && composer cs-check && composer analyse && vendor/bin/phpunit --no-coverage'

# E2E 關卡
cd tests/e2e
npm ci
npx playwright install --with-deps chromium
CI=true npm test
```

## GitHub CLI 排查

```bash
# 查看 PR 檢查結果
gh pr checks <pr_number>

# 查看最近工作流程執行
gh run list --limit 20

# 查看失敗工作日誌
gh run view <run_id> --json jobs
gh run view <run_id> --job <job_id> --log-failed
```

## 備註

- 若 CI 因環境差異（路徑、金鑰、設定）失敗，請依工作流程順序在本機重現。
- 工作流程調整與文件更新應放在同一個 PR。
