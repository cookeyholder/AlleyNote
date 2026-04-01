<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Http\Response;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * CSRF 防護中介層.
 *
 * 使用 Double-Submit Cookie 模式：
 * 1. 任何請求都會在回應中設定 CSRF Cookie（若尚未存在）
 * 2. 狀態變更請求必須在 X-CSRF-TOKEN header 中攜帶與 Cookie 相同的值
 *
 * 不需要伺服器端 session，適合無狀態 API。
 */
class CsrfMiddleware implements MiddlewareInterface
{
    private const DEFAULT_PRIORITY = 15;
    private const MIDDLEWARE_NAME = 'csrf';
    private const COOKIE_NAME = 'csrf_token';
    private const TOKEN_LENGTH = 32;

    public function __construct(
        private int $priority = self::DEFAULT_PRIORITY,
        private bool $enabled = true,
        private ?LoggerInterface $logger = null,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        $method = $request->getMethod();

        // 安全方法：直接通過，但附加 CSRF Cookie
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            $response = $handler->handle($request);
            return $this->attachCsrfCookie($request, $response);
        }

        // 狀態變更請求：驗證 Double-Submit Cookie
        $headerToken = $request->getHeaderLine('X-CSRF-TOKEN');
        $cookieToken = $this->getCookieToken($request);

        if ($headerToken === '' || $cookieToken === '' || !hash_equals($cookieToken, $headerToken)) {
            $this->logger?->warning('CSRF validation failed', [
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                'method' => $method,
                'path' => $request->getUri()->getPath(),
                'reason' => $headerToken === '' ? 'missing_header_token' : ($cookieToken === '' ? 'missing_cookie_token' : 'token_mismatch'),
            ]);

            // 即使驗證失敗也附加 CSRF Cookie，讓前端可以取得 token 重試
            $errorResponse = new Response(
                statusCode: 403,
                headers: ['Content-Type' => 'application/json'],
                body: json_encode([
                    'success' => false,
                    'error' => 'CSRF Token 驗證失敗',
                    'code' => 'CSRF_INVALID',
                ], JSON_UNESCAPED_UNICODE),
            );
            return $this->attachCsrfCookie($request, $errorResponse);
        }

        // 驗證通過，繼續處理請求並確保回應中仍有 CSRF Cookie
        $response = $handler->handle($request);
        return $this->attachCsrfCookie($request, $response);
    }

    /**
     * 從 Cookie 取得 CSRF Token.
     */
    private function getCookieToken(ServerRequestInterface $request): string
    {
        $cookies = $request->getCookieParams();
        $token = $cookies[self::COOKIE_NAME] ?? '';

        return is_string($token) ? $token : '';
    }

    /**
     * 在回應中附加 CSRF Token Cookie（若 Cookie 尚未存在）.
     */
    private function attachCsrfCookie(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // 若 Cookie 中已有 token，不需要重新產生
        if ($this->getCookieToken($request) !== '') {
            return $response;
        }

        $newToken = bin2hex(random_bytes(self::TOKEN_LENGTH));

        // 使用 Set-Cookie header 附加 CSRF token
        // 不設定 HttpOnly 讓前端 JS 可以讀取（以便放入 X-CSRF-TOKEN header）
        // SameSite=Strict 防止跨站請求攜帶 Cookie
        // Secure 僅在 HTTPS 連線下傳輸（生產環境必備）
        $cookieValue = sprintf(
            '%s=%s; Path=/; SameSite=Strict; Secure',
            self::COOKIE_NAME,
            $newToken
        );

        // 保留既有 Set-Cookie headers
        $existingCookies = $response->getHeader('Set-Cookie');
        $existingCookies[] = $cookieValue;

        return $response->withHeader('Set-Cookie', $existingCookies);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getName(): string
    {
        return self::MIDDLEWARE_NAME;
    }

    public function shouldProcess(ServerRequestInterface $request): bool
    {
        return $this->enabled;
    }
}
