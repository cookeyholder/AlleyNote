<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\JwtAuthenticationMiddleware;
use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Exceptions\TokenExpiredException;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * JWT 認證中介軟體單元測試 - 使用新測試框架重構.
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
            // 使用新工具建立真實請求
            $request = $this->createRequest('GET', $path);
            $this->assertFalse($this->middleware->shouldProcess($request), "路徑 {$path} 應該被跳過");
        }
    }

    public function testShouldProcessApiPaths(): void
    {
        // 使用新工具建立真實請求
        $request = $this->createRequest('GET', '/api/posts');
        $this->assertTrue($this->middleware->shouldProcess($request));
    }

    public function testShouldReturn401WhenNoToken(): void
    {
        $request = $this->createRequest('GET', '/api/posts');

        $response = $this->middleware->process($request, $this->handler);

        // 使用新工具進行斷言
        $this->assertResponseStatus($response, 401);
        $this->assertJsonResponseMatches($response, [
            'success' => false,
            'error' => '缺少有效的認證 Token',
        ]);
    }

    public function testShouldPassWhenTokenValid(): void
    {
        $token = 'valid-token';
        $request = $this->createRequest('GET', '/api/posts');
        $request = $this->withJwtAuth($request, $token);

        $payload = new JwtPayload('jti', '123', 'iss', ['alleynote-client'], new DateTimeImmutable(), new DateTimeImmutable('+1 hour'));

        $this->jwtTokenService->shouldReceive('validateAccessToken')->once()->with($token)->andReturn($payload);
        $this->handler->shouldReceive('handle')->once()->andReturn(new Response(200));

        $response = $this->middleware->process($request, $this->handler);
        $this->assertResponseStatus($response, 200);
    }

    public function testShouldReturn401WhenTokenExpired(): void
    {
        $token = 'expired-token';
        $request = $this->createRequest('GET', '/api/posts');
        $request = $this->withJwtAuth($request, $token);

        $this->jwtTokenService->shouldReceive('validateAccessToken')
            ->once()
            ->andThrow(new TokenExpiredException(TokenExpiredException::ACCESS_TOKEN, null, null, 'Token 已過期'));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertResponseStatus($response, 401);
        $this->assertJsonResponseMatches($response, [
            'success' => false,
            'error' => 'Token 已過期',
        ]);
    }
}
