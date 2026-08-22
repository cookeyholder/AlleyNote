## 1. 後端 CI 最佳化

- [ ] 1.1 修改 ci.yml 使用 coverage: pcov 並配置 concurrency 與 paths-ignore
- [ ] 1.2 加入 PHPStan 結果快取步驟

## 2. E2E 測試與 Playwright 並行加速

- [ ] 2.1 修改 playwright.config.js 在 CI 環境下啟用 2 個 worker
- [ ] 2.2 修改 e2e-tests.yml 加入 concurrency 與 paths-ignore

## 3. 安全掃描與前端 CI 流程調整

- [ ] 3.1 修改 security.yml 加入 concurrency 與 paths-ignore
- [ ] 3.2 修改 frontend-ci.yml 加入 concurrency 與 paths-ignore

## 4. 驗證與效能測試

- [ ] 4.1 驗證本機與 CI 設定語法正確
- [ ] 4.2 發起 PR 並量測加速後的 CI 執行耗時
