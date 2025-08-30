<?php

declare(strict_types=1);

namespace App\Shared\Cache\Contracts;

/**
 * 標籤化快取介面。
 * 
 * 支援按標籤組織和批次操作快取項目
 */
interface TaggedCacheInterface
{
    /**
     * 取得快取資料。
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 設定快取資料。
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * 檢查快取是否存在。
     */
    public function has(string $key): bool;

    /**
     * 刪除快取。
     */
    public function forget(string $key): bool;

    /**
     * 清空所有標籤化快取。
     */
    public function flush(): bool;

    /**
     * 記憶化取得。
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed;

    /**
     * 增加新標籤。
     * 
     * @param string|array<string> $tags 標籤
     */
    public function addTags(string|array $tags): TaggedCacheInterface;

    /**
     * 取得所有標籤。
     * 
     * @return array<string> 標籤陣列
     */
    public function getTags(): array;

    /**
     * 按標籤清空快取。
     * 
     * @param string|array<string> $tags 要清空的標籤
     */
    public function flushByTags(string|array $tags): int;

    /**
     * 取得標籤下的所有快取鍵。
     * 
     * @return array<string> 快取鍵陣列
     */
    public function getTaggedKeys(): array;
}