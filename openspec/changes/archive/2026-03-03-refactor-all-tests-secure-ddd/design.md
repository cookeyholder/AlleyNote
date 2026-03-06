# Design: 全站測試程式重構 (Full Test Suite Refactoring - Secure-DDD)

## Context

目前的測試體系（約 178 個檔案）分散在多個目錄，雖然有 `TestCase` 基類，但缺乏對 **Secure-DDD** 組件（如 `NetworkHelper`, `OutputSanitizerService`）的統一支援。

## Goals / Non-Goals

**Goals:**
- **建立 SecureDDDTestCase**: 提供自動化 Mocking 與常見斷言。
- **百分之百覆蓋**: 重構所有現有測試檔案。
- **優化 AAA 結構**: 提升測試可讀性。

## Decisions

### 1. 建立 `Tests\SecureDDDTestCase`
- **方案**: 建立一個繼承自 `Tests\TestCase` 的新基類。
- **功能**:
    - `mockNetworkHelper()`: 預設模擬常見 IP 來源。
    - `mockOutputSanitizer()`: 提供對 `sanitizeHtml` 與 `sanitizeRichText` 的標準 Mock。
    - `assertSafeResponse()`: 自定義斷言，檢查回應結構是否符合 Secure-DDD 規範。

### 2. 分階段重構策略
- **第一階段**: 重構 `Unit/Services` 與 `Unit/Models`（核心業務與安全性）。
- **第二階段**: 重構 `Unit/Controllers` 與 `Integration/Http`（接口與邊界）。
- **第三階段**: 重構其餘模組（Statistics, Database, etc.）。

## Risks / Trade-offs

- **[Risk] 重構工作量極大** → **Mitigation**: 採用批次處理腳本與樣板輔助重構。
- **[Risk] 破壞既有測試邏輯** → **Mitigation**: 在重構前確保既有測試可在本地（或透過 Docker）執行一次作為基準。
