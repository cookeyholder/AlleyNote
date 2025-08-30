# 使用者活動記錄系統架構文件

## 概述

本文件描述了 AlleyNote 專案中用戶活動記錄系統的完整架構、設計原則和實作細節。該系統採用領域驅動設計（DDD）原則，提供強健、可擴展的使用者行為監控和分析功能。

## 系統特色

### ✨ 核心功能
- **全面的活動記錄**: 支援 21 種預定義活動類型
- **實時異常檢測**: 智慧型可疑行為分析系統
- **批次處理**: 高效能的批次記錄功能
- **靈活的查詢**: 支援多維度資料檢索和分析
- **自動清理**: 基於保留政策的資料生命週期管理

### 🏗️ 架構優勢
- **領域驅動設計**: 清晰的業務邏輯分層
- **強型別安全**: 完整的型別檢查和驗證
- **高效能索引**: 優化的資料庫查詢效能
- **完整測試覆蓋**: 100% 的測試覆蓋率
- **RESTful API**: 標準化的 API 介面

## 架構概覽

```
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                        │
│  ┌─────────────────────┐  ┌─────────────────────────────────┐ │
│  │ ActivityLogController│  │     Middleware & Validators     │ │
│  └─────────────────────┘  └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                     Domain Layer                            │
│  ┌───────────────────────────────────────────────────────┐   │
│  │                   Services                            │   │
│  │ ┌─────────────────┐ ┌─────────────────────────────────┐│   │
│  │ │ActivityLogging  │ │  SuspiciousActivityDetector    ││   │
│  │ │    Service      │ │                                 ││   │
│  │ └─────────────────┘ └─────────────────────────────────┘│   │
│  └───────────────────────────────────────────────────────┘   │
│  ┌───────────────────────────────────────────────────────┐   │
│  │                  Entities & VOs                       │   │
│  │ ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐│   │
│  │ │ ActivityLog │ │   DTOs      │ │     Enums           ││   │
│  │ │   Entity    │ │             │ │ (Type, Category,    ││   │
│  │ │             │ │             │ │  Status)            ││   │
│  │ └─────────────┘ └─────────────┘ └─────────────────────┘│   │
│  └───────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
┌─────────────────────────────────────────────────────────────┐
│                Infrastructure Layer                         │
│  ┌─────────────────────┐  ┌─────────────────────────────────┐ │
│  │ActivityLogRepository│  │     Database & Indexing        │ │
│  │                     │  │                                 │ │
│  └─────────────────────┘  └─────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## 核心元件

### 1. 領域模型 (Domain Models)

#### ActivityLog Entity
- **位置**: `app/Domains/Security/Entities/ActivityLog.php`
- **責任**: 用戶活動的核心實體，封裝所有活動相關的業務邏輯
- **特色**:
  - 不可變實體設計
  - 豐富的領域行為
  - 完整的驗證邏輯

#### 枚舉 (Enums)
- **ActivityType**: 21 種活動類型（登入、登出、文章操作等）
- **ActivityCategory**: 4 個主要類別（認證、內容、檔案、安全）
- **ActivityStatus**: 活動執行狀態（成功、失敗、錯誤、封鎖）

### 2. 應用服務 (Application Services)

#### ActivityLoggingService
- **位置**: `app/Domains/Security/Services/ActivityLoggingService.php`
- **責任**: 活動記錄的主要業務邏輯
- **核心方法**:
  - `log()`: 通用記錄方法
  - `logSuccess()`: 成功操作記錄
  - `logFailure()`: 失敗操作記錄
  - `logSecurityEvent()`: 安全事件記錄
  - `logBatch()`: 批次記錄

#### SuspiciousActivityDetector
- **位置**: `app/Domains/Security/Services/SuspiciousActivityDetector.php`
- **責任**: 異常行為檢測和分析
- **檢測類型**:
  - 失敗率檢測
  - 頻率異常檢測
  - IP 行為模式分析

### 3. 基礎設施 (Infrastructure)

#### ActivityLogRepository
- **位置**: `app/Domains/Security/Repositories/ActivityLogRepository.php`
- **責任**: 資料存取層抽象
- **效能特色**:
  - 複合索引優化
  - 批次操作支援
  - 分頁查詢
  - 資料清理

### 4. API 層 (API Layer)

#### ActivityLogController
- **位置**: `app/Application/Controllers/Api/V1/ActivityLogController.php`
- **責任**: RESTful API 端點
- **支援操作**:
  - 活動記錄 (`POST /api/v1/activity-logs`)
  - 批次記錄 (`POST /api/v1/activity-logs/batch`)
  - 查詢記錄 (`GET /api/v1/activity-logs`)

## 資料模型

### 資料庫架構

```sql
CREATE TABLE user_activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    user_id TEXT,
    session_id TEXT,
    action_type TEXT NOT NULL,
    action_category TEXT NOT NULL,
    target_type TEXT,
    target_id TEXT,
    status TEXT NOT NULL DEFAULT 'success',
    description TEXT,
    metadata TEXT,
    ip_address TEXT,
    user_agent TEXT,
    request_method TEXT,
    request_path TEXT,
    created_at TEXT NOT NULL,
    occurred_at TEXT NOT NULL
);
```

### 效能索引

系統配備了 12 個策略性索引來優化查詢效能：

```sql
-- 基本索引
CREATE INDEX user_activity_logs_user_id_index ON user_activity_logs(user_id);
CREATE INDEX user_activity_logs_occurred_at_index ON user_activity_logs(occurred_at);
CREATE INDEX user_activity_logs_action_category_index ON user_activity_logs(action_category);
CREATE INDEX user_activity_logs_status_index ON user_activity_logs(status);

-- 複合索引 (2024-12-27 優化)
CREATE INDEX user_activity_logs_user_id_action_category_index ON user_activity_logs(user_id, action_category);
CREATE INDEX user_activity_logs_user_id_status_index ON user_activity_logs(user_id, status);
CREATE INDEX user_activity_logs_action_category_occurred_at_index ON user_activity_logs(action_category, occurred_at);
```

## 使用範例

### 記錄使用者登入成功

```php
use App\Domains\Security\Services\ActivityLoggingService;
use App\Domains\Security\Enums\ActivityType;

$activityLogger = $container->get(ActivityLoggingService::class);

$success = $activityLogger->logSuccess(
    actionType: ActivityType::LOGIN_SUCCESS,
    userId: 123,
    targetType: 'user',
    targetId: '123',
    metadata: [
        'login_method' => 'password',
        'remember_me' => true,
        'user_agent' => 'Mozilla/5.0...',
        'ip_address' => '192.168.1.100'
    ]
);
```

### 批次記錄多個活動

```php
$activities = [
    CreateActivityLogDTO::success(
        ActivityType::POST_VIEWED,
        userId: 123,
        targetType: 'post',
        targetId: '456'
    ),
    CreateActivityLogDTO::success(
        ActivityType::ATTACHMENT_DOWNLOADED,
        userId: 123,
        targetType: 'attachment',
        targetId: '789'
    ),
];

$results = $activityLogger->logBatch($activities);
```

### 檢測可疑活動

```php
use App\Domains\Security\Services\SuspiciousActivityDetector;

$detector = $container->get(SuspiciousActivityDetector::class);

$analysis = $detector->analyzeUserActivity(
    userId: 123,
    timeWindowMinutes: 60
);

if ($analysis->isSuspicious()) {
    foreach ($analysis->getDetectedPatterns() as $pattern) {
        echo "可疑模式: {$pattern->getType()}, 風險分數: {$pattern->getRiskScore()}\n";
    }
}
```

## API 端點

### 記錄單一活動

```http
POST /api/v1/activity-logs
Content-Type: application/json

{
    "action_type": "auth.login.success",
    "user_id": 123,
    "description": "使用者登入成功",
    "metadata": {
        "login_method": "password",
        "ip_address": "192.168.1.100"
    }
}
```

### 查詢活動記錄

```http
GET /api/v1/activity-logs?user_id=123&limit=50&page=1
```

## 效能指標

基於最新的效能測試結果：

| 操作類型 | 平均執行時間 | 基準要求 | 狀態 |
|---------|------------|----------|------|
| 單一記錄 | < 1ms | < 50ms | ✅ 優秀 |
| 批次記錄 (100筆) | < 10ms | < 500ms | ✅ 優秀 |
| 複雜查詢 | < 5ms | < 100ms | ✅ 優秀 |
| 異常檢測 | < 20ms | < 200ms | ✅ 優秀 |

## 配置選項

### 記錄等級控制

```php
// 設定記錄等級 (1-5，數字越高越嚴格)
$activityLogger->setLogLevel(3);

// 停用特定活動類型
$activityLogger->disableLogging(ActivityType::POST_VIEWED);
```

### 資料保留政策

```php
// 清理 30 天前的記錄
$deletedCount = $activityLogger->cleanup();
```

## 監控與維護

### 健康檢查

系統提供內建的健康檢查功能：

```php
// 檢查服務狀態
$isHealthy = $activityLogger->isHealthy();

// 取得統計資訊
$stats = $repository->getSystemStats();
```

### 效能監控

建議監控以下指標：
- 記錄成功率 (目標: > 99.9%)
- 平均回應時間 (目標: < 10ms)
- 資料庫連線狀態
- 磁碟空間使用率

## 故障排除

### 常見問題

1. **記錄失敗**
   - 檢查資料庫連線
   - 驗證輸入資料格式
   - 確認使用者權限

2. **效能問題**
   - 檢查索引使用狀況
   - 考慮批次處理
   - 調整資料保留政策

3. **異常檢測不準確**
   - 調整檢測閾值
   - 增加訓練資料量
   - 檢查時間範圍設定

## 擴展指南

### 新增活動類型

1. 在 `ActivityType` 枚舉中新增類型
2. 更新 `ActivityCategory` 分類
3. 擴展測試覆蓋
4. 更新 API 文件

### 客製化檢測邏輯

```php
class CustomSuspiciousDetector extends SuspiciousActivityDetector
{
    protected function analyzeCustomPattern(array $activities): DetectionResult
    {
        // 實作客製化檢測邏輯
    }
}
```

## 版本歷史

- **v1.0.0** (2024-12): 初始發布，核心功能實作
- **v1.1.0** (2024-12): 效能優化，新增複合索引
- **v1.2.0** (2024-12): 異常檢測系統，批次處理支援

---

*最後更新: 2024年12月27日*
*文件版本: v1.2.0*
