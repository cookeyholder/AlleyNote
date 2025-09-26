<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Services\RateLimitService;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 文章瀏覽速率限制中介軟體.
 *
 * 專為文章瀏覽端點設計的輕量級速率限制，防止濫用同時保持高效能
 */
class PostViewRateLimitMiddleware implements MiddlewareInterface
{
    /** @var int 每個 IP 每分鐘最大請求數 */
    private const MAX_REQUESTS_PER_MINUTE = 120;

    /** @var int 每個認證使用者每分鐘最大請求數 */
    private const MAX_REQUESTS_PER_USER_PER_MINUTE = 300;

    /** @var int 時間窗口（秒） */
    private const TIME_WINDOW = 60;

    public function __construct(
        private readonly RateLimitService $rateLimitService,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);

        // 取得識別資訊
        $ip = $this->getRealClientIP($request);
        $userIdAttr = $request->getAttribute('user_id');
        $userId = null;
        if (is_numeric($userIdAttr)) {
            $userId = (int) $userIdAttr;
        }

        // 檢查速率限制
        $rateLimitResult = $this->checkRateLimit($ip, $userId);

        if (!$rateLimitResult['allowed']) {
            return $this->createRateLimitResponse($rateLimitResult);
        }

        // 處理請求
        $response = $handler->handle($request);

        // 添加速率限制標頭
        return $this->addRateLimitHeaders($response, $rateLimitResult, $startTime);
    }

    /**
     * 檢查速率限制.
     *
     * @return array{allowed: bool, limit: int, remaining: int, reset: int, key: string}
     */
    private function checkRateLimit(string $ip, ?int $userId): array
    {
        $now = time();

        if ($userId !== null) {
            // 認證使用者：使用較寬鬆的限制
            $key = "post_view_user_{$userId}";
            $limit = self::MAX_REQUESTS_PER_USER_PER_MINUTE;
        } else {
            // 匿名使用者：按 IP 限制
            $key = "post_view_ip_{$ip}";
            $limit = self::MAX_REQUESTS_PER_MINUTE;
        }

        $result = $this->rateLimitService->checkLimit($key, $limit, self::TIME_WINDOW);

        // 確保返回類型正確
        $allowed = $result['allowed'] ?? false;
        $remaining = $result['remaining'] ?? 0;
        $reset = $result['reset'] ?? ($now + self::TIME_WINDOW);

        return [
            'allowed' => is_bool($allowed) ? $allowed : false,
            'limit' => $limit,
            'remaining' => is_numeric($remaining) ? (int) $remaining : 0,
            'reset' => is_numeric($reset) ? (int) $reset : ($now + self::TIME_WINDOW),
            'key' => $key,
        ];
    }

    /**
     * 建立速率限制回應.
     */
    private function createRateLimitResponse(array $rateLimitResult): ResponseInterface
    {
        $resetTime = $rateLimitResult['reset'];
        $retryAfter = max(1, is_numeric($resetTime) ? (int) $resetTime - time() : 60);

        $data = [
            'success' => false,
            'error' => [
                'message' => '請求過於頻繁，請稍後再試',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
            ],
            'meta' => [
                'rate_limit' => [
                    'limit' => $rateLimitResult['limit'],
                    'remaining' => 0,
                    'reset' => $resetTime,
                ],
            ],
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{"success":false,"error":{"message":"Rate limit exceeded"}}';
        }

        $limitStr = is_numeric($rateLimitResult['limit']) ? (string) $rateLimitResult['limit'] : '100';
        $resetStr = is_numeric($resetTime) ? (string) $resetTime : (string) (time() + 60);

        return new Response(429, [
            'Content-Type' => 'application/json',
            'Retry-After' => (string) $retryAfter,
            'X-RateLimit-Limit' => $limitStr,
            'X-RateLimit-Remaining' => '0',
            'X-RateLimit-Reset' => $resetStr,
        ], $json);
    }

    /**
     * 添加速率限制標頭.
     */
    private function addRateLimitHeaders(
        ResponseInterface $response,
        array $rateLimitResult,
        float $startTime,
    ): ResponseInterface {
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);

        $limitStr = is_numeric($rateLimitResult['limit']) ? (string) $rateLimitResult['limit'] : '100';
        $remainingStr = is_numeric($rateLimitResult['remaining']) ? (string) $rateLimitResult['remaining'] : '0';
        $resetStr = is_numeric($rateLimitResult['reset']) ? (string) $rateLimitResult['reset'] : (string) (time() + 60);

        return $response
            ->withHeader('X-RateLimit-Limit', $limitStr)
            ->withHeader('X-RateLimit-Remaining', $remainingStr)
            ->withHeader('X-RateLimit-Reset', $resetStr)
            ->withHeader('X-Processing-Time', "{$processingTime}ms");
    }

    /**
     * 取得真實的客戶端 IP 位址.
     */
    private function getRealClientIP(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        // 檢查代理伺服器的標頭
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        foreach ($headers as $header) {
            if (!empty($serverParams[$header]) && is_string($serverParams[$header])) {
                $ips = array_map('trim', explode(',', $serverParams[$header]));
                foreach ($ips as $ip) {
                    // 驗證 IP 並排除私有和保留地址
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }

        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';

        return is_string($remoteAddr) ? $remoteAddr : '127.0.0.1';
    }
}
