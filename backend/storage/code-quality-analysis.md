# 程式碼品質分析報告

**生成時間**: 2025-09-29 04:32:59

## 📊 PSR-4 合規性

- **總檔案數**: 324
- **合規檔案數**: 320
- **合規率**: 98.77%

### PSR-4 問題清單

- **app/Application.php**: 命名空間 App 與檔案路徑不符
- **app/Shared/Helpers/functions.php**: 缺少命名空間宣告
- **app/Infrastructure/Config/container.php**: 缺少命名空間宣告
- **scripts/ScriptBootstrap.php**: 命名空間 AlleyNote\Scripts 與檔案路徑不符

## 🚀 現代 PHP 特性使用情況

- **枚舉型別**: 14 次使用
- **唯讀屬性**: 92 次使用
- **Match 表達式**: 70 次使用
- **聯合型別**: 90 次使用
- **建構子屬性提升**: 0 次使用
- **屬性標籤**: 0 次使用
- **空安全運算子**: 0 次使用

### 可改善的檔案 (前10個)

**app/Application.php**:
  - 可以將 switch 語句改為 match 表達式 (1 處)
  - 缺少回傳型別宣告的函式 (3 處)

**app/Domains/Statistics/Contracts/BatchExportResult.php**:
  - 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/Contracts/ExportResult.php**:
  - 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/Models/StatisticsSnapshot.php**:
  - 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/DTOs/StatisticsOverviewDTO.php**:
  - 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/DTOs/ContentInsightsDTO.php**:
  - 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/DTOs/SourceDistributionDTO.php**:
  - 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/DTOs/PostStatisticsDTO.php**:
  - 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/DTOs/UserStatisticsDTO.php**:
  - 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/Events/PostViewed.php**:
  - 缺少回傳型別宣告的函式 (1 處)

## 🏛️ DDD 架構分析

- **完整性評分**: 100%
- **總組件數**: 53

- **實體**: 3 個
- **值物件**: 13 個
- **聚合根**: 0 個
- **儲存庫**: 6 個
- **領域服務**: 29 個
- **領域事件**: 2 個

