## ADDED Requirements

### Requirement: Repository 介面回傳領域實體

`StatisticsSnapshotRepositoryInterface` SHALL 回傳 `Entities\StatisticsSnapshot` 而非 `Models\StatisticsSnapshot`。

#### Scenario: findById 回傳 Entities

- **WHEN** `findById()` 被呼叫
- **THEN** 回傳型別為 `?Entities\StatisticsSnapshot`

#### Scenario: findByUuid 回傳 Entities

- **WHEN** `findByUuid()` 被呼叫
- **THEN** 回傳型別為 `?Entities\StatisticsSnapshot`

#### Scenario: findByTypeAndPeriod 回傳 Entities

- **WHEN** `findByTypeAndPeriod()` 被呼叫
- **THEN** 回傳型別為 `?Entities\StatisticsSnapshot`

#### Scenario: 集合查詢方法回傳 Entities 陣列

- **WHEN** `findBySnapshotType()`、`findByPeriodType()`、`findByDateRange()`、`findLatest()`、`findByTotalViews()` 任一方法被呼叫
- **THEN** 回傳型別為 `Entities\StatisticsSnapshot[]`

#### Scenario: create 回傳 Entities

- **WHEN** `create()` 被呼叫
- **THEN** 回傳型別為 `Entities\StatisticsSnapshot`

### Requirement: Models 類別被移除

`App\Domains\Statistics\Models\StatisticsSnapshot` SHALL 被刪除。

#### Scenario: 類別檔案不存在

- **WHEN** 專案中搜尋 `class StatisticsSnapshot` in `App\Domains\Statistics\Models`
- **THEN** 該類別不存在

#### Scenario: 無其他程式碼引用 Models

- **WHEN** 搜尋 `App\Domains\Statistics\Models\StatisticsSnapshot`
- **THEN** 無任何引用

### Requirement: 測試必須反映型別變更

`tests/Unit/Domains/Statistics/Models/StatisticsSnapshotTest.php` SHALL 被移除以對應 `Models\StatisticsSnapshot` 的刪除。

#### Scenario: 測試檔案被移除

- **WHEN** 搜尋 `StatisticsSnapshotTest` in `tests/Unit/Domains/Statistics/Models/`
- **THEN** 該測試檔案不存在

### Requirement: 靜態分析通過

變更後的程式碼 MUST 通過 PHPStan level 10 檢查。

#### Scenario: PHPStan 無錯誤

- **WHEN** 執行 `composer analyse`
- **THEN** 無新增錯誤
