## Context

現行測試混合了三種性質：

1. 使用者旅程（應保留在 E2E）
2. 資料/規則正確性（應下沉整合測試）
3. 重複性頁面巡檢（應合併為 smoke）

## Goals

- 降低 E2E 對環境資料與外部依賴的脆弱性。
- 保留關鍵風險路徑的端到端保護。
- 讓規則驗證在整合測試中可穩定、可重現。

## Non-Goals

- 本變更不在同一批次完整實作所有整合測試案例；先完成 E2E 降級與責任切分。
- 不重寫產品功能邏輯。

## Architecture / Strategy

### 1) 分層策略

- E2E（保留）
  - 登入/登出與授權重導
  - 文章建立與最小編輯流程
  - 後台主要導覽可達性
  - 批次刪除主流程（小樣本）
  - 角色權限核心互動

- Integration（新增/接手）
  - 時區轉換與 DB 儲存一致性
  - 密碼規則矩陣（長度/字元集/黑名單/連續字元）
  - 統計 API 數據正確性與排序
  - 設定值持久化、跨頁反映
  - 標籤/角色/權限關聯完整性

### 2) E2E 降級方式

- 對高環境依賴套件採 `describe.skip` 或條件式 skip（以穩定 CI）。
- 對重複 suite 合併為 smoke（減少重複登入與跳頁）。
- 對資料正確性 assertions 以存在性/流程性 assertions 取代。

### 3) 套用檔案範圍（本次 apply）

- 明確降級：
  - `06-timezone.spec.js`
  - `13-batch-delete-posts.spec.js`（保留主流程、精簡邊界案例）
  - `14-admin-pages-comprehensive.spec.js`（合併至個別頁面 smoke）
- 既有已降級（納入此 change 管理）：
  - `11-statistics.spec.js`
  - `15-system-settings.spec.js`
  - `16-settings-integration.spec.js`
  - `17-ckeditor-features.spec.js`
  - `19-ckeditor-availability.spec.js`
  - `15-post-detail.spec.js`

## Rollout

1. 先讓 E2E 套件回到穩定可執行（低 false-negative）。
2. 逐步補齊 PHP 整合測試（按風險優先：密碼規則→時區→設定→統計）。
3. 最終移除暫時性 skip，改由明確 smoke + integration coverage 組合。
