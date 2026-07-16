<?php

declare(strict_types=1);

namespace App\Shared\Cache\Contracts;

interface CacheInterface
{
    /**
     * 從快取中取得資料。
     *
     * @param string $key 快取鍵
     * @param mixed $default 預設回傳值
     *
     * @return mixed 快取的資料，若不存在則回傳 $default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 將資料存入快取。
     *
     * @param string $key 快取鍵
     * @param mixed $value 要快取的資料
     * @param int $ttl 存活時間（秒）
     *
     * @return bool 是否成功存入
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * 檢查快取鍵是否存在。
     *
     * @param string $key 快取鍵
     *
     * @return bool 是否存在
     */
    public function has(string $key): bool;

    /**
     * 刪除快取。
     *
     * @param string $key 快取鍵
     *
     * @return bool 是否成功刪除
     */
    public function delete(string $key): bool;

    /**
     * 清空所有快取。
     *
     * @return bool 是否成功清空
     */
    public function clear(): bool;

    /**
     * 記憶化快取 - 如果快取不存在則執行回調並快取結果。
     *
     * @param string $key 快取鍵
     * @param callable $callback 回調函式
     * @param int $ttl 存活時間（秒）
     *
     * @return mixed 快取的資料或回調結果
     */
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed;

    /**
     * 取得快取統計資訊。
     *
     * @return array 包含命中率、快取數量等統計資訊
     */
    public function getStats(): array;
}
