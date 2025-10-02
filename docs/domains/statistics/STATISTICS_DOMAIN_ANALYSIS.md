# 統計領域概念分析文件

## 版本資訊
- 版本：v1.0.0
- 建立日期：2025-09-21
- 責任人：開發團隊
- 領域專家：待確認

## 領域概述

統計領域 (Statistics Domain) 負責管理和計算各種文章相關的統計資訊，包括文章數量、來源分布、使用者活躍度等指標。此領域的核心職責是提供準確、及時的統計資料以支援決策制定和系統監控。

## 統計領域概念分析

### 1. 核心領域概念 (Core Domain Concepts)

#### 1.1 統計快照 (Statistics Snapshot)
**概念描述**：特定時間點的統計資料快照，避免重複計算提升效能。

**業務規則**：
- 每個快照必須有唯一的識別碼
- 快照資料一旦建立就不可變更
- 快照必須包含計算時間和有效期限
- 不同統計類型和時間週期的快照可以並存

**屬性**：
- 快照識別碼 (Snapshot ID)
- 統計類型 (Statistics Type)
- 統計週期 (Statistics Period)
- 統計資料 (Statistics Data)
- 建立時間 (Created At)
- 有效期限 (Expires At)

#### 1.2 統計指標 (Statistics Metric)
**概念描述**：具體的統計測量值，如文章總數、平均瀏覽量等。

**業務規則**：
- 指標值必須是非負數
- 指標必須有明確的計算定義
- 指標可以是絕對值或相對值
- 指標必須可以追溯其計算來源

**屬性**：
- 指標名稱 (Metric Name)
- 指標值 (Metric Value)
- 指標單位 (Metric Unit)
- 計算方式 (Calculation Method)

**擴充指標建議**：
- **內容分析指標**：
  - **標籤與分類分佈**：分析熱門標籤和分類，了解內容趨勢。
  - **文章平均長度**：不同來源或分類下的文章篇幅分析。
- **使用者互動指標**：
  - **留言分佈**：熱門留言文章、平均留言數等。
  - **使用者活躍度**：區分首次發文使用者與持續活躍使用者。

#### 1.3 統計週期 (Statistics Period)
**概念描述**：統計資料的時間範圍，如日、週、月、年。

**業務規則**：
- 週期必須有明確的開始和結束時間
- 週期不能重疊但可以包含
- 週期類型必須是預定義的枚舉值
- 週期長度必須符合業務需求

**屬性**：
- 週期類型 (Period Type): daily, weekly, monthly, yearly
- 開始時間 (Start Time)
- 結束時間 (End Time)
- 時區 (Timezone)

#### 1.4 來源類型 (Source Type)
**概念描述**：文章的建立來源，用於分析文章的來源分布。

**業務規則**：
- 來源類型必須是預定義的枚舉值
- 每篇文章必須有一個來源類型
- 來源詳情可以為空但來源類型不能為空
- 歷史文章預設來源為 'web'

**屬性**：
- 類型代碼 (Type Code): web, api, import, migration
- 類型名稱 (Type Name)
- 類型描述 (Type Description)

### 2. 聚合根設計 (Aggregate Root Design)

#### 2.1 統計快照聚合 (Statistics Snapshot Aggregate)
**職責**：
- 管理統計快照的生命週期
- 確保快照資料的一致性和完整性
- 處理快照的過期和清理邏輯
- 提供快照資料的查詢介面

**聚合邊界**：
- 包含統計快照實體
- 包含相關的統計指標值物件
- 包含統計週期值物件
- 不包含原始資料來源（Post 等）

**不變條件 (Invariants)**：
- 快照建立後內容不可變更
- 快照必須有有效的統計週期
- 快照資料序列化後必須符合 JSON 格式的字串
- 同一週期和類型的快照在同一時間只能有一個有效版本

### 3. 值物件設計 (Value Objects Design)

#### 3.1 StatisticsPeriod (統計週期值物件)
```php
interface StatisticsPeriodProps {
    type: PeriodType;      // daily, weekly, monthly, yearly
    startTime: DateTime;   // 週期開始時間
    endTime: DateTime;     // 週期結束時間
    timezone: string;      // 時區
}
```

**驗證規則**：
- startTime 必須早於 endTime
- 週期長度必須符合類型定義
- 時區必須是有效的 timezone 字串

#### 3.2 StatisticsMetric (統計指標值物件)
```php
interface StatisticsMetricProps {
    name: string;          // 指標名稱
    value: number;         // 指標數值
    unit: string;          // 指標單位
    calculationMethod: string; // 計算方式描述
}
```

**驗證規則**：
- value 必須是非負數
- name 不能為空
- unit 和 calculationMethod 可以為空但不能是無意義字串

#### 3.3 SourceType (來源類型值物件)
```php
interface SourceTypeProps {
    code: string;          // 類型代碼: web, api, import, migration
    name: string;          // 類型名稱
    description?: string;  // 類型描述（可選）
}
```

**驗證規則**：
- code 必須是預定義的枚舉值
- name 不能為空
- description 可以為空

#### 3.4 與現有 Post 模型整合
**整合策略**：
- Post 模型需要新增 `source_type` 和 `source_detail` 屬性
- 維持現有 Post 模型的 API 相容性
- 使用資料庫 Migration 安全新增欄位
- 歷史資料預設來源為 'web'

**Post 模型修改**：
```php
class Post {
    private ?string $sourceType;     // 來源類型：web, api, import, migration
    private ?string $sourceDetail;   // 來源詳情：具體描述或標識

    public function getSourceType(): ?string { return $this->sourceType; }
    public function getSourceDetail(): ?string { return $this->sourceDetail; }
}
```

**資料庫欄位定義**：
```sql
-- 新增到 posts 表
ALTER TABLE posts ADD COLUMN source_type VARCHAR(20) NOT NULL DEFAULT 'web';
ALTER TABLE posts ADD COLUMN source_detail TEXT NULL;

-- 建立索引提升查詢效能
CREATE INDEX idx_posts_source_type ON posts(source_type);
CREATE INDEX idx_posts_created_source ON posts(created_at, source_type);
```

### 4. 領域事件設計 (Domain Events Design)

#### 4.1 StatisticsSnapshotCreated (統計快照已建立)
**觸發時機**：統計快照成功建立時

**事件資料**：
- 快照 ID
- 統計類型
- 統計週期
- 建立時間
- 統計結果摘要

**業務用途**：
- 通知快取系統更新
- 觸發相關統計的重新計算
- 記錄統計活動日誌

#### 4.2 StatisticsCalculationRequested (統計計算已請求)
**觸發時機**：手動或定時觸發統計計算時

**事件資料**：
- 請求 ID
- 統計類型
- 統計週期
- 請求來源
- 請求時間

**業務用途**：
- 追蹤統計計算請求
- 防止重複計算
- 監控計算效能

#### 4.3 StatisticsSnapshotExpired (統計快照已過期)
**觸發時機**：統計快照達到過期時間時

**事件資料**：
- 快照 ID
- 統計類型
- 統計週期
- 過期時間

**業務用途**：
- 觸發快照清理程序
- 通知需要重新計算
- 更新快取狀態

### 5. 領域服務設計 (Domain Services Design)

#### 5.1 StatisticsCalculatorService (統計計算服務)
**職責**：
- 執行各種統計指標的計算邏輯
- 協調多個統計類型的計算
- 提供統計計算的標準介面

**主要方法**：
- `calculatePostStatistics(period: StatisticsPeriod): StatisticsMetric[]`
- `calculateSourceDistribution(period: StatisticsPeriod): StatisticsMetric[]`
- `calculateUserActivity(period: StatisticsPeriod): StatisticsMetric[]`
- `calculatePopularContent(period: StatisticsPeriod): StatisticsMetric[]`

#### 5.2 StatisticsValidationService (統計驗證服務)
**職責**：
- 驗證統計資料的合理性
- 檢查統計結果的一致性
- 提供資料品質檢查

**主要方法**：
- `validateMetrics(metrics: StatisticsMetric[]): ValidationResult`
- `checkDataConsistency(snapshot: StatisticsSnapshot): boolean`
- `detectAnomalies(current: StatisticsSnapshot, previous: StatisticsSnapshot): AnomalyReport`

### 6. Repository 介面設計 (Repository Interfaces)

#### 6.1 StatisticsRepositoryInterface
**職責**：統計快照的持久化操作

**主要方法**：
- `save(snapshot: StatisticsSnapshot): void`
- `findById(id: SnapshotId): StatisticsSnapshot | null`
- `findByPeriodAndType(period: StatisticsPeriod, type: string): StatisticsSnapshot | null`
- `findExpiredSnapshots(): StatisticsSnapshot[]`
- `deleteExpiredSnapshots(): int`

#### 6.2 PostStatisticsRepositoryInterface
**職責**：文章相關統計資料的查詢操作

**主要方法**：
- `countPostsByPeriod(period: StatisticsPeriod): int`
- `countPostsBySourceType(sourceType: SourceType, period: StatisticsPeriod): int`
- `getPostViewStatistics(period: StatisticsPeriod): StatisticsMetric[]`
- `getPopularPosts(period: StatisticsPeriod, limit: int): PostStatistics[]`

#### 6.3 UserStatisticsRepositoryInterface
**職責**：使用者相關統計資料的查詢操作，明確與使用者領域的互動邊界。

**主要方法**：
- `countActiveUsers(period: StatisticsPeriod): int`
- `getNewUserCount(period: StatisticsPeriod): int`
- `getUserPostCountDistribution(period: StatisticsPeriod): StatisticsMetric[]`

### 6.4 效能考量與最佳化策略

#### 6.3.1 大量資料處理策略
**分批處理**：
- 統計計算採用分批處理，每批處理 1000 筆資料
- 使用 LIMIT 和 OFFSET 進行分頁查詢
- 避免一次性載入大量資料到記憶體

**查詢最佳化**：
- 使用覆蓋索引減少磁碟 I/O
- 預先計算常用統計結果
- 使用資料庫函數進行聚合計算

**並行處理**：
- 不同時間週期的統計可並行執行
- 使用佇列系統處理大量統計任務
- 實作任務失敗重試機制

#### 6.3.2 快取策略細節
**多層快取架構**：
```php
// 快取鍵命名規範
const CACHE_PREFIX = 'statistics';
const CACHE_KEYS = [
    'overview' => 'statistics:overview:{period}:{date}',
    'posts' => 'statistics:posts:{type}:{period}:{date}',
    'sources' => 'statistics:sources:{period}:{date}',
    'users' => 'statistics:users:{period}:{date}',
];

// 快取有效期設定
const CACHE_TTL = [
    'daily' => 3600,    // 1 小時
    'weekly' => 7200,   // 2 小時
    'monthly' => 14400, // 4 小時
    'yearly' => 28800,  // 8 小時
];
```

**快取標籤管理**：
- 使用 `statistics` 作為主標籤
- 依統計類型設定子標籤：`statistics:posts`、`statistics:users`
- 支援按標籤批量失效快取

**快取預熱策略**：
- 在低峰時段預先計算熱門統計
- 新資料產生時觸發相關快取更新
- 定期檢查快取命中率並調整策略

### 7. 業務約束與規則 (Business Constraints & Rules)

#### 7.1 統計計算規則
1. **時間範圍規則**：
   - 日統計：當日 00:00 至 23:59
   - 週統計：週一 00:00 至週日 23:59
   - 月統計：月初第一天 00:00 至月末最後一天 23:59
   - 年統計：年初第一天 00:00 至年末最後一天 23:59

2. **資料一致性規則**：
   - 統計快照建立後不可修改
   - 同一時間週期只能有一個有效快照
   - 過期快照必須標記為無效

3. **效能規則**：
   - 統計快照有效期：日統計 6 小時，週統計 1 天，月統計 7 天
   - 大量資料統計使用批次處理
   - 快取優先返回已有統計結果

#### 7.2 資料品質規則
1. **數值驗證**：
   - 所有統計數值必須非負
   - 百分比數值必須在 0-100 範圍內
   - 趨勢變化幅度超過 100% 需要驗證

2. **邏輯一致性**：
   - 子項目總和不能超過父項目
   - 相關統計之間的數值必須邏輯一致

### 8. 領域專家確認事項

#### 8.1 業務需求確認
- [ ] 統計週期定義是否符合業務需求？
- [ ] 來源類型分類是否完整？
- [ ] 統計指標是否涵蓋所有必要資訊？
- [ ] 資料保存期限是否合理？

#### 8.2 技術實作確認
- [ ] 聚合邊界劃分是否合理？
- [ ] 值物件設計是否符合不變性要求？
- [ ] 領域事件是否涵蓋主要業務場景？
- [ ] Repository 介面是否滿足查詢需求？

#### 8.3 效能考量確認
- [ ] 統計快照的有效期設定是否平衡即時性和效能？
- [ ] 大量資料統計的處理策略是否可行？
- [ ] 快取策略是否符合業務場景？

### 9. 技術實作指導原則

#### 9.1 程式碼品質要求
- 所有類別必須通過 PHPStan Level 10 檢查
- 使用 `declare(strict_types=1)` 嚴格類型檢查
- 遵循 PSR-12 程式碼風格標準
- 每個方法必須有完整的 DocBlock 註解

#### 9.2 測試策略
- 領域邏輯測試覆蓋率必須達到 95% 以上
- 使用 Mock 物件隔離外部依賴
- 包含邊界條件和異常情況測試
- 整合測試驗證完整業務流程

#### 9.3 安全性考量
- 統計查詢必須驗證使用者權限
- 敏感統計資料需要額外授權
- 防止透過統計功能洩露使用者隱私
- 記錄統計操作的審計日誌

#### 9.4 監控與日誌
- 統計計算耗時超過 5 秒需要記錄警告
- 監控快取命中率，低於 70% 需要調整策略
- 記錄統計 API 的呼叫頻率和回應時間
- 建立統計功能的健康檢查端點

## 總結

本文件定義了統計領域的核心概念、聚合根、值物件、領域事件和領域服務。設計遵循 DDD 原則，確保業務邏輯封裝在領域層，並為後續的應用層和基礎設施層實作提供清晰的指導。

此設計需要領域專家的審核和確認，特別是業務規則和統計邏輯的部分，以確保符合實際業務需求。

## 下一步驟

1. 領域專家審核此文件
2. 確認業務規則和統計邏輯
3. 開始實作值物件 (T1.2)
4. 實作統計實體 (T1.3)
5. 定義 Repository 介面 (T1.4)
6. 建立領域服務 (T1.5)
