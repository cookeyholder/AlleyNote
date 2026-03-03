<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\JwtAuthenticationMiddleware;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Exceptions\TokenExpiredException;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Uri;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
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

    public function testShouldSkipProcessingForPublicPaths(): void
    {
        $publicPaths = ['/auth/login', '/auth/register', '/health'];

        foreach ($publicPaths as $path) {
            $request = new ServerRequest('GET', new Uri($path));
            $this->assertFalse($this->middleware->shouldProcess($request));
        }
    }

    public function testShouldProcessApiPaths(): void
    {
        $request = new ServerRequest('GET', new Uri('/api/posts'));
        $this->assertTrue($this->middleware->shouldProcess($request));
    }

    public function testShouldReturn401WhenNoToken(): void
    {
        $request = new ServerRequest('GET', new Uri('http://localhost/api/posts'));
        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(401, $response->getStatusCode());

        $response->getBody()->rewind();
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('缺少有效的認證 Token', $body['error']);
    }

    public function testShouldPassWhenTokenValid(): void
    {
        $token = 'valid-token';
        $request = new ServerRequest('GET', new Uri('http://localhost/api/posts'), ['Authorization' => 'Bearer ' . $token]);

        $payload = new JwtPayload('jti', '123', 'iss', ['alleynote-client'], new DateTimeImmutable(), new DateTimeImmutable('+1 hour'));

        $this->jwtTokenService->shouldReceive('validateAccessToken')->once()->with($token)->andReturn($payload);
        $this->handler->shouldReceive('handle')->once()->andReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldReturn401WhenTokenExpired(): void
    {
        $token = 'expired-token';
        // 使用帶有 Host 的完整 URI 確保 getPath() 回傳 /api/posts
        $request = new ServerRequest('GET', new Uri('http://localhost/api/posts'), ['Authorization' => 'Bearer ' . $token]);

        // 修正 TokenExpiredException 的建構，避免使用不支援的具名參數
        $this->jwtTokenService->shouldReceive('validateAccessToken')
            ->once()
            ->andThrow(new TokenExpiredException(TokenExpiredException::ACCESS_TOKEN, null, null, 'Token 已過期'));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(401, $response->getStatusCode());

        $response->getBody()->rewind();
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Token 已過期', $body['error']);
    }
}
