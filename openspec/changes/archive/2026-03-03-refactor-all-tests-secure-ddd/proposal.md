# Proposal: 全站測試程式重構 (Full Test Suite Refactoring - Secure-DDD)

## Why

為了確保 AlleyNote 專案的品質保證體系與最新的 **Secure-DDD** 架構保持一致，目前的 178 個測試檔案需要全面重構。現有的測試大多缺乏統一的輔助工具，且未能充分驗證集中化的安全組件（如 `NetworkHelper` 與 `OutputSanitizerService`）。本次重構旨在提升測試的可維護性、嚴謹性與執行效率。

## What Changes

- **導入 Secure-DDD 測試基類**: 建立具備自動 Mock 常用安全組件能力的 Base Test Classes。
- **重構 Unit Tests**: 將所有單元測試轉化為 AAA (Arrange-Act-Assert) 模式，並確保 Mock 邏輯符合 Secure-DDD 規範。
- **重構 Integration Tests**: 確保集成測試能正確驗證跨層的安全防禦機制。
- **統一 Mocking 策略**: 消除測試中的重複 Mock 邏輯，建立標準化的 Domain Service Mocks。
- **清理過時測試**: 移除與舊架構（如 Controller 內的私有 IP 取得方法）相關的冗餘測試。

## Capabilities

### Modified Capabilities

- `test-infrastructure`: 升級整個測試基礎設施，支援 Secure-DDD 模式。

## Impact

- **測試覆蓋**: 涵蓋 `backend/tests` 下的所有 178 個測試檔案。
- **開發者體驗**: 提供更直觀、更易寫的測試輔助工具。
