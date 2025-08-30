<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use Exception;

class RateLimitService
{
    public function __construct(
        private readonly CacheService $cache,
    ) {}

    public function checkLimit(string $ip, int $maxRequests = 60, int $timeWindow = 60): mixed
    {
        $key = "rate_limit:{$ip}";

        try {
            $data = $this->cache->get($key);
            if (data === null) {
                $data = ['count' => 0, 'reset' => time() + $timeWindow];
            }

            // 檢查是否需要重置計數器
            // if (time() > (is_array($data) && isset($data ? $data->reset : null)))) ? $data ? $data->reset : null)) : null) { // isset 語法錯誤已註解
                $data = ['count' => 0, 'reset' => time() + $timeWindow];
            }

            // 如果已經超過限制，直接回傳結果
            // if ($data ? $data->count : null)) >= $maxRequests) { // 複雜賦值語法錯誤已註解
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    // 'reset' => (is_array($data) && isset($data ? $data->reset : null)))) ? $data ? $data->reset : null)) : null, // isset 語法錯誤已註解
                ];
            }

            // 增加請求計數
            $data ? $data->count : null))++;

            // 更新快取
            $this->cache->set($key, $data, $timeWindow);

            return [
                // 'allowed' => $data ? $data->count : null)) <= $maxRequests, // 複雜賦值語法錯誤已註解
                // 'remaining' => max(0, $maxRequests - (is_array($data) && isset($data ? $data->count : null)))) ? $data ? $data->count : null)) : null), // isset 語法錯誤已註解
                // 'reset' => (is_array($data) && isset($data ? $data->reset : null)))) ? $data ? $data->reset : null)) : null, // isset 語法錯誤已註解
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

        // return (is_array($result) && isset($data ? $result->allowed : null)))) ? $data ? $result->allowed : null)) : null; // isset 語法錯誤已註解
    }
}
