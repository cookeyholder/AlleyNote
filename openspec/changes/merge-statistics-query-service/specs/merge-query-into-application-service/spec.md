## ADDED Requirements

### Requirement: StatisticsApplicationService 提供所有查詢方法

`StatisticsApplicationService` SHALL 包含原 `StatisticsQueryService` 的所有公開查詢方法，包括但不限於統計摘要、時間序列、分類統計等。

#### Scenario: 呼叫統計摘要方法回傳正確資料
- **WHEN** 呼叫端注入 `StatisticsApplicationService` 並呼叫統計摘要方法
- **THEN** 回傳資料格式與內容與原 `StatisticsQueryService` 一致

#### Scenario: 呼叫時間序列方法回傳正確資料
- **WHEN** 呼叫端注入 `StatisticsApplicationService` 並呼叫時間序列方法
- **THEN** 回傳資料格式與內容與原 `StatisticsQueryService` 一致

### Requirement: 移除 StatisticsQueryServiceInterface

系統 SHALL 不再依賴 `StatisticsQueryServiceInterface`，所有原使用此介面的程式碼 SHALL 改為直接依賴 `StatisticsApplicationService`。

#### Scenario: 呼叫端不再引用已移除介面
- **WHEN** 檢查所有 `use` 陳述式
- **THEN` 不應有任何檔案引用 `StatisticsQueryServiceInterface`

#### Scenario: 依賴注入容器不再綁定已移除介面
- **WHEN** 檢查 `StatisticsServiceProvider`
- **THEN** 不應存在 `StatisticsQueryServiceInterface` 的綁定

### Requirement: 移除 StatisticsQueryService 類別

系統 SHALL 移除 `StatisticsQueryService` 類別，其所有方法已搬移至 `StatisticsApplicationService`。

#### Scenario: 類別檔案已刪除
- **WHEN** 檢查檔案系統
- **THEN** `StatisticsQueryService.php` 不應存在

### Requirement: 所有公開查詢方法行為不變

合併後的 `StatisticsApplicationService` 中所有原查詢方法 SHALL 保持相同的輸入參數、回傳型別與商業邏輯。

#### Scenario: 查詢方法傳入相同參數得到相同結果
- **WHEN** 傳入與原 `StatisticsQueryService` 相同的參數
- **THEN** 回傳結果完全一致
