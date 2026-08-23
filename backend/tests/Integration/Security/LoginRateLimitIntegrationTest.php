<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Domains\Security\Contracts\RateLimitServiceInterface;
use App\Shared\Contracts\CacheServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\AuthApiIntegrationTestCase;

/**
 * 登入端點速率限制整合測試.
 *
 * 以完整應用程式堆疊驗證 RateLimitMiddleware 對認證端點的防護：
 * - 同一 IP 超量請求觸發 HTTP 429 與 Retry-After 標頭
 * - 不同 IP 的計數器互相獨立
 * - 時間窗口過期後計數器重置、請求恢復放行
 * - RateLimitService 搭配容器快取（記憶體驅動，契約同 Redis）的計數行為
 *
 * 快取驅動在測試環境為每個 Application 實例獨立的記憶體快取，
 * 且基底類別於 setUp／tearDown 清除快取，故不會汙染其他測試。
 */
#[Group('integration')]
#[Group('api')]
#[Group('security')]
#[Group('rate-limit')]
final class LoginRateLimitIntegrationTest extends AuthApiIntegrationTestCase
{
    /** RateLimitMiddleware 預設每分鐘最大請求數 */
    private const MAX_REQUESTS = 60;

    private const TARGET_IP = '203.0.113.10';

    /**
     * 送出一筆缺少密碼欄位的登入請求（快速回 400，不觸發認證服務）.
     */
    private function sendLoginRequest(string $ip): ResponseInterface
    {
        return $this->request('POST', '/api/auth/login', [
            'email' => 'ratelimit@example.com',
        ], ip: $ip);
    }

    /**
     * 將指定 IP 的速率限制計數器打滿並回傳超量後的第一筆回應.
     *
     * @return array<int, ResponseInterface> 前段允許的最後一筆與被拒絕的第一筆
     */
    private function exhaustLoginRateLimit(string $ip): array
    {
        $lastAllowed = null;
        for ($i = 0; $i < self::MAX_REQUESTS; $i++) {
            $lastAllowed = $this->sendLoginRequest($ip);
            $this->assertNotSame(
                429,
                $lastAllowed->getStatusCode(),
                '第 ' . ($i + 1) . " 筆請求不應被限流：{$lastAllowed->getBody()}",
            );
        }
        $rejected = $this->sendLoginRequest($ip);

        return [$lastAllowed, $rejected];
    }

    /**
     * 測試同一 IP 超量登入請求會收到 429 與限流標頭及 JSON 錯誤內容.
     */
    public function testExcessiveLoginRequestsTrigger429WithHeaders(): void
    {
        [, $rejected] = $this->exhaustLoginRateLimit(self::TARGET_IP);

        $this->assertSame(429, $rejected->getStatusCode(), '超量請求應被限流');
        $this->assertSame((string) self::MAX_REQUESTS, $rejected->getHeaderLine('X-RateLimit-Limit'));
        $this->assertSame('0', $rejected->getHeaderLine('X-RateLimit-Remaining'));

        $retryAfter = $rejected->getHeaderLine('Retry-After');
        $this->assertNotSame('', $retryAfter, '429 回應必須包含 Retry-After 標頭');
        $this->assertGreaterThanOrEqual(0, (int) $retryAfter);
        $this->assertLessThanOrEqual(self::MAX_REQUESTS, (int) $retryAfter);

        $data = $this->getJson($rejected);
        $this->assertSame('Rate limit exceeded', $data['error'] ?? '', '429 內容應說明超出速率限制');
        $this->assertSame(self::MAX_REQUESTS, $data['limit'] ?? 0);
        $this->assertSame(0, $data['remaining'] ?? -1);
        $this->assertGreaterThan(0, $data['reset'] ?? 0);
    }

    /**
     * 測試限流前的回應帶有遞減的剩餘次數標頭.
     */
    public function testAllowedResponsesCarryDecreasingRemainingHeader(): void
    {
        $response = $this->sendLoginRequest(self::TARGET_IP);

        $this->assertNotSame(429, $response->getStatusCode());
        $this->assertSame((string) self::MAX_REQUESTS, $response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertSame((string) (self::MAX_REQUESTS - 1), $response->getHeaderLine('X-RateLimit-Remaining'));
        $this->assertNotSame('', $response->getHeaderLine('X-RateLimit-Reset'));
    }

    /**
     * 測試不同 IP 的速率限制計數器互相獨立.
     */
    public function testRateLimitCountersAreIndependentPerClientIp(): void
    {
        $otherIp = '203.0.113.11';
        $this->exhaustLoginRateLimit(self::TARGET_IP);

        $response = $this->sendLoginRequest($otherIp);

        $this->assertNotSame(
            429,
            $response->getStatusCode(),
            '其他 IP 不應受到已耗盡 IP 的限流影響：' . $response->getBody(),
        );
    }

    /**
     * 測試時間窗口過期後計數器重置且請求恢復放行.
     */
    public function testCounterResetsAfterTimeWindowExpires(): void
    {
        $this->exhaustLoginRateLimit(self::TARGET_IP);

        // 直接透過容器快取種入一筆已過期的窗口資料，模擬時間流逝
        $cache = $this->app->getContainer()->get(CacheServiceInterface::class);
        $this->assertInstanceOf(CacheServiceInterface::class, $cache);
        $seeded = $cache->set(
            'rate_limit:' . self::TARGET_IP,
            ['count' => self::MAX_REQUESTS, 'reset' => time() - 1],
            60,
        );
        $this->assertTrue($seeded, '種入過期快取資料失敗');

        $response = $this->sendLoginRequest(self::TARGET_IP);

        $this->assertNotSame(
            429,
            $response->getStatusCode(),
            '時間窗口過期後應重新放行：' . $response->getBody(),
        );
    }

    /**
     * 測試 RateLimitService 搭配容器快取的計數遞減與恢復行為.
     *
     * 容器預設快取驅動為記憶體，其與 Redis 驅動共用同一組
     * CacheServiceInterface 契約，因此此處同步驗證計數語意。
     */
    public function testRateLimitServiceCountsDownAndRecoversWithRealCache(): void
    {
        $service = $this->app->getContainer()->get(RateLimitServiceInterface::class);
        $this->assertInstanceOf(RateLimitServiceInterface::class, $service);
        $ip = '203.0.113.12';

        $first = $service->checkLimit($ip, 3, 60);
        $second = $service->checkLimit($ip, 3, 60);
        $third = $service->checkLimit($ip, 3, 60);
        $fourth = $service->checkLimit($ip, 3, 60);

        $this->assertTrue($first['allowed'], '第一次請求應被允許');
        $this->assertSame(2, $first['remaining']);
        $this->assertTrue($second['allowed']);
        $this->assertSame(1, $second['remaining']);
        $this->assertTrue($third['allowed'], '達到上限額度的那次請求仍應被允許');
        $this->assertSame(0, $third['remaining']);
        $this->assertFalse($fourth['allowed'], '超出上限後應被拒絕');
        $this->assertSame(0, $fourth['remaining']);

        // 種入過期窗口，驗證計數器會重置
        $cache = $this->app->getContainer()->get(CacheServiceInterface::class);
        $this->assertInstanceOf(CacheServiceInterface::class, $cache);
        $cache->set('rate_limit:' . $ip, ['count' => 3, 'reset' => time() - 1], 60);
        $recovered = $service->checkLimit($ip, 3, 60);
        $this->assertTrue($recovered['allowed'], '窗口過期後應重新允許請求');
        $this->assertSame(2, $recovered['remaining']);

        // 不同 IP 的計數不受影響
        $freshIpResult = $service->checkLimit('203.0.113.13', 3, 60);
        $this->assertTrue($freshIpResult['allowed']);
        $this->assertSame(2, $freshIpResult['remaining']);
    }
}
