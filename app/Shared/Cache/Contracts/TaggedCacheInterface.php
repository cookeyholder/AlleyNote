<?php

declare(strict_types=1);

namespace App\Shared\Cache\Contracts;

/**
 * 標籤化快取介面
 *
 * 支援按標籤組織和批次操作快取項目，提供豐富的標籤管理功能
 */
interface TaggedCacheInterface
{
    /**
     * 取得快取資料
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 設定快取資料
     */
    public function put(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * 檢查快取是否存在
     */
    public function has(string $key): bool;

    /**
     * 刪除快取
     */
    public function forget(string $key): bool;

    /**
     * 清空所有標籤化快取
     */
    public function flush(): bool;

    /**
     * 記憶化取得
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed;

    /**
     * 增加新標籤到快取管理器
     *
     * @param string|array<string> $tags 標籤
     */
    public function addTags(string|array $tags): TaggedCacheInterface;

    /**
     * 取得當前快取管理器的所有標籤
     *
     * @return array<string> 標籤陣列
     */
    public function getTags(): array;

    /**
     * 按標籤清空快取
     *
     * @param string|array<string> $tags 要清空的標籤
     */
    public function flushByTags(string|array $tags): int;

    /**
     * 取得標籤下的所有快取鍵
     *
     * @return array<string> 快取鍵陣列
     */
    public function getTaggedKeys(): array;

    // ========== 新增的進階標籤功能 ==========

    /**
     * 使用指定標籤存放快取項目
     *
     * @param string $key 快取鍵
     * @param mixed $value 快取值
     * @param array<string> $tags 標籤陣列
     * @param int $ttl 存活時間（秒）
     * @return bool 是否成功
     */
    public function putWithTags(string $key, mixed $value, array $tags, int $ttl = 3600): bool;

    /**
     * 取得指定標籤的所有快取鍵
     *
     * @param string $tag 標籤
     * @return array<string> 快取鍵陣列
     */
    public function getKeysByTag(string $tag): array;

    /**
     * 取得快取項目的所有標籤
     *
     * @param string $key 快取鍵
     * @return array<string> 標籤陣列
     */
    public function getTagsByKey(string $key): array;

    /**
     * 為現有快取項目添加標籤
     *
     * @param string $key 快取鍵
     * @param string|array<string> $tags 標籤或標籤陣列
     * @return bool 是否成功
     */
    public function addTagsToKey(string $key, string|array $tags): bool;

    /**
     * 從快取項目移除標籤
     *
     * @param string $key 快取鍵
     * @param string|array<string> $tags 標籤或標籤陣列
     * @return bool 是否成功
     */
    public function removeTagsFromKey(string $key, string|array $tags): bool;

    /**
     * 檢查快取項目是否包含指定標籤
     *
     * @param string $key 快取鍵
     * @param string $tag 標籤
     * @return bool 是否包含
     */
    public function hasTag(string $key, string $tag): bool;

    /**
     * 取得所有系統標籤
     *
     * @return array<string> 所有標籤
     */
    public function getAllTags(): array;

    /**
     * 清除未使用的標籤
     *
     * @return int 清除的標籤數量
     */
    public function cleanupUnusedTags(): int;

    /**
     * 取得標籤統計資訊
     *
     * @return array<string, int> 標籤名稱 => 快取項目數量
     */
    public function getTagStatistics(): array;

    /**
     * 檢查標籤是否存在
     *
     * @param string $tag 標籤名稱
     * @return bool 是否存在
     */
    public function tagExists(string $tag): bool;

    /**
     * 建立新的標籤化快取實例
     *
     * @param string|array<string> $tags 標籤或標籤陣列
     * @return TaggedCacheInterface 新的標籤化快取實例
     */
    public function tags(string|array $tags): TaggedCacheInterface;
}
