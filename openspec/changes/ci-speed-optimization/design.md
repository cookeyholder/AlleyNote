# 設計文件：CI 流程加速

## 架構變更

### 1. 後端 CI (`ci.yml`)
- `coverage: pcov` 取代 `coverage: xdebug`。
- 新增 `storage/phpstan` 快取，加速 PHPStan Level 10 靜態分析。
- 加入 `concurrency`，同一 PR 推進新 Commit 時自動取消前一次舊任務。
- 加入 `paths-ignore`，文件變更不觸發後端測試。

### 2. 端對端測試 (`e2e-tests.yml` & `playwright.config.js`)
- CI 環境下設定 `workers: 2`，充分利用 2-Core Runner 算力。
- 完善 Playwright 瀏覽器快取機制。
- 加入 `concurrency` 與 `paths-ignore`。

### 3. 安全與前端 CI (`security.yml`, `frontend-ci.yml`)
- 移除重複的重複掃描步驟。
- 加入 `concurrency` 與 `paths-ignore`。
