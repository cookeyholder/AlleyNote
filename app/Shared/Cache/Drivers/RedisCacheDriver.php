<?php

declare(strict_types=1);

namespace App\Shared\Cache\Drivers;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use Redis;
use RedisException;

/**
 * Redis 快取驅動。
 * 
 * 使用 Redis 存儲快取資料，支援分散式快取和高效能訪問
 */
class RedisCacheDriver implements CacheDriverInterface
{
    /** @var Redis Redis 連線 */
    private Redis $redis;

    /** @var string 鍵前綴 */
    private string $prefix;

    /** @var array<string, int> 統計資料 */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'clears' => 0,
    ];

    /** @var int 預設 TTL */
    private const DEFAULT_TTL = 3600;

    public function __construct(array $config = [])
    {
        $this->redis = new Redis();
        $this->prefix = $config['prefix'] ?? 'alleynote_cache:';
        
        $this->connect($config);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $value = $this->redis->get($this->getPrefixedKey($key));
            
            if ($value === false) {
                $this->stats['misses']++;
                return $default;
            }

            $data = unserialize($value);
            $this->stats['hits']++;
            return $data;
        } catch (RedisException) {
            $this->stats['misses']++;
            return $default;
        }
    }

    public function put(string $key, mixed $value, int $ttl = self::DEFAULT_TTL): bool
    {
        try {
            $prefixedKey = $this->getPrefixedKey($key);
            $serializedValue = serialize($value);
            
            $result = $ttl > 0 
                ? $this->redis->setex($prefixedKey, $ttl, $serializedValue)
                : $this->redis->set($prefixedKey, $serializedValue);
            
            if ($result) {
                $this->stats['sets']++;
            }

            return $result;
        } catch (RedisException) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        try {
            $exists = $this->redis->exists($this->getPrefixedKey($key));
            return is_int($exists) && $exists > 0;
        } catch (RedisException) {
            return false;
        }
    }

    public function forget(string $key): bool
    {
        try {
            $result = $this->redis->del($this->getPrefixedKey($key));
            if ($result > 0) {
                $this->stats['deletes']++;
                return true;
            }
            return false;
        } catch (RedisException) {
            return false;
        }
    }

    public function flush(): bool
    {
        try {
            // 只清除帶有前綴的快取
            $keys = $this->redis->keys($this->prefix . '*');
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
            $this->stats['clears']++;
            return true;
        } catch (RedisException) {
            return false;
        }
    }

    public function many(array $keys): array
    {
        $result = [];
        
        try {
            $prefixedKeys = array_map([$this, 'getPrefixedKey'], $keys);
            $values = $this->redis->mget($prefixedKeys);
            
            foreach ($keys as $index => $key) {
                $value = $values[$index] ?? false;
                if ($value !== false) {
                    $result[$key] = unserialize($value);
                    $this->stats['hits']++;
                } else {
                    $result[$key] = null;
                    $this->stats['misses']++;
                }
            }
        } catch (RedisException) {
            // 回退到單個操作
            foreach ($keys as $key) {
                $result[$key] = $this->get($key);
            }
        }

        return $result;
    }

    public function putMany(array $values, int $ttl = self::DEFAULT_TTL): bool
    {
        try {
            $pipe = $this->redis->multi();
            
            foreach ($values as $key => $value) {
                $prefixedKey = $this->getPrefixedKey($key);
                $serializedValue = serialize($value);
                
                if ($ttl > 0) {
                    $pipe->setex($prefixedKey, $ttl, $serializedValue);
                } else {
                    $pipe->set($prefixedKey, $serializedValue);
                }
            }
            
            $results = $pipe->exec();
            $success = $results !== false && !in_array(false, $results, true);
            
            if ($success) {
                $this->stats['sets'] += count($values);
            }

            return $success;
        } catch (RedisException) {
            // 回退到單個操作
            $success = true;
            foreach ($values as $key => $value) {
                if (!$this->put($key, $value, $ttl)) {
                    $success = false;
                }
            }
            return $success;
        }
    }

    public function forgetMany(array $keys): bool
    {
        try {
            $prefixedKeys = array_map([$this, 'getPrefixedKey'], $keys);
            $deleted = $this->redis->del($prefixedKeys);
            
            $this->stats['deletes'] += $deleted;
            return $deleted === count($keys);
        } catch (RedisException) {
            // 回退到單個操作
            $success = true;
            foreach ($keys as $key) {
                if (!$this->forget($key)) {
                    $success = false;
                }
            }
            return $success;
        }
    }

    public function forgetPattern(string $pattern): int
    {
        try {
            $prefixedPattern = $this->prefix . $pattern;
            $keys = $this->redis->keys($prefixedPattern);
            
            if (empty($keys)) {
                return 0;
            }
            
            $deleted = $this->redis->del($keys);
            $this->stats['deletes'] += $deleted;
            
            return $deleted;
        } catch (RedisException) {
            return 0;
        }
    }

    public function increment(string $key, int $value = 1): int
    {
        try {
            $result = $this->redis->incrBy($this->getPrefixedKey($key), $value);
            return $result;
        } catch (RedisException) {
            // 回退到 get/set 操作
            $current = $this->get($key, 0);
            $newValue = (int) $current + $value;
            $this->put($key, $newValue);
            return $newValue;
        }
    }

    public function decrement(string $key, int $value = 1): int
    {
        try {
            $result = $this->redis->decrBy($this->getPrefixedKey($key), $value);
            return $result;
        } catch (RedisException) {
            // 回退到 get/set 操作
            $current = $this->get($key, 0);
            $newValue = (int) $current - $value;
            $this->put($key, $newValue);
            return $newValue;
        }
    }

    public function remember(string $key, callable $callback, int $ttl = self::DEFAULT_TTL): mixed
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        if ($value !== null) {
            $this->put($key, $value, $ttl);
        }

        return $value;
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, $callback, 0);
    }

    public function getStats(): array
    {
        $totalRequests = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $totalRequests > 0 ? ($this->stats['hits'] / $totalRequests) * 100 : 0;

        $redisStats = [];
        try {
            $info = $this->redis->info('memory');
            $redisStats = [
                'redis_memory_used' => $info['used_memory'] ?? 0,
                'redis_memory_peak' => $info['used_memory_peak'] ?? 0,
                'redis_connected_clients' => $this->redis->info('clients')['connected_clients'] ?? 0,
            ];
        } catch (RedisException) {
            // 忽略 Redis 資訊獲取錯誤
        }

        return array_merge($this->stats, $redisStats, [
            'hit_rate' => round($hitRate, 2),
            'prefix' => $this->prefix,
            'connection_status' => $this->isAvailable(),
        ]);
    }

    public function getConnection(): mixed
    {
        return $this->redis;
    }

    public function isAvailable(): bool
    {
        try {
            return $this->redis->ping() === '+PONG';
        } catch (RedisException) {
            return false;
        }
    }

    public function cleanup(): int
    {
        // Redis 自動處理 TTL 過期，不需要手動清理
        return 0;
    }

    /**
     * 連線到 Redis。
     */
    private function connect(array $config): void
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $password = $config['password'] ?? null;
        $database = $config['database'] ?? 0;
        $timeout = $config['timeout'] ?? 2.5;

        try {
            $this->redis->connect($host, $port, $timeout);
            
            if ($password !== null) {
                $this->redis->auth($password);
            }
            
            $this->redis->select($database);
        } catch (RedisException $e) {
            throw new \RuntimeException("無法連線到 Redis: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得帶前綴的快取鍵。
     */
    private function getPrefixedKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * 取得快取鍵前綴。
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * 設定快取鍵前綴。
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * 重設統計資料。
     */
    public function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
            'clears' => 0,
        ];
    }

    /**
     * 解構函式，關閉 Redis 連線。
     */
    public function __destruct()
    {
        try {
            if ($this->redis->isConnected()) {
                $this->redis->close();
            }
        } catch (RedisException) {
            // 忽略關閉連線時的錯誤
        }
    }
}