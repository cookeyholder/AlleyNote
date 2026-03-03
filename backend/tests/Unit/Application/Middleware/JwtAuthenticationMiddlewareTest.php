<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\JwtAuthenticationMiddleware;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\TokenExpiredException;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use DateTimeImmutable;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * JWT 認證中介軟體單元測試.
 */
final class JwtAuthenticationMiddlewareTest extends TestCase
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

    /**
     * 輔助方法：建立一個會被 Middleware 處理的請求 (路徑以 /api/ 開頭).
     */
    private function createApiRequest(string $method = 'GET', string $path = '/api/posts', array $headers = []): ServerRequest
    {
        // 確保 path 絕對是以 /api/ 開頭
        $path = '/' . ltrim($path, '/');
        return new ServerRequest($method, new Uri($path), $headers);
    }

    public function testShouldSkipProcessingForPublicPaths(): void
    {
        $publicPaths = [
            '/auth/login',
            '/auth/register',
            '/auth/refresh',
            '/health',
            '/status',
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
        $request = $this->createApiRequest();

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        
        $response->getBody()->rewind();
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('缺少有效的認證 Token', $body['error']);
    }

    public function testShouldExtractTokenFromAuthorizationHeader(): void
    {
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload();

        $request = $this->createApiRequest('GET', '/api/posts', [
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
            ->andReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldExtractTokenFromQueryParameter(): void
    {
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload();

        $request = $this->createApiRequest('GET', '/api/posts');
        $request = $request->withQueryParams(['token' => $token]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andReturn($payload);

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldExtractTokenFromCookie(): void
    {
        $token = 'valid.jwt.token';
        $payload = $this->createValidPayload();

        $request = $this->createApiRequest();
        $request = $request->withCookieParams(['access_token' => $token]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andReturn($payload);

        $this->handler
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldReturnUnauthorizedWhenTokenExpired(): void
    {
        $token = 'expired.jwt.token';
        $request = $this->createApiRequest('GET', '/api/posts', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->with($token)
            ->andThrow(new TokenExpiredException('Token 已過期'));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        
        $response->getBody()->rewind();
        $body = json_decode($response->getBody()->getContents(), true);
        // Middleware 捕捉到異常後會統一回傳訊息或原訊息
        $this->assertTrue(isset($body['error']));
    }

    public function testShouldHandleGenericExceptionGracefully(): void
    {
        $token = 'valid.jwt.token';
        $request = $this->createApiRequest('GET', '/api/posts', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $this->jwtTokenService
            ->shouldReceive('validateAccessToken')
            ->once()
            ->andThrow(new RuntimeException('Unexpected error'));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());
        
        $response->getBody()->rewind();
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue(isset($body['error']));
    }

    private function createValidPayload(array $customClaims = []): JwtPayload
    {
        $now = new DateTimeImmutable();
        return new JwtPayload(
            jti: 'jwt-id-123',
            sub: '123',
            iss: 'alleynote-api',
            aud: ['alleynote-client'],
            iat: $now,
            exp: $now->modify('+1 hour'),
            nbf: $now,
            customClaims: $customClaims,
        );
    }
}
