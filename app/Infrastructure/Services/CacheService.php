<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Shared\Contracts\CacheServiceInterface;

class CacheService implements CacheServiceInterface
{
    private string $cachePath;
    private const TTL = 3600; // 預設快取時間 1 小時

    // 快取統計
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'size' => 0,
    ];

    public function __construct()
    {
        $this->cachePath = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function get(string $key): mixed
    {
        $filename = $this->getCacheFilename($key);
        if (!file_exists($filename)) {
            $this->stats['misses']++;
            return null;
        }

        $data = file_get_contents($filename);
        if ($data === false) {
            $this->stats['misses']++;
            return null;
        }

        $cacheData = json_decode($data, true);
        if (!is_array($cacheData) || !isset($cacheData['expiry']) || !isset($cacheData['data'])) {
            $this->stats['misses']++;
            return null;
        }

        if (time() > $cacheData['expiry']) {
            unlink($filename);
            $this->stats['misses']++;
            return null;
        }

        $this->stats['hits']++;
        return $cacheData['data'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $filename = $this->getCacheFilename($key);
        $this->stats['sets']++;
        $cacheData = [
            'expiry' => time() + ($ttl ?: self::TTL),
            'data' => $value,
        ];

        $result = file_put_contents($filename, json_encode($cacheData)) !== false;
        if ($result) {
            $this->updateCacheSize();
        }
        return $result;
    }

    public function delete(string $key): bool
    {
        $filename = $this->getCacheFilename($key);
        if (!file_exists($filename)) {
            return true;
        }

        $result = unlink($filename);
        if ($result) {
            $this->stats['deletes']++;
            $this->updateCacheSize();
        }
        return $result;
    }

    public function deletePattern(string $pattern): int
    {
        // 基礎實作：刪除所有快取檔案（當模式匹配無法精確實現時）
        // 在生產環境中，應該使用 Redis 或其他支援模式匹配的快取系統
        if (strpos($pattern, '*') !== false) {
            return $this->clear() ? 1 : 0;
        }

        // 精確匹配
        return $this->delete($pattern) ? 1 : 0;
    }

    public function clear(): bool
    {
        $files = glob($this->cachePath . '/*');
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);

        if ($value === null) {
            $value = $callback();
            if ($value !== null) {
                $this->set($key, $value, $ttl ?: self::TTL);
            }
        }

        return $value;
    }

    public function has(string $key): bool
    {
        $filename = $this->getCacheFilename($key);
        if (!file_exists($filename)) {
            return false;
        }

        $data = file_get_contents($filename);
        if ($data === false) {
            return false;
        }

        $cacheData = json_decode($data, true);
        if (!is_array($cacheData) || !isset($cacheData['expiry'])) {
            return false;
        }

        if (time() > $cacheData['expiry']) {
            unlink($filename);
            return false;
        }

        return true;
    }

    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function setMultiple(array $values, int $ttl = 3600): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }



    private function getCacheFilename(string $key): string
    {
        return $this->cachePath . '/' . hash('sha256', $key) . '.cache';
    }

    /**
     * 更新快取大小統計
     */
    private function updateCacheSize(): void
    {
        $size = 0;
        $files = glob($this->cachePath . '/*.cache');
        if ($files) {
            foreach ($files as $file) {
                $size += filesize($file);
            }
        }
        $this->stats['size'] = $size;
    }

    /**
     * 取得快取統計資訊
     */
    public function getStats(): array
    {
        $this->updateCacheSize();
        $files = glob($this->cachePath . '/*.cache');

        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'sets' => $this->stats['sets'],
            'deletes' => $this->stats['deletes'],
            'hit_rate' => $this->stats['hits'] + $this->stats['misses'] > 0
                ? round($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses']) * 100, 2)
                : 0,
            'total_size' => $this->stats['size'],
            'file_count' => $files ? count($files) : 0,
            'cache_path' => $this->cachePath,
        ];
    }

    /**
     * 清理過期的快取檔案
     */
    public function cleanExpired(): int
    {
        $cleaned = 0;
        $files = glob($this->cachePath . '/*.cache');

        if ($files) {
            foreach ($files as $file) {
                $data = file_get_contents($file);
                if ($data !== false) {
                    $cacheData = json_decode($data, true);
                    if (is_array($cacheData) && isset($cacheData['expiry'])) {
                        if (time() > $cacheData['expiry']) {
                            unlink($file);
                            $cleaned++;
                        }
                    }
                }
            }
        }

        $this->updateCacheSize();
        return $cleaned;
    }

    /**
     * 重設統計資訊
     */
    public function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
            'size' => 0,
        ];
    }
}
