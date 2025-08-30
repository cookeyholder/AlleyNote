<?php

declare(strict_types=1);

namespace App\Shared\Cache\Drivers;

use App\Shared\Cache\Contracts\CacheDriverInterface;

/**
 * 多層快取驅動。
 *
 * 組合多個快取驅動，按照優先順序查詢和存儲資料
 */
class LayeredCacheDriver implements CacheDriverInterface
{
    /** @var array<CacheDriverInterface> 快取層級 */
    private array $layers;

    /** @var array<string, int> 統計資料 */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'clears' => 0,
        'layer_promotions' => 0,
    ];

    /**
     * @param array<CacheDriverInterface> $layers 快取層級，按優先順序排列
     */
    public function __construct(array $layers)
    {
        if (empty($layers)) {
            throw new \InvalidArgumentException('至少需要一個快取層級');
        }

        $this->layers = array_values($layers);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        foreach ($this->layers as $index => $layer) {
            if (!$layer->isAvailable()) {
                continue;
            }

            $value = $layer->get($key, $default);

            if ($value !== $default) {
                $this->stats['hits']++;

                // 將資料推送到更高優先級的層級
                $this->promoteToHigherLayers($key, $value, $index);

                return $value;
            }
        }

        $this->stats['misses']++;
        return $default;
    }

    public function put(string $key, mixed $value, int $ttl = 3600): bool
    {
        $success = true;

        foreach ($this->layers as $layer) {
            if (!$layer->isAvailable()) {
                continue;
            }

            if (!$layer->put($key, $value, $ttl)) {
                $success = false;
            }
        }

        if ($success) {
            $this->stats['sets']++;
        }

        return $success;
    }

    public function has(string $key): bool
    {
        foreach ($this->layers as $layer) {
            if (!$layer->isAvailable()) {
                continue;
            }

            if ($layer->has($key)) {
                return true;
            }
        }

        return false;
    }

    public function forget(string $key): bool
    {
        $success = true;

        foreach ($this->layers as $layer) {
            if (!$layer->isAvailable()) {
                continue;
            }

            if (!$layer->forget($key)) {
                $success = false;
            }
        }

        if ($success) {
            $this->stats['deletes']++;
        }

        return $success;
    }

    public function flush(): bool
    {
        $success = true;

        foreach ($this->layers as $layer) {
            if (!$layer->isAvailable()) {
                continue;
            }

            if (!$layer->flush()) {
                $success = false;
            }
        }

        if ($success) {
            $this->stats['clears']++;
        }

        return $success;
    }

    public function many(array $keys): array
    {
        $result = [];
        $missingKeys = $keys;

        foreach ($this->layers as $index => $layer) {
            if (!$layer->isAvailable() || empty($missingKeys)) {
                continue;
            }

            $layerResult = $layer->many($missingKeys);

            foreach ($layerResult as $key => $value) {
                if ($value !== null) {
                    $result[$key] = $value;

                    // 將找到的資料推送到更高優先級的層級
                    $this->promoteToHigherLayers($key, $value, $index);

                    // 從待查找列表中移除
                    $missingKeys = array_filter($missingKeys, fn($k) => $k !== $key);
                }
            }
        }

        // 為未找到的鍵設定 null 值
        foreach ($missingKeys as $key) {
            $result[$key] = null;
        }

        return $result;
    }

    public function putMany(array $values, int $ttl = 3600): bool
    {
        $success = true;

        foreach ($this->layers as $layer) {
            if (!$layer->isAvailable()) {
                continue;
            }

            if (!$layer->putMany($values, $ttl)) {
                $success = false;
            }
        }

        if ($success) {
            $this->stats['sets'] += count($values);
        }

        return $success;
    }

    public function forgetMany(array $keys): bool
    {
        $success = true;

        foreach ($this->layers as $layer) {
            if (!$layer->isAvailable()) {
                continue;
            }

            if (!$layer->forgetMany($keys)) {
                $success = false;
            }
        }

        if ($success) {
            $this->stats['deletes'] += count($keys);
        }

        return $success;
    }

    public function forgetPattern(string $pattern): int
    {
        $totalDeleted = 0;

        foreach ($this->layers as $layer) {
            if (!$layer->isAvailable()) {
                continue;
            }

            $deleted = $layer->forgetPattern($pattern);
            $totalDeleted += $deleted;
        }

        $this->stats['deletes'] += $totalDeleted;
        return $totalDeleted;
    }

    public function increment(string $key, int $value = 1): int
    {
        // 只在第一個可用的層級執行增量操作
        foreach ($this->layers as $layer) {
            if ($layer->isAvailable()) {
                $result = $layer->increment($key, $value);

                // 同步到其他層級
                $this->syncToOtherLayers($key, $result, $layer);

                return $result;
            }
        }

        throw new \RuntimeException('沒有可用的快取層級');
    }

    public function decrement(string $key, int $value = 1): int
    {
        // 只在第一個可用的層級執行減量操作
        foreach ($this->layers as $layer) {
            if ($layer->isAvailable()) {
                $result = $layer->decrement($key, $value);

                // 同步到其他層級
                $this->syncToOtherLayers($key, $result, $layer);

                return $result;
            }
        }

        throw new \RuntimeException('沒有可用的快取層級');
    }

    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
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

        $layerStats = [];
        foreach ($this->layers as $index => $layer) {
            $layerStats["layer_{$index}"] = [
                'driver' => get_class($layer),
                'available' => $layer->isAvailable(),
                'stats' => $layer->getStats(),
            ];
        }

        return array_merge($this->stats, [
            'total_layers' => count($this->layers),
            'hit_rate' => round($hitRate, 2),
            'layers' => $layerStats,
        ]);
    }

    public function getConnection(): mixed
    {
        return $this->layers;
    }

    public function isAvailable(): bool
    {
        foreach ($this->layers as $layer) {
            if ($layer->isAvailable()) {
                return true;
            }
        }

        return false;
    }

    public function cleanup(): int
    {
        $totalCleaned = 0;

        foreach ($this->layers as $layer) {
            if ($layer->isAvailable()) {
                $cleaned = $layer->cleanup();
                $totalCleaned += $cleaned;
            }
        }

        return $totalCleaned;
    }

    /**
     * 將資料推送到更高優先級的層級。
     */
    private function promoteToHigherLayers(string $key, mixed $value, int $fromIndex): void
    {
        for ($i = 0; $i < $fromIndex; $i++) {
            $layer = $this->layers[$i];

            if ($layer->isAvailable()) {
                $layer->put($key, $value);
                $this->stats['layer_promotions']++;
            }
        }
    }

    /**
     * 同步資料到其他層級。
     */
    private function syncToOtherLayers(string $key, mixed $value, CacheDriverInterface $excludeLayer): void
    {
        foreach ($this->layers as $layer) {
            if ($layer !== $excludeLayer && $layer->isAvailable()) {
                $layer->put($key, $value);
            }
        }
    }

    /**
     * 取得所有層級。
     */
    public function getLayers(): array
    {
        return $this->layers;
    }

    /**
     * 新增快取層級。
     */
    public function addLayer(CacheDriverInterface $layer): void
    {
        $this->layers[] = $layer;
    }

    /**
     * 移除快取層級。
     */
    public function removeLayer(CacheDriverInterface $layer): bool
    {
        $key = array_search($layer, $this->layers, true);

        if ($key !== false) {
            unset($this->layers[$key]);
            $this->layers = array_values($this->layers); // 重新索引
            return true;
        }

        return false;
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
            'layer_promotions' => 0,
        ];

        foreach ($this->layers as $layer) {
            if (method_exists($layer, 'resetStats')) {
                $layer->resetStats();
            }
        }
    }
}
