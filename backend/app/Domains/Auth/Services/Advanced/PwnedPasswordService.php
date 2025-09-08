<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Advanced;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Pwned Password 服務。
 *
 * 整合 Have I Been Pwned API 來檢查密碼是否曾在資料外洩中出現
 */
class PwnedPasswordService
{
    private const HIBP_API_URL = 'https://api.pwnedpasswords.com/range/';

    private const REQUEST_TIMEOUT = 5; // 5 秒超時

    private const CACHE_TTL = 86400; // 24 小時快取

    private Client $httpClient;

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => self::REQUEST_TIMEOUT,
            'headers' => [
                'User-Agent' => 'AlleyNote-Password-Checker/1.0',
            ],
        ]);
    }

    /**
     * 檢查密碼是否被 Pwned。
     */
    public function isPwned(string $password): bool
    {
        try {
            $count = $this->getPasswordCount($password);
            return $count > 0;
        } catch (Exception $e) {
            // 如果 API 失敗，出於安全考量回傳 false（允許密碼）
            error_log('Pwned password check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 取得密碼在資料外洩中出現的次數。
     */
    public function getPasswordCount(string $password): int
    {
        if (empty($password)) {
            return 0;
        }

        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        $hashList = $this->fetchHashesFromApi($prefix);
        if ($hashList === null) {
            return 0;
        }

        return $this->findHashInList($suffix, $hashList);
    }

    /**
     * 檢查密碼強度並提供建議。
     *
     * @return array<string, mixed>
     */
    public function checkPasswordSecurity(string $password): array
    {
        $result = [
            'is_pwned' => false,
            'pwned_count' => 0,
            'risk_level' => 'low',
            'recommendations' => [],
        ];

        try {
            $count = $this->getPasswordCount($password);
            $result['pwned_count'] = $count;
            $result['is_pwned'] = $count > 0;

            if ($count > 0) {
                $result['risk_level'] = $this->calculateRiskLevel($count);
                $result['recommendations'] = $this->generateRecommendations($count);
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            error_log('Password security check failed: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * 從 HIBP API 取得雜湊值列表。
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
            error_log('HIBP API request failed: ' . $e->getMessage());
            return null;
        } catch (Exception $e) {
            error_log('Unexpected error during HIBP API call: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 在雜湊列表中查找指定的後綴。
     */
    private function findHashInList(string $suffix, string $hashList): int
    {
        $lines = explode("\n", $hashList);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = explode(':', $line);
            if (count($parts) !== 2) {
                continue;
            }

            [$hashSuffix, $count] = $parts;
            if (strtoupper($hashSuffix) === strtoupper($suffix)) {
                return (int) $count;
            }
        }

        return 0;
    }

    /**
     * 計算風險等級。
     */
    private function calculateRiskLevel(int $count): string
    {
        if ($count >= 100000) {
            return 'critical';
        }

        if ($count >= 10000) {
            return 'high';
        }

        if ($count >= 1000) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * 生成安全建議。
     *
     * @return array<string>
     */
    private function generateRecommendations(int $count): array
    {
        $recommendations = [
            '這個密碼已在資料外洩中出現，強烈建議更換',
            '使用至少 12 個字符的複雜密碼',
            '結合大小寫字母、數字和特殊符號',
            '避免使用個人資訊或常見詞彙',
            '考慮使用密碼管理器生成隨機密碼',
        ];

        if ($count >= 100000) {
            array_unshift($recommendations, '⚠️ 極高風險：此密碼極其常見，請立即更換');
        } elseif ($count >= 10000) {
            array_unshift($recommendations, '⚠️ 高風險：此密碼經常被使用，安全性極低');
        }

        return $recommendations;
    }

    /**
     * 檢查服務是否可用。
     */
    public function isServiceAvailable(): bool
    {
        try {
            $response = $this->httpClient->get(self::HIBP_API_URL . '00000');
            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 取得服務統計資訊。
     *
     * @return array<string, mixed>
     */
    public function getServiceStats(): array
    {
        return [
            'api_url' => self::HIBP_API_URL,
            'timeout' => self::REQUEST_TIMEOUT,
            'cache_ttl' => self::CACHE_TTL,
            'service_available' => $this->isServiceAvailable(),
            'last_check' => date('Y-m-d H:i:s'),
        ];
    }
}
