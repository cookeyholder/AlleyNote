<?php

declare(strict_types=1);

namespace App\Shared\Cache\Services;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\CacheStrategyInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Monitoring\Contracts\CacheMonitorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 快取管理器。
 *
 * 負責管理多個快取驅動，提供統一的快取存取介面
 */
class CacheManager implements CacheManagerInterface
{
    /** @var array<string, CacheDriverInterface> 註冊的驅動 */
    private array $drivers = [];

    /** @var array<string> 驅動優先級 */
    private array $driverPriority = [];

    /** @var string 預設驅動名稱 */
    private string $defaultDriver = 'memory';

    /** @var CacheStrategyInterface 快取策略 */
    private CacheStrategyInterface $strategy;

    /** @var LoggerInterface 記錄器 */
    private LoggerInterface $logger;

    /** @var CacheMonitorInterface|null 快取監控器 */
    private ?CacheMonitorInterface $monitor;

    /** @var array<string, mixed> 設定 */
    private array $config;

    /** @var array<string, int> 統計資料 */
    private array $stats = [
        'total_gets' => 0,
        'total_hits' => 0,
        'total_misses' => 0,
        'total_puts' => 0,
        'total_deletes' => 0,
        'total_flushes' => 0,
        'driver_failures' => 0,
        'strategy_cache_denials' => 0,
    ];

    public function __construct(
        CacheStrategyInterface $strategy,
        LoggerInterface $logger = null,
        array $config = [],
        CacheMonitorInterface $monitor = null
    ) {
        $this->strategy = $strategy;
        $this->logger = $logger ?? new NullLogger();
        $this->monitor = $monitor;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function addDriver(string $name, CacheDriverInterface $driver, int $priority = 10): void
    {
        $this->drivers[$name] = $driver;
        $this->driverPriority[$name] = $priority;

        // 根據優先級排序
        arsort($this->driverPriority);

        $this->logger->debug('快取驅動已新增', [
            'driver_name' => $name,
            'driver_class' => get_class($driver),
            'priority' => $priority,
        ]);
    }

    public function removeDriver(string $name): bool
    {
        if (isset($this->drivers[$name])) {
            unset($this->drivers[$name], $this->driverPriority[$name]);

            $this->logger->debug('快取驅動已移除', [
                'driver_name' => $name,
            ]);

            return true;
        }

        return false;
    }

    public function getDriver(string $name): ?CacheDriverInterface
    {
        return $this->drivers[$name] ?? null;
    }

    public function getDrivers(): array
    {
        return $this->drivers;
    }

    public function getAvailableDrivers(): array
    {
        return array_filter($this->drivers, fn($driver) => $driver->isAvailable());
    }

    public function setDefaultDriver(string $name): void
    {
        if (!isset($this->drivers[$name])) {
            throw new \InvalidArgumentException("驅動 '{$name}' 不存在");
        }

        $this->defaultDriver = $name;

        $this->logger->debug('預設快取驅動已設定', [
            'driver_name' => $name,
        ]);
    }

    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }

    public function isDriverAvailable(string $driverName): bool
    {
        return isset($this->drivers[$driverName]) && $this->drivers[$driverName]->isAvailable();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->stats['total_gets']++;
        $startTime = microtime(true);

        $availableDrivers = $this->getOrderedAvailableDrivers();
        
        if (empty($availableDrivers)) {
            $this->logger->warning('沒有可用的快取驅動', ['key' => $key]);
            $this->stats['total_misses']++;
            return $default;
        }

        foreach ($availableDrivers as $name => $driver) {
            $driverStartTime = microtime(true);
            
            try {
                if ($driver->has($key)) {
                    $value = $driver->get($key, $default);
                    $duration = (microtime(true) - $driverStartTime) * 1000; // 轉換為毫秒
                    
                    // 記錄監控資料
                    if ($this->monitor) {
                        $this->monitor->recordHit($name, $key, $duration);
                        $this->monitor->recordOperation('get', $name, true, $duration, ['result' => 'hit']);
                    }
                    
                    // 快取命中，同步到其他驅動
                    $this->syncCacheToHigherPriorityDrivers($key, $value, $name);
                    
                    $this->stats['total_hits']++;
                    
                    $this->logger->debug('快取命中', [
                        'key' => $key,
                        'driver' => $name,
                        'duration' => $duration,
                    ]);
                    
                    return $value;
                }
                
                // 記錄未命中
                $duration = (microtime(true) - $driverStartTime) * 1000;
                if ($this->monitor) {
                    $this->monitor->recordMiss($name, $key, $duration);
                }
                
            } catch (\Exception $e) {
                $duration = (microtime(true) - $driverStartTime) * 1000;
                
                if ($this->monitor) {
                    $this->monitor->recordError($name, 'get', $e->getMessage(), ['key' => $key]);
                    $this->monitor->recordOperation('get', $name, false, $duration, ['error' => $e->getMessage()]);
                }
                
                $this->handleDriverError($name, $e, 'get', ['key' => $key]);
            }
        }

        $this->stats['total_misses']++;
        
        $this->logger->debug('快取未命中', ['key' => $key]);
        
        return $default;
    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->stats['total_puts']++;
        $startTime = microtime(true);

        if (!$this->strategy->shouldCache($key, $value, $ttl)) {
            $this->stats['strategy_cache_denials']++;
            $this->logger->debug('快取策略拒絕快取', [
                'key' => $key,
                'ttl' => $ttl,
            ]);
            return false;
        }

        $adjustedTtl = $this->strategy->decideTtl($key, $value, $ttl);
        $availableDrivers = $this->getOrderedAvailableDrivers();
        $selectedDriver = $this->strategy->selectDriver($availableDrivers, $key, $value);

        if (!$selectedDriver) {
            $this->logger->warning('沒有適合的快取驅動', [
                'key' => $key,
                'available_drivers' => array_keys($availableDrivers),
            ]);
            return false;
        }

        $success = false;
        $driverName = array_search($selectedDriver, $availableDrivers, true);
        $driverStartTime = microtime(true);

        try {
            $success = $selectedDriver->put($key, $value, $adjustedTtl);
            $duration = (microtime(true) - $driverStartTime) * 1000; // 轉換為毫秒
            
            // 記錄監控資料
            if ($this->monitor) {
                $this->monitor->recordOperation('put', $driverName, $success, $duration, [
                    'key' => $key,
                    'ttl' => $adjustedTtl,
                    'value_size' => strlen(serialize($value)),
                ]);
            }

            if ($success) {
                $this->logger->debug('快取已存放', [
                    'key' => $key,
                    'driver' => $driverName,
                    'ttl' => $adjustedTtl,
                    'duration' => $duration,
                ]);
            }
        } catch (\Exception $e) {
            $duration = (microtime(true) - $driverStartTime) * 1000;
            
            if ($this->monitor) {
                $this->monitor->recordError($driverName, 'put', $e->getMessage(), [
                    'key' => $key,
                    'ttl' => $adjustedTtl,
                ]);
                $this->monitor->recordOperation('put', $driverName, false, $duration, [
                    'error' => $e->getMessage(),
                ]);
            }
            
            $success = $this->handleDriverError($driverName, $e, 'put', [
                'key' => $key,
                'value' => $value,
                'ttl' => $adjustedTtl,
            ]);
        }

        return $success;
    }

    public function has(string $key): bool
    {
        $availableDrivers = $this->getOrderedAvailableDrivers();
        $startTime = microtime(true);

        foreach ($availableDrivers as $name => $driver) {
            $driverStartTime = microtime(true);
            try {
                $hasKey = $driver->has($key);
                $duration = (microtime(true) - $driverStartTime) * 1000;
                
                // 記錄監控資料
                if ($this->monitor) {
                    $this->monitor->recordOperation('has', $name, true, $duration, [
                        'key' => $key,
                        'found' => $hasKey,
                    ]);
                }
                
                if ($hasKey) {
                    return true;
                }
            } catch (\Exception $e) {
                $duration = (microtime(true) - $driverStartTime) * 1000;
                
                if ($this->monitor) {
                    $this->monitor->recordError($name, 'has', $e->getMessage(), [
                        'key' => $key,
                    ]);
                    $this->monitor->recordOperation('has', $name, false, $duration, [
                        'error' => $e->getMessage(),
                    ]);
                }
                
                $this->handleDriverError($name, $e, 'has', ['key' => $key]);
            }
        }

        return false;
    }

    public function forget(string $key): bool
    {
        $this->stats['total_deletes']++;
        $startTime = microtime(true);

        $success = true;
        $availableDrivers = $this->getOrderedAvailableDrivers();

        foreach ($availableDrivers as $name => $driver) {
            $driverStartTime = microtime(true);
            try {
                $result = $driver->forget($key);
                $duration = (microtime(true) - $driverStartTime) * 1000;
                
                // 記錄監控資料
                if ($this->monitor) {
                    $this->monitor->recordOperation('forget', $name, $result, $duration, [
                        'key' => $key,
                    ]);
                }
                
                if (!$result) {
                    $success = false;
                }
            } catch (\Exception $e) {
                $duration = (microtime(true) - $driverStartTime) * 1000;
                
                if ($this->monitor) {
                    $this->monitor->recordError($name, 'forget', $e->getMessage(), [
                        'key' => $key,
                    ]);
                    $this->monitor->recordOperation('forget', $name, false, $duration, [
                        'error' => $e->getMessage(),
                    ]);
                }
                
                $this->handleDriverError($name, $e, 'forget', ['key' => $key]);
                $success = false;
            }
        }

        if ($success) {
            $this->logger->debug('快取已刪除', ['key' => $key]);
        }

        return $success;
    }

    public function flush(): bool
    {
        $this->stats['total_flushes']++;
        $startTime = microtime(true);

        $success = true;
        $availableDrivers = $this->getOrderedAvailableDrivers();

        foreach ($availableDrivers as $name => $driver) {
            $driverStartTime = microtime(true);
            try {
                $result = $driver->flush();
                $duration = (microtime(true) - $driverStartTime) * 1000;
                
                // 記錄監控資料
                if ($this->monitor) {
                    $this->monitor->recordOperation('flush', $name, $result, $duration, [
                        'driver' => $name,
                    ]);
                }
                
                if (!$result) {
                    $success = false;
                }
            } catch (\Exception $e) {
                $duration = (microtime(true) - $driverStartTime) * 1000;
                
                if ($this->monitor) {
                    $this->monitor->recordError($name, 'flush', $e->getMessage(), [
                        'driver' => $name,
                    ]);
                    $this->monitor->recordOperation('flush', $name, false, $duration, [
                        'error' => $e->getMessage(),
                    ]);
                }
                
                $this->handleDriverError($name, $e, 'flush', []);
                $success = false;
            }
        }

        if ($success) {
            $this->logger->info('所有快取已清空');
        }

        return $success;
    }

    public function many(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function putMany(array $values, int $ttl = 3600): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->put($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function increment(string $key, int $value = 1): int|false
    {
        $availableDrivers = $this->getOrderedAvailableDrivers();

        foreach ($availableDrivers as $name => $driver) {
            try {
                if (method_exists($driver, 'increment')) {
                    return $driver->increment($key, $value);
                }
            } catch (\Exception $e) {
                $this->handleDriverError($name, $e, 'increment', ['key' => $key, 'value' => $value]);
            }
        }

        return false;
    }

    public function decrement(string $key, int $value = 1): int|false
    {
        $availableDrivers = $this->getOrderedAvailableDrivers();

        foreach ($availableDrivers as $name => $driver) {
            try {
                if (method_exists($driver, 'decrement')) {
                    return $driver->decrement($key, $value);
                }
            } catch (\Exception $e) {
                $this->handleDriverError($name, $e, 'decrement', ['key' => $key, 'value' => $value]);
            }
        }

        return false;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->put($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->forget($key);
    }

    public function clear(): bool
    {
        return $this->flush();
    }

    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        try {
            $value = $this->strategy->handleMiss($key, $callback);
            $this->put($key, $value, $ttl);
            return $value;
        } catch (\Exception $e) {
            $this->logger->error('記憶化快取失敗', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function prefix(string $prefix): CacheManagerInterface
    {
        // 建立一個有前綴的代理快取管理器
        return new PrefixedCacheManager($this, $prefix);
    }

    public function driver(?string $driver = null): CacheDriverInterface
    {
        if ($driver === null) {
            $driver = $this->defaultDriver;
        }

        $driverInstance = $this->getDriver($driver);

        if ($driverInstance === null) {
            throw new \InvalidArgumentException("驅動 '{$driver}' 不存在");
        }

        return $driverInstance;
    }

    public function getHealthStatus(): array
    {
        $healthStatus = [];

        foreach ($this->drivers as $name => $driver) {
            $status = [
                'name' => $name,
                'class' => get_class($driver),
                'available' => false,
                'priority' => $this->driverPriority[$name] ?? 0,
                'error' => null,
            ];

            try {
                $status['available'] = $driver->isAvailable();

                // 執行健康檢查
                if ($status['available']) {
                    $testKey = '__health_check__' . time();
                    $driver->put($testKey, 'test', 60);
                    $retrieved = $driver->get($testKey);
                    $driver->forget($testKey);

                    if ($retrieved !== 'test') {
                        $status['available'] = false;
                        $status['error'] = '讀寫測試失敗';
                    }
                }
            } catch (\Exception $e) {
                $status['available'] = false;
                $status['error'] = $e->getMessage();
            }

            $healthStatus[$name] = $status;
        }

        return $healthStatus;
    }

    public function warmup(array $warmupCallbacks): array
    {
        $results = [];

        foreach ($warmupCallbacks as $key => $callback) {
            try {
                $startTime = microtime(true);
                $value = $callback();
                $this->put($key, $value, $this->config['warmup_ttl'] ?? 7200);
                $endTime = microtime(true);

                $results[$key] = [
                    'success' => true,
                    'duration' => round(($endTime - $startTime) * 1000, 2), // 毫秒
                ];

                $this->logger->debug('快取預熱成功', [
                    'key' => $key,
                    'duration' => $results[$key]['duration'],
                ]);
            } catch (\Exception $e) {
                $results[$key] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                $this->logger->error('快取預熱失敗', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    public function cleanup(): array
    {
        $results = [];

        foreach ($this->drivers as $name => $driver) {
            try {
                $cleaned = 0;

                if (method_exists($driver, 'cleanup')) {
                    $cleaned = $driver->cleanup();
                }

                $results[$name] = [
                    'success' => true,
                    'cleaned_items' => $cleaned,
                ];

                $this->logger->debug('快取清理完成', [
                    'driver' => $name,
                    'cleaned_items' => $cleaned,
                ]);
            } catch (\Exception $e) {
                $results[$name] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                $this->logger->error('快取清理失敗', [
                    'driver' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    public function tags(string|array $tags): TaggedCacheInterface
    {
        // 標準化為陣列
        if (is_string($tags)) {
            $tags = [$tags];
        }

        // 找到支援標籤的驅動
        $availableDrivers = $this->getOrderedAvailableDrivers();

        foreach ($availableDrivers as $name => $driver) {
            if ($driver instanceof TaggedCacheInterface) {
                return $driver->tags($tags);
            }
        }

        // 如果沒有支援標籤的驅動，拋出例外
        throw new \RuntimeException('沒有可用的標籤快取驅動');
    }

    public function getStats(): array
    {
        $totalRequests = $this->stats['total_gets'];
        $hitRate = $totalRequests > 0 ? ($this->stats['total_hits'] / $totalRequests) * 100 : 0;

        $driverStats = [];
        foreach ($this->drivers as $name => $driver) {
            $driverStats[$name] = [
                'class' => get_class($driver),
                'available' => $driver->isAvailable(),
                'priority' => $this->driverPriority[$name] ?? 0,
            ];

            if (method_exists($driver, 'getStats')) {
                $driverStats[$name]['stats'] = $driver->getStats();
            }
        }

        return array_merge($this->stats, [
            'hit_rate' => round($hitRate, 2),
            'drivers' => $driverStats,
            'strategy_stats' => $this->strategy->getStats(),
            'config' => $this->config,
        ]);
    }

    public function resetStats(): void
    {
        $this->stats = [
            'total_gets' => 0,
            'total_hits' => 0,
            'total_misses' => 0,
            'total_puts' => 0,
            'total_deletes' => 0,
            'total_flushes' => 0,
            'driver_failures' => 0,
            'strategy_cache_denials' => 0,
        ];

        $this->strategy->resetStats();

        foreach ($this->drivers as $driver) {
            if (method_exists($driver, 'resetStats')) {
                $driver->resetStats();
            }
        }

        $this->logger->debug('快取統計資料已重設');
    }

    /**
     * 根據優先級取得可用驅動。
     */
    private function getOrderedAvailableDrivers(): array
    {
        $ordered = [];

        foreach (array_keys($this->driverPriority) as $name) {
            $driver = $this->drivers[$name];
            if ($driver->isAvailable()) {
                $ordered[$name] = $driver;
            }
        }

        return $ordered;
    }

    /**
     * 同步快取到更高優先級的驅動。
     */
    private function syncCacheToHigherPriorityDrivers(string $key, mixed $value, string $currentDriver): void
    {
        if (!$this->config['enable_sync']) {
            return;
        }

        $currentPriority = $this->driverPriority[$currentDriver] ?? 0;
        $ttl = $this->config['sync_ttl'] ?? 3600;

        foreach ($this->driverPriority as $name => $priority) {
            if ($priority > $currentPriority && $this->drivers[$name]->isAvailable()) {
                try {
                    $this->drivers[$name]->put($key, $value, $ttl);

                    $this->logger->debug('快取已同步', [
                        'key' => $key,
                        'from_driver' => $currentDriver,
                        'to_driver' => $name,
                    ]);
                } catch (\Exception $e) {
                    $this->logger->warning('快取同步失敗', [
                        'key' => $key,
                        'driver' => $name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * 處理驅動錯誤。
     */
    private function handleDriverError(string $driverName, \Exception $error, string $operation, array $params): mixed
    {
        $this->stats['driver_failures']++;

        $this->logger->error('快取驅動錯誤', [
            'driver' => $driverName,
            'operation' => $operation,
            'error' => $error->getMessage(),
            'params' => $params,
        ]);

        // 使用策略處理失敗
        $availableDrivers = array_filter(
            $this->drivers,
            fn($driver, $name) => $name !== $driverName && $driver->isAvailable(),
            ARRAY_FILTER_USE_BOTH
        );

        return $this->strategy->handleDriverFailure(
            $this->drivers[$driverName],
            $availableDrivers,
            $operation,
            $params
        );
    }

    /**
     * 取得預設設定。
     */
    private function getDefaultConfig(): array
    {
        return [
            'enable_sync' => false,
            'sync_ttl' => 3600,
            'max_retry_attempts' => 3,
            'retry_delay' => 100,
            'enable_compression' => false,
            'compression_threshold' => 1024,
        ];
    }

    /**
     * 更新設定。
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);

        $this->logger->debug('快取管理器設定已更新', [
            'config' => $config,
        ]);
    }

    /**
     * 取得設定。
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
