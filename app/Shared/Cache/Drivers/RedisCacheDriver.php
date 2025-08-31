<?php

declare(strict_types=1);

namespace App\Shared\Cache\Drivers;

use App\Shared\Cache\Contracts\CacheDriverInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use Redis;
use RedisException;

/**
 * Redis 快取驅動。
 *
 * 使用 Redis 存儲快取資料，支援分散式快取和高效能訪問
 * 提供標籤支援功能
 */
class RedisCacheDriver implements CacheDriverInterface, TaggedCacheInterface
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

    /** @var array<string> 當前標籤 */
    private array $tags = [];

    /** @var int 預設 TTL */
    private const DEFAULT_TTL = 3600;

    /** @var string 標籤索引前綴 */
    private const TAG_INDEX_PREFIX = 'tag_index:';

    public function __construct(array $config = [])
    {
        $this->redis = new Redis();
        $prefix = $config['prefix'] ?? 'alleynote_cache:';
        $this->prefix = is_string($prefix) ? $prefix : 'alleynote_cache:';

        $this->connect($config);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $prefixedKey = $this->getPrefixedKey($key);
            $value = $this->redis->get($prefixedKey);

            if ($value === false) {
                $this->stats['misses']++;
                return $default;
            }

            $this->stats['hits']++;
            $unserializedValue = is_string($value) ? unserialize($value) : false;
            return $unserializedValue === false ? $default : $unserializedValue;
        } catch (RedisException) {
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

                // 如果有標籤，添加到標籤索引
                if (!empty($this->tags)) {
                    $this->addKeyToTags($key, $this->tags);
                }
            }

            return (bool) $result;
        } catch (RedisException) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        try {
            $prefixedKey = $this->getPrefixedKey($key);
            $result = $this->redis->exists($prefixedKey);
            return (int) $result > 0;
        } catch (RedisException) {
            return false;
        }
    }

    public function forget(string $key): bool
    {
        try {
            $result = $this->redis->del($this->getPrefixedKey($key));
            if (is_int($result) && $result > 0) {
                $this->stats['deletes']++;
                $this->removeKeyFromAllTags($key);
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
                if ($value !== false && is_string($value)) {
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

                // 如果有標籤，添加到標籤索引
                if (!empty($this->tags)) {
                    foreach (array_keys($values) as $key) {
                        $this->addKeyToTags($key, $this->tags);
                    }
                }
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

            if (is_int($deleted)) {
                $this->stats['deletes'] += $deleted;

                if ($deleted > 0) {
                    foreach ($keys as $key) {
                        $this->removeKeyFromAllTags($key);
                    }
                }

                return $deleted === count($keys);
            }

            return false;
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
            return is_int($result) ? $result : 0;
        } catch (RedisException) {
            // 回退到 get/set 操作
            $current = $this->get($key, 0);
            $newValue = (is_int($current) || is_numeric($current)) ? (int) $current + $value : $value;
            $this->put($key, $newValue);
            return $newValue;
        }
    }

    public function decrement(string $key, int $value = 1): int
    {
        try {
            $result = $this->redis->decrBy($this->getPrefixedKey($key), $value);
            return is_int($result) ? $result : 0;
        } catch (RedisException) {
            // 回退到 get/set 操作
            $current = $this->get($key, 0);
            $newValue = (is_int($current) || is_numeric($current)) ? (int) $current - $value : -$value;
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
        $timeout = $config['timeout'] ?? 0.0;

        $host = is_string($host) ? $host : '127.0.0.1';
        $port = is_int($port) ? $port : 6379;
        $timeout = is_float($timeout) || is_int($timeout) ? (float) $timeout : 0.0;

        try {
            $this->redis->connect($host, $port, $timeout);

            if (isset($config['password']) && is_string($config['password'])) {
                $this->redis->auth($config['password']);
            }

            if (isset($config['database']) && is_int($config['database'])) {
                $this->redis->select($config['database']);
            }
        } catch (RedisException $e) {
            throw new RedisException('Redis connection failed: ' . $e->getMessage());
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

    // ===== 標籤化快取介面實作 =====

    /**
     * 設定快取標籤
     *
     * @param array<string>|string $tags 標籤陣列或單一標籤
     * @return TaggedCacheInterface 標籤化快取實例
     */
    public function tags(array|string $tags): TaggedCacheInterface
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];

        // 建立新的驅動實例以避免標籤污染
        $driver = clone $this;
        $driver->tags = $tagsArray;

        return $driver;
    }

    /**
     * 取得當前標籤
     *
     * @return array<string> 標籤陣列
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * 根據標籤清空快取
     *
     * @param array<string>|string $tags 要清空的標籤
     * @return int 清空的項目數量
     */
    public function flushByTags(array|string $tags): int
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];
        $totalDeleted = 0;

        try {
            foreach ($tagsArray as $tag) {
                $tagIndexKey = $this->getTagIndexKey($tag);
                $keys = $this->redis->sMembers($tagIndexKey);

                if (!empty($keys)) {
                    // 刪除所有與標籤相關的快取鍵
                    $deleted = $this->redis->del($keys);
                    $totalDeleted += $deleted;

                    // 清空標籤索引
                    $this->redis->del($tagIndexKey);
                }

                $this->stats['deletes'] += is_array($keys) ? count($keys) : 0;
            }
        } catch (RedisException) {
            // 標籤清空失敗，回退處理
        }

        return $totalDeleted;
    }

    /**
     * 根據標籤取得快取鍵
     *
     * @param string $tag 標籤名稱
     * @return array<string> 快取鍵陣列
     */
    public function getKeysByTag(string $tag): array
    {
        try {
            $tagIndexKey = $this->getTagIndexKey($tag);
            $keys = $this->redis->sMembers($tagIndexKey);

            // 移除前綴並過濾存在的鍵
            $result = [];
            foreach ($keys as $key) {
                if (str_starts_with($key, $this->prefix)) {
                    $unprefixedKey = substr($key, strlen($this->prefix));
                    if ($this->has($unprefixedKey)) {
                        $result[] = $unprefixedKey;
                    }
                }
            }

            return $result;
        } catch (RedisException) {
            return [];
        }
    }

    /**
     * 根據多個標籤取得共同的快取鍵
     *
     * @param array<string> $tags 標籤陣列
     * @return array<string> 共同的快取鍵陣列
     */
    public function getKeysByTags(array $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        try {
            $tagIndexKeys = array_map([$this, 'getTagIndexKey'], $tags);

            // 使用 Redis 的集合交集運算
            $tempKey = 'temp_intersection_' . uniqid();
            $this->redis->sInterStore($tempKey, ...$tagIndexKeys);
            $keys = $this->redis->sMembers($tempKey);
            $this->redis->del($tempKey);

            // 移除前綴並過濾存在的鍵
            $result = [];
            foreach ($keys as $key) {
                if (str_starts_with($key, $this->prefix)) {
                    $unprefixedKey = substr($key, strlen($this->prefix));
                    if ($this->has($unprefixedKey)) {
                        $result[] = $unprefixedKey;
                    }
                }
            }

            return $result;
        } catch (RedisException) {
            return [];
        }
    }

    /**
     * 檢查標籤是否存在
     *
     * @param string $tag 標籤名稱
     * @return bool 是否存在
     */
    public function tagExists(string $tag): bool
    {
        try {
            $tagIndexKey = $this->getTagIndexKey($tag);
            $result = $this->redis->exists($tagIndexKey);
            return is_int($result) && $result > 0;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * 取得所有標籤
     *
     * @return array<string> 所有標籤陣列
     */
    public function getAllTags(): array
    {
        try {
            $pattern = $this->prefix . self::TAG_INDEX_PREFIX . '*';
            $keys = $this->redis->keys($pattern);

            $tags = [];
            foreach ($keys as $key) {
                $tag = substr($key, strlen($this->prefix . self::TAG_INDEX_PREFIX));
                if ($tag) {
                    $tags[] = $tag;
                }
            }

            return $tags;
        } catch (RedisException) {
            return [];
        }
    }

    /**
     * 取得標籤統計資訊
     *
     * @return array<string, mixed> 標籤統計資訊
     */
    public function getTagStatistics(): array
    {
        $statistics = [
            'total_tags' => 0,
            'tags' => [],
        ];

        try {
            $allTags = $this->getAllTags();
            $statistics['total_tags'] = count($allTags);

            foreach ($allTags as $tag) {
                $keys = $this->getKeysByTag($tag);
                $statistics['tags'][$tag] = [
                    'key_count' => count($keys),
                    'sample_keys' => array_slice($keys, 0, 5), // 顯示前 5 個鍵作為範例
                ];
            }
        } catch (RedisException) {
            // 統計失敗
        }

        return $statistics;
    }



    // 實現 TaggedCacheInterface 的缺少方法

    /**
     * 增加新標籤到快取管理器
     */
    public function addTags(string|array $tags): TaggedCacheInterface
    {
        $tagsArray = is_array($tags) ? $tags : [$tags];
        $this->tags = array_unique(array_merge($this->tags, $tagsArray));
        return $this;
    }

    /**
     * 為現有快取項目添加標籤
     */
    public function addTagsToKey(string $key, string|array $tags): bool
    {
        $tags = is_array($tags) ? $tags : [$tags];

        try {
            $this->addKeyToTags($key, $tags);
            return true;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * 取得指定標籤的所有快取鍵
     */
    public function getTaggedKeys(): array
    {
        $allKeys = [];

        foreach ($this->tags as $tag) {
            try {
                $tagIndexKey = $this->getTagIndexKey($tag);
                $keys = $this->redis->sMembers($tagIndexKey);
                $allKeys = array_merge($allKeys, $keys);
            } catch (RedisException) {
                continue;
            }
        }

        return array_unique($allKeys);
    }

    /**
     * 取得快取項目的所有標籤
     */
    public function getTagsByKey(string $key): array
    {
        try {
            $prefixedKey = $this->getPrefixedKey($key);
            $tagKey = $prefixedKey . ':tags';

            $tags = $this->redis->sMembers($tagKey);
            return is_array($tags) ? $tags : [];
        } catch (RedisException) {
            return [];
        }
    }

    /**
     * 檢查快取項目是否包含指定標籤
     */
    public function hasTag(string $key, string $tag): bool
    {
        $tags = $this->getTagsByKey($key);
        return in_array($tag, $tags, true);
    }

    /**
     * 從快取項目移除標籤
     */
    public function removeTagsFromKey(string $key, string|array $tags): bool
    {
        $tags = is_array($tags) ? $tags : [$tags];

        try {
            $prefixedKey = $this->getPrefixedKey($key);
            $tagKey = $prefixedKey . ':tags';

            foreach ($tags as $tag) {
                $this->redis->sRem($tagKey, $tag);
                $tagIndexKey = $this->getTagIndexKey($tag);
                $this->redis->sRem($tagIndexKey, $prefixedKey);
            }

            return true;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * 使用指定標籤存放快取項目
     */
    public function putWithTags(string $key, mixed $value, array $tags, int $ttl = self::DEFAULT_TTL): bool
    {
        $this->tags = $tags;
        return $this->put($key, $value, $ttl);
    }

    /**
     * 清除未使用的標籤
     */
    public function cleanupUnusedTags(): int
    {
        try {
            $pattern = $this->prefix . self::TAG_INDEX_PREFIX . '*';
            $tagKeys = $this->redis->keys($pattern);
            $cleanedCount = 0;

            foreach ($tagKeys as $tagKey) {
                $members = $this->redis->sMembers($tagKey);
                if (empty($members)) {
                    $this->redis->del($tagKey);
                    $cleanedCount++;
                }
            }

            return $cleanedCount;
        } catch (RedisException) {
            return 0;
        }
    }

    /**
     * 將快取鍵添加到標籤索引
     *
     * @param string $key 快取鍵
     * @param array<string> $tags 標籤陣列
     */
    private function addKeyToTags(string $key, array $tags): void
    {
        try {
            $prefixedKey = $this->getPrefixedKey($key);

            foreach ($tags as $tag) {
                $tagIndexKey = $this->getTagIndexKey($tag);
                $this->redis->sAdd($tagIndexKey, $prefixedKey);
            }
        } catch (RedisException) {
            // 標籤索引添加失敗，但不影響主要快取操作
        }
    }

    /**
     * 從所有標籤索引中移除快取鍵
     *
     * @param string $key 快取鍵
     */
    private function removeKeyFromAllTags(string $key): void
    {
        try {
            $prefixedKey = $this->getPrefixedKey($key);
            $allTags = $this->getAllTags();

            foreach ($allTags as $tag) {
                $tagIndexKey = $this->getTagIndexKey($tag);
                $this->redis->sRem($tagIndexKey, $prefixedKey);
            }
        } catch (RedisException) {
            // 標籤索引清理失敗
        }
    }

    /**
     * 取得標籤索引鍵
     *
     * @param string $tag 標籤名稱
     * @return string 標籤索引鍵
     */
    private function getTagIndexKey(string $tag): string
    {
        return $this->prefix . self::TAG_INDEX_PREFIX . $tag;
    }

}
