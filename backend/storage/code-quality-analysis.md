# 程式碼品質分析報告

**生成時間**: 2025-10-02 19:01:46

## 📊 PSR-4 合規性

- **總檔案數**: 352
- **合規檔案數**: 348
- **合規率**: 98.86%
- **狀態**: ✅ 優秀

### PSR-4 問題清單


#### 命名空間路徑不符 (2 個)

- **app/Application.php**: 命名空間 App 與檔案路徑不符
- **scripts/ScriptBootstrap.php**: 命名空間 AlleyNote\Scripts 與檔案路徑不符

#### 缺少命名空間 (2 個)

- **app/Shared/Helpers/functions.php**: 缺少命名空間宣告
- **app/Infrastructure/Config/container.php**: 缺少命名空間宣告

#### 類別與檔案名稱不一致 (2 個)

- **scripts/Analysis/scan-missing-return-types.php**: 類別名稱 ReturnTypeScanner 與檔案名稱 scan-missing-return-types 不一致
- **scripts/Analysis/analyze-code-quality.php**: 類別名稱 Callable 與檔案名稱 analyze-code-quality 不一致

## 🚀 現代 PHP 特性使用情況

- **特性採用率**: 81.82%
- **總使用次數**: 6870
- **掃描檔案數**: 352

### 特性使用明細

| 特性 | 使用次數 | 狀態 |
|------|---------|------|
| 枚舉型別 (PHP 8.1+) | 17 | ⚠️ |
| 唯讀屬性 (PHP 8.1+) | 0 | ❌ |
| 唯讀類別 (PHP 8.2+) | 52 | ✅ |
| Match 表達式 (PHP 8.0+) | 117 | ✅ |
| 聯合型別 (PHP 8.0+) | 20 | ⚠️ |
| 交集型別 (PHP 8.1+) | 0 | ❌ |
| 建構子屬性提升 (PHP 8.0+) | 124 | ✅ |
| 屬性標籤 (PHP 8.0+) | 72 | ✅ |
| 空安全運算子 (PHP 8.0+) | 116 | ✅ |
| 具名參數 (PHP 8.0+) | 6148 | ✅ |
| First-class Callable (PHP 8.1+) | 204 | ✅ |

### 可改善的檔案 (前10個)

**app/Application.php**:
  📝 缺少回傳型別宣告的函式 (2 處)
  🔒 可以考慮將類別標記為 readonly (1 處)
  ⚡ 可以使用建構子屬性提升簡化程式碼 (1 處)

**app/Domains/Statistics/Contracts/BatchExportResult.php**:
  📝 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/Contracts/ExportResult.php**:
  📝 缺少回傳型別宣告的函式 (1 處)

**app/Domains/Statistics/Models/StatisticsSnapshot.php**:
  📝 缺少回傳型別宣告的函式 (1 處)
  🔒 可以考慮將類別標記為 readonly (1 處)

**app/Domains/Statistics/DTOs/StatisticsOverviewDTO.php**:
  📝 缺少回傳型別宣告的函式 (1 處)
  🔒 可以考慮將類別標記為 readonly (1 處)

**app/Domains/Statistics/DTOs/ContentInsightsDTO.php**:
  📝 缺少回傳型別宣告的函式 (1 處)
  🔒 可以考慮將類別標記為 readonly (1 處)

**app/Domains/Statistics/DTOs/SourceDistributionDTO.php**:
  📝 缺少回傳型別宣告的函式 (1 處)
  🔒 可以考慮將類別標記為 readonly (1 處)

**app/Domains/Statistics/DTOs/PostStatisticsDTO.php**:
  📝 缺少回傳型別宣告的函式 (1 處)
  🔒 可以考慮將類別標記為 readonly (1 處)

**app/Domains/Statistics/DTOs/UserStatisticsDTO.php**:
  📝 缺少回傳型別宣告的函式 (1 處)
  🔒 可以考慮將類別標記為 readonly (1 處)

**app/Domains/Statistics/Events/PostViewed.php**:
  📝 缺少回傳型別宣告的函式 (1 處)
  🔒 可以考慮將類別標記為 readonly (1 處)

## 🏛️ DDD 架構分析

- **完整性評分**: 100%
- **總組件數**: 80

### 品質指標

- **值物件使用率**: 89.29%
- **Repository 覆蓋率**: 200%
- **事件驅動準備度**: 80%
- **關注點分離度**: 100%

### 組件統計

- ✅ **實體**: 3 個
- ✅ **值物件**: 25 個
- ❌ **聚合根**: 0 個
- ✅ **儲存庫**: 6 個
- ✅ **領域服務**: 29 個
- ✅ **領域事件**: 7 個
- ✅ **DTO**: 2 個
- ✅ **規格物件**: 7 個
- ✅ **工廠**: 1 個

### 限界上下文分析

| 上下文 | 完整度 | 實體 | 值物件 | 儲存庫 | 服務 | 事件 |
|--------|--------|------|--------|--------|------|------|
| **Attachment** | ❌ 40% | ❌| ❌| ✅| ✅| ❌ |
| **Auth** | ✅ 85% | ✅| ✅| ✅| ✅| ❌ |
| **Post** | ⚠️ 75% | ❌| ✅| ✅| ✅| ✅ |
| **Security** | ⚠️ 65% | ✅| ❌| ✅| ✅| ❌ |
| **Shared** | ❌ 20% | ❌| ✅| ❌| ❌| ❌ |
| **Statistics** | ✅ 80% | ✅| ✅| ❌| ✅| ✅ |

## 📈 總體評估

**綜合評分**: 93.56/100

**等級**: A (優秀)

