<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Middleware;

use App\Application\Middleware\SecurityHeadersMiddleware;
use App\Domains\Security\Services\Headers\SecurityHeaderService;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Support\UnitTestCase;

/**
 * SecurityHeadersMiddleware 單元測試.
 */
#[CoversClass(SecurityHeadersMiddleware::class)]
class SecurityHeadersMiddlewareTest extends UnitTestCase
{
    private SecurityHeadersMiddleware $middleware;

    private SecurityHeaderService&MockInterface $headerService;

    private RequestHandlerInterface&MockInterface $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headerService = Mockery::mock(SecurityHeaderService::class);
        $this->handler = Mockery::mock(RequestHandlerInterface::class);
        $this->middleware = new SecurityHeadersMiddleware(
            $this->headerService,
        );
    }

    #[Test]
    public function testMetadataAndGettersSetters(): void
    {
        $this->assertEquals(1, $this->middleware->getPriority());
        $this->assertEquals('security-headers', $this->middleware->getName());
        $this->assertTrue($this->middleware->isEnabled());

        $this->middleware->setPriority(5);
        $this->assertEquals(5, $this->middleware->getPriority());

        $this->middleware->setEnabled(false);
        $this->assertFalse($this->middleware->isEnabled());

        $request = Mockery::mock(ServerRequestInterface::class);
        $this->assertFalse($this->middleware->shouldProcess($request));
    }

    #[Test]
    public function testProcessWhenDisabled(): void
    {
        $this->middleware->setEnabled(false);

        $request = Mockery::mock(ServerRequestInterface::class);
        $response = new Response(200, ['X-Custom' => 'val']);

        $this->handler->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn($response);

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('val', $result->getHeaderLine('X-Custom'));
    }

    #[Test]
    public function testProcessWithServerSignatureDisabled(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('withAttribute')
            ->once()
            ->with('csp_nonce', 'random-nonce-123')
            ->andReturnSelf();

        $this->headerService->shouldReceive('generateNonce')
            ->once()
            ->andReturn('random-nonce-123');

        $this->headerService->shouldReceive('generateHeaders')
            ->once()
            ->andReturn([
                'X-Frame-Options'        => 'DENY',
                'X-Content-Type-Options' => 'nosniff',
            ]);

        $this->headerService->shouldReceive('isServerSignatureEnabled')
            ->once()
            ->andReturn(false);

        $rawResponse = new Response(200, [
            'X-Powered-By' => 'PHP/8.4',
            'Server'       => 'Apache/2.4',
        ]);

        $this->handler->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn($rawResponse);

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals('DENY', $result->getHeaderLine('X-Frame-Options'));
        $this->assertEquals('nosniff', $result->getHeaderLine('X-Content-Type-Options'));
        $this->assertFalse($result->hasHeader('X-Powered-By'));
        $this->assertFalse($result->hasHeader('Server'));
    }

    #[Test]
    public function testProcessWithServerSignatureEnabled(): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('withAttribute')
            ->once()
            ->with('csp_nonce', 'random-nonce-456')
            ->andReturnSelf();

        $this->headerService->shouldReceive('generateNonce')
            ->once()
            ->andReturn('random-nonce-456');

        $this->headerService->shouldReceive('generateHeaders')
            ->once()
            ->andReturn([
                'Server'          => 'AlleyNote/1.0',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);

        $this->headerService->shouldReceive('isServerSignatureEnabled')
            ->once()
            ->andReturn(true);

        $rawResponse = new Response(200);

        $this->handler->shouldReceive('handle')
            ->once()
            ->with($request)
            ->andReturn($rawResponse);

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals('AlleyNote/1.0', $result->getHeaderLine('Server'));
        $this->assertEquals('SAMEORIGIN', $result->getHeaderLine('X-Frame-Options'));
    }
}
