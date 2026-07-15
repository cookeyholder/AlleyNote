## Why

Statistics 領域的 5 個 DTO（SourceDistributionDTO、ContentInsightsDTO、PostStatisticsDTO、UserStatisticsDTO、StatisticsOverviewDTO）違反單一職責原則（SRP），將資料傳輸與分析計算邏輯混合在同一類別中。分析方法如 `calculateQualityScore()`、`getPerformanceGrade()`、`generateContentCalendarSuggestions()` 等嵌入 DTO，且 `ensure*` 型別強制輔助方法在 5 個檔案間重複拷貝（4 種 unique 方法 × 5 份拷貝）。此外 `PostStatisticsDTO` 還有 `filterIntegerMap()` 方法與 `ensureStringIntArray` 邏輯高度重疊。隨業務邏輯增長，DTO 體積持續膨脹，導致維護困難與測試覆蓋不足。

## What Changes

- 為 5 個 DTO 建立對應的 Analyzer 類別（含 StatisticsOverviewAnalyzer），將分析/計算方法從 DTO 遷移至 Analyzer
- 每個 Analyzer 回傳專屬的 Result 值物件，取代原始 DTO 中的陣列回傳
- 新增 `PerformanceGrade` backed enum 作為共享值物件
- 新增 `Recommendation` 值物件作為共享型別
- 將 5 份重複的 `ensure*` 靜態輔助方法集中為 `ArraySanitizer` 工具類別，同時將 `PostStatisticsDTO::filterIntegerMap()` 一併納入（其邏輯為 `ensureStringIntArray` + ≥0 檢查）
- DTO 保留純資料存取方法（getter、`fromArray`、`toArray`、`jsonSerialize`、`hasData`、`getSummary`、`validateData`）
- **DTO 的 `toArray()` 與 `getSummary()` 因內部呼叫分析邏輯，串聯輸出結構會改變**：分析結果區塊（如 `traffic_quality_analysis`、`performance_grade`）將從 DTO 序列化中移除，改由 Controller/Service 層透過 Analyzer 產出後組合。這會影響序列化契約，但 DTO 的公開 getter 保持不變。
- **純粹搬遷**，不改變任何分析邏輯的行為

## Capabilities

### New Capabilities
- `source-distribution-analyzer`: 從 SourceDistributionDTO 萃取流量品質分析、管道效能分析、裝置使用模式、趨勢洞察等計算邏輯
- `content-insights-analyzer`: 從 ContentInsightsDTO 萃取效能評級、內容策略建議、最佳化洞察、季節性策略、讀者行為分析等計算邏輯
- `post-statistics-analyzer`: 從 PostStatisticsDTO 萃取品質評分、互動指標、內容分析、作者生產力、最佳長度評分、內容多樣性等計算邏輯
- `user-statistics-analyzer`: 從 UserStatisticsDTO 萃取互動分析、活動洞察、尖峰活躍時段、活動模式、週末 vs 平日活動等計算邏輯
- `statistics-overview-analyzer`: 從 StatisticsOverviewDTO 萃取活動分數計算與活動等級判定邏輯
- `performance-grade-enum`: PerformanceGrade backed enum（如 EXCELLENT、GOOD、AVERAGE、POOR、CRITICAL）
- `recommendation-value-object`: Recommendation 值物件，封裝建議標題、描述、優先級、類別
- `array-sanitizer-utility`: 共用 ArraySanitizer 工具類別，集中管理 `ensureStringMixedArray`、`ensureStringIntArray`、`ensureIntArrayStringMixedArray`、`ensureStringNumberArray`，以及 `filterIntegerMap`（含 ≥0 檢查的 ensureStringIntArray 變體）等型別安全方法

### Modified Capabilities
- （無）— 不改變任何 spec 層級的行為需求

## Impact

- **5 個 DTO 檔案**：移除分析/計算方法與 ensure* 靜態方法，保留純資料存取
- **至少 11 個新檔案**：5 個 Analyzer + 5 個 Result + 1 個 ArraySanitizer
- **2 個值物件**：`Enums/PerformanceGrade.php`、`ValueObjects/Recommendation.php`
- **命名空間**：`App\Domains\Statistics\Analyzers\`（新增）
- **DTO 序列化輸出變更**：`toArray()` / `jsonSerialize()` 不再包含分析結果區塊（如 `traffic_quality_analysis`、`performance_grade` 等）。Controller/Service 層需自行組合 Analyzer 結果。
- **約 6 個 Application 層檔案需修改注入**：Controllers/Services 需注入 Analyzer 並組合回應
- **Test 檔案需同步搬遷**：5 個 DTO 單元測試中被移除方法的測試需移至對應 Analyzer 測試
- **無資料庫變更**：純程式碼重構
- **無現有 DTO 公開 getter 變更**：所有 `get*()` 方法簽章維持不變
