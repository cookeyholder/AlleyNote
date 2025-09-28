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
- ✅ Post 模型新增 `source_type` 欄位（enum: 'web', 'api', 'import', 'migration'）
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
```sql
CREATE TABLE statistics_snapshots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    snapshot_type VARCHAR(50) NOT NULL COMMENT '統計類型: overview, posts, sources, users, popular',
    period_type VARCHAR(20) NOT NULL COMMENT '統計週期: daily, weekly, monthly, yearly',
    period_start DATETIME NOT NULL COMMENT '統計週期開始時間',
    period_end DATETIME NOT NULL COMMENT '統計週期結束時間',
    statistics_data TEXT NOT NULL COMMENT '儲存序列化後的統計資料 JSON 字串',
    metadata TEXT NULL COMMENT '儲存序列化後的元資料 JSON 字串',
    expires_at DATETIME NULL COMMENT '快照過期時間',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- 索引和約束
    UNIQUE(snapshot_type, period_type, period_start, period_end),
    CONSTRAINT chk_snapshot_type CHECK (snapshot_type IN ('overview', 'posts', 'sources', 'users', 'popular')),
    CONSTRAINT chk_period_type CHECK (period_type IN ('daily', 'weekly', 'monthly', 'yearly')),
    CONSTRAINT chk_period_range CHECK (period_start < period_end)
);

-- 建立索引
CREATE INDEX idx_snapshot_type_period ON statistics_snapshots(snapshot_type, period_type);
CREATE INDEX idx_period_range ON statistics_snapshots(period_start, period_end);
CREATE INDEX idx_expires_at ON statistics_snapshots(expires_at);
CREATE INDEX idx_created_at ON statistics_snapshots(created_at);
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
- ✅ 包含完整的查詢參數驗證

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

#### 4.1.1 API 回應格式標準
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

#### 4.1.2 錯誤碼定義
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

#### 4.2 統計管理 API
**需求描述**：提供管理員專用的統計管理功能。

**驗收標準**：
- ✅ POST `/api/admin/statistics/refresh` - 手動重新計算統計
- ✅ DELETE `/api/admin/statistics/cache` - 清除統計快取
- ✅ GET `/api/admin/statistics/health` - 統計系統健康檢查
- ✅ 需要管理員權限驗證
- ✅ 操作記錄到活動日誌

### 5. 快取與效能最佳化

#### 5.1 快取策略實作
**需求描述**：實作多層次快取策略，提升統計查詢效能。

**驗收標準**：
- ✅ 支援 Redis 快取統計結果
- ✅ 快取鍵命名規範統一
- ✅ 支援快取標籤分組管理
- ✅ 快取過期時間合理設定
- ✅ 支援快取預熱機制
- ✅ 快取失效策略正確實作

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
const MONITORING_METRICS = [
    'cache_hit_rate',              // 快取命中率
    'cache_miss_rate',             // 快取未命中率
    'cache_response_time',         // 快取回應時間
    'cache_rebuild_time',          // 快取重建時間
    'statistics_calculation_time', // 統計計算時間
    'api_response_time',           // API 回應時間
    'error_rate',                  // 錯誤率
    'request_volume'               // 請求量
];
```

### 6. 測試需求

#### 6.1 單元測試
**驗收標準**：
- ✅ 所有 Service 類別測試覆蓋率 ≥ 90%
- ✅ 所有 Repository 類別測試覆蓋率 ≥ 90%
- ✅ 統計計算邏輯測試涵蓋邊界條件
- ✅ 快取機制測試涵蓋失效情境
- ✅ 錯誤處理測試涵蓋所有例外情況

#### 6.2 整合測試
**驗收標準**：
- ✅ 統計 API 端點完整測試
- ✅ 資料庫查詢效能測試
- ✅ 快取整合測試
- ✅ 認證授權測試
- ✅ 並發存取測試

### 7. 效能要求

#### 7.1 回應時間要求
- 快取命中時：統計查詢 < 100ms
- 快取未命中時：統計查詢 < 2s
- 快照生成：< 10s
- 批量資料處理：< 30s

#### 7.2 併發處理能力
- 同時支援 100+ 統計查詢請求
- 快照生成不阻塞查詢操作
- 快取更新採用非阻塞機制

### 8. 部署與監控

#### 8.1 部署需求
**驗收標準**：
- ✅ Docker 容器化部署
- ✅ 資料庫 migration 自動執行
- ✅ 快取服務依賴檢查
- ✅ 環境變數配置完整
- ✅ 健康檢查端點正常運作

#### 8.2 監控告警
**需要監控的指標**：
- 統計 API 回應時間
- 快取命中率
- 錯誤率
- 系統資源使用率
- 資料庫查詢效能

**告警閾值**：
- API 回應時間 > 5s
- 快取命中率 < 70%
- 錯誤率 > 5%
- CPU 使用率 > 80%
- 記憶體使用率 > 80%

---

## 技術架構圖

```
Frontend (Vite + TypeScript)
    ↓ HTTP/REST API
Statistics Controller
    ↓ Service Layer
Statistics Calculator Service ← Statistics Snapshot Service
    ↓ Repository Layer
Statistics Repository
    ↓ Data Layer
SQLite Database ← Redis Cache
```

## 開發階段規劃

### Phase 1: 基礎設施建立 (Week 1)
- 資料庫 Schema 設計與 Migration
- 基本 Domain Models 建立
- Repository 介面定義

### Phase 2: 核心服務開發 (Week 2-3)
- 統計計算服務實作
- 快照管理服務實作
- 快取策略實作

### Phase 3: API 開發 (Week 4)
- RESTful API 端點實作
- 認證授權整合
- API 文件生成

### Phase 4: 測試與最佳化 (Week 5)
- 單元測試與整合測試
- 效能調校與最佳化
- 監控告警設定

### Phase 5: 前端整合 (Week 6)
- 前端統計頁面開發
- 圖表與視覺化實作
- 使用者體驗最佳化

---

## 附錄

### A. 資料庫索引建議
```sql
-- 統計查詢最佳化索引
CREATE INDEX idx_posts_created_status ON posts(created_at, status);
CREATE INDEX idx_posts_user_created ON posts(user_id, created_at);
CREATE INDEX idx_posts_source_created ON posts(source_type, created_at);

-- 複合索引用於複雜統計查詢
CREATE INDEX idx_posts_stats_full ON posts(created_at, status, source_type, user_id);
CREATE INDEX idx_posts_popular ON posts(view_count DESC, created_at DESC);
```

### B. 快取失效規則
```php
// 快取失效觸發條件
const CACHE_INVALIDATION_TRIGGERS = [
    'post_created' => ['statistics:posts', 'statistics:overview'],
    'post_updated' => ['statistics:posts', 'statistics:overview'],
    'post_deleted' => ['statistics:posts', 'statistics:overview'],
    'user_activity' => ['statistics:users'],
    'view_count_updated' => ['statistics:popular'],
    'manual_refresh' => ['statistics:*'],
];
```

### C. 監控查詢範例
```sql
-- 快取命中率監控
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_requests,
    SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) as cache_hits,
    ROUND(SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as hit_rate
FROM statistics_requests_log 
WHERE created_at >= DATE('now', '-7 days')
GROUP BY DATE(created_at)
ORDER BY date;
```