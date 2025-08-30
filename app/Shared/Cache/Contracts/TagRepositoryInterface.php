<?php

declare(strict_types=1);

namespace App\Shared\Cache\Contracts;

/**
 * 標籤儲存庫介面
 *
 * 負責管理快取標籤的持久化儲存和檢索
 */
interface TagRepositoryInterface
{
    /**
     * 為快取鍵設定標籤
     *
     * @param string $key 快取鍵
     * @param array<string> $tags 標籤陣列
     * @param int $ttl 存活時間（秒）
     * @return bool 是否成功
     */
    public function setTags(string $key, array $tags, int $ttl = 3600): bool;

    /**
     * 取得快取鍵的所有標籤
     *
     * @param string $key 快取鍵
     * @return array<string> 標籤陣列
     */
    public function getTags(string $key): array;

    /**
     * 為快取鍵添加標籤
     *
     * @param string $key 快取鍵
     * @param array<string> $tags 要添加的標籤
     * @return bool 是否成功
     */
    public function addTags(string $key, array $tags): bool;

    /**
     * 從快取鍵移除標籤
     *
     * @param string $key 快取鍵
     * @param array<string> $tags 要移除的標籤
     * @return bool 是否成功
     */
    public function removeTags(string $key, array $tags): bool;

    /**
     * 檢查快取鍵是否包含指定標籤
     *
     * @param string $key 快取鍵
     * @param string $tag 標籤
     * @return bool 是否包含
     */
    public function hasTag(string $key, string $tag): bool;

    /**
     * 取得指定標籤的所有快取鍵
     *
     * @param string $tag 標籤
     * @return array<string> 快取鍵陣列
     */
    public function getKeysByTag(string $tag): array;

    /**
     * 按標籤刪除快取鍵記錄
     *
     * @param string|array<string> $tags 標籤或標籤陣列
     * @return array<string> 被刪除的快取鍵陣列
     */
    public function deleteByTags(string|array $tags): array;

    /**
     * 刪除快取鍵的標籤記錄
     *
     * @param string $key 快取鍵
     * @return bool 是否成功
     */
    public function deleteKey(string $key): bool;

    /**
     * 取得所有標籤
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
     * @param string $tag 標籤
     * @return bool 是否存在
     */
    public function tagExists(string $tag): bool;

    /**
     * 更新快取鍵的過期時間
     *
     * @param string $key 快取鍵
     * @param int $ttl 新的存活時間（秒）
     * @return bool 是否成功
     */
    public function touch(string $key, int $ttl): bool;

    /**
     * 清除所有標籤記錄
     *
     * @return bool 是否成功
     */
    public function flush(): bool;
}