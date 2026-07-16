## 1. 搬遷 StatisticsQueryService 至 Domain 層

- [ ] 1.1 在 `Domains/Statistics/Services/` 建立新的 `StatisticsQueryService.php`，namespace 為 `App\Domains\Statistics\Services`
- [ ] 1.2 將 `Application/Services/Statistics/StatisticsQueryService.php` 的全部內容搬移至新檔案，更新 namespace 並移除對介面的 `implements`
- [ ] 1.3 刪除原 `Application/Services/Statistics/StatisticsQueryService.php`

## 2. 搬遷 DTO 至 Domain 層（避免反向依賴）

- [ ] 2.1 在 `Domains/Statistics/DTOs/` 建立新的 `PaginatedStatisticsDTO.php`，namespace 為 `App\Domains\Statistics\DTOs`
- [ ] 2.2 將 `Application/Services/Statistics/DTOs/PaginatedStatisticsDTO.php` 的全部內容搬移至新檔案，更新 namespace
- [ ] 2.3 在 `Domains/Statistics/DTOs/` 建立新的 `StatisticsQueryDTO.php`，namespace 為 `App\Domains\Statistics\DTOs`
- [ ] 2.4 將 `Application/Services/Statistics/DTOs/StatisticsQueryDTO.php` 的全部內容搬移至新檔案，更新 namespace
- [ ] 2.5 刪除原 `Application/Services/Statistics/DTOs/PaginatedStatisticsDTO.php` 與 `StatisticsQueryDTO.php`
- [ ] 2.6 若 `Application/Services/Statistics/DTOs/` 目錄已無其他檔案，一併刪除

## 3. 移除 StatisticsQueryServiceInterface

- [ ] 3.1 刪除 `Domains/Statistics/Contracts/StatisticsQueryServiceInterface.php`

## 4. 更新呼叫端

- [ ] 4.1 更新 `StatisticsController.php`：更新 `StatisticsQueryService` 與 `StatisticsQueryDTO` 的 use 陳述式
- [ ] 4.2 更新 `StatisticsAdminController.php`：同上
- [ ] 4.3 更新 `StatisticsExportService.php`：更新 `StatisticsQueryServiceInterface` 為 `StatisticsQueryService`
- [ ] 4.4 更新 `StatisticsQueryAdapter.php`：更新 `StatisticsQueryServiceInterface` 為 `StatisticsQueryService`

## 5. 更新 DI 容器

- [ ] 5.1 更新 `StatisticsServiceProvider.php`：將 `StatisticsQueryService` 的 namespace 更新為新路徑，移除 `StatisticsQueryServiceInterface` 綁定

## 6. 更新測試

- [ ] 6.1 更新 `StatisticsQueryServiceTest.php`：將 `StatisticsQueryService` 與兩 DTO 的 use 陳述式改為新 namespace
- [ ] 6.2 更新 `StatisticsExportServiceTest.php`：將 `StatisticsQueryServiceInterface` 改為直接依賴 `StatisticsQueryService`（或改用匿名類別實作新類別簽章）
- [ ] 6.3 搜尋其他測試檔案中是否有對 Application namespace DTOs 或 Service 的參照

## 7. 驗證

- [ ] 7.1 執行 `composer analyse` 確認無靜態分析錯誤
- [ ] 7.2 執行 `composer test` 確認所有測試通過
