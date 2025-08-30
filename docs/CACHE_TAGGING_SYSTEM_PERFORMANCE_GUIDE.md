# 快取標籤系統效能與最佳實務指南

## 概述

本指南提供 AlleyNote 快取標籤系統的效能優化策略、最佳實務建議和監控指南，幫助開發者構建高效、可維護的快取架構。

## 效能優化策略

### 1. 標籤設計原則

#### 標籤命名最佳實務

```php
// ✅ 良好的標籤命名
$userTag = CacheTag::user(123);           // "user:123"
$moduleTag = CacheTag::module('posts');   // "module:posts"
$groupTag = CacheTag::group('admins');    // "group:admins"

// ❌ 避免的標籤命名
$badTag1 = new CacheTag('very-long-tag-name-that-exceeds-reasonable-limits'); // 過長
$badTag2 = new CacheTag('user_post_comment_like_123');                        // 過於具體
$badTag3 = new CacheTag('all_data');                                          // 過於寬泛
```

#### 標籤階層設計

```php
// ✅ 建議的標籤階層
class TagStrategy
{
    public function getUserCacheTags(int $userId): array
    {
        return [
            CacheTag::user($userId)->getName(),      // "user:123" - 基礎用戶標籤
            CacheTag::group('users')->getName(),     // "group:users" - 用戶群組
            CacheTag::module('profile')->getName()   // "module:profile" - 功能模組
        ];
    }

    public function getPostCacheTags(int $postId, int $authorId): array
    {
        return [
            "post:{$postId}",                        // 特定文章
            CacheTag::user($authorId)->getName(),    // 作者
            CacheTag::module('posts')->getName(),    // 文章模組
            CacheTag::temporal('daily')->getName()   // 時間分類
        ];
    }
}
```

### 2. 分組策略優化

#### 分組大小控制

```php
class OptimizedGroupStrategy
{
    private const MAX_ITEMS_PER_GROUP = 100;
    private const MAX_TAGS_PER_GROUP = 10;

    public function createUserGroup(int $userId): TaggedCacheInterface
    {
        // 將用戶資料分成多個小分組，而不是一個大分組
        $groupManager = app(CacheGroupManager::class);
        
        // 基本資料分組
        $basicGroup = $groupManager->group("user_basic_{$userId}", [
            CacheTag::user($userId)->getName(),
            'profile',
            'basic'
        ]);
        
        // 偏好設定分組
        $preferencesGroup = $groupManager->group("user_preferences_{$userId}", [
            CacheTag::user($userId)->getName(),
            'preferences',
            'settings'
        ]);
        
        return $basicGroup;
    }

    public function createContentGroups(string $contentType): array
    {
        $groupManager = app(CacheGroupManager::class);
        
        return [
            // 熱門內容分組（快速存取）
            'hot' => $groupManager->group("{$contentType}_hot", [
                CacheTag::module($contentType)->getName(),
                'hot',
                'priority:high'
            ]),
            
            // 一般內容分組
            'normal' => $groupManager->group("{$contentType}_normal", [
                CacheTag::module($contentType)->getName(),
                'normal',
                'priority:medium'
            ])
        ];
    }
}
```

#### 智能分組依賴

```php
class SmartGroupDependencies
{
    public function setupUserGroupHierarchy(int $userId): void
    {
        $groupManager = app(CacheGroupManager::class);
        
        // 建立分層結構
        $userGroup = "user_{$userId}";
        $userPostsGroup = "user_posts_{$userId}";
        $userCommentsGroup = "user_comments_{$userId}";
        
        // 設定依賴關係：用戶基本資料 -> 用戶內容
        $groupManager->setDependencies($userGroup, [
            $userPostsGroup,
            $userCommentsGroup
        ]);
        
        // 設定失效規則
        $groupManager->setInvalidationRules($userGroup, [
            'max_age' => 3600,
            'invalidate_on' => ['user_profile_update', 'user_status_change']
        ]);
        
        // 內容相關分組的獨立失效規則
        $groupManager->setInvalidationRules($userPostsGroup, [
            'max_age' => 1800,  // 更短的快取時間
            'invalidate_on' => ['post_create', 'post_update', 'post_delete']
        ]);
    }
}
```

### 3. 記憶體使用優化

#### 標籤索引優化

```php
class TagIndexOptimization
{
    private CacheGroupManager $groupManager;
    private TaggedCacheInterface $cache;

    public function optimizeTagUsage(): void
    {
        // 定期清理未使用的標籤
        $this->cleanupUnusedTags();
        
        // 合併相似標籤
        $this->mergeSimilarTags();
        
        // 壓縮標籤索引
        $this->compressTagIndex();
    }

    private function cleanupUnusedTags(): int
    {
        $cleaned = $this->cache->cleanupUnusedTags();
        
        Log::info('標籤清理完成', [
            'cleaned_count' => $cleaned,
            'timestamp' => now()
        ]);
        
        return $cleaned;
    }

    private function mergeSimilarTags(): void
    {
        $stats = $this->cache->getTagStatistics();
        
        foreach ($stats as $tag => $count) {
            // 如果標籤使用次數很少，考慮合併到更通用的標籤
            if ($count < 5 && $this->isMergeableTag($tag)) {
                $this->mergeToGeneralTag($tag);
            }
        }
    }

    private function isMergeableTag(string $tag): bool
    {
        // 檢查是否為可合併的細分標籤
        return preg_match('/^user:\d+_(temp|cache|session)$/', $tag);
    }

    private function mergeToGeneralTag(string $specificTag): void
    {
        // 將細分標籤的快取項目移動到更通用的標籤下
        $keys = $this->cache->getKeysByTag($specificTag);
        
        foreach ($keys as $key) {
            $this->cache->removeTagsFromKey($key, $specificTag);
            $this->cache->addTagsToKey($key, 'user:session');
        }
    }
}
```

### 4. 查詢效能優化

#### 批次操作

```php
class BatchOperations
{
    public function batchFlushUserCaches(array $userIds): array
    {
        $groupManager = app(CacheGroupManager::class);
        $results = [];
        
        // 收集所有要清空的分組
        $groupsToFlush = [];
        foreach ($userIds as $userId) {
            $groupsToFlush[] = "user_{$userId}";
            $groupsToFlush[] = "user_posts_{$userId}";
            $groupsToFlush[] = "user_comments_{$userId}";
        }
        
        // 批次清空
        $totalCleared = $groupManager->flushGroups($groupsToFlush, false);
        
        return [
            'cleared_groups' => count($groupsToFlush),
            'cleared_items' => $totalCleared,
            'user_count' => count($userIds)
        ];
    }

    public function batchTagOperations(array $operations): array
    {
        $cache = app(TaggedCacheInterface::class);
        $results = [];
        
        foreach ($operations as $operation) {
            switch ($operation['type']) {
                case 'flush':
                    $results[] = $cache->flushByTags($operation['tags']);
                    break;
                case 'add_tags':
                    $results[] = $cache->addTagsToKey(
                        $operation['key'], 
                        $operation['tags']
                    );
                    break;
                case 'remove_tags':
                    $results[] = $cache->removeTagsFromKey(
                        $operation['key'], 
                        $operation['tags']
                    );
                    break;
            }
        }
        
        return $results;
    }
}
```

## 效能監控

### 1. 關鍵指標監控

```php
class CachePerformanceMonitor
{
    private TaggedCacheInterface $cache;
    private CacheGroupManager $groupManager;
    private MetricsCollectorInterface $metrics;

    public function collectMetrics(): array
    {
        $metrics = [
            'cache_stats' => $this->getCacheStats(),
            'tag_stats' => $this->getTagStats(),
            'group_stats' => $this->getGroupStats(),
            'performance_stats' => $this->getPerformanceStats()
        ];
        
        $this->reportMetrics($metrics);
        return $metrics;
    }

    private function getCacheStats(): array
    {
        return [
            'total_items' => $this->cache->getTotalItems(),
            'memory_usage' => $this->cache->getMemoryUsage(),
            'hit_rate' => $this->cache->getHitRate(),
            'miss_rate' => $this->cache->getMissRate()
        ];
    }

    private function getTagStats(): array
    {
        $tagStats = $this->cache->getTagStatistics();
        
        return [
            'total_tags' => count($tagStats),
            'most_used_tags' => array_slice(
                arsort($tagStats), 0, 10, true
            ),
            'unused_tags' => array_filter($tagStats, fn($count) => $count === 0),
            'average_items_per_tag' => array_sum($tagStats) / max(count($tagStats), 1)
        ];
    }

    private function getGroupStats(): array
    {
        $groupStats = $this->groupManager->getGroupStatistics();
        
        return [
            'total_groups' => $groupStats['total_groups'],
            'groups_with_dependencies' => count($groupStats['dependencies']),
            'groups_with_rules' => $groupStats['invalidation_rules_count'],
            'largest_groups' => $this->getLargestGroups($groupStats['groups'])
        ];
    }

    private function getPerformanceStats(): array
    {
        return [
            'average_response_time' => $this->metrics->getAverageResponseTime('cache'),
            'cache_operations_per_second' => $this->metrics->getOperationsPerSecond('cache'),
            'error_rate' => $this->metrics->getErrorRate('cache')
        ];
    }
}
```

### 2. 預警系統

```php
class CacheAlertSystem
{
    private const THRESHOLDS = [
        'hit_rate_min' => 0.8,          // 命中率低於 80%
        'memory_usage_max' => 0.9,      // 記憶體使用超過 90%
        'response_time_max' => 100,     // 響應時間超過 100ms
        'error_rate_max' => 0.01        // 錯誤率超過 1%
    ];

    public function checkAlerts(array $metrics): array
    {
        $alerts = [];
        
        // 檢查命中率
        if ($metrics['cache_stats']['hit_rate'] < self::THRESHOLDS['hit_rate_min']) {
            $alerts[] = [
                'type' => 'low_hit_rate',
                'severity' => 'warning',
                'message' => "快取命中率過低: {$metrics['cache_stats']['hit_rate']}",
                'suggestion' => '檢查快取策略和 TTL 設定'
            ];
        }
        
        // 檢查記憶體使用
        if ($metrics['cache_stats']['memory_usage'] > self::THRESHOLDS['memory_usage_max']) {
            $alerts[] = [
                'type' => 'high_memory_usage',
                'severity' => 'critical',
                'message' => "記憶體使用過高: {$metrics['cache_stats']['memory_usage']}",
                'suggestion' => '執行快取清理或增加記憶體限制'
            ];
        }
        
        // 檢查未使用標籤
        $unusedTagsCount = count($metrics['tag_stats']['unused_tags']);
        if ($unusedTagsCount > 50) {
            $alerts[] = [
                'type' => 'too_many_unused_tags',
                'severity' => 'info',
                'message' => "未使用標籤過多: {$unusedTagsCount}",
                'suggestion' => '執行標籤清理操作'
            ];
        }
        
        return $alerts;
    }
}
```

## 最佳實務建議

### 1. 快取策略設計

#### 分層快取策略

```php
class LayeredCacheStrategy
{
    // L1: 記憶體快取（最快，容量小）
    private MemoryCacheDriver $l1Cache;
    
    // L2: Redis 快取（快，容量中）
    private RedisCacheDriver $l2Cache;
    
    // L3: 資料庫快取（慢，容量大）
    private DatabaseCacheDriver $l3Cache;

    public function get(string $key, callable $callback = null, int $ttl = 3600): mixed
    {
        // L1 快取檢查
        $value = $this->l1Cache->get($key);
        if ($value !== null) {
            $this->recordHit('l1');
            return $value;
        }
        
        // L2 快取檢查
        $value = $this->l2Cache->get($key);
        if ($value !== null) {
            $this->recordHit('l2');
            // 回寫到 L1
            $this->l1Cache->put($key, $value, min($ttl, 300));
            return $value;
        }
        
        // L3 快取檢查
        $value = $this->l3Cache->get($key);
        if ($value !== null) {
            $this->recordHit('l3');
            // 回寫到上層快取
            $this->l2Cache->put($key, $value, $ttl);
            $this->l1Cache->put($key, $value, min($ttl, 300));
            return $value;
        }
        
        // 快取未命中，執行回調
        if ($callback) {
            $value = $callback();
            if ($value !== null) {
                $this->putToAllLayers($key, $value, $ttl);
            }
            $this->recordMiss();
            return $value;
        }
        
        return null;
    }
}
```

#### TTL 最佳化策略

```php
class TTLOptimizationStrategy
{
    private const TTL_PROFILES = [
        'user_session' => 1800,      // 30 分鐘
        'user_profile' => 3600,      // 1 小時
        'content_list' => 900,       // 15 分鐘
        'content_detail' => 1800,    // 30 分鐘
        'system_config' => 86400,    // 24 小時
        'static_data' => 604800      // 7 天
    ];

    public function getTTL(string $dataType, array $context = []): int
    {
        $baseTTL = self::TTL_PROFILES[$dataType] ?? 3600;
        
        // 根據上下文調整 TTL
        if (isset($context['priority'])) {
            switch ($context['priority']) {
                case 'high':
                    return $baseTTL * 2;  // 高優先級資料快取更久
                case 'low':
                    return $baseTTL / 2;  // 低優先級資料快取較短
            }
        }
        
        // 根據使用頻率調整
        if (isset($context['access_frequency'])) {
            $frequency = $context['access_frequency'];
            if ($frequency > 100) {
                return $baseTTL * 1.5;  // 高頻存取資料延長快取時間
            } elseif ($frequency < 10) {
                return $baseTTL * 0.5;  // 低頻存取資料縮短快取時間
            }
        }
        
        return $baseTTL;
    }
}
```

### 2. 錯誤處理和降級

```php
class CacheErrorHandling
{
    private LoggerInterface $logger;
    private MetricsCollectorInterface $metrics;

    public function safeGet(string $key, callable $fallback, int $ttl = 3600): mixed
    {
        try {
            $cache = app(TaggedCacheInterface::class);
            
            return $cache->remember($key, $fallback, $ttl);
            
        } catch (CacheException $e) {
            // 快取操作失敗，記錄錯誤並降級
            $this->logger->warning('快取操作失敗，使用降級策略', [
                'key' => $key,
                'error' => $e->getMessage(),
                'fallback' => 'direct_database_query'
            ]);
            
            $this->metrics->increment('cache.errors.degraded');
            
            // 直接執行回調函數
            return $fallback();
        }
    }

    public function safeFlush(array $tags): int
    {
        try {
            $cache = app(TaggedCacheInterface::class);
            return $cache->flushByTags($tags);
            
        } catch (CacheException $e) {
            $this->logger->error('快取清空失敗', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            
            $this->metrics->increment('cache.errors.flush_failed');
            
            // 返回 0 表示沒有清空任何項目
            return 0;
        }
    }
}
```

### 3. 效能測試和基準

```php
class CachePerformanceBenchmark
{
    public function runBenchmarks(): array
    {
        $results = [];
        
        // 測試基本操作效能
        $results['basic_operations'] = $this->benchmarkBasicOperations();
        
        // 測試標籤操作效能
        $results['tag_operations'] = $this->benchmarkTagOperations();
        
        // 測試分組操作效能
        $results['group_operations'] = $this->benchmarkGroupOperations();
        
        // 測試大數據量效能
        $results['bulk_operations'] = $this->benchmarkBulkOperations();
        
        return $results;
    }

    private function benchmarkBasicOperations(): array
    {
        $cache = app(TaggedCacheInterface::class);
        $iterations = 10000;
        
        // PUT 操作基準測試
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $cache->put("bench_key_{$i}", "value_{$i}", 3600);
        }
        $putTime = microtime(true) - $start;
        
        // GET 操作基準測試
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $cache->get("bench_key_{$i}");
        }
        $getTime = microtime(true) - $start;
        
        return [
            'put_ops_per_second' => $iterations / $putTime,
            'get_ops_per_second' => $iterations / $getTime,
            'put_avg_time_ms' => ($putTime / $iterations) * 1000,
            'get_avg_time_ms' => ($getTime / $iterations) * 1000
        ];
    }

    private function benchmarkTagOperations(): array
    {
        $cache = app(TaggedCacheInterface::class);
        $iterations = 1000;
        
        // 標籤設定基準測試
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $taggedCache = $cache->tags(["tag_{$i}", 'benchmark']);
            $taggedCache->put("tagged_key_{$i}", "value_{$i}", 3600);
        }
        $taggedPutTime = microtime(true) - $start;
        
        // 標籤清空基準測試
        $start = microtime(true);
        $cleared = $cache->flushByTags(['benchmark']);
        $flushTime = microtime(true) - $start;
        
        return [
            'tagged_put_ops_per_second' => $iterations / $taggedPutTime,
            'flush_by_tags_time_ms' => $flushTime * 1000,
            'items_cleared' => $cleared
        ];
    }
}
```

## 監控和維護

### 1. 定期維護任務

```php
class CacheMaintenanceScheduler
{
    public function scheduleMaintenanceTasks(): void
    {
        // 每小時執行的輕量級清理
        $this->schedule(function() {
            $this->lightweightCleanup();
        })->hourly();
        
        // 每日執行的深度清理
        $this->schedule(function() {
            $this->deepCleanup();
        })->daily();
        
        // 每週執行的統計分析
        $this->schedule(function() {
            $this->weeklyAnalysis();
        })->weekly();
    }

    private function lightweightCleanup(): void
    {
        $cache = app(TaggedCacheInterface::class);
        $groupManager = app(CacheGroupManager::class);
        
        // 清理未使用的標籤
        $cleanedTags = $cache->cleanupUnusedTags();
        
        // 清理過期分組
        $cleanedGroups = $groupManager->cleanupExpiredGroups();
        
        Log::info('輕量級快取清理完成', [
            'cleaned_tags' => $cleanedTags,
            'cleaned_groups' => $cleanedGroups
        ]);
    }

    private function deepCleanup(): void
    {
        $cache = app(TaggedCacheInterface::class);
        $groupManager = app(CacheGroupManager::class);
        
        // 分析和優化標籤使用
        $this->analyzeTagUsage();
        
        // 重組快取分組
        $this->reorganizeGroups();
        
        // 更新統計資料
        $this->updateStatistics();
    }
}
```

### 2. 效能報告生成

```php
class CachePerformanceReporter
{
    public function generateWeeklyReport(): array
    {
        $report = [
            'summary' => $this->generateSummary(),
            'performance_trends' => $this->generatePerformanceTrends(),
            'optimization_suggestions' => $this->generateOptimizationSuggestions(),
            'usage_patterns' => $this->generateUsagePatterns()
        ];
        
        // 發送報告給系統管理員
        $this->sendReport($report);
        
        return $report;
    }

    private function generateSummary(): array
    {
        $monitor = new CachePerformanceMonitor();
        $metrics = $monitor->collectMetrics();
        
        return [
            'total_cache_operations' => $metrics['performance_stats']['operations_count'],
            'average_hit_rate' => $metrics['cache_stats']['hit_rate'],
            'memory_efficiency' => $this->calculateMemoryEfficiency($metrics),
            'cost_savings' => $this->calculateCostSavings($metrics)
        ];
    }
}
```

## 總結

快取標籤系統的效能優化需要持續的監控、分析和調整。遵循本指南的建議，可以：

1. **提升效能**：通過合理的標籤設計和分組策略，減少快取操作的開銷
2. **節省資源**：通過智能清理和維護，最佳化記憶體使用
3. **提高可靠性**：通過錯誤處理和降級策略，確保系統穩定運行
4. **便於維護**：通過監控和自動化維護，降低運維成本

記住，效能優化是一個持續的過程，需要根據實際使用情況和業務需求不斷調整策略。