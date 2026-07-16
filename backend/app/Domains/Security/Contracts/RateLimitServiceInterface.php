<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

interface RateLimitServiceInterface
{
    /**
     * 檢查指定 IP 的速率限制，回傳限制結果陣列.
     *
     * @param string $ip 要檢查的 IP 位址或識別鍵值
     * @param int $maxRequests 時間窗口內允許的最大請求數
     * @param int $timeWindow 時間窗口（秒）
     *
     * @return array{allowed: bool, remaining: int, reset: int} 包含 allowed、remaining、reset 的陣列
     */
    public function checkLimit(string $ip, int $maxRequests = 60, int $timeWindow = 60): array;

    /**
     * 檢查請求是否被允許（簡化版本的 checkLimit）.
     *
     * @param string $ip 要檢查的 IP 位址或識別鍵值
     * @param int $maxRequests 時間窗口內允許的最大請求數
     * @param int $timeWindow 時間窗口（秒）
     *
     * @return bool true 表示請求允許，false 表示已達速率限制
     */
    public function isAllowed(string $ip, int $maxRequests = 60, int $timeWindow = 60): bool;
}
