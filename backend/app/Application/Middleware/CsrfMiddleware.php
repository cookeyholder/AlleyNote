<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Http\Response;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * CSRF 防護中介層.
 *
 * 工作流程：
 * 1. GET 請求：產生 CSRF Token 並透過 Set-Cookie 回傳
 * 2. POST/PUT/PATCH/DELETE 請求：驗證 X-CSRF-TOKEN header 是否與 Cookie 中的 token 一致
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
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        $method = $request->getMethod();

        // 安全方法不需要 CSRF 驗證，但仍需產生 token 供後續請求使用
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            $response = $handler->handle($request);
            return $this->attachCsrfCookie($request, $response);
        }

        // 狀態變更請求：驗證 CSRF Token
        $headerToken = $request->getHeaderLine('X-CSRF-TOKEN');
        $cookieToken = $this->getCookieToken($request);

        if (empty($headerToken) || empty($cookieToken) || !hash_equals($cookieToken, $headerToken)) {
            return new Response(
                statusCode: 403,
                headers: ['Content-Type' => 'application/json'],
                body: json_encode(['success' => false, 'error' => '無效的 CSRF Token', 'code' => 'CSRF_INVALID'], JSON_UNESCAPED_UNICODE),
            );
        }

        return $handler->handle($request);
    }

    /**
     * 從 Cookie 取得 CSRF Token.
     */
    private function getCookieToken(ServerRequestInterface $request): ?string
    {
        $cookies = $request->getCookieParams();
        $token = $cookies[self::COOKIE_NAME] ?? null;

        if (!is_string($token) || $token === '') {
            return null;
        }

        return $token;
    }

    /**
     * 在回應中附加 CSRF Token Cookie.
     */
    private function attachCsrfCookie(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $existingToken = $this->getCookieToken($request);

        if ($existingToken !== null) {
            return $response;
        }

        $newToken = bin2hex(random_bytes(self::TOKEN_LENGTH));

        return $response->withHeader('Set-Cookie', sprintf(
            '%s=%s; Path=/; HttpOnly; SameSite=Strict; Secure',
            self::COOKIE_NAME,
            $newToken
        ));
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
