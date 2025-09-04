<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Advanced;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PwnedPasswordService
{
    private const HIBP_API_URL = 'https://api.pwnedpasswords.com/range/';

    private const REQUEST_TIMEOUT = 5; // 5 秒超時

    private const CACHE_TTL = 86400; // 24 小時快取

    private Client $httpClient;

    private ?array $cache = null;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => self::REQUEST_TIMEOUT,
            'headers' => [
                'User-Agent' => 'AlleyNote/1.0 Security Service',
                'Accept' => 'text/plain',
            ],
        ]);
    }

    /**
     * 檢查密碼是否在已知的洩露資料庫中.
     *
     * @param string $password 要檢查的密碼
     * @return array 包含是_leaked, count, error 等資訊的陣列
     */
    public function isPasswordPwned(string $password): array
    {
        try {
            // 計算密碼的 SHA-1 雜湊值
            $sha1Hash = strtoupper(sha1($password));
            $prefix = substr($sha1Hash, 0, 5);
            $suffix = substr($sha1Hash, 5);

            // 檢查快取
            $cacheKey = "pwned_prefix_{$prefix}";
            if ($this->isInCache($cacheKey)) {
                $hashList = $this->getFromCache($cacheKey);
            } else {
                // 呼叫 HIBP API
                $hashList = $this->fetchHashesFromApi($prefix);
                if ($hashList !== null) {
                    $this->setCache($cacheKey, $hashList);
                }
            }

            if ($hashList === null) {
                return [
                    'is_leaked' => false,
                    'count' => 0,
                    'error' => 'API 呼叫失敗，無法驗證密碼安全性',
                    'api_available' => false,
                ];
            }

            // 在回傳的雜湊列表中查找
            $count = $this->findHashInList($suffix, $hashList);

            return [
                'is_leaked' => $count > 0,
                'count' => $count,
                'error' => null,
                'api_available' => true,
            ];
        } catch (Exception $e) {
            // 記錄錯誤但不阻止使用者操作
            error_log('PwnedPasswordService error: ' . $e->getMessage());

            return [
                'is_leaked' => false,
                'count' => 0,
                'error' => 'Unable to check password against breach database',
                'api_available' => false,
            ];
        }
    }

    /**
     * 從 HIBP API 取得雜湊值列表.
     */
    private function fetchHashesFromApi(string $prefix): ?string
    {
        try {
            $response = $this->httpClient->get(self::HIBP_API_URL . $prefix);

            if ($response->getStatusCode() === 200) {
                return $response->getBody()->getContents();
            }

            return null;
        } catch (RequestException $e) {
            // 網路或 API 錯誤
            error_log('HIBP API request failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * 在雜湊列表中查找指定的後綴.
     */
    private function findHashInList(string $suffix, string $hashList): int
    {
        $lines = explode("\r\n", $hashList);

        foreach ($lines as $line) {
            $parts = explode(':', $line);
            if (count($parts) === 2 && $parts[0] === $suffix) {
                return (int) $parts[1];
            }
        }

        return 0;
    }

    /**
     * 簡單的記憶體快取實作.
     */
    private function isInCache(string $key): bool
    {
        return isset($this->cache[$key])
            && time() - $this->cache[$key]['timestamp'] < self::CACHE_TTL;
    }

    private function getFromCache(string $key): ?string
    {
        return $this->cache[$key]['data'] ?? null;
    }

    private function setCache(string $key, string $data): void
    {
        $this->cache[$key] = [
            'data' => $data,
            'timestamp' => time(),
        ];
    }

    /**
     * 清除快取.
     */
    public function clearCache(): void
    {
        $this->cache = null;
    }

    /**
     * 取得 API 狀態.
     */
    public function getApiStatus(): array
    {
        try {
            $response = $this->httpClient->get(self::HIBP_API_URL . '00000');

            return [
                'available' => $response->getStatusCode() === 200,
                'response_time' => null, // 可以實作回應時間測量
            ];
        } catch (Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 批次檢查多個密碼
     */
    public function checkMultiplePasswords(array $passwords): array
    {
        $results = [];

        foreach ($passwords as $index => $password) {
            $results[$index] = $this->isPasswordPwned($password);

            // 加入小延遲避免 API 限制
            if (count($passwords) > 1) {
                usleep(100000); // 0.1 秒
            }
        }

        return $results;
    }
}
