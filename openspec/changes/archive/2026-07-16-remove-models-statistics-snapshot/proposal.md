## Why

`App\Domains\Statistics\Models\StatisticsSnapshot`（159 行）與 `App\Domains\Statistics\Entities\StatisticsSnapshot`（481 行）為同一概念的重複實作。Models 版本僅作為貧血資料映射器存在，缺少領域邏輯與驗證，是歷史命名錯誤的產物。

## What Changes

- 刪除 `Models\StatisticsSnapshot` 類別
- `StatisticsSnapshotRepositoryInterface` 所有回傳型別由 `Models\StatisticsSnapshot` 改為 `Entities\StatisticsSnapshot`
- 更新測試中對 `Models\StatisticsSnapshot` 的引用

## Capabilities

### New Capabilities

- `remove-models-snapshot`: 移除 `Models\StatisticsSnapshot`，統一使用 `Entities\StatisticsSnapshot`，更新 repository 介面、實作與測試

## Impact

- 刪除 1 個檔案：`backend/app/Domains/Statistics/Models/StatisticsSnapshot.php`
- 修改 1 個檔案：`backend/app/Domains/Statistics/Contracts/StatisticsSnapshotRepositoryInterface.php`
- 修改 1 個測試檔案：`backend/tests/Unit/Domains/Statistics/Models/StatisticsSnapshotTest.php`
