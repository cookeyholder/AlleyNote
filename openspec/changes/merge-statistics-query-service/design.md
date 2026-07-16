## Context

目前 `StatisticsQueryServiceInterface`（`app/Domains/Statistics/Contracts/StatisticsQueryServiceInterface.php`）為 DIP 而建立，但僅有單一實作 `StatisticsQueryService`（`app/Application/Services/Statistics/StatisticsQueryService.php`，667 行、6 公開方法 + 19 私有方法）。`StatisticsApplicationService`（`app/Application/Services/Statistics/StatisticsApplicationService.php`，431 行）目前負責建立與管理快照，不包含查詢方法。兩者同屬統計領域，合併後可減少抽象層數與檔案數量。

## Goals / Non-Goals

**Goals:**
- 移除 `StatisticsQueryServiceInterface`
- 移除 `StatisticsQueryService`
- 將查詢方法合併至 `StatisticsApplicationService`
- 更新所有呼叫端與依賴注入

**Non-Goals:**
- 不改變任何公開 API 的行為
- 不更動資料庫查詢邏輯
- 不重構方法內部實作
- 不改變測試涵蓋範圍

## Decisions

| 決策 | 選擇 | 替代方案 | 理由 |
|------|------|----------|------|
| 合併目標 | `StatisticsApplicationService` | 保留原架構 | 兩服務同屬統計領域，查詢與快照操作在概念上屬於同一服務職責 |
| 方法搬移策略 | 直接複製全部方法 | 抽取共用基底類別 | 僅有單一實作，無需再引入抽象層；複製後微調 `use` 與型別即可 |
| 介面移除 | 完全刪除檔案 | 標記為 deprecated | 無其他實作，保留只增加搜尋雜訊 |
| 呼叫端更新 | 同時修改所有引用處 | 分階段進行 | 屬同一變更範疇，一次完成可避免中間狀態 |

## Risks / Trade-offs

- [類別膨脹] `StatisticsApplicationService` 從 ~431 行增至 ~1098 行 → 尚在合理範圍內，未來可按職責拆分為更小的 Service
- [建構子膨脹] 合併後建構子依賴從 3 個增至 6 個（`StatisticsAggregationServiceInterface`、`StatisticsCacheServiceInterface`、`StatisticsConfigService`、`StatisticsRepositoryInterface`、`LoggerInterface`、`PDO`）→ 仍在合理範圍，PHP-DI auto-wiring 可自動解析
- [測試耦合] 單一 Service 需 mock 更多依賴 → 測試 factory 與 setup 可共用，整體測試複雜度不增
- [回滾成本] 若合併後發現問題，需反向拆分 → 因僅為方法搬移，可 Git revert 安全回滾
