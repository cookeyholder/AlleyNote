## Why

根據 2026-03-03 的全方位程式碼審查報告 (v1.0)，專案目前在 CSRF 防護、Token 儲存安全性、資料庫查詢效能以及代碼純淨度方面存在多個 P0/P1 等級的風險。為了確保系統達到生產環境的安全性與效能要求，需要進行系統性的修復與重構。

## What Changes

- **資安防護**：
  - 前端 `apiClient.js` 自動化處理 CSRF Token。
  - JWT Token 儲存機制檢討（localStorage 轉向 Cookie 的初步評估與準備）。
  - 強化 IP 獲取邏輯的安全性。
- **效能優化**：
  - 修正 `PostRepository` 中的 N+1 查詢問題。
  - 最佳化統計資料的快取 TTL 策略。
- **代碼純淨度**：
  - 全面清理殘留的 `error_log` 與 `console.log`。
  - 標準化 DTO 參數型別。

## Capabilities

### New Capabilities
- `automated-csrf-handling`: 前端自動化 CSRF 防禦機制。
- `batch-repository-operations`: 高效的資料庫批次更新能力。

### Modified Capabilities
- `jwt-authentication`: 增強 IP 驗證的可靠性。

## Impact

- 影響前端 API 請求流程。
- 改善資料庫執行效能。
- 提升整體系統安全性。
