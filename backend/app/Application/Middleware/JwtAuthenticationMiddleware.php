<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\TokenExpiredException;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use App\Shared\Helpers\NetworkHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class JwtAuthenticationMiddleware implements MiddlewareInterface
{
    private const DEFAULT_PRIORITY = 10;

    private const MIDDLEWARE_NAME = 'jwt-auth';

    public function __construct(
        private readonly JwtTokenServiceInterface $jwtTokenService,
        private int $priority = self::DEFAULT_PRIORITY,
        private bool $enabled = true,
    ) {}

    /**
     * 處理 JWT 認證請求.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$this->enabled) {
            return $handler->handle($request);
        }
        if (!$this->shouldProcess($request)) {
            return $handler->handle($request);
        }

        try {
            // 1. 提取 JWT token
            $accessToken = $this->extractToken($request);
            if ($accessToken === null) {
                return $this->createUnauthorizedResponse('缺少有效的認證 Token');
            }
            // 2. 驗證 token 有效性（包含黑名單檢查）
            $payload = $this->jwtTokenService->validateAccessToken($accessToken);
            // 3. 執行額外的安全性檢查
            $this->performSecurityChecks($request, $payload);
            // 4. 將使用者資訊注入到請求中
            $request = $this->injectUserContext($request, $payload, $accessToken);
        } catch (TokenExpiredException $e) {
            return $this->createUnauthorizedResponse('Token 已過期', 'TOKEN_EXPIRED');
        } catch (InvalidTokenException $e) {
            return $this->createUnauthorizedResponse('Token 無效', 'TOKEN_INVALID');
        } catch (Throwable $e) {
            if (function_exists('app_log')) {
                app_log('error', 'JWT authentication failed', [
                    'exception' => $e->getMessage(),
                    'exception_class' => $e::class,
                    'path' => $request->getUri()->getPath(),
                ]);
            }

            $isProduction = (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development')) === 'production';
            $message = $isProduction ? '認證驗證失敗' : ('認證驗證失敗: ' . $e->getMessage());

            return $this->createUnauthorizedResponse($message, 'AUTH_FAILED');
        }

        // 5. 繼續執行後續中介軟體（認證成功後）
        return $handler->handle($request);
    }

    /**
     * 從請求中提取 JWT token.
     */
    private function extractToken(ServerRequestInterface $request): ?string
    {
        // 1. 從 Authorization header 提取
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            if (!empty($token)) {
                return $token;
            }
        }
        // 2. 從 Cookie 提取 (access_token)
        $cookies = $request->getCookieParams();
        $cookieToken = $cookies['access_token'] ?? null;
        if (!empty($cookieToken) && is_string($cookieToken)) {
            return $cookieToken;
        }

        return null;
    }

    /**
     * 執行額外的安全性檢查.
     */
    private function performSecurityChecks(ServerRequestInterface $request, JwtPayload $payload): void
    {
        // 1. IP 地址驗證（可配置，預設關閉以免影響行動網路/NAT 使用者）
        $enableIpBinding = filter_var(
            getenv('JWT_IP_BINDING_ENABLED') ?: ($_ENV['JWT_IP_BINDING_ENABLED'] ?? 'false'),
            FILTER_VALIDATE_BOOLEAN,
        );
        if ($enableIpBinding) {
            $tokenIpAddress = $payload->getCustomClaim('ip_address');
            if ($tokenIpAddress !== null) {
                $currentIp = NetworkHelper::getClientIp($request, NetworkHelper::getTrustedProxies());
                if ($tokenIpAddress !== $currentIp) {
                    throw new InvalidTokenException('Token 的 IP 地址不匹配');
                }
            }
        }
    }

    /**
     * 將使用者資訊注入到請求中.
     */
    private function injectUserContext(
        ServerRequestInterface $request,
        JwtPayload $payload,
        string $accessToken,
    ): ServerRequestInterface {
        return $request
            ->withAttribute('jwt_payload', $payload)
            ->withAttribute('access_token', $accessToken)
            ->withAttribute('user_id', $payload->getUserId())
            ->withAttribute('username', $payload->getCustomClaim('username'))
            ->withAttribute('email', $payload->getCustomClaim('email'))
            ->withAttribute('role', $payload->getCustomClaim('role'))
            ->withAttribute('permissions', $payload->getCustomClaim('permissions') ?? [])
            ->withAttribute('authenticated', true);
    }

    /**
     * 建立未授權的回應.
     */
    private function createUnauthorizedResponse(string $message, string $code = 'UNAUTHORIZED'): ResponseInterface
    {
        $responseData = [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('c'),
        ];

        return new Response(
            statusCode: 401,
            headers: [
                'Content-Type' => 'application/json',
                'WWW-Authenticate' => 'Bearer realm="API"',
            ],
            body: json_encode($responseData, JSON_UNESCAPED_UNICODE) ?: '',
        );
    }

    /**
     * 檢查是否應該處理此請求.
     */
    public function shouldProcess(ServerRequestInterface $request): bool
    {
        if (!$this->enabled) {
            return false;
        }
        $skipPaths = [
            '/auth/login',
            '/auth/register',
            '/auth/refresh',
            '/health',
            '/status',
            '/favicon.ico',
        ];
        $path = $request->getUri()->getPath();
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return false;
            }
        }

        return str_starts_with($path, '/api/') || str_starts_with($path, '/auth/me');
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getName(): string
    {
        return self::MIDDLEWARE_NAME;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
