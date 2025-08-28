<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Exceptions\InvalidTokenException;
use AlleyNote\Domains\Auth\Exceptions\TokenExpiredException;
use AlleyNote\Domains\Auth\ValueObjects\JwtPayload;
use App\Application\Middleware\JwtAuthenticationMiddleware;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * JWT 認證中介軟體單元測試.
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class JwtAuthenticationMiddlewareTest extends TestCase
{
    private JwtTokenServiceInterface|MockInterface $jwtTokenService;

    private JwtAuthenticationMiddleware $middleware;

    private RequestHandlerInterface|MockInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtTokenService = Mockery::mock(JwtTokenServiceInterface::class);
        $this->middleware = new JwtAuthenticationMiddleware($this->jwtTokenService);
        $this->handler = Mockery::mock(RequestHandlerInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testShouldSkipProcessingForPublicPaths(): void
    {
        $publicPaths = [
            '/auth/login',
            '/auth/register',
            '/auth/refresh',
            '/health',
            '/status',
            '/favicon.ico',
        ];

        foreach ($publicPaths as $path) {
            $request = new ServerRequest('GET', new Uri($path));

            $this->assertFalse(
                $this->middleware->shouldProcess($request),
                "路徑 {$path} 應該被跳過",
            );
        }
    }

    public function testShouldProcessAuthenticatedPaths(): void
    {
        $authenticatedPaths = [
            '/api/posts',
            '/api/users',
            '/auth/me',
            '/api/admin/dashboard',
        ];

        foreach ($authenticatedPaths as $path) {
            $request = new ServerRequest('GET', new Uri($path));

            $this->assertTrue(
                $this->middleware->shouldProcess($request),
                "路徑 {$path} 應該需要認證",
            );
        }
    }

    public function testShouldReturnUnauthorizedWhenNoTokenProvided(): void
    {
        $request = new ServerRequest('GET', new Uri('/api/posts'));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('Bearer realm="API"', $response->getHeaderLine('WWW-Authenticate'));

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('缺少有效的認證 Token', $body['error']);
        $this->assertEquals('UNAUTHORIZED', $body['code']);
    }

    public function testShouldExtractTokenFromAuthorizationHeader(): void
    {
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload();

        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andReturn($payload);

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new Response());

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldExtractTokenFromQueryParameter(): void
    {
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload();

        // 使用正確的 Guzzle PSR7 ServerRequest 建構方式
        $uri = new Uri('http://example.com/api/posts?token=' . urlencode($token));
        $request = new ServerRequest('GET', $uri);

        // 由於 PSR-7 ServerRequest 的 query params 需要從 URI 解析，
        // 我們需要使用 withQueryParams 方法明確設置
        $queryParams = [];
        parse_str($uri->getQuery(), $queryParams);
        $request = $request->withQueryParams($queryParams);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andReturn($payload);

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new Response());

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldExtractTokenFromCookie(): void
    {
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload();

        $request = new ServerRequest('GET', new Uri('/api/posts'));
        $request = $request->withCookieParams(['access_token' => $token]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andReturn($payload);

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new Response());

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldInjectUserContextIntoRequest(): void
    {
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload(); // 使用預設 payload，不加 custom claims

        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andReturn($payload);

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new Response());

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldReturnUnauthorizedWhenTokenExpired(): void
    {
        $token = 'expired.jwt.token';
        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andThrow(new TokenExpiredException('Token 已過期'));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Token 已過期', $body['error']);
        $this->assertEquals('TOKEN_EXPIRED', $body['code']);
    }

    public function testShouldReturnUnauthorizedWhenTokenInvalid(): void
    {
        $token = 'invalid.jwt.token';
        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andThrow(new InvalidTokenException('Token 無效'));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Token 無效', $body['error']);
        $this->assertEquals('TOKEN_INVALID', $body['code']);
    }

    public function testShouldValidateIpAddressWhenPresentInToken(): void
    {
        // 這個測試僅驗證沒有 IP claim 時通過
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload(); // 不包含 ip_address claim

        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->andReturn($payload);

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new Response());

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldFailWhenIpAddressMismatch(): void
    {
        // 測試 IP 不匹配的情況
        $token = 'valid.jwt.token';
        $tokenIp = '203.0.113.1';
        $currentIp = '203.0.113.2'; // 不同的 IP
        $payload = $this->createValidPayload(['ip_address' => $tokenIp]);

        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
            'X-Forwarded-For' => $currentIp,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andReturn($payload);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Token 無效', $body['error']);
        $this->assertEquals('TOKEN_INVALID', $body['code']);
    }

    public function testShouldRejectWhenIpAddressMismatch(): void
    {
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload(['ip_address' => '192.168.1.100']);

        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
        ], null, '1.1', ['REMOTE_ADDR' => '192.168.1.200']);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->andReturn($payload);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Token 無效', $body['error']);
        $this->assertEquals('TOKEN_INVALID', $body['code']);
    }

    public function testShouldValidateDeviceFingerprint(): void
    {
        $token = 'valid.jwt.token';
        $deviceId = 'device-fingerprint-123';
        $payload = $this->createValidPayload(['device_id' => $deviceId]);

        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
            'X-Device-ID' => $deviceId,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->andReturn($payload);

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new Response());

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldRejectWhenDeviceFingerprintMismatch(): void
    {
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload(['device_id' => 'device-fingerprint-123']);

        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
            'X-Device-ID' => 'device-fingerprint-456',
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->andReturn($payload);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('Token 無效', $body['error']);
        $this->assertEquals('TOKEN_INVALID', $body['code']);
    }

    public function testShouldSkipProcessingWhenDisabled(): void
    {
        $middleware = new JwtAuthenticationMiddleware($this->jwtTokenService, 10, false);

        $request = new ServerRequest('GET', new Uri('/api/posts'));

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn(new Response());

        $response = $middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldHandleGenericExceptionGracefully(): void
    {
        $token = 'valid.jwt.token';
        $request = new ServerRequest('GET', new Uri('/api/posts'), [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->andThrow(new RuntimeException('Unexpected error'));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('認證驗證失敗', $body['error']);
        $this->assertEquals('AUTH_FAILED', $body['code']);
    }

    public function testShouldPrioritizeAuthorizationHeaderOverOtherMethods(): void
    {
        $headerToken = 'header.jwt.token';
        $queryToken = 'query.jwt.token';
        $cookieToken = 'cookie.jwt.token';
        $payload = $this->createValidPayload();

        $request = new ServerRequest('GET', new Uri('/api/posts?token=' . $queryToken), [
            'Authorization' => 'Bearer ' . $headerToken,
        ]);
        $request = $request->withCookieParams(['access_token' => $cookieToken]);

        // 應該使用 header 中的 token
        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($headerToken)  // 驗證使用的是 header token
            ->andReturn($payload);

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new Response());

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCanGetAndSetPriority(): void
    {
        $this->assertEquals(10, $this->middleware->getPriority());

        $this->middleware->setPriority(5);
        $this->assertEquals(5, $this->middleware->getPriority());
    }

    public function testCanGetName(): void
    {
        $this->assertEquals('jwt-auth', $this->middleware->getName());
    }

    public function testCanSetEnabled(): void
    {
        $this->assertTrue($this->middleware->isEnabled());

        $this->middleware->setEnabled(false);
        $this->assertFalse($this->middleware->isEnabled());

        $this->middleware->setEnabled(true);
        $this->assertTrue($this->middleware->isEnabled());
    }

    /**
     * 建立有效的 JWT payload.
     *
     * @param array<string, mixed> $customClaims 自訂宣告
     */
    private function createValidPayload(array $customClaims = []): JwtPayload
    {
        $now = new DateTimeImmutable();
        $exp = $now->modify('+1 hour');

        return new JwtPayload(
            jti: 'jwt-id-123',
            sub: '123',
            iss: 'alleynote-api',
            aud: ['alleynote-client'],
            iat: $now,
            exp: $exp,
            nbf: $now,
            customClaims: $customClaims,
        );
    }
}
