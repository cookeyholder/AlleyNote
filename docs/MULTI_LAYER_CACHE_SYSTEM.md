# AlleyNote 多層快取系統

## 系統概述

AlleyNote 的多層快取系統提供了靈活、高效能的快取解決方案，支援記憶體、檔案和 Redis 快取驅動的統一管理。

## 核心架構

### 快取驅動 (Cache Drivers)

#### 記憶體快取驅動 (MemoryCacheDriver)
- **特性**：最快的存取速度，適合小型資料
- **限制**：程序結束後資料消失
- **配置**：
```php
'memory' => [
    'enabled' => true,
    'priority' => 90,
    'max_size' => 1000,
    'ttl' => 3600,
]
```

#### 檔案快取驅動 (FileCacheDriver)
- **特性**：持久化儲存，適合中大型資料
- **配置**：
```php
'file' => [
    'enabled' => true,
    'priority' => 50,
    'path' => '/storage/cache',
    'ttl' => 3600,
]
```

#### Redis 快取驅動 (RedisCacheDriver)
- **特性**：分散式快取，支援叢集和高可用
- **配置**：
```php
'redis' => [
    'enabled' => true,
    'priority' => 70,
    'host' => '127.0.0.1',
    'port' => 6379,
    'database' => 0,
    'prefix' => 'alleynote:cache:',
]
```

### 快取管理器 (CacheManager)

統一管理多個快取驅動，提供：
- 自動驅動選擇
- 故障轉移
- 效能監控
- 批次操作

### 快取策略 (CacheStrategy)

智能決策引擎，包含：
- 快取可行性判斷
- 驅動選擇邏輯
- TTL 調整策略
- 失敗處理機制

## 使用方式

### 基本操作

```php
use App\Shared\Cache\Contracts\CacheManagerInterface;

// 取得快取管理器
$cache = $container->get(CacheManagerInterface::class);

// 儲存快取
$cache->set('user:123', $userData, 3600);

// 讀取快取
$userData = $cache->get('user:123');

// 檢查快取是否存在
if ($cache->has('user:123')) {
    // 處理快取命中
}

// 刪除快取
$cache->delete('user:123');

// 清空所有快取
$cache->clear();
```

### 記憶化快取

```php
// 如果快取不存在則執行回調函式
$userData = $cache->remember('user:123', function() {
    return $this->userRepository->find(123);
}, 3600);
```

### 批次操作

```php
// 批次儲存
$cache->putMany([
    'key1' => 'value1',
    'key2' => 'value2',
], 3600);

// 批次讀取
$values = $cache->many(['key1', 'key2']);
```

### 前綴快取

```php
// 建立有前綴的快取實例
$userCache = $cache->prefix('users:');
$userCache->set('123', $userData); // 實際鍵為 'users:123'
```

### 指定驅動

```php
// 使用特定驅動
$redisCache = $cache->driver('redis');
$redisCache->set('session:abc', $sessionData);
```

## 環境設定

### .env 檔案配置

```env
# 預設驅動
CACHE_DEFAULT_DRIVER=memory

# 快取路徑
CACHE_PATH=/var/www/html/storage/cache

# Redis 設定
REDIS_ENABLED=false
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DATABASE=0
```

### 完整設定範例

```php
use App\Shared\Cache\Providers\CacheServiceProvider;

// 建立設定
$config = CacheServiceProvider::createConfigBuilder()
    ->defaultDriver('memory')
    ->memoryDriver(['max_size' => 1000])
    ->fileDriver('/custom/cache/path')
    ->redisDriver([
        'host' => 'redis-server',
        'port' => 6379,
        'prefix' => 'myapp:',
    ])
    ->strategy([
        'min_ttl' => 60,
        'max_ttl' => 86400,
        'exclude_patterns' => ['temp:*', 'debug:*'],
    ])
    ->manager([
        'enable_sync' => true,
        'sync_ttl' => 3600,
    ])
    ->build();

// 註冊到容器
$provider = new CacheServiceProvider($container, $config);
$provider->register();
```

## 監控與維護

### 效能統計

```php
// 取得快取統計
$stats = $cache->getStats();

echo "命中率: " . $stats['hit_rate'] . "%\n";
echo "總請求: " . $stats['total_gets'] . "\n";
echo "快取命中: " . $stats['total_hits'] . "\n";
echo "快取未命中: " . $stats['total_misses'] . "\n";
```

### 健康檢查

```php
// 檢查所有驅動健康狀態
$health = $cache->getHealthStatus();

foreach ($health as $driverName => $status) {
    echo "{$driverName}: " . ($status['available'] ? 'OK' : 'ERROR') . "\n";
    if ($status['error']) {
        echo "  錯誤: {$status['error']}\n";
    }
}
```

### 快取預熱

```php
// 預熱重要資料
$results = $cache->warmup([
    'config:app' => fn() => loadAppConfig(),
    'menu:main' => fn() => loadMainMenu(),
    'settings:user' => fn() => loadUserSettings(),
]);

foreach ($results as $key => $result) {
    if ($result['success']) {
        echo "{$key} 預熱成功 ({$result['duration']}ms)\n";
    } else {
        echo "{$key} 預熱失敗: {$result['error']}\n";
    }
}
```

### 清理過期項目

```php
// 清理所有驅動的過期項目
$results = $cache->cleanup();

foreach ($results as $driver => $result) {
    if ($result['success']) {
        echo "{$driver}: 清理了 {$result['cleaned_items']} 個項目\n";
    }
}
```

## 最佳實踐

### 1. 快取鍵命名
```php
// 好的快取鍵命名
'user:profile:123'
'article:content:456'
'config:app:settings'

// 避免的命名
'u123'  // 不清楚
'data'  // 太通用
```

### 2. TTL 設定指引
- 使用者會話：30 分鐘 - 1 小時
- 設定資料：2-24 小時
- 內容資料：1-6 小時
- 統計資料：15 分鐘 - 1 小時

### 3. 快取更新策略
```php
// 寫入時更新 (Write-Through)
$cache->set($key, $newData);
$database->save($newData);

// 延遲寫入 (Write-Behind)
$cache->set($key, $newData);
Queue::push(new UpdateDatabaseJob($newData));

// 寫入時失效 (Write-Invalidate)
$database->save($newData);
$cache->delete($key);
```

### 4. 錯誤處理
```php
try {
    $data = $cache->remember('expensive_data', function() {
        return $this->calculateExpensiveData();
    });
} catch (\Exception $e) {
    // 快取失敗時的降級處理
    $this->logger->warning('快取操作失敗', ['error' => $e->getMessage()]);
    return $this->calculateExpensiveData();
}
```

## 故障排除

### 常見問題

1. **Redis 連線失敗**
   - 檢查 Redis 服務是否運行
   - 驗證連線參數
   - 檢查網路連線

2. **檔案快取權限錯誤**
   - 確保快取目錄可寫入
   - 檢查目錄權限 (755 或 777)

3. **記憶體快取容量不足**
   - 調整 `max_size` 參數
   - 優化快取策略

4. **快取未命中率高**
   - 檢查 TTL 設定
   - 分析快取鍵模式
   - 優化快取策略

### 效能最佳化

1. **驅動優先級調整**
```php
// 調整優先級以最佳化效能
$manager->addDriver('memory', $memoryDriver, 100);  // 最高
$manager->addDriver('redis', $redisDriver, 80);     // 中等
$manager->addDriver('file', $fileDriver, 60);       // 最低
```

2. **快取同步策略**
```php
// 啟用同步以改善命中率
'manager' => [
    'enable_sync' => true,
    'sync_ttl' => 3600,
]
```

3. **策略參數調整**
```php
// 針對應用特性調整
'strategy' => [
    'min_ttl' => 300,           // 5 分鐘最小 TTL
    'max_ttl' => 43200,         // 12 小時最大 TTL
    'max_value_size' => 2048,   // 2KB 最大值大小
]
```

## API 參考

### CacheManagerInterface

| 方法 | 說明 | 參數 | 回傳值 |
|------|------|------|--------|
| `get(string $key, mixed $default = null)` | 取得快取 | 鍵名, 預設值 | 快取值或預設值 |
| `set(string $key, mixed $value, int $ttl = 3600)` | 設定快取 | 鍵名, 值, TTL | bool |
| `has(string $key)` | 檢查是否存在 | 鍵名 | bool |
| `delete(string $key)` | 刪除快取 | 鍵名 | bool |
| `clear()` | 清空所有快取 | 無 | bool |
| `remember(string $key, callable $callback, int $ttl)` | 記憶化取得 | 鍵名, 回調, TTL | mixed |
| `prefix(string $prefix)` | 建立前綴實例 | 前綴 | CacheManagerInterface |
| `driver(string $driver = null)` | 取得指定驅動 | 驅動名稱 | CacheDriverInterface |
| `getStats()` | 取得統計資訊 | 無 | array |
| `getHealthStatus()` | 取得健康狀態 | 無 | array |

這個多層快取系統為 AlleyNote 提供了高效能、可靠的快取解決方案，支援從簡單的記憶體快取到複雜的分散式 Redis 快取等多種場景。
