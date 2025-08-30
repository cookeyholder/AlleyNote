# 使用者活動記錄系統使用指南

## 概述

本指南提供 AlleyNote 使用者活動記錄系統的實用說明，包括基本使用方法、進階功能配置和最佳實踐建議。

## 快速開始

### 基本記錄操作

```php
use App\Domains\Security\Services\ActivityLoggingService;
use App\Domains\Security\Enums\ActivityType;

// 取得服務實例
$activityLogger = $container->get(ActivityLoggingService::class);

// 記錄使用者登入成功
$success = $activityLogger->logSuccess(
    actionType: ActivityType::LOGIN_SUCCESS,
    userId: 123,
    metadata: [
        'ip_address' => '192.168.1.100',
        'user_agent' => $request->getHeaderLine('User-Agent')
    ]
);

if (!$success) {
    // 處理記錄失敗的情況
    $logger->warning('Failed to log user activity');
}
```

### 使用 DTO 進行詳細記錄

```php
use App\Domains\Security\DTOs\CreateActivityLogDTO;

// 建立詳細的活動記錄 DTO
$dto = new CreateActivityLogDTO(
    actionType: ActivityType::POST_CREATED,
    userId: 123,
    sessionId: session_id(),
    targetType: 'post',
    targetId: '456',
    description: '使用者建立新文章',
    metadata: [
        'post_title' => '我的第一篇文章',
        'category' => 'general',
        'word_count' => 500
    ],
    ipAddress: '192.168.1.100',
    userAgent: 'Mozilla/5.0...',
    requestMethod: 'POST',
    requestPath: '/api/posts'
);

$result = $activityLogger->log($dto);
```

## 活動類型參考

### 認證相關 (Authentication)
```php
ActivityType::LOGIN_SUCCESS     // 登入成功
ActivityType::LOGIN_FAILED      // 登入失敗
ActivityType::LOGOUT            // 登出
ActivityType::PASSWORD_CHANGED  // 密碼變更
```

### 內容相關 (Content)
```php
ActivityType::POST_CREATED      // 文章建立
ActivityType::POST_UPDATED      // 文章更新
ActivityType::POST_DELETED      // 文章刪除
ActivityType::POST_VIEWED       // 文章檢視
```

### 檔案管理 (File Management)
```php
ActivityType::ATTACHMENT_UPLOADED    // 附件上傳
ActivityType::ATTACHMENT_DOWNLOADED  // 附件下載
ActivityType::ATTACHMENT_DELETED     // 附件刪除
ActivityType::ATTACHMENT_VIRUS_DETECTED // 病毒檢測
```

### 安全相關 (Security)
```php
ActivityType::ACCESS_DENIED         // 存取被拒
ActivityType::IP_BLOCKED           // IP 被封鎖
ActivityType::SUSPICIOUS_ACTIVITY  // 可疑活動
ActivityType::PERMISSION_DENIED    // 權限被拒
```

## 批次記錄

當需要一次記錄多個活動時，使用批次記錄可以提高效能：

```php
// 準備多個活動記錄
$activities = [
    CreateActivityLogDTO::success(
        ActivityType::POST_VIEWED,
        userId: 123,
        targetType: 'post',
        targetId: '1'
    ),
    CreateActivityLogDTO::success(
        ActivityType::POST_VIEWED,
        userId: 123,
        targetType: 'post',
        targetId: '2'
    ),
    CreateActivityLogDTO::success(
        ActivityType::ATTACHMENT_DOWNLOADED,
        userId: 123,
        targetType: 'attachment',
        targetId: '10'
    ),
];

// 執行批次記錄
$results = $activityLogger->logBatch($activities);

// 檢查結果
foreach ($results as $index => $result) {
    if ($result['success']) {
        echo "Activity {$index} logged with ID: {$result['id']}\n";
    } else {
        echo "Failed to log activity {$index}: {$result['error']}\n";
    }
}
```

## 記錄控制功能

### 設定記錄等級

```php
// 設定記錄等級 (1-5，數字越高越嚴格)
// Level 1: 記錄所有活動
// Level 2: 記錄重要活動
// Level 3: 記錄關鍵活動
// Level 4: 僅記錄安全相關活動
// Level 5: 僅記錄緊急事件

$activityLogger->setLogLevel(3);
```

### 停用特定活動類型

```php
// 暫時停用文章檢視記錄（減少日誌量）
$activityLogger->disableLogging(ActivityType::POST_VIEWED);

// 重新啟用
$activityLogger->enableLogging(ActivityType::POST_VIEWED);

// 檢查是否啟用
if ($activityLogger->isLoggingEnabled(ActivityType::POST_VIEWED)) {
    // 記錄活動
}
```

## 查詢與分析

### 查詢使用者活動

```php
use App\Domains\Security\Repositories\ActivityLogRepository;

$repository = $container->get(ActivityLogRepository::class);

// 查詢特定使用者的活動
$userActivities = $repository->findByUserId(
    userId: 123,
    limit: 50,
    offset: 0
);

// 使用過濾條件查詢
$filteredActivities = $repository->findWithFilters([
    'user_id' => 123,
    'action_category' => 'authentication',
    'status' => 'success',
    'date_from' => '2024-01-01',
    'date_to' => '2024-12-31'
], page: 1, limit: 20);
```

### 統計分析

```php
// 取得使用者活動統計
$stats = $repository->getUserActivityStats(userId: 123, days: 7);

echo "過去 7 天活動數: {$stats['total_count']}\n";
echo "成功率: {$stats['success_rate']}%\n";

// 取得活動分佈
$distribution = $repository->getActivityDistribution(
    userId: 123,
    period: '30d'
);

foreach ($distribution as $category => $count) {
    echo "{$category}: {$count}\n";
}
```

## 異常檢測

### 基本異常檢測

```php
use App\Domains\Security\Services\SuspiciousActivityDetector;

$detector = $container->get(SuspiciousActivityDetector::class);

// 分析使用者活動
$analysis = $detector->analyzeUserActivity(
    userId: 123,
    timeWindowMinutes: 60
);

if ($analysis->isSuspicious()) {
    echo "檢測到可疑活動！風險分數: {$analysis->getRiskScore()}\n";
    
    foreach ($analysis->getDetectedPatterns() as $pattern) {
        echo "- {$pattern->getDescription()}\n";
        echo "  風險分數: {$pattern->getRiskScore()}\n";
    }
    
    // 取得建議措施
    foreach ($analysis->getRecommendations() as $recommendation) {
        echo "建議: {$recommendation}\n";
    }
}
```

### 自訂檢測規則

```php
// 分析 IP 行為模式
$ipAnalysis = $detector->analyzeIpActivity(
    ipAddress: '192.168.1.100',
    timeWindowMinutes: 30
);

// 分析全域活動模式
$globalAnalysis = $detector->analyzeGlobalPatterns(
    timeWindowMinutes: 60,
    minimumRiskScore: 70
);
```

## 系統維護

### 清理舊記錄

```php
// 清理 30 天前的記錄（預設保留政策）
$deletedCount = $activityLogger->cleanup();
echo "清理了 {$deletedCount} 筆舊記錄\n";

// 自訂清理政策
$deletedCount = $repository->deleteOldRecords(
    new DateTimeImmutable('-90 days')
);
```

### 健康檢查

```php
// 檢查服務健康狀態
if ($activityLogger->isHealthy()) {
    echo "活動記錄服務運作正常\n";
} else {
    echo "活動記錄服務異常，請檢查日誌\n";
}

// 取得系統統計
$systemStats = $repository->getSystemStats();
echo "總記錄數: {$systemStats['total_records']}\n";
echo "今日記錄數: {$systemStats['today_records']}\n";
echo "平均記錄頻率: {$systemStats['avg_records_per_hour']}/小時\n";
```

## RESTful API 使用

### 使用 cURL 記錄活動

```bash
# 記錄單一活動
curl -X POST http://localhost/api/v1/activity-logs \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d '{
    "action_type": "auth.login.success",
    "user_id": 123,
    "description": "使用者登入成功",
    "metadata": {
      "ip_address": "192.168.1.100"
    }
  }'

# 查詢活動記錄
curl "http://localhost/api/v1/activity-logs?user_id=123&limit=10"
```

### JavaScript 前端整合

```javascript
// 記錄前端活動
async function logActivity(actionType, metadata = {}) {
    try {
        const response = await fetch('/api/v1/activity-logs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                action_type: actionType,
                user_id: getCurrentUserId(),
                metadata: metadata
            })
        });
        
        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('Failed to log activity:', error);
        return false;
    }
}

// 使用範例
logActivity('post.viewed', {
    post_id: 123,
    view_duration: 30,
    referrer: document.referrer
});
```

## 效能考量

### 最佳化建議

1. **批次記錄**: 對於高頻操作，建議使用批次記錄
2. **非同步處理**: 考慮使用佇列進行非同步記錄
3. **記錄等級控制**: 在高流量環境中適當調整記錄等級
4. **定期清理**: 建立自動清理機制避免資料表過大

### 效能監控

```php
// 監控記錄效能
$start = microtime(true);
$success = $activityLogger->log($dto);
$duration = microtime(true) - $start;

if ($duration > 0.1) { // 100ms
    $logger->warning('Slow activity logging detected', [
        'duration' => $duration,
        'action_type' => $dto->getActionType()->value
    ]);
}
```

## 故障排除

### 常見問題

1. **記錄失敗**
   ```php
   // 檢查日誌取得詳細錯誤資訊
   tail -f logs/app.log | grep "Failed to log activity"
   ```

2. **效能問題**
   ```php
   // 檢查資料庫索引使用狀況
   EXPLAIN QUERY PLAN SELECT * FROM user_activity_logs WHERE user_id = 123;
   ```

3. **異常檢測不準確**
   ```php
   // 調整檢測參數
   $analysis = $detector->analyzeUserActivity(
       userId: 123,
       timeWindowMinutes: 30, // 縮短時間窗口
       failureThreshold: 0.2  // 調整失敗率閾值
   );
   ```

## 安全考量

### 敏感資料保護

```php
// 避免在 metadata 中記錄敏感資訊
$dto = CreateActivityLogDTO::success(
    ActivityType::LOGIN_SUCCESS,
    userId: 123,
    metadata: [
        // ✅ 安全
        'ip_address' => $request->getClientIp(),
        'login_method' => 'password',
        
        // ❌ 避免記錄敏感資料
        // 'password' => $plainPassword,
        // 'credit_card' => $cardNumber
    ]
);
```

### 存取權限控制

```php
// 確保只有授權使用者可以查詢活動記錄
if (!$user->hasPermission('activity_logs.read')) {
    throw new AccessDeniedException('權限不足');
}

// 限制使用者只能查看自己的活動記錄
$activities = $repository->findByUserId($currentUser->getId());
```

---

**需要協助？**
- 📧 聯絡開發團隊: dev@alleynote.com
- 📚 查看 [API 文件](API_DOCUMENTATION.md)
- 🏗️ 參考 [架構文件](USER_ACTIVITY_LOGGING_ARCHITECTURE.md)

*最後更新: 2024年12月27日*