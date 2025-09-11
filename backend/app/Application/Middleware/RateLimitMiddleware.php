<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use App\Infrastructure\Services\RateLimitService;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class RateLimitMiddleware implements MiddlewareInterface
{
    private RateLimitService $rateLimitService;

    /** @var array<string, mixed> */
    private array $config;

    public function __construct(RateLimitService $rateLimitService, array $config = [])
    {
        $this->rateLimitService = $rateLimitService;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri()->getPath();

        // 檢查是否需要跳過速率限制
        if (in_array($uri, $this->config['skip_paths'], true)) {
            return $handler->handle($request);
        }

        // 取得真實客戶端 IP
        $serverParams = $request->getServerParams();
        /** @var array<string, mixed> $serverParams */
        $ip = $this->getRealClientIP($serverParams);

        // 判斷操作類型
        $action = $this->determineAction($request);

        // 取得使用者 ID（如果已登入）
        $userId = $this->getUserId($request);

        // 檢查速率限制
        $maxRequestsConfig = $this->config['max_requests'] ?? 60;
        $timeWindowConfig = $this->config['time_window'] ?? 60;
        $maxRequests = is_int($maxRequestsConfig) ? $maxRequestsConfig : 60;
        $timeWindow = is_int($timeWindowConfig) ? $timeWindowConfig : 60;
        $result = $this->rateLimitService->checkLimit($ip, $maxRequests, $timeWindow);

        /** @var array<string, mixed> $result */
        if (!$result['allowed']) {
            return $this->createRateLimitResponse($result, $request);
        }

        // 設定速率限制標頭
        $response = $handler->handle($request);

        return $this->addRateLimitHeaders($response, $result);
    }

    /**
     * 判斷請求的操作類型.
     */
    private function determineAction(Request $request): string
    {
        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();

        // 登入相關 (最優先判斷)
        if (strpos($uri, '/auth/login') !== false) {
            return 'login';
        }

        if (strpos($uri, '/auth/register') !== false) {
            return 'register';
        }

        if (strpos($uri, '/auth/password-reset') !== false) {
            return 'password_reset';
        }

        // API 路由
        if (strpos($uri, '/api/') === 0) {
            return 'api';
        }

        // 內容建立
        if ($method == 'POST' && strpos($uri, '/posts') !== false) {
            return 'post_create';
        }

        // 預設
        return 'default';
    }

    /**
     * 取得使用者 ID.
     */
    private function getUserId(Request $request): ?int
    {
        // 從 request attributes 中取得使用者 ID
        $userId = $request->getAttribute('user_id');

        return $userId ? (int) $userId : null;
    }

    /**
     * 建立速率限制回應.
     */
    private function createRateLimitResponse(array $result, Request $request): ResponseInterface
    {
        // 判斷回應格式
        $acceptHeader = $request->getHeaderLine('Accept');
        $isJsonRequest = strpos($acceptHeader, 'application/json') !== false
            || strpos($request->getUri()->getPath(), '/api/') === 0;

        // 確保數值型別正確
        $resetTime = is_int($result['reset']) ? $result['reset'] : time();
        $limit = is_int($result['limit']) ? $result['limit'] : 0;
        $remaining = is_int($result['remaining']) ? $result['remaining'] : 0;

        if ($isJsonRequest) {
            $body = json_encode([
                'error' => 'Rate limit exceeded',
                'message' => '請求過於頻繁，請稍後再試',
                'limit' => $limit,
                'remaining' => $remaining,
                'reset' => $resetTime,
                'retry_after' => $resetTime - time(),
            ]) ?: '';

            $response = new Response(429, ['Content-Type' => 'application/json'], $body);
        } else {
            $body = $this->generateRateLimitHtml($result);
            $response = new Response(429, ['Content-Type' => 'text/html; charset=utf-8'], $body);
        }

        return $response
            ->withHeader('Retry-After', (string) ($resetTime - time()))
            ->withHeader('X-RateLimit-Limit', (string) $limit)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string) $resetTime);
    }

    /**
     * 添加速率限制標頭.
     */
    private function addRateLimitHeaders(ResponseInterface $response, /** @var array<string, mixed> */ array $result): ResponseInterface
    {
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $result['limit'])
            ->withHeader('X-RateLimit-Remaining', (string) $result['remaining'])
            ->withHeader('X-RateLimit-Reset', (string) $result['reset']);
    }

    /**
     * 產生速率限制 HTML 頁面.
     */
    private function generateRateLimitHtml(array $result): string
    {
        $resetTime = is_int($result['reset']) ? $result['reset'] : time();
        $limit = is_int($result['limit']) ? $result['limit'] : 0;
        $remaining = is_int($result['remaining']) ? $result['remaining'] : 0;
        $retryAfter = $resetTime - time();
        $retryTime = date('H:i:s', $resetTime);

        return <<<HTML
                        <!DOCTYPE html>
                        <html lang="zh-TW">
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>請求過於頻繁</title>
                            <style>
                                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                                       background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                       margin: 0; padding: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
                                .container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                                            max-width: 500px; text-align: center; }
                                .icon { font-size: 64px; margin-bottom: 20px; }
                                h1 { color: #2c3e50; margin-bottom: 20px; }
                                .message { color: #7f8c8d; line-height: 1.6; margin-bottom: 30px; }
                                .info { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px;
                                        padding: 20px; margin: 20px 0; }
                                .retry-info { text-align: center; margin-top: 30px; }
                                .countdown { font-size: 24px; font-weight: bold; color: #e74c3c; }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <div class="icon">⏱️</div>
                                <h1>請求過於頻繁</h1>
                                <div class="message">
                                    您的請求過於頻繁，為了確保服務的穩定性，我們暫時限制了您的存取。<br>
                                    請稍候再試，感謝您的理解。
                                </div>
                                <div class="info">
                                    <strong>限制資訊：</strong><br>
                                    • 每分鐘最多 {$limit} 次請求<br>
                                    • 剩餘配額：{$remaining} 次<br>
                                    • 重置時間：{$retryTime}
                                </div>
                                <div class="retry-info">
                                    <div class="countdown" id="countdown">{$retryAfter}</div>
                                    <p>秒後自動重新整理</p>
                                </div>
                            </div>
                            <script>
                                let countdown = {$retryAfter};
                                const element = document.getElementById('countdown');
                                const timer = setInterval(() => {
                                    countdown--;
                                    element.textContent = countdown;
                                    if (countdown <= 0) {
                                        clearInterval(timer);
                                        location.reload();
                                    }
                                }, 1000);
                            </script>
                        </body>
                        </html>
            HTML;
    }

    /**
     * 預設設定.
     */
    private function getDefaultConfig(): array
    {
        return [
            'skip_paths' => [
                '/health',
                '/status',
                '/favicon.ico',
            ],
        ];
    }

    /**
     * 取得真實的客戶端 IP 位址.
     */
    private function getRealClientIP(array $serverParams): string
    {
        // 檢查代理伺服器的標頭
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
        ];

        foreach ($headers as $header) {
            $headerValue = $serverParams[$header] ?? null;
            if (!empty($headerValue) && is_string($headerValue)) {
                $ips = explode(',', $headerValue);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';

        return is_string($remoteAddr) ? $remoteAddr : '127.0.0.1';
    }

    public function getPriority(): int
    {
        return 10; // 中等優先級
    }

    public function getName(): string
    {
        return 'rate-limit';
    }

    public function shouldProcess(Request $request): bool
    {
        $uri = $request->getUri()->getPath();

        // 檢查是否需要跳過速率限制
        return !in_array($uri, $this->config['skip_paths'], true);
    }
}
