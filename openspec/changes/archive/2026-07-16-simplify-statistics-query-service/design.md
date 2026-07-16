## Context

Statistics 領域目前有兩個服務類別：

- `Application\Services\Statistics\StatisticsApplicationService` — 處理寫入操作（記錄瀏覽、記錄點擊等）
- `Application\Services\Statistics\StatisticsQueryService` — 處理查詢操作（統計報表、趨勢分析等）
- `Domains\Statistics\Contracts\StatisticsQueryServiceInterface` — 僅被 StatisticsQueryService 實作的介面

先前 PR #125 將兩者合併為 god class，但因違反單一職責原則而遭還原。正確做法是保持兩類別獨立，但將 Query Service 置於其所屬的 Domain 層，並移除過度設計的介面。

## Goals / Non-Goals

**Goals:**
- 移除 `StatisticsQueryServiceInterface`，消除過度抽象
- 將 `StatisticsQueryService` 搬遷至 `Domains\Statistics\Services\`，反映其領域職責
- 一併搬遷 `PaginatedStatisticsDTO` 與 `StatisticsQueryDTO` 至 `Domains\Statistics\DTOs\`，避免 Domain→Application 反向依賴
- 更新所有呼叫端與 DI 註冊

**Non-Goals:**
- 不改變任何商業邏輯
- 不改變 `StatisticsQueryService` 的公開方法簽章與行為
- 不觸碰 `StatisticsApplicationService` 或 Statistics 領域的其他元件
- 不修改資料庫結構、API 端點或外部契約

## Decisions

| 決策 | 選項 | 選擇理由 |
|------|------|----------|
| 介面移除 | 保留 vs 移除 | 單一實作的介面增加無謂的間接層，違反 YAGNI。移除後直接依賴類別。 |
| 搬遷目標 | Application vs Domain | `StatisticsQueryService` 封裝統計查詢的領域邏輯（計算趨勢、統計摘要等），應屬於 Domain 層而非 Application 層。 |
| 類別合併 | 合併 vs 分離 | PR #125 證明合併會產生 god class。查詢與寫入是不同職責，應保持獨立。 |

## Risks / Trade-offs

- [風險] 某個呼叫端可能遺漏未更新 → [緩解] 更新完成後執行 `composer analyse`（PHPStan Level 10）可自動檢測所有未解析的型別參照
- [風險] 測試檔案中的 mock 仍引用舊 namespace 或介面 → [緩解] 檢查所有測試檔案中的 `use` 陳述式與 mock 建立語句
- [風險] DI container 中綁定介面的定義未更新 → [緩解] 將介面綁定改為自動綁定（autowiring），或直接綁定類別
- [風險] `PaginatedStatisticsDTO` 與 `StatisticsQueryDTO` 搬遷後，Controller 需改用新 namespace → [緩解] Controller 已在呼叫端更新範圍內
- [風險] 介面 `StatisticsQueryServiceInterface` 的方法簽章（`array $options`）與實作（型別 DTO）差異極大，移除後無相容性問題 → 已確認無其他實作
