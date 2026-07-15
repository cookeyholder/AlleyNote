## Context

Statistics 領域目前有 5 個 DTO，每個 DTO 混合了資料承載（property + getter）與分析計算（`calculate*`、`getPerformanceGrade`、`generate*` 等方法）。分析邏輯依賴 DTO 內部狀態但與 DTO 的核心職責（資料傳輸）無關。此外，`ensureStringMixedArray`、`ensureStringIntArray`、`ensureIntArrayStringMixedArray`、`ensureStringNumberArray` 等型別安全輔助方法在 5 個 DTO 間完全重複。

此設計遵循 Option C：每個 DTO 對應一個 Analyzer + Result 類別，將分析邏輯萃取至獨立的 Analyzer 層。

## Goals / Non-Goals

**Goals:**
- 將分析/計算方法從 5 個 DTO 中完全移除
- 為每個 DTO 建立對應的 Analyzer 類別（回傳 Result 值物件）
- 建立共享的 `PerformanceGrade` enum 與 `Recommendation` 值物件
- 將重複的 `ensure*` 輔助方法集中為 `ArraySanitizer`
- 維持 DTO 現有公開 API 的完全相容性

**Non-Goals:**
- 不改變任何分析/計算的行為邏輯
- 不重構或最佳化現有演算法
- 不變更資料庫結構或 API 端點（但 DTO 的 `toArray()` / `jsonSerialize()` 輸出結構會因移除分析區塊而改變—Controller/Service 層需補回）
- 不修改 DTO 的 getter 名稱或回傳型別
- 不引入新依賴或框架

## Decisions

### 1. Analyzer 命名空間與定位
- **路徑**：`Domains/Statistics/Analyzers/`
- **定位**：Analyzer 是無狀態的服務物件，接收 DTO 作為建構子參數，提供分析公開方法
- **不使用 Service 層**：現有 Services 目錄存放的是基礎設施協調邏輯（匯出、聚合、快取等），分析邏輯屬領域計算，獨立放置更清晰

### 2. Result 類別設計
- 每個 Analyzer 對應一個 Immutable Result 類別
- Result 使用 `readonly` property（PHP 8.1+）搭配建構子屬性提升
- 實作 `JsonSerializable` 以維持序列化相容性
- 分析方法回傳 Result 而非原始陣列

### 3. PerformanceGrade Enum
- PHP 8.1+ backed enum（`string`）
- 值：`EXCELLENT`、`GOOD`、`AVERAGE`、`POOR`、`CRITICAL`
- 提供 `fromScore(float $score): self` 工廠方法，封裝評分門檻邏輯
- 所有 DTO 中原本的 `getQualityLevel()`、`getPerformanceGrade()` 等皆統一由此 enum 處理

### 4. Recommendation Value Object
- 封裝單一建議的結構：`title`、`description`、`priority`（`high`/`medium`/`low`）、`category`
- 不可變，提供 `toArray(): array` 方法
- Analyzer 的回傳建議從陣列升級為 `Recommendation[]`

### 5. ArraySanitizer Utility
- **路徑**：`Domains/Statistics/Helpers/ArraySanitizer.php`
- 靜態工具類別，包含所有 `ensure*` 方法
- 消除 5 份完全重複的程式碼
- 保留原始簽章與行為

### 6. DTO 保留範圍
- 每個 DTO 保留：建構子、property、getter、`fromArray`、`toArray`、`jsonSerialize`、`hasData`、`getSummary`、`validateData`
- 僅移除分析/計算相關方法（含 private helper）
- 被移除方法的呼叫端（Controllers/Services）改為注入對應 Analyzer

### 7. 不採用選項（Alternatives Considered）

### 8. DTO toArray() / getSummary() 與分析分離衝突

- **問題**：`SourceDistributionDTO.toArray()` 內部呼叫 `getTrafficQualityAnalysis()`、`getDeviceUsagePattern()` 等方法。若將分析方法移走，`toArray()` 無法編譯。
- **決策**：
  - `toArray()` 改為僅輸出純資料欄位（property + 簡單衍生 getter）。原本的分析區塊（如 `traffic_quality_analysis`、`performance_grade`、`engagement_metrics`、`content_analysis` 等）全部移除。
  - `getSummary()` 同理，移除分析相關欄位（如 `performance_grade`、`device_pattern`、`activity_level`）。
  - Controller / Service 層負責呼叫 DTO `toArray()` + Analyzer 各方法並組合為最終回應。
  - `jsonSerialize()` 仍委託 `toArray()`，因此序列化輸出結構會改變。此為預期行為，需在 API 規格文件中標記。
- **優點**：DTO 完全成為純資料容器，符合 SRP；Controller/Service 明確掌握回應組合邏輯。
- **Trade-off**：Controller/Service 層需增加 2-5 行組合程式碼；非向後相容的序列化變更。

### 9. StatisticsOverviewDTO 專屬 Analyzer

- **決策**：與其他 DTO 一致，為 `StatisticsOverviewDTO` 建立 `StatisticsOverviewAnalyzer` + `StatisticsOverviewResult`。
- **轉移方法**：
  - `getActivityLevel()`（public，內部呼叫 `calculateActivityScore()`）
  - `calculateActivityScore()`（private）
- **保留在 DTO 的方法**：
  - `getGrowthRate()` — 純屬性衍生計算，非分析邏輯
  - `getPostsPerUser()` — 純屬性衍生計算，非分析邏輯

### 10. ContentInsightsDTO.getPerformanceGrade() 回傳型別變更

- **問題**：原始方法回傳 `string`（如 `'A+'`、`'C'`），但 `PerformanceGrade` enum 的值為 `EXCELLENT`、`GOOD` 等。
- **決策**：
  - Analyzer 中的對應方法回傳 `PerformanceGrade` enum。
  - 原始對應關係：`A+`/`A` → `EXCELLENT`、`B+`/`B` → `GOOD`、`C+`/`C` → `AVERAGE`、`D` → `POOR`。
  - 不保留 `getPerformanceGrade(): string` 的舊有回傳型別；呼叫端若需字串可呼叫 `$grade->value`。
  - 此為行為相容但型別不相容的變更，需更新所有呼叫端。

### 11. PostStatisticsDTO.filterIntegerMap() 合併至 ArraySanitizer

- `filterIntegerMap()` 本質上是 `ensureStringIntArray` 加上 `>= 0` 檢查。ArraySanitizer 中新增 `ensureStringNonNegativeIntArray(mixed $data): array` 涵蓋此邏輯。
- `PostStatisticsDTO.fromArray()` 中所有 `self::filterIntegerMap(...)` 呼叫改為 `ArraySanitizer::ensureStringNonNegativeIntArray(...)`。

| 選項 | 理由 |
|------|------|
| **A: 留在原地不動** | DTO 持續膨脹，違反 SRP，測試困難 |
| **B: 直接移入現有 Services** | Service 已有明確職責（匯出/聚合），混合分析邏輯會造成 Service 膨脹 |
| **C（採用）: Analyzer + Result** | 最符合 DDD 分層，分析邏輯有獨立歸宿，Result 提供型別安全回傳 |

## Risks / Trade-offs

- **遷移遺漏風險** → 每個 Analyzer 比對原始 DTO 的每個分析方法，確保無遺漏；撰寫測試驗證搬遷前後輸出一致
- **toArray() 序列化變更** → 各 DTO 原有的 `calculated_metrics`、分析區塊將改變結構。影響所有依賴 `jsonSerialize()` 的 API 消費者。需在 Controller 層透過 Analyzer 補回分析結果。
- **效能影響** → Analyzer 新增一層物件建立開銷，但 PHP 8.4 的 JIT 與 readonly class 可忽略不計
- **Controller/Service 需改注入與組合邏輯** → 影響約 6 個 Application 層檔案，需逐一修改建構子、呼叫點與回應組合
- **不改變行為** → 必須嚴格「複製貼上」而非重構，避免引入新 bug
- **測試需要搬遷** → 現有 DTO 測試中對分析方法的斷言（約 25 個測試案例）需移至對應的 Analyzer 測試類別

## 驗證與回滾計畫

### 驗證策略
1. **逐方法比對測試**：每個分析搬遷後，執行輸入輸出比對測試，驗證結果與原始 DTO 方法完全一致
2. **完整測試套件**：搬遷完成後執行 `composer check-all`，確保無回歸
3. **程式碼審查**：所有搬遷 PR 需經至少一人審查，確認無行為變更
4. **變更記錄**：在 `CHANGELOG.md` 中記錄 DTO 序列化結構變更摘要

### 回滾計畫
1. **逐檔案回滾**：若某個 Analyzer 出現問題，僅回滾該 Analyzer + 對應 DTO 還原方法，不影響其他 4 組
2. **git commit 分開**：每個 DTO+Analyzer 為獨立 commit，方便個別 revert
3. **回滾後驗證**：回滾後執行 `composer test` 確保測試通過
4. **不可回滾情境**：若已發布到正式環境且 API 消費者已依賴新序列化結構，則需透過版本號遞增（v2）而非回滾處理
