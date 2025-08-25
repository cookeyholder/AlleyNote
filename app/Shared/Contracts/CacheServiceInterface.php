<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

/**
 * 快取服務介面.
 *
 * 定義快取操作的標準介面
 */
interface CacheServiceInterface
{
    /**
     * 從快取中取得資料.
     *
     * @param string $key 快取鍵
     * @return mixed 快取的資料，若不存在則返回 null
     */
    public function get(string $key): mixed;

    /**
     * 將資料存入快取.
     *
     * @param string $key 快取鍵
     * @param mixed $value 要快取的資料
     * @param int $ttl 存活時間（秒），0 表示永不過期
     * @return bool 是否成功存入
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * 檢查快取鍵是否存在.
     *
     * @param string $key 快取鍵
     * @return bool 是否存在
     */
    public function has(string $key): bool;

    /**
     * 刪除快取.
     *
     * @param string $key 快取鍵
     * @return bool 是否成功刪除
     */
    public function delete(string $key): bool;

    /**
     * 清空所有快取.
     *
     * @return bool 是否成功清空
     */
    public function clear(): bool;

    /**
     * 批次取得多個快取.
     *
     * @param array $keys 快取鍵陣列
     * @return array 快取資料陣列，格式為 [key => value]
     */
    public function getMultiple(array $keys): array;

    /**
     * 批次設定多個快取.
     *
     * @param array $values 快取資料陣列，格式為 [key => value]
     * @param int $ttl 存活時間（秒）
     * @return bool 是否全部成功設定
     */
    public function setMultiple(array $values, int $ttl = 3600): bool;

    /**
     * 批次刪除多個快取.
     *
     * @param array $keys 快取鍵陣列
     * @return bool 是否全部成功刪除
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * 依照模式刪除快取.
     *
     * @param string $pattern 快取鍵模式（支援萬用字元）
     * @return int 刪除的快取數量
     */
    public function deletePattern(string $pattern): int;

    /**
     * 取得快取統計資訊.
     *
     * @return array 包含命中率、快取數量等統計資訊
     */
    public function getStats(): array;

    /**
     * 記憶化快取 - 如果快取不存在則執行回調並快取結果.
     *
     * @param string $key 快取鍵
     * @param callable $callback 回調函式
     * @param int|null $ttl 存活時間（秒），null 使用預設值
     * @return mixed 快取的資料或回調結果
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed;
}
