<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Exceptions\InvalidTokenException;
use AlleyNote\Domains\Auth\Exceptions\TokenExpiredException;
use AlleyNote\Domains\Auth\ValueObjects\JwtPayload;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Exception;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JWT 認證中介軟體.
 *
 * 負責驗證 JWT token 的有效性，並將使用者資訊注入到請求中。
 * 支援從 Authorization header、query 參數或 cookie 提取 token。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class JwtAuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * 中介軟體優先順序（數值越小優先級越高）.
     */
    private const DEFAULT_PRIORITY = 10;

    /**
     * 中介軟體名稱.
     */
    private const MIDDLEWARE_NAME = 'jwt-auth';

    public function __construct(
        private JwtTokenServiceInterface $jwtTokenService,
        private int $priority = self::DEFAULT_PRIORITY,
        private bool $enabled = true,
    ) {}

    /**
     * 處理 JWT 認證請求.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param RequestHandlerInterface $handler 請求處理器
     * @return ResponseInterface HTTP 回應物件
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$this->enabled || !$this->shouldProcess($request)) {
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

            // 5. 繼續執行後續中介軟體
            return $handler->handle($request);
        } catch (TokenExpiredException $e) {
            return $this->createUnauthorizedResponse('Token 已過期', 'TOKEN_EXPIRED');
        } catch (InvalidTokenException $e) {
            return $this->createUnauthorizedResponse('Token 無效', 'TOKEN_INVALID');
        } catch (Exception $e) {
            return $this->createUnauthorizedResponse('認證驗證失敗', 'AUTH_FAILED');
        }
    }

    /**
     * 從請求中提取 JWT token.
     *
     * 支援多種提取方式：
     * 1. Authorization header (Bearer token)
     * 2. Query 參數 (token)
     * 3. Cookie (access_token)
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return string|null 提取到的 token 或 null
     */
    private function extractToken(ServerRequestInterface $request): ?string
    {
        // 1. 優先從 Authorization header 提取
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            if (!empty($token)) {
                return $token;
            }
        }

        // 2. 從 query 參數提取
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams['token'])) {
            return $queryParams['token'];
        }

        // 3. 從 cookie 提取
        $cookies = $request->getCookieParams();
        if (!empty($cookies['access_token'])) {
            return $cookies['access_token'];
        }

        return null;
    }

    /**
     * 執行額外的安全性檢查.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param JwtPayload $payload JWT payload
     * @throws InvalidTokenException 當安全性檢查失敗時
     */
    private function performSecurityChecks(ServerRequestInterface $request, JwtPayload $payload): void
    {
        // 1. IP 地址驗證（如果 payload 包含 IP 資訊）
        $tokenIpAddress = $payload->getCustomClaim('ip_address');
        if ($tokenIpAddress !== null) {
            $currentIp = $this->getClientIpAddress($request);
            if ($tokenIpAddress !== $currentIp) {
                throw new InvalidTokenException('Token 的 IP 地址不匹配');
            }
        }

        // 2. 裝置指紋驗證（可選）
        $tokenDeviceId = $payload->getCustomClaim('device_id');
        if ($tokenDeviceId !== null) {
            $currentDeviceId = $this->extractDeviceFingerprint($request);
            if ($tokenDeviceId !== $currentDeviceId) {
                throw new InvalidTokenException('Token 的裝置指紋不匹配');
            }
        }
    }

    /**
     * 將使用者資訊注入到請求中.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param JwtPayload $payload JWT payload
     * @param string $accessToken 原始 access token
     * @return ServerRequestInterface 注入使用者資訊後的請求物件
     */
    private function injectUserContext(
        ServerRequestInterface $request,
        JwtPayload $payload,
        string $accessToken,
    ): ServerRequestInterface {
        // 注入使用者資訊到請求屬性
        return $request
            ->withAttribute('jwt_payload', $payload)
            ->withAttribute('access_token', $accessToken)
            ->withAttribute('user_id', $payload->getUserId())
            ->withAttribute('username', $payload->getCustomClaim('username'))
            ->withAttribute('email', $payload->getCustomClaim('email'))
            ->withAttribute('role', $payload->getCustomClaim('role'))
            ->withAttribute('permissions', $payload->getCustomClaim('permissions') ?? [])
            ->withAttribute('device_id', $payload->getCustomClaim('device_id'))
            ->withAttribute('authenticated', true);
    }

    /**
     * 建立未授權的回應.
     *
     * @param string $message 錯誤訊息
     * @param string $code 錯誤代碼
     * @return ResponseInterface HTTP 回應物件
     */
    private function createUnauthorizedResponse(string $message, string $code = 'UNAUTHORIZED'): ResponseInterface
    {
        $responseData = [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('c'),
        ];

        $body = (json_encode($responseData, JSON_UNESCAPED_UNICODE) ?? '');

        return new Response(
            status: 401,
            headers: [
                'Content-Type' => 'application/json',
                'WWW-Authenticate' => 'Bearer realm="API"',
            ],
            body: $body,
        );
    }

    /**
     * 取得客戶端真實 IP 位址.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return string 客戶端 IP 位址
     */
    private function getClientIpAddress(ServerRequestInterface $request): string
    {
        // 檢查各種可能包含真實 IP 的標頭
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load Balancer/Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR',               // Standard
        ];

        $serverParams = $request->getServerParams();

        foreach ($headers as $header) {
            if (isset($serverParams[$header]) && !empty($serverParams[$header])) {
                $ip = trim(explode(',', $serverParams[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }

            if ($request->hasHeader($header)) {
                $ip = trim(explode(',', $request->getHeaderLine($header))[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // 預設回傳 localhost（適用於開發環境）
        return '127.0.0.1';
    }

    /**
     * 提取裝置指紋.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return string|null 裝置指紋或 null
     */
    private function extractDeviceFingerprint(ServerRequestInterface $request): ?string
    {
        // 從 header 提取裝置指紋
        $deviceId = $request->getHeaderLine('X-Device-ID');
        if (!empty($deviceId)) {
            return $deviceId;
        }

        // 從 user agent 生成簡單指紋
        $userAgent = $request->getHeaderLine('User-Agent');
        if (!empty($userAgent)) {
            return hash('sha256', $userAgent . $this->getClientIpAddress($request));
        }

        return null;
    }

    /**
     * 檢查是否應該處理此請求.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return bool 是否應該處理
     */
    public function shouldProcess(ServerRequestInterface $request): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // 跳過不需要認證的路徑
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

        // 只處理需要認證的 API 路徑
        return str_starts_with($path, '/api/') || str_starts_with($path, '/auth/me');
    }

    /**
     * 取得中介軟體優先順序.
     *
     * @return int 優先順序（數值越小優先級越高）
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * 取得中介軟體名稱.
     *
     * @return string 中介軟體名稱
     */
    public function getName(): string
    {
        return self::MIDDLEWARE_NAME;
    }

    /**
     * 設定中介軟體優先順序.
     *
     * @param int $priority 優先順序
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * 設定中介軟體是否啟用.
     *
     * @param bool $enabled 是否啟用
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * 檢查中介軟體是否啟用.
     *
     * @return bool 是否啟用
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
