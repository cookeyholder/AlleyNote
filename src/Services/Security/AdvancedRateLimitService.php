<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Services\CacheService;

class AdvancedRateLimitService
{
    private CacheService $cache;
    private array $config;
    private array $trustedProxies;

    public function __construct(CacheService $cache, array $config = [])
    {
        $this->cache = $cache;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->trustedProxies = $this->config['trusted_proxies'] ?? [];
    }

    /**
     * 檢查速率限制
     */
    public function checkLimit(
        string $identifier,
        string $action = 'default',
        ?int $userId = null
    ): array {
        // 取得此操作的限制設定
        $limits = $this->getLimitsForAction($action);

        // 如果有使用者 ID，優先使用使用者限制
        if ($userId !== null) {
            $userResult = $this->checkUserLimit($userId, $action, $limits['user']);
            if (!$userResult['allowed']) {
                return $userResult;
            }
        }

        // 檢查 IP 限制
        return $this->checkIPLimit($identifier, $action, $limits['ip']);
    }

    /**
     * 取得真實客戶端 IP
     */
    public function getRealClientIP(array $serverParams): string
    {
        // 檢查 X-Forwarded-For 標頭
        if (isset($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIPs = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            $clientIP = trim($forwardedIPs[0]);

            // 驗證來源是否為可信任的代理
            $remoteAddr = $serverParams['REMOTE_ADDR'] ?? '';
            if ($this->isTrustedProxy($remoteAddr) && filter_var($clientIP, FILTER_VALIDATE_IP)) {
                return $clientIP;
            }
        }

        // 檢查其他常見的代理標頭
        $proxyHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        ];

        foreach ($proxyHeaders as $header) {
            if (isset($serverParams[$header])) {
                $ip = trim($serverParams[$header]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * 檢查使用者限制
     */
    private function checkUserLimit(int $userId, string $action, array $limits): array
    {
        $key = "rate_limit:user:{$userId}:{$action}";
        return $this->performLimitCheck($key, $limits);
    }

    /**
     * 檢查 IP 限制
     */
    private function checkIPLimit(string $ip, string $action, array $limits): array
    {
        $key = "rate_limit:ip:{$ip}:{$action}";
        return $this->performLimitCheck($key, $limits);
    }

    /**
     * 執行限制檢查
     */
    private function performLimitCheck(string $key, array $limits): array
    {
        try {
            $data = $this->cache->get($key);
            $now = time();

            if ($data === null) {
                $data = [
                    'count' => 0,
                    'reset' => $now + $limits['window'],
                    'first_request' => $now
                ];
            }

            // 檢查是否需要重置計數器
            if ($now > $data['reset']) {
                $data = [
                    'count' => 0,
                    'reset' => $now + $limits['window'],
                    'first_request' => $now
                ];
            }

            // 檢查是否超過限制
            if ($data['count'] >= $limits['max_requests']) {
                return [
                    'allowed' => false,
                    'limit' => $limits['max_requests'],
                    'remaining' => 0,
                    'reset' => $data['reset'],
                    'window' => $limits['window'],
                    'action' => $key
                ];
            }

            // 增加請求計數
            $data['count']++;

            // 更新快取
            $this->cache->set($key, $data, $limits['window']);

            return [
                'allowed' => true,
                'limit' => $limits['max_requests'],
                'remaining' => max(0, $limits['max_requests'] - $data['count']),
                'reset' => $data['reset'],
                'window' => $limits['window'],
                'action' => $key
            ];
        } catch (\Exception $e) {
            // 如果快取服務不可用，記錄錯誤但允許請求
            error_log("Advanced rate limit check failed: " . $e->getMessage());
            return [
                'allowed' => true,
                'limit' => $limits['max_requests'],
                'remaining' => $limits['max_requests'],
                'reset' => time() + $limits['window'],
                'window' => $limits['window'],
                'action' => $key,
                'error' => 'Cache unavailable'
            ];
        }
    }

    /**
     * 取得操作的限制設定
     */
    private function getLimitsForAction(string $action): array
    {
        return $this->config['limits'][$action] ?? $this->config['limits']['default'];
    }

    /**
     * 檢查是否為可信任的代理
     */
    private function isTrustedProxy(string $ip): bool
    {
        if (empty($this->trustedProxies)) {
            return false;
        }

        foreach ($this->trustedProxies as $trustedProxy) {
            if ($this->ipInRange($ip, $trustedProxy)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查 IP 是否在指定範圍內
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range);

        if (
            !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
            !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        ) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * 清除特定鍵的限制
     */
    public function clearLimit(string $identifier, string $action = 'default', ?int $userId = null): void
    {
        if ($userId !== null) {
            $key = "rate_limit:user:{$userId}:{$action}";
            $this->cache->delete($key);
        }

        $key = "rate_limit:ip:{$identifier}:{$action}";
        $this->cache->delete($key);
    }

    /**
     * 取得限制狀態
     */
    public function getLimitStatus(string $identifier, string $action = 'default', ?int $userId = null): array
    {
        $limits = $this->getLimitsForAction($action);
        $status = ['ip' => null, 'user' => null];

        // IP 狀態
        $ipKey = "rate_limit:ip:{$identifier}:{$action}";
        $ipData = $this->cache->get($ipKey);
        if ($ipData) {
            $status['ip'] = [
                'count' => $ipData['count'],
                'limit' => $limits['ip']['max_requests'],
                'remaining' => max(0, $limits['ip']['max_requests'] - $ipData['count']),
                'reset' => $ipData['reset']
            ];
        }

        // 使用者狀態
        if ($userId !== null) {
            $userKey = "rate_limit:user:{$userId}:{$action}";
            $userData = $this->cache->get($userKey);
            if ($userData) {
                $status['user'] = [
                    'count' => $userData['count'],
                    'limit' => $limits['user']['max_requests'],
                    'remaining' => max(0, $limits['user']['max_requests'] - $userData['count']),
                    'reset' => $userData['reset']
                ];
            }
        }

        return $status;
    }

    /**
     * 預設設定
     */
    private function getDefaultConfig(): array
    {
        return [
            'trusted_proxies' => [
                '127.0.0.1',
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
                // Cloudflare IP ranges (部分)
                '103.21.244.0/22',
                '103.22.200.0/22',
                '103.31.4.0/22',
                '104.16.0.0/12',
                '108.162.192.0/18',
                '131.0.72.0/22'
            ],
            'limits' => [
                'default' => [
                    'ip' => ['max_requests' => 60, 'window' => 60],
                    'user' => ['max_requests' => 120, 'window' => 60]
                ],
                'login' => [
                    'ip' => ['max_requests' => 5, 'window' => 300],  // 5 attempts per 5 minutes
                    'user' => ['max_requests' => 3, 'window' => 300] // 3 attempts per 5 minutes
                ],
                'register' => [
                    'ip' => ['max_requests' => 3, 'window' => 3600], // 3 per hour
                    'user' => ['max_requests' => 1, 'window' => 3600] // 1 per hour
                ],
                'password_reset' => [
                    'ip' => ['max_requests' => 5, 'window' => 3600], // 5 per hour
                    'user' => ['max_requests' => 3, 'window' => 3600] // 3 per hour
                ],
                'post_create' => [
                    'ip' => ['max_requests' => 10, 'window' => 300], // 10 per 5 minutes
                    'user' => ['max_requests' => 20, 'window' => 300] // 20 per 5 minutes
                ],
                'api' => [
                    'ip' => ['max_requests' => 100, 'window' => 60], // 100 per minute
                    'user' => ['max_requests' => 200, 'window' => 60] // 200 per minute
                ]
            ]
        ];
    }
}
