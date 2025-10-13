# 統計功能實作計畫

## 當前狀況

### 已完成項目
- ✅ 前端統計頁面 UI (`/frontend/js/pages/admin/statistics.js`)
- ✅ 前端 API 整合 (使用 `/api/statistics/*` 和 `/api/v1/activity-logs/*`)
- ✅ 後端路由配置 (`/backend/config/routes/statistics.php`)
- ✅ 控制器實作 (`StatisticsController`, `StatisticsChartController`, `ActivityLogController`)
- ✅ 服務層架構 (`StatisticsQueryService`)
- ✅ 權限檢查和參數驗證

### 待實作項目
- ❌ 統計資料實際查詢邏輯 (目前返回模擬資料)
- ❌ Chart.js 時間序列資料查詢
- ❌ 登入失敗統計功能完善
- ❌ 單元測試和整合測試
- ❌ E2E 自動化測試

## 問題分析

### 核心問題
當前 `StatisticsQueryService` 中的以下方法返回模擬資料：
1. `buildOverviewFromRepository()` - 統計概覽
2. `buildPostStatisticsFromRepository()` - 文章統計
3. `buildUserStatisticsFromRepository()` - 使用者統計
4. `buildPopularContentFromRepository()` - 熱門內容

### 資料庫結構
已確認存在以下資料表：
- `posts` (id, title, content, views, publish_date, created_at, ...)
- `users` (id, username, email, created_at, ...)
- `post_views` (id, post_id, user_id, user_ip, view_date)
- `user_activity_logs` (id, user_id, action_type, occurred_at, metadata)

## 實作策略

### 方案一：修改現有服務（建議）
在 `StatisticsQueryService` 中注入 PDO 連接，實作實際的 SQL 查詢。

**優點**：
- 不破壞現有架構
- 快速實作
- 直接使用 SQL 查詢，效能最佳

**缺點**：
- 服務層直接使用 PDO，不符合嚴格的 DDD 原則

### 方案二：建立專用 Repository
為統計查詢建立專用的 Repository，實作所有查詢方法。

**優點**：
- 符合 DDD 架構
- 易於測試和維護
- 關注點分離

**缺點**：
- 需要更多程式碼
- 實作時間較長

## 實作步驟 (方案一)

### Step 1: 修改 StatisticsQueryService
```php
public function __construct(
    private readonly StatisticsRepositoryInterface $statisticsRepository,
    private readonly StatisticsCacheServiceInterface $cacheService,
    private readonly LoggerInterface $logger,
    private readonly PDO $db, // 新增 PDO 注入
) {}
```

### Step 2: 實作實際查詢方法

#### 2.1 統計概覽查詢
```php
private function buildOverviewFromRepository(StatisticsQueryDTO $query): StatisticsOverviewDTO
{
    $startDate = $query->getStartDate()?->format('Y-m-d H:i:s');
    $endDate = $query->getEndDate()?->format('Y-m-d H:i:s');
    
    // 查詢總文章數
    $totalPosts = $this->queryTotalPosts($startDate, $endDate);
    
    // 查詢活躍使用者數
    $activeUsers = $this->queryActiveUsers($startDate, $endDate);
    
    // 查詢新使用者數
    $newUsers = $this->queryNewUsers($startDate, $endDate);
    
    // 查詢總瀏覽量
    $totalViews = $this->queryTotalViews($startDate, $endDate);
    
    return new StatisticsOverviewDTO(...);
}
```

#### 2.2 熱門文章查詢
```sql
SELECT 
    p.id,
    p.title,
    p.views,
    p.slug,
    p.publish_date
FROM posts p
WHERE p.publish_date BETWEEN :start_date AND :end_date
    AND p.status = 'published'
    AND p.deleted_at IS NULL
ORDER BY p.views DESC
LIMIT :limit
```

#### 2.3 流量時間序列查詢
```sql
SELECT 
    DATE(pv.view_date) as date,
    COUNT(*) as views,
    COUNT(DISTINCT pv.user_ip) as visitors
FROM post_views pv
WHERE pv.view_date BETWEEN :start_date AND :end_date
GROUP BY DATE(pv.view_date)
ORDER BY date ASC
```

### Step 3: 更新服務提供者
在 `StatisticsServiceProvider` 中注入 PDO：
```php
StatisticsQueryService::class => \DI\factory(function (ContainerInterface $container): StatisticsQueryService {
    $statisticsRepository = $container->get(StatisticsRepositoryInterface::class);
    $cacheService = $container->get(StatisticsCacheServiceInterface::class);
    $logger = $container->get(LoggerInterface::class);
    $db = $container->get('db'); // 注入 PDO
    
    return new StatisticsQueryService($statisticsRepository, $cacheService, $logger, $db);
}),
```

### Step 4: 實作 Chart.js 時間序列端點
修改 `StatisticsChartController::getViewsTimeSeries()` 以返回正確格式的資料。

### Step 5: 完善登入失敗統計
確認 `ActivityLogController::getLoginFailureStats()` 正常運作。

## 測試計畫

### 單元測試
- [ ] StatisticsQueryService 各方法測試
- [ ] SQL 查詢正確性測試
- [ ] 邊界條件測試

### 整合測試
- [ ] API 端點測試
- [ ] 權限控制測試
- [ ] 錯誤處理測試

### E2E 測試
- [ ] 統計頁面載入測試
- [ ] 時間範圍切換測試
- [ ] 圖表顯示測試
- [ ] 資料更新測試

## 時程規劃

| 步驟 | 預估時間 | 狀態 |
|------|---------|------|
| Step 1-2: 實作實際查詢 | 2-3 小時 | ⏳ 待開始 |
| Step 3: 更新服務提供者 | 30 分鐘 | ⏳ 待開始 |
| Step 4: Chart.js 資料格式 | 1 小時 | ⏳ 待開始 |
| Step 5: 登入失敗統計 | 30 分鐘 | ⏳ 待開始 |
| 單元測試 | 2 小時 | ⏳ 待開始 |
| E2E 測試 | 1 小時 | ⏳ 待開始 |
| 程式碼品質檢查 | 1 小時 | ⏳ 待開始 |

**總計**：約 8-10 小時

## 驗收標準

### 功能性
- ✅ 統計概覽顯示實際資料
- ✅ 熱門文章列表正確排序
- ✅ 流量趨勢圖表正確顯示
- ✅ 登入失敗統計準確
- ✅ 時間範圍切換功能正常

### 效能
- ✅ 統計頁面載入時間 < 3 秒
- ✅ API 回應時間 < 1 秒
- ✅ 大量資料查詢不超時

### 品質
- ✅ PHPStan Level 10 通過
- ✅ PHP CS Fixer 通過
- ✅ 所有單元測試通過
- ✅ 所有 E2E 測試通過

## 注意事項

1. **快取策略**：實作查詢時要考慮快取，避免重複查詢
2. **效能優化**：對於大量資料，考慮使用資料庫索引和分頁
3. **錯誤處理**：確保所有查詢都有適當的錯誤處理
4. **安全性**：使用參數化查詢，防止 SQL 注入
5. **資料一致性**：確保統計資料的準確性和一致性
