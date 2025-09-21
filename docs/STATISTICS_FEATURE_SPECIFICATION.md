# 文章統計功能規格書

## 版本資訊
- 版本：v1.0.0
- 建立日期：2025-09-21
- 責任人：開發團隊

## 功能概述

建立一個完整的文章統計系統，提供文章數量、來源分布、使用者活躍度等多維度統計資料，支援 Dashboard 展示和報表匯出功能。

## 功能需求

### 1. 統計資料結構設計

#### 1.1 文章來源追蹤
**需求描述**：為文章新增來源追蹤機制，記錄文章的建立管道和來源類型。

**驗收標準**：
- ✅ Post 模型新增 `source_type` 欄位（enum: 'web', 'api- ✅ 快取失效策略正確實作

#### 5.1.1 快取鍵命名規範
**快取鍵結構**：
```php
// 基本格式: {prefix}:{type}:{period}:{date_hash}
const CACHE_KEYS = [
    'overview' => 'alley:stats:overview:{period}:{date_hash}',
    'posts' => 'alley:stats:posts:{type}:{period}:{date_hash}',
    'sources' => 'alley:stats:sources:{period}:{date_hash}',
    'users' => 'alley:stats:users:{period}:{date_hash}',
    'popular' => 'alley:stats:popular:{limit}:{period}:{date_hash}',
];

// 範例
const EXAMPLE_KEYS = [
    'alley:stats:overview:monthly:2025-09',
    'alley:stats:posts:by_status:daily:2025-09-21',
    'alley:stats:sources:weekly:2025-W38',
    'alley:stats:users:activity:monthly:2025-09',
    'alley:stats:popular:10:daily:2025-09-21'
];
```

**快取標籤系統**：
```php
// 主標籤
const PRIMARY_TAG = 'statistics';

// 子標籤
const SUB_TAGS = [
    'statistics:overview',
    'statistics:posts',
    'statistics:sources',
    'statistics:users',
    'statistics:popular'
];

// 時間標籤
const TIME_TAGS = [
    'statistics:daily',
    'statistics:weekly',
    'statistics:monthly',
    'statistics:yearly'
];
```

#### 5.1.2 監控與效能指標
**快取效能目標**：
- 快取命中率 ≥ 80%
- 快取回應時間 < 50ms
- 快取失效重建時間 < 2 秒

**監控指標**：
```php
// 需要監控的指標
const MONITORING_METRICS = [
    'cache_hit_rate',           // 快取命中率
    'cache_miss_rate',          // 快取未命中率
    'cache_response_time',      // 快取回應時間
    'cache_rebuild_time',       // 快取重建時間
    'statistics_calculation_time', // 統計計算時間
    'api_response_time',        // API 回應時間
    'error_rate',               // 錯誤率
    'request_volume'            // 請求量
];
```import', 'migration'）
- ✅ Post 模型新增 `source_detail` 欄位（記錄具體來源資訊）
- ✅ 資料庫 migration 正確建立並可逆轉
- ✅ 現有資料向下相容，預設來源為 'web'
- ✅ 相關索引正確建立，查詢效能達標

#### 1.1.1 資料庫結構詳細
**posts 表修改**：
```sql
-- 新增來源追蹤欄位
ALTER TABLE posts
ADD COLUMN source_type VARCHAR(20) NOT NULL DEFAULT 'web'
COMMENT '文章來源類型: web, api, import, migration',
ADD COLUMN source_detail TEXT NULL
COMMENT '來源詳情描述或標識';

-- 建立效能索引
CREATE INDEX idx_posts_source_type ON posts(source_type);
CREATE INDEX idx_posts_created_source ON posts(created_at, source_type);
CREATE INDEX idx_posts_status_source ON posts(status, source_type, created_at);

-- 為統計查詢最佳化的複合索引
CREATE INDEX idx_posts_stats_composite ON posts(created_at, status, source_type, user_id);
```

**欄位約束與驗證**：
- `source_type` 使用 CHECK 約束限制可選值
- `source_detail` 長度不超過 1000 字元
- 歷史資料的缺失值處理

```sql
-- 欄位約束
ALTER TABLE posts ADD CONSTRAINT chk_source_type
CHECK (source_type IN ('web', 'api', 'import', 'migration'));

ALTER TABLE posts ADD CONSTRAINT chk_source_detail_length
CHECK (LENGTH(source_detail) <= 1000);
```

#### 1.2 統計快照表設計
**需求描述**：建立統計資料快照表，避免每次重新計算，提升查詢效能。

**驗收標準**：
- ✅ `statistics_snapshots` 表正確建立
- ✅ 支援多種統計維度（daily, weekly, monthly, yearly）
- ✅ 包含統計日期、類型、數值等欄位
- ✅ 支援 JSON 格式儲存複雜統計資料
- ✅ 建立適當的索引和外鍵約束

#### 1.2.1 統計快照表結構詳細
**statistics_snapshots 表定義**：
```sql
CREATE TABLE statistics_snapshots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    snapshot_type VARCHAR(50) NOT NULL COMMENT '統計類型: overview, posts, sources, users, popular',
    period_type VARCHAR(20) NOT NULL COMMENT '統計週期: daily, weekly, monthly, yearly',
    period_start DATETIME NOT NULL COMMENT '統計週期開始時間',
    period_end DATETIME NOT NULL COMMENT '統計週期結束時間',
    statistics_data TEXT NOT NULL COMMENT '儲存序列化後的統計資料 JSON 字串',
    metadata TEXT NULL COMMENT '儲存序列化後的元資料 JSON 字串：計算參數、版本等',
    expires_at DATETIME NULL COMMENT '快照過期時間',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- 索引和約束
    UNIQUE(snapshot_type, period_type, period_start, period_end),
    INDEX idx_snapshot_type_period (snapshot_type, period_type),
    INDEX idx_period_range (period_start, period_end),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at),

    -- 檢查約束
    CONSTRAINT chk_snapshot_type CHECK (snapshot_type IN ('overview', 'posts', 'sources', 'users', 'popular')),
    CONSTRAINT chk_period_type CHECK (period_type IN ('daily', 'weekly', 'monthly', 'yearly')),
    CONSTRAINT chk_period_range CHECK (period_start < period_end),
    CONSTRAINT chk_expires_at CHECK (expires_at IS NULL OR expires_at > created_at)
);
```

**統計資料 JSON 格式範例**：
```json
{
  "total_posts": 1250,
  "by_status": {
    "published": 1100,
    "draft": 120,
    "archived": 30
  },
  "by_source": {
    "web": 800,
    "api": 300,
    "import": 100,
    "migration": 50
  },
  "trends": {
    "vs_previous_period": "+12.5%",
    "growth_rate": 0.125
  },
  "calculated_at": "2025-09-21T10:30:00Z",
  "calculation_time_ms": 1250
}
```

### 2. 統計服務設計

#### 2.1 統計計算服務
**需求描述**：建立統計計算核心服務，負責各種統計指標的計算邏輯。

**驗收標準**：
- ✅ `StatisticsCalculatorService` 類別符合 DDD 架構
- ✅ 支援文章總數統計（按狀態、時間範圍）
- ✅ 支援來源分布統計（按類型、時間範圍）
- ✅ 支援使用者活躍度統計（發文數、瀏覽數）
- ✅ 支援熱門文章統計（按瀏覽數、時間範圍）
- ✅ 所有統計方法包含完整的錯誤處理
- ✅ 支援可選的快取機制

#### 2.2 統計快照服務
**需求描述**：管理統計快照的生成、更新和清理。

**驗收標準**：
- ✅ `StatisticsSnapshotService` 類別正確實作
- ✅ 支援定時生成統計快照
- ✅ 支援手動觸發快照更新
- ✅ 支援過期快照自動清理
- ✅ 包含完整的例外處理機制
- ✅ 記錄操作日誌便於除錯

### 3. Repository 設計

#### 3.1 統計查詢 Repository
**需求描述**：建立專門的統計查詢 Repository，提供高效的統計資料存取。

**驗收標準**：
- ✅ `StatisticsRepositoryInterface` 介面定義完整
- ✅ `StatisticsRepository` 實作所有介面方法
- ✅ 支援複雜的統計查詢（聚合、分組、篩選）
- ✅ 查詢效能最佳化（使用適當索引）
- ✅ 支援分頁查詢避免記憶體問題
- ✅ - 包含完整的查詢參數驗證

### 3.1 API 回應格式標準
**成功回應格式**：
```json
{
  "success": true,
  "data": {
    "statistics": {
      // 實際統計資料
    },
    "metadata": {
      "period": {
        "type": "monthly",
        "start": "2025-09-01T00:00:00Z",
        "end": "2025-09-30T23:59:59Z"
      },
      "generated_at": "2025-09-21T10:30:00Z",
      "cache_hit": true,
      "calculation_time_ms": 125
    }
  },
  "message": "統計資料擷取成功",
  "timestamp": "2025-09-21T10:30:00Z"
}
```

**錯誤回應格式**：
```json
{
  "success": false,
  "error": {
    "code": "STATS_INVALID_PERIOD",
    "message": "統計週期參數無效",
    "details": {
      "field": "period_type",
      "value": "invalid_period",
      "allowed_values": ["daily", "weekly", "monthly", "yearly"]
    },
    "trace_id": "stats-req-20250921-103000-001"
  },
  "timestamp": "2025-09-21T10:30:00Z"
}
```

### 3.2 錯誤碼定義
**統計功能錯誤碼規範**：
```php
class StatisticsErrorCodes {
    // 參數驗證錯誤
    const INVALID_PERIOD_TYPE = 'STATS_INVALID_PERIOD';
    const INVALID_DATE_RANGE = 'STATS_INVALID_DATE_RANGE';
    const INVALID_STATISTICS_TYPE = 'STATS_INVALID_TYPE';

    // 資料存取錯誤
    const DATA_NOT_FOUND = 'STATS_DATA_NOT_FOUND';
    const CALCULATION_FAILED = 'STATS_CALCULATION_FAILED';
    const CACHE_ERROR = 'STATS_CACHE_ERROR';

    // 權限錯誤
    const INSUFFICIENT_PERMISSIONS = 'STATS_INSUFFICIENT_PERMISSIONS';
    const ADMIN_REQUIRED = 'STATS_ADMIN_REQUIRED';

    // 系統錯誤
    const SERVICE_UNAVAILABLE = 'STATS_SERVICE_UNAVAILABLE';
    const TIMEOUT = 'STATS_TIMEOUT';
    const RATE_LIMIT_EXCEEDED = 'STATS_RATE_LIMIT_EXCEEDED';
}
```

**HTTP 狀態碼對照**：
- 200: 成功回應
- 400: 參數錯誤或驗證失敗
- 401: 未授權或 Token 無效
- 403: 權限不足
- 404: 統計資料不存在
- 429: 請求頻率過高
- 500: 伺服器內部錯誤
- 503: 統計服務不可用

### 4. API 設計

#### 4.1 統計查詢 API
**需求描述**：提供 RESTful API 供前端查詢各種統計資料。

**驗收標準**：
- ✅ GET `/api/statistics/overview` - 總覽統計
- ✅ GET `/api/statistics/posts` - 文章統計（支援時間範圍篩選）
- ✅ GET `/api/statistics/sources` - 來源分布統計
- ✅ GET `/api/statistics/users` - 使用者活躍度統計
- ✅ GET `/api/statistics/popular` - 熱門內容統計
- ✅ 所有 API 支援 JWT 認證
- ✅ 回傳資料格式標準化（包含 metadata）
- ✅ 完整的錯誤處理和 HTTP 狀態碼
- ✅ API 文件自動生成（Swagger）

#### 4.2 統計管理 API
**需求描述**：提供管理員專用的統計管理功能。

**驗收標準**：
- ✅ POST `/api/admin/statistics/refresh` - 手動重新計算統計
- ✅ DELETE `/api/admin/statistics/cache` - 清除統計快取
- ✅ GET `/api/admin/statistics/health` - 統計系統健康檢查，應至少包含：資料庫連線、快取服務連線、最新快照時間戳。
- ✅ 需要管理員權限驗證
- ✅ 操作記錄到活動日誌

### 5. 快取策略

#### 5.1 統計資料快取
**需求描述**：實作多層次快取策略，提升統計查詢效能。

**驗收標準**：
- ✅ 支援 Redis 快取統計結果
- ✅ 快取鍵命名規範統一
- ✅ 支援快取標籤分組管理
- ✅ 快取過期時間合理設定
- ✅ 支援快取預熱機制
- ✅ - 快取失效策略正確實作

#### 5.1.1 快取鍵命名規範
**快取鍵結構**：
```php
// 基本格式: {prefix}:{type}:{period}:{date_hash}
const CACHE_KEYS = [
    'overview' => 'alley:stats:overview:{period}:{date_hash}',
    'posts' => 'alley:stats:posts:{type}:{period}:{date_hash}',
    'sources' => 'alley:stats:sources:{period}:{date_hash}',
    'users' => 'alley:stats:users:{period}:{date_hash}',
    'popular' => 'alley:stats:popular:{limit}:{period}:{date_hash}',
];

// 範例
const EXAMPLE_KEYS = [
    'alley:stats:overview:monthly:2025-09',
    'alley:stats:posts:by_status:daily:2025-09-21',
    'alley:stats:sources:weekly:2025-W38',
    'alley:stats:users:activity:monthly:2025-09',
    'alley:stats:popular:10:daily:2025-09-21'
];
```

**快取標籤系統**：
```php
// 主標籤
const PRIMARY_TAG = 'statistics';

// 子標籤
const SUB_TAGS = [
    'statistics:overview',
    'statistics:posts',
    'statistics:sources',
    'statistics:users',
    'statistics:popular'
];

// 時間標籤
const TIME_TAGS = [
    'statistics:daily',
    'statistics:weekly',
    'statistics:monthly',
    'statistics:yearly'
];
```

#### 5.1.2 監控與效能指標
**快取效能目標**：
- 快取命中率 ≥ 80%
- 快取回應時間 < 50ms
- 快取失效重建時間 < 2 秒

**監控指標**：
```php
// 需要監控的指標
const MONITORING_METRICS = [
    'cache_hit_rate',           // 快取命中率
    'cache_miss_rate',          // 快取未命中率
    'cache_response_time',      // 快取回應時間
    'cache_rebuild_time',       // 快取重建時間
    'statistics_calculation_time', // 統計計算時間
    'api_response_time',        // API 回應時間
    'error_rate',               // 錯誤率
    'request_volume'            // 請求量
];
```

### 6. 前端整合（可選）

#### 6.1 統計 Dashboard
**需求描述**：在管理介面中新增統計 Dashboard 頁面。

**驗收標準**：
- ✅ 總覽卡片展示關鍵指標
- ✅ 圖表展示趨勢資料（折線圖、圓餅圖）
- ✅ 支援時間範圍選擇
- ✅ 響應式設計支援行動裝置
- ✅ 資料即時更新機制
- ✅ 載入狀態和錯誤處理

## 非功能需求

### 效能需求
- 統計 API 回應時間 < 2 秒
- 支援併發查詢數 ≥ 100
- 快取命中率 ≥ 80%

### 安全需求
- 所有 API 需要適當的權限驗證
- 敏感統計資料需要管理員權限
- 防止統計資料洩露用戶隱私

### 維護需求
- 完整的單元測試覆蓋率 ≥ 90%
- 詳細的 API 文件
- 操作手冊和故障排除指南

### 資料保存需求
- 統計快照資料保存期限：日統計 30 天，週統計 90 天，月統計 1 年，年統計永久保存
- 原始日誌資料保存 180 天
- 定期清理過期快照資料

### 隱私保護需求
- 統計資料不包含個人識別資訊
- IP 位址進行部分遾蔽處理
- 敏感統計資料加密儲存
- 符合 GDPR 等隱私保護法規

## 技術約束

### 架構約束
- 遵循 DDD 架構原則
- 符合專案的程式碼品質標準
- 使用現有的快取和資料庫基礎設施

### 相容性約束
- PHP 8.4+ 相容
- 不破壞現有 API 向下相容性
- 支援現有的認證機制

## 參考資料

- 專案 DDD 架構指南
- API 設計規範
- 資料庫設計最佳實踐
- 快取策略指南
�程時間等。

## 參考資料

- 專案 DDD 架構指南
- API 設計規範
- 資料庫設計最佳實踐
- 快取策略指南
構指南
- API 設計規範
- 資料庫設計最佳實踐
- 快取策略指南
��

### 資料保存需求
- 統計快照資料保存期限：日統計 30 天，週統計 90 天，月統計 1 年，年統計永久保存
- 原始日誌資料保存 180 天
- 定期清理過期快照資料

### 隱私保護需求
- 統計資料不包含個人識別資訊
- IP 位址進行部分遾蔽處理
- 敏感統計資料加密儲存
- 符合 GDPR 等隱私保護法規

## 技術約束

### 架構約束
- 遵循 DDD 架構原則
- 符合專案的程式碼品質標準
- 使用現有的快取和資料庫基礎設施

### 相容性約束
- PHP 8.4+ 相容
- 不破壞現有 API 向下相容性
- 支援現有的認證機制

## 參考資料

- 專案 DDD 架構指南
- API 設計規範
- 資料庫設計最佳實踐
- 快取策略指南


- 專案 DDD 架構指南
- API 設計規範
- 資料庫設計最佳實踐
- 快取策略指南
