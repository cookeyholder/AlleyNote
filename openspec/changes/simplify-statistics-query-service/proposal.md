## Why

`StatisticsQueryServiceInterface` 僅有單一實作 `StatisticsQueryService`，屬於過度設計的介面抽象層，增加維護成本卻無實際效益。另一方面，`StatisticsApplicationService` 與 `StatisticsQueryService` 分別負責寫入與查詢，職責清晰，不應合併。先前 PR #125 將兩者合併產生 god class，已遭還原。本次變更採正確做法：移除介面、將 Query Service 搬遷至領域層。

## What Changes

- **移除** `StatisticsQueryServiceInterface`（單一實作，無保留必要）
- **搬遷** `StatisticsQueryService` 從 `Application\Services\Statistics\` 至 `Domains\Statistics\Services\`
- **一併搬遷** `PaginatedStatisticsDTO` 與 `StatisticsQueryDTO` 從 `Application\Services\Statistics\DTOs\` 至 `Domains\Statistics\DTOs\`（避免 Domain→Application 反向依賴）
- **更新** namespace 從 `App\Application\Services\Statistics` 改為 `App\Domains\Statistics\Services`（Service）及 `App\Domains\Statistics\DTOs`（DTOs）
- **更新** 所有呼叫端（Controller x2、ExportService、QueryAdapter、ServiceProvider）
- 不改變任何商業邏輯與公開方法簽章

## Capabilities

### New Capabilities
- `move-query-service-to-domain`: 將 StatisticsQueryService 從 Application 層搬遷至 Domain 層，並移除其多餘的介面

### Modified Capabilities
- （無）— 純重構，不改變任何 spec 層級的行為需求

## Impact

- `Application\Services\Statistics\StatisticsQueryService` 檔案移動至 `Domains\Statistics\Services\`
- `Application\Services\Statistics\DTOs\PaginatedStatisticsDTO` 與 `StatisticsQueryDTO` 檔案移動至 `Domains\Statistics\DTOs\`
- `Domains\Statistics\Contracts\StatisticsQueryServiceInterface` 檔案刪除
- 更新 4 個呼叫端 + ServiceProvider + 測試
- 資料庫、API 路由、外部契約不受影響
