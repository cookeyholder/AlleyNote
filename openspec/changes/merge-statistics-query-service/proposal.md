## Why

`StatisticsQueryServiceInterface` 僅有一個實作 `StatisticsQueryService`，且短期內無引入第二個實作的計畫，違反 YAGNI 原則，屬於過度工程化。合併後可減少介面維護成本與程式碼複雜度。

## What Changes

- **移除** `StatisticsQueryServiceInterface`（Domains 層介面）
- **移除** `StatisticsQueryService`（Application 層服務）
- 將 `StatisticsQueryService` 的 25 個方法（6 公開 + 19 私有）合併至 `StatisticsApplicationService`
- 更新所有呼叫端使用 `StatisticsApplicationService` 取代原介面/服務
- 更新 `StatisticsServiceProvider` 移除介面綁定
- 更新對應測試

## Capabilities

### New Capabilities
- `merge-query-into-application-service`: 將統計查詢功能合併至統計應用服務，移除不必要的抽象層

### Modified Capabilities

無 — 此變更僅涉及實作重組，不改變既有行為規格。

## Impact

- 7 個檔案受影響（含測試）
- 移除 1 個介面檔案、1 個服務類別檔案
- 1 個服務類別新增 25 個方法（merged）
- 4 個呼叫端更新依賴注入
- 1 個 Service Provider 更新註冊邏輯
- 測試檔案需對應調整
