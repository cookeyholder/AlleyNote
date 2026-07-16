## 1. 更新 Repository 介面與實作

- [x] 1.1 更新 `StatisticsSnapshotRepositoryInterface.php` — 將所有 `Models\StatisticsSnapshot` 回傳型別改為 `Entities\StatisticsSnapshot`
  - AC: import 由 `use App\Domains\Statistics\Models\StatisticsSnapshot` 改為 `use App\Domains\Statistics\Entities\StatisticsSnapshot`
  - AC: 所有 `find*()`、`create()` 方法的回傳型別標註皆使用 `Entities\StatisticsSnapshot`
- [x] 1.2 搜尋 repository 實作類別，將 `new Models\StatisticsSnapshot($data)` 改為 `new Entities\StatisticsSnapshot($data)`
  - AC: 無任何對 `Models\StatisticsSnapshot` 的實例化

## 2. 更新測試

- [x] 2.1 移除 `tests/Unit/Domains/Statistics/Models/StatisticsSnapshotTest.php`
  - AC: 測試目錄中不再包含 `Models/StatisticsSnapshotTest.php`

## 3. 刪除 Models 類別

- [x] 3.1 刪除 `backend/app/Domains/Statistics/Models/StatisticsSnapshot.php`
  - AC: 該類別檔案不再存在於專案中

## 4. 驗證

- [x] 4.1 執行 `composer test` 確認無回歸錯誤
  - AC: 所有測試通過
- [x] 4.2 執行 `composer analyse` 確認 PHPStan level 10 無新增錯誤
  - AC: PHPStan 通過
- [x] 4.3 執行 `composer cs-check` 確認程式碼風格無誤
  - AC: PHP-CS-Fixer 檢查通過
