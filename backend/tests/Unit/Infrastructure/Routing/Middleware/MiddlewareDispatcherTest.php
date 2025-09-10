<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Routing\Middleware;

use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;

/**
 * MiddlewareDispatcher 單元測試.
 */
class MiddlewareDispatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MiddlewareDispatcher $dispatcher;

    private ServerRequestInterface $mockRequest;

    private ResponseInterface $mockResponse;

    private RequestHandlerInterface $mockFinalHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new MiddlewareDispatcher();
        $this->mockRequest = Mockery::mock(ServerRequestInterface::class);
        $this->mockResponse = Mockery::mock(ResponseInterface::class);
        $this->mockFinalHandler = Mockery::mock(RequestHandlerInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testDispatchWithEmptyMiddlewareArray(): void
    {
        $this->mockFinalHandler->shouldReceive('handle')
            ->once()
            ->with($this->mockRequest)
            ->andReturn($this->mockResponse);

        $response = $this->dispatcher->dispatch(
            $this->mockRequest,
            [],
            $this->mockFinalHandler,
        );

        $this->assertSame($this->mockResponse, $response);
    }

    public function testDispatchWithSingleMiddleware(): void
    {
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $middleware->shouldReceive('process')
            ->once()
            ->with($this->mockRequest, Mockery::type(RequestHandlerInterface::class))
            ->andReturn($this->mockResponse);

        $response = $this->dispatcher->dispatch(
            $this->mockRequest,
            [$middleware],
            $this->mockFinalHandler,
        );

        $this->assertSame($this->mockResponse, $response);
    }

    public function testDispatchWithMultipleMiddlewares(): void
    {
        $middleware1 = Mockery::mock(MiddlewareInterface::class);
        $middleware2 = Mockery::mock(MiddlewareInterface::class);
        $middleware3 = Mockery::mock(MiddlewareInterface::class);

        // 設定中介軟體處理順序：middleware1 → middleware2 → middleware3 → finalHandler
        $middleware1->shouldReceive('process')
            ->once()
            ->with($this->mockRequest, Mockery::type(RequestHandlerInterface::class))
            ->andReturnUsing(function ($request, $handler) {
                return $handler->handle($request);
            });

        $middleware2->shouldReceive('process')
            ->once()
            ->with($this->mockRequest, Mockery::type(RequestHandlerInterface::class))
            ->andReturnUsing(function ($request, $handler) {
                return $handler->handle($request);
            });

        $middleware3->shouldReceive('process')
            ->once()
            ->with($this->mockRequest, Mockery::type(RequestHandlerInterface::class))
            ->andReturnUsing(function ($request, $handler) {
                return $handler->handle($request);
            });

        $this->mockFinalHandler->shouldReceive('handle')
            ->once()
            ->with($this->mockRequest)
            ->andReturn($this->mockResponse);

        $response = $this->dispatcher->dispatch(
            $this->mockRequest,
            [$middleware1, $middleware2, $middleware3],
            $this->mockFinalHandler,
        );

        $this->assertSame($this->mockResponse, $response);
    }

    public function testBuildChainWithEmptyMiddlewareArray(): void
    {
        $handler = $this->dispatcher->buildChain([], $this->mockFinalHandler);

        $this->assertSame($this->mockFinalHandler, $handler);
    }

    public function testBuildChainWithSingleMiddleware(): void
    {
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $handler = $this->dispatcher->buildChain([$middleware], $this->mockFinalHandler);

        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
        $this->assertNotSame($this->mockFinalHandler, $handler);
    }

    public function testBuildChainWithMultipleMiddlewares(): void
    {
        $middleware1 = Mockery::mock(MiddlewareInterface::class);
        $middleware2 = Mockery::mock(MiddlewareInterface::class);

        $handler = $this->dispatcher->buildChain(
            [$middleware1, $middleware2],
            $this->mockFinalHandler,
        );

        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
        $this->assertNotSame($this->mockFinalHandler, $handler);
    }

    public function testMiddlewareChainExecutionOrder(): void
    {
        $executionOrder = [];

        // 建立模擬中介軟體，記錄執行順序
        $middleware1 = Mockery::mock(MiddlewareInterface::class);
        $middleware2 = Mockery::mock(MiddlewareInterface::class);
        $middleware3 = Mockery::mock(MiddlewareInterface::class);

        $middleware1->shouldReceive('process')
            ->once()
            ->andReturnUsing(function ($request, $handler) use (&$executionOrder) {
                $executionOrder[] = 'middleware1_before';
                $response = $handler->handle($request);
                $executionOrder[] = 'middleware1_after';

                return $response;
            });

        $middleware2->shouldReceive('process')
            ->once()
            ->andReturnUsing(function ($request, $handler) use (&$executionOrder) {
                $executionOrder[] = 'middleware2_before';
                $response = $handler->handle($request);
                $executionOrder[] = 'middleware2_after';

                return $response;
            });

        $middleware3->shouldReceive('process')
            ->once()
            ->andReturnUsing(function ($request, $handler) use (&$executionOrder) {
                $executionOrder[] = 'middleware3_before';
                $response = $handler->handle($request);
                $executionOrder[] = 'middleware3_after';

                return $response;
            });

        $this->mockFinalHandler->shouldReceive('handle')
            ->once()
            ->andReturnUsing(function ($request) use (&$executionOrder) {
                $executionOrder[] = 'final_handler';

                return $this->mockResponse;
            });

        $this->dispatcher->dispatch(
            $this->mockRequest,
            [$middleware1, $middleware2, $middleware3],
            $this->mockFinalHandler,
        );

        // 驗證執行順序
        $expectedOrder = [
            'middleware1_before',
            'middleware2_before',
            'middleware3_before',
            'final_handler',
            'middleware3_after',
            'middleware2_after',
            'middleware1_after',
        ];

        $this->assertEquals($expectedOrder, $executionOrder);
    }

    public function testMiddlewareCanShortCircuitChain(): void
    {
        $shortCircuitResponse = Mockery::mock(ResponseInterface::class);

        $middleware1 = Mockery::mock(MiddlewareInterface::class);
        $middleware2 = Mockery::mock(MiddlewareInterface::class);

        // middleware1 短路並直接返回回應，不呼叫下一個處理器
        $middleware1->shouldReceive('process')
            ->once()
            ->with($this->mockRequest, Mockery::type(RequestHandlerInterface::class))
            ->andReturn($shortCircuitResponse);

        // middleware2 不應該被呼叫
        $middleware2->shouldNotReceive('process');

        // finalHandler 也不應該被呼叫
        $this->mockFinalHandler->shouldNotReceive('handle');

        $response = $this->dispatcher->dispatch(
            $this->mockRequest,
            [$middleware1, $middleware2],
            $this->mockFinalHandler,
        );

        $this->assertSame($shortCircuitResponse, $response);
    }

    public function testMiddlewareCanModifyRequest(): void
    {
        $modifiedRequest = Mockery::mock(ServerRequestInterface::class);

        $middleware = Mockery::mock(MiddlewareInterface::class);

        // 中介軟體修改請求並傳遞給下一個處理器
        $middleware->shouldReceive('process')
            ->once()
            ->with($this->mockRequest, Mockery::type(RequestHandlerInterface::class))
            ->andReturnUsing(function ($request, $handler) use ($modifiedRequest) {
                // 傳遞修改過的請求給下一個處理器
                return $handler->handle($modifiedRequest);
            });

        $this->mockFinalHandler->shouldReceive('handle')
            ->once()
            ->with($modifiedRequest)
            ->andReturn($this->mockResponse);

        $response = $this->dispatcher->dispatch(
            $this->mockRequest,
            [$middleware],
            $this->mockFinalHandler,
        );

        $this->assertSame($this->mockResponse, $response);
    }

    public function testCreateMiddlewareHandlerReturnsCorrectInterface(): void
    {
        $middleware = Mockery::mock(MiddlewareInterface::class);
        $nextHandler = Mockery::mock(RequestHandlerInterface::class);

        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->dispatcher);
        $method = $reflection->getMethod('createMiddlewareHandler');
        $method->setAccessible(true);

        $handler = $method->invoke($this->dispatcher, $middleware, $nextHandler);

        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
    }

    public function testCreatedHandlerDelegatesToMiddleware(): void
    {
        $middleware = Mockery::mock(MiddlewareInterface::class);
        $nextHandler = Mockery::mock(RequestHandlerInterface::class);

        // 使用反射來測試私有方法
        $reflection = new ReflectionClass($this->dispatcher);
        $method = $reflection->getMethod('createMiddlewareHandler');
        $method->setAccessible(true);

        $handler = $method->invoke($this->dispatcher, $middleware, $nextHandler);

        $middleware->shouldReceive('process')
            ->once()
            ->with($this->mockRequest, $nextHandler)
            ->andReturn($this->mockResponse);

        $response = $handler->handle($this->mockRequest);

        $this->assertSame($this->mockResponse, $response);
    }
}
