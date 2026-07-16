<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Shared\Cache\Contracts\CacheInterface;

interface CacheServiceInterface extends CacheInterface
{
    /**
     * 批次取得多個快取。
     *
     * @param array<string, mixed> $keys 快取鍵陣列
     *
     * @return array 快取資料陣列，格式為 [key => value]
     */
    public function getMultiple(array $keys): array;

    /**
     * 批次設定多個快取。
     *
     * @param array<string, mixed> $values 快取資料陣列，格式為 [key => value]
     * @param int $ttl 存活時間（秒）
     *
     * @return bool 是否全部成功設定
     */
    public function setMultiple(array $values, int $ttl = 3600): bool;

    /**
     * 批次刪除多個快取。
     *
     * @param array<string, mixed> $keys 快取鍵陣列
     *
     * @return bool 是否全部成功刪除
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * 依照模式刪除快取。
     *
     * @param string $pattern 快取鍵模式（支援萬用字元）
     *
     * @return int 刪除的快取數量
     */
    public function deletePattern(string $pattern): int;
}
