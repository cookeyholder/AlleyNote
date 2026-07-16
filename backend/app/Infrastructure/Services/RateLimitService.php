<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domains\Security\Contracts\RateLimitServiceInterface;
use Throwable;

class RateLimitService implements RateLimitServiceInterface
{
    public function __construct(
        private readonly CacheService $cache,
    ) {}

    public function checkLimit(string $ip, int $maxRequests = 60, int $timeWindow = 60): array
    {
        $key = "rate_limit:{$ip}";

        try {
            /** @var array{count: int, reset: int}|null $data */
            $data = $this->cache->get($key);
            if ($data === null) {
                $data = ['count' => 0, 'reset' => time() + $timeWindow];
            }
            $reset = (int) $data['reset'];
            $count = (int) $data['count'];
            // 檢查是否需要重置計數器
            if (time() > $reset) {
                $data = ['count' => 0, 'reset' => time() + $timeWindow];
                $count = 0;
                $reset = time() + $timeWindow;
            }
            // 如果已經超過限制，直接回傳結果
            if ($count >= $maxRequests) {
                return [
                    'allowed'   => false,
                    'remaining' => 0,
                    'reset'     => $reset,
                ];
            }
            // 增加請求計數
            $count++;
            $data['count'] = $count;
            // 更新快取
            $this->cache->set($key, $data, $timeWindow);

            return [
                'allowed'   => $count <= $maxRequests,
                'remaining' => max(0, $maxRequests - $count),
                'reset'     => $reset,
            ];
        } catch (Throwable $e) {
            // 如果快取服務不可用，拒絕請求（fail-closed）
            app_log('error', '速率限制檢查失敗', ['exception' => $e->getMessage()]);

            return [
                'allowed'   => false,
                'remaining' => 0,
                'reset'     => time() + $timeWindow,
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
