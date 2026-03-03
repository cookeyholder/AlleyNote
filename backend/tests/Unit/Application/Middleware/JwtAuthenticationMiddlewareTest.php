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
use Tests\Support\UnitTestCase;

/**
 * JWT 認證中介軟體單元測試.
 */
final class JwtAuthenticationMiddlewareTest extends UnitTestCase
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

        $this->assertJsonResponseMatches($response, [
            'success' => false,
            'error' => ['code' => 'UNAUTHORIZED', 'message' => '缺少有效的認證 Token']
        ]);
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
        $request = new ServerRequest('GET', new Uri('http://localhost/api/posts'), ['Authorization' => 'Bearer ' . $token]);

        $this->jwtTokenService->shouldReceive('validateAccessToken')
            ->once()
            ->andThrow(new TokenExpiredException(TokenExpiredException::ACCESS_TOKEN, null, null, 'Token 已過期'));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertEquals(401, $response->getStatusCode());

        $this->assertJsonResponseMatches($response, [
            'success' => false,
            'error' => ['code' => 'TOKEN_EXPIRED', 'message' => 'Token 已過期']
        ]);
    }
}
