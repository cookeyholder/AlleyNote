<?php

declare(strict_types=1);

namespace App\Services;

class CacheService
{
    private string $cachePath;
    private const TTL = 3600; // 預設快取時間 1 小時

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
            return null;
        }

        $data = file_get_contents($filename);
        if ($data === false) {
            return null;
        }

        $cacheData = json_decode($data, true);
        if (!is_array($cacheData) || !isset($cacheData['expiry']) || !isset($cacheData['data'])) {
            return null;
        }

        if (time() > $cacheData['expiry']) {
            unlink($filename);
            return null;
        }

        return $cacheData['data'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $filename = $this->getCacheFilename($key);
        $cacheData = [
            'expiry' => time() + ($ttl ?: self::TTL),
            'data' => $value
        ];

        return file_put_contents($filename, json_encode($cacheData)) !== false;
    }

    public function delete(string $key): bool
    {
        $filename = $this->getCacheFilename($key);
        if (!file_exists($filename)) {
            return true;
        }

        return unlink($filename);
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
                $this->set($key, $value, $ttl);
            }
        }

        return $value;
    }

    private function getCacheFilename(string $key): string
    {
        return $this->cachePath . '/' . md5($key) . '.cache';
    }
}
