## 1. 共享基礎建設

- [ ] 1.1 建立 `Domains/Statistics/Enums/PerformanceGrade.php` backed enum，含 `fromScore` 工廠方法
- [ ] 1.2 建立 `Domains/Statistics/ValueObjects/Recommendation.php` 不可變值物件
- [ ] 1.3 建立 `Domains/Statistics/Helpers/ArraySanitizer.php` 靜態工具類別，集中所有 `ensure*` 方法

## 2. SourceDistributionAnalyzer

- [ ] 2.1 建立 `Domains/Statistics/Analyzers/SourceDistributionResult.php` 結果類別
- [ ] 2.2 建立 `Domains/Statistics/Analyzers/SourceDistributionAnalyzer.php`，遷移 `getTrafficQualityAnalysis`、`getChannelPerformanceAnalysis`、`getDeviceUsagePattern`、`getTrendInsights` 及相關 private helper（`calculateQualityScore`、`getQualityLevel`、`getQualityRecommendations`、`calculateChannelDiversity`、`getDirectPercentage`、`getSocialPercentage`）
- [ ] 2.3 從 `SourceDistributionDTO.php` 移除已遷移的分析方法與重複的 `ensure*` 靜態方法，改為 use `ArraySanitizer`

## 3. ContentInsightsAnalyzer

- [ ] 3.1 建立 `Domains/Statistics/Analyzers/ContentInsightsResult.php` 結果類別
- [ ] 3.2 建立 `Domains/Statistics/Analyzers/ContentInsightsAnalyzer.php`，遷移 `getPerformanceGrade`、`getContentStrategyRecommendations`、`getOptimizationInsights`、`getSeasonalContentStrategy`、`getReaderBehaviorAnalysis` 及相關 private helper（`getCurrentSeason`、`getRefreshRecommendations`、`generateContentCalendarSuggestions`）
- [ ] 3.3 從 `ContentInsightsDTO.php` 移除已遷移的分析方法與重複的 `ensure*` 靜態方法，改為 use `ArraySanitizer`

## 4. PostStatisticsAnalyzer

- [ ] 4.1 建立 `Domains/Statistics/Analyzers/PostStatisticsResult.php` 結果類別
- [ ] 4.2 建立 `Domains/Statistics/Analyzers/PostStatisticsAnalyzer.php`，遷移 `getContentQualityMetrics`、`calculateQualityScore`、`calculateAuthorProductivity`、`calculateOptimalLengthScore`、`calculateContentDiversity`
- [ ] 4.3 從 `PostStatisticsDTO.php` 移除已遷移的分析方法與重複的 `ensure*` 靜態方法，改為 use `ArraySanitizer`

## 5. UserStatisticsAnalyzer

- [ ] 5.1 建立 `Domains/Statistics/Analyzers/UserStatisticsResult.php` 結果類別
- [ ] 5.2 建立 `Domains/Statistics/Analyzers/UserStatisticsAnalyzer.php`，遷移 `getEngagementAnalysis`、`getActivityInsights`、`getPeakActiveHour`、`getActivityPattern`、`getWeekendVsWeekdayActivity`
- [ ] 5.3 從 `UserStatisticsDTO.php` 移除已遷移的分析方法與重複的 `ensure*` 靜態方法，改為 use `ArraySanitizer`
- [ ] 5.4 更新 `UserStatisticsDTO.toArray()`：移除 `engagement_analysis`、`activity_insights` 區塊（僅保留純資料 + `calculated_metrics` 中的簡單衍生欄位）

## 6. StatisticsOverviewAnalyzer

- [ ] 6.1 建立 `Domains/Statistics/Analyzers/StatisticsOverviewResult.php` 結果類別
- [ ] 6.2 建立 `Domains/Statistics/Analyzers/StatisticsOverviewAnalyzer.php`，遷移 `getActivityLevel`、`calculateActivityScore`
- [ ] 6.3 從 `StatisticsOverviewDTO.php` 移除已遷移的分析方法與重複的 `ensure*` 靜態方法，改為 use `ArraySanitizer`
- [ ] 6.4 更新 `StatisticsOverviewDTO.toArray()`：移除 `calculated_metrics.activity_level`（保留 `growth_rate`、`posts_per_user` 等簡單衍生）
- [ ] 6.5 更新 `StatisticsOverviewDTO.getSummary()`：移除 `activity_level`

## 7. PostStatisticsDTO 補充方法遷移

- [ ] 7.1 將 `getEngagementMetrics()` 方法邏輯遷移至 `PostStatisticsAnalyzer` 中（因內部呼叫 `calculateAuthorProductivity()`）
- [ ] 7.2 將 `getContentAnalysis()` 方法邏輯遷移至 `PostStatisticsAnalyzer` 中（因內部呼叫 `calculateOptimalLengthScore()`、`calculateContentDiversity()`）
- [ ] 7.3 更新 `PostStatisticsDTO.toArray()`：移除 `engagement_metrics`、`content_analysis`、`content_quality` 區塊（僅保留純資料 + 簡單衍生欄位）

## 8. ArraySanitizer 補充

- [ ] 8.1 在 `ArraySanitizer` 中新增 `ensureStringNonNegativeIntArray()` 方法（`ensureStringIntArray` + ≥0 檢查），取代 `PostStatisticsDTO.filterIntegerMap()`
- [ ] 8.2 更新 `PostStatisticsDTO.fromArray()` 中所有 `self::filterIntegerMap(...)` 呼叫為 `ArraySanitizer::ensureStringNonNegativeIntArray(...)`

## 9. DTO toArray() / getSummary() 更新（其餘未涵蓋者）

- [ ] 9.1 `SourceDistributionDTO.toArray()`：移除 `traffic_quality_analysis`、`channel_performance_analysis`、`device_usage_pattern`、`trend_insights` 區塊
- [ ] 9.2 `SourceDistributionDTO.getSummary()`：移除 `device_pattern`（原內部呼叫 `getDeviceUsagePattern()['pattern']`）
- [ ] 9.3 `ContentInsightsDTO.toArray()`：移除 `calculated_metrics.performance_grade`、`strategy_recommendations`、`optimization_insights`、`seasonal_content_strategy`、`reader_behavior_analysis` 區塊
- [ ] 9.4 `ContentInsightsDTO.getSummary()`：移除 `performance_grade`

## 10. 呼叫端更新

- [ ] 10.1 搜尋所有引用被移除方法的 Controller 與 Service，改為注入對應 Analyzer
- [ ] 10.2 更新 `StatisticsQueryService`：注入 Analyzer，在 `getOverview()` 中組合 DTO + StatisticsOverviewAnalyzer 結果
- [ ] 10.3 更新所有回傳統計 DTO 的 Controller 方法：在序列化前呼叫對應 Analyzer 補充分析結果
- [ ] 10.4 更新 PHP-DI container 定義（`config/container.php`），註冊所有 Analyzer
- [ ] 10.5 執行 `composer check-all` 驗證無 static analysis 與測試錯誤

## 11. 測試

- [ ] 11.1 為每個 Analyzer 撰寫單元測試（5 個 AnalyzerTest），驗證搬遷前後輸出一致
- [ ] 11.2 將現有 DTO 測試中對分析方法的斷言移至對應的 AnalyzerTest（約 25 個測試案例）
  - `SourceDistributionDTOTest` → `SourceDistributionAnalyzerTest`（至少 7 個測試案例）
  - `ContentInsightsDTOTest` → `ContentInsightsAnalyzerTest`（至少 9 個測試案例）
  - `PostStatisticsDTOTest` → `PostStatisticsAnalyzerTest`（至少 4 個測試案例）
  - `UserStatisticsDTOTest` → `UserStatisticsAnalyzerTest`（至少 4 個測試案例）
  - `StatisticsOverviewDTOTest` → `StatisticsOverviewAnalyzerTest`（至少 2 個測試案例）
- [ ] 11.3 為 `PerformanceGrade`、`Recommendation`、`ArraySanitizer` 撰寫單元測試
- [ ] 11.4 更新現有 DTO 測試：移除已搬遷方法的斷言，保留純資料存取測試
- [ ] 11.5 為 Controller/Service 新增整合測試，驗證 DTO + Analyzer 組合後的回應結構正確
- [ ] 11.6 執行完整測試套件確認無回歸

## 12. 文件與驗收

- [ ] 12.1 在 API 規格文件中標記 DTO 序列化結構變更
- [ ] 12.2 執行 `composer check-all` 最終驗證

