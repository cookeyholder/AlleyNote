<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Contracts;

/**
 * 統計快取服務介面.
 *
 * 定義統計功能專用的快取操作，擴展基本快取功能以支援標籤管理、
 * 批量失效等高級功能。
 */
interface StatisticsCacheServiceInterface
{
    /**
     * 記憶化快取 - 如果快取不存在則執行回調並快取結果.
     *
     * @param string $key 快取鍵
     * @param callable $callback 回調函式
     * @param int $ttl 存活時間（秒）
     * @return mixed 快取的資料或回調結果
     */
    public function remember(string $key, callable $callback, int $ttl): mixed;

    /**
     * 根據標籤清除快取.
     *
     * @param array<string> $tags 快取標籤陣列
     */
    public function flushByTags(array $tags): void;

    /**
     * 刪除指定快取鍵.
     *
     * @param array<string>|string $keys 快取鍵或快取鍵陣列
     */
    public function forget(array|string $keys): void;

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
     * @param int $ttl 存活時間（秒）
     * @param array<string> $tags 快取標籤
     * @return bool 是否成功存入
     */
    public function put(string $key, mixed $value, int $ttl, array $tags = []): bool;

    /**
     * 檢查快取鍵是否存在.
     *
     * @param string $key 快取鍵
     * @return bool 是否存在
     */
    public function has(string $key): bool;

    /**
     * 清空所有統計快取.
     *
     * @return bool 是否成功清空
     */
    public function flush(): bool;

    /**
     * 取得快取統計資訊.
     *
     * @return array<string, mixed> 包含命中率、快取數量等統計資訊
     */
    public function getStats(): array;

    /**
     * 預熱快取 - 根據標籤預熱相關的快取資料.
     *
     * @param array<string, callable> $warmupCallbacks 預熱回調函式陣列
     * @param int $warmupTtl 預熱快取存活時間（秒）
     * @return array<string, array{success: bool, duration?: float, error?: string}> 預熱結果
     */
    public function warmup(array $warmupCallbacks, int $warmupTtl = 7200): array;
}
