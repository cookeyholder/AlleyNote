## Why

目前專案代碼行覆蓋率為 48.46%、方法覆蓋率為 42.64%，尚未達到高品質企業級系統標準（85% 以上）。為了確保各 Bounded Context 與核心 API 的穩定性與防止回歸錯誤，需要系統化補齊單元測試與端對端 API 整合測試，將全案測試覆蓋率提升至 85% 以上。

## What Changes

- **單元測試全覆蓋 (Unit Tests Expansion)**：針對 7 個 Bounded Context 的 Services、Repositories、Controllers、Middleware、ValueObjects、DTOs 補齊邊界條件、異常路徑與型別驗證測試。
- **端對端 API 整合測試 (Integration Tests Expansion)**：建立完整的 PSR-7 HTTP 請求到資料庫持久層的整合測試，涵蓋文章 CRUD、會員認證與授權、附件上傳、系統設定、安全防護與審計日誌。
- **全鏈路 Mock 與 SQLite Memory 整合**：採用標準化測試基類與工廠機制，確保測試具備高隔離性與高速執行能力。

## Capabilities

### New Capabilities
- `unit-testing-expansion`: 針對核心領域服務與應用層控制器擴展單元測試覆蓋率。
- `integration-testing-expansion`: 針對全系統 API 端點與資料庫交互建立完整的整合測試套件。

### Modified Capabilities
<!-- 無既有業務需求行為變更 -->

## Impact

- 影響範圍：`backend/tests/Unit/` 與 `backend/tests/Integration/`。
- 不修改既有生產業務邏輯，僅提升代碼質量與 CI 測試嚴謹度。
