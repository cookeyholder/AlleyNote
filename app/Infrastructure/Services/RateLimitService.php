<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use Exception;

class RateLimitService
{
    public function __construct(
        private readonly CacheService $cache,
    ) {}

    public function checkLimit(string $ip, int $maxRequests = 60, int $timeWindow = 60): array
    {
        $key = "rate_limit:{$ip}";

        try {
            $data = $this->cache->get($key);
            if ($data === null) {
                $data = ['count' => 0, 'reset' => time() + $timeWindow];
            }

            // 檢查是否需要重置計數器
            if (time() > $data['reset']) {
                $data = ['count' => 0, 'reset' => time() + $timeWindow];
            }

            // 如果已經超過限制，直接回傳結果
            if ($data['count'] >= $maxRequests) {
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'reset' => $data['reset'],
                ];
            }

            // 增加請求計數
            $data['count']++;

            // 更新快取
            $this->cache->set($key, $data, $timeWindow);

            return [
                'allowed' => $data['count'] <= $maxRequests,
                'remaining' => max(0, $maxRequests - $data['count']),
                'reset' => $data['reset'],
            ];
        } catch (Exception $e) {
            // 如果快取服務不可用，預設允許請求
            error_log('速率限制檢查失敗: ' . $e->getMessage());

            return [
                'allowed' => true,
                'remaining' => $maxRequests,
                'reset' => time() + $timeWindow,
            ];
        }
    }

    /**
     * 檢查請求是否被允許（簡化版本的 checkLimit）.
     */
    public function isAllowed(string $ip, int $maxRequests = 60, int $timeWindow = 60): bool
    {
        $result = $this->checkLimit($ip, $maxRequests, $timeWindow);

        return $result['allowed'];
    }
}
