<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\Middleware;

use App\Infrastructure\Routing\Contracts\MiddlewareDispatcherInterface;
use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 中介軟體執行器.
 *
 * 負責執行中介軟體鏈，採用遞迴方式建立執行鏈
 */
class MiddlewareDispatcher implements MiddlewareDispatcherInterface
{
    public function dispatch(
        ServerRequestInterface $request,
        array $middlewares,
        RequestHandlerInterface $finalHandler,
    ): ResponseInterface {
        $chain = $this->buildChain($middlewares, $finalHandler);

        return $chain->handle($request);
    }

    public function buildChain(
        array $middlewares,
        RequestHandlerInterface $finalHandler,
    ): RequestHandlerInterface {
        // 反向遍歷中介軟體，從最後一個開始建立鏈
        $handler = $finalHandler;

        for ($i = count($middlewares) - 1; $i >= 0; $i--) {
            $middleware = $middlewares[$i];
            $handler = $this->createMiddlewareHandler($middleware, $handler);
        }

        return $handler;
    }

    /**
     * 建立中介軟體處理器.
     *
     * @param MiddlewareInterface $middleware 中介軟體
     * @param RequestHandlerInterface $nextHandler 下一個處理器
     */
    private function createMiddlewareHandler(
        MiddlewareInterface $middleware,
        RequestHandlerInterface $nextHandler,
    ): RequestHandlerInterface {
        return new class ($middleware, $nextHandler) implements RequestHandlerInterface {
            public function __construct(
                private MiddlewareInterface $middleware,
                private RequestHandlerInterface $nextHandler,
            ) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, $this->nextHandler);
            }
        };
    }
}
