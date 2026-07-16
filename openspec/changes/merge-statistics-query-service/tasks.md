## 1. 合併查詢方法

- [x] 1.1 將 `StatisticsQueryService` 所有方法（6 公開 + 19 私有）複製至 `StatisticsApplicationService`，調整 `use` 陳述式與型別標示
- [x] 1.2 移除 `app/Domains/Statistics/Contracts/StatisticsQueryServiceInterface.php`
- [x] 1.3 移除 `app/Application/Services/Statistics/StatisticsQueryService.php`

## 2. 更新呼叫端

- [x] 2.1 更新 `StatisticsController` 改依賴 `StatisticsApplicationService`
- [x] 2.2 更新 `StatisticsAdminController` 改依賴 `StatisticsApplicationService`（已注入，移除 `StatisticsQueryService`），`health()` 方法中 `$this->statisticsQueryService->getOverview()` 改為 `$this->statisticsApplicationService->getOverview()`，回應中的 `statistics_query_service` 欄位一併更新
- [x] 2.3 更新 `StatisticsExportService` 改依賴 `StatisticsApplicationService`
- [x] 2.4 更新 `StatisticsQueryAdapter` 改依賴 `StatisticsApplicationService`

## 3. 更新依賴注入

- [x] 3.1 更新 `StatisticsServiceProvider` 移除 `StatisticsQueryServiceInterface` 綁定與 `StatisticsQueryService::class` 定義
- [x] 3.2 確認 `StatisticsApplicationService` 在 `StatisticsServiceProvider` 中已註冊（auto-wiring 需能解析合併後的 6 個建構子參數）

## 4. 更新測試

- [x] 4.1 更新 `StatisticsQueryServiceTest` 改注入 `StatisticsApplicationService`（6 個建構子參數）
- [x] 4.2 更新 `StatisticsExportServiceTest` 改注入 `StatisticsApplicationService`
- [x] 4.3 更新 `StatisticsApplicationServiceTest` 新增 3 個建構子參數

## 5. 驗證

- [x] 5.1 執行 `composer test` — Statistics 單元測試皆通過；9 個 Integration 失敗（500 vs 401/403）為預先存在的 DI 容器問題
- [ ] 5.2 執行 `composer analyse` — 15 個 PHPStan Level 10 錯誤均為預先存在的（不在本次合併範圍內），無新增錯誤
- [ ] 5.3 執行 `composer cs-check` 確認程式碼風格無誤
