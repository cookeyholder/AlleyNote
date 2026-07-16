## Context

`App\Domains\Statistics\Models\StatisticsSnapshot`（159 行）是一個貧血資料映射器，僅實作 `JsonSerializable`，提供 getter、`toArray()`、`fromArray()` 與建構式，沒有任何領域邏輯、驗證或商業行為。與之對應的 `App\Domains\Statistics\Entities\StatisticsSnapshot`（481 行）則是完整的領域實體，包含快照類型驗證、週期管理、資料完整性檢查、過期判斷、統計指標查詢等領域方法。

`Models\StatisticsSnapshot` 僅被 `StatisticsSnapshotRepositoryInterface` 引用作為所有 `find*()` 方法的回傳型別。`StatisticsSnapshotRepositoryInterface` 目前已無任何實作類別，也無 DI 容器綁定（屬於死亡程式碼），但基於完整性仍一併修正其型別約束。

## Goals / Non-Goals

**Goals:**
- 移除 `Models\StatisticsSnapshot` 類別，消除重複
- 將 `StatisticsSnapshotRepositoryInterface` 的所有 `Models\StatisticsSnapshot` 回傳型別改為 `Entities\StatisticsSnapshot`
- 更新相依測試使其改用 `Entities\StatisticsSnapshot`

**Non-Goals:**
- 不更動 `Entities\StatisticsSnapshot` 的程式碼
- 不改變資料庫 schema 或 migration
- 不改變 repository 介面的方法簽章結構（僅回傳型別）
- 不更動其他 Domain 的程式碼

## Decisions

1. **回傳型別直接改為 `Entities\StatisticsSnapshot`**
   - `Models\StatisticsSnapshot` 與 `Entities\StatisticsSnapshot` 的建構式都接受 `array $data`，資料來源相同（皆為 PDO 查詢結果），因此 repository 實作只需將 `new Models\StatisticsSnapshot($row)` 改為 `new Entities\StatisticsSnapshot($row)` 即可。
   - `Entities\StatisticsSnapshot` 的建構式會驗證 `snapshot_type` 是否為支援的類型，若資料庫中存在不合法的類型值會在執行時期拋出 `InvalidArgumentException`。這在理論上是改善（防禦性程式設計），但如果資料中有髒資料需要先清理。

2. **測試直接移除並取代**
   - `tests/Unit/Domains/Statistics/Models/StatisticsSnapshotTest.php` 是針對 `Models\StatisticsSnapshot` 的行為測試，由於該類別將被刪除，測試應一併移除。如果需要補償測試覆蓋，可考慮針對 `Entities\StatisticsSnapshot` 新增測試，但不在本次變更範圍內。

## Risks / Trade-offs

- **[風險] Entities 建構式驗證更嚴格**：`Entities\StatisticsSnapshot` 在建構時會驗證 `snapshot_type` 是否在支援清單內，若資料庫中存在舊資料或髒資料，可能導致 repository 查詢拋出例外。→ **緩解方式**：在部署前先確認資料庫中 `statistics_snapshots.snapshot_type` 的值皆為合法值。
