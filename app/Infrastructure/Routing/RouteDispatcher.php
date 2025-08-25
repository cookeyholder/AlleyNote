<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 路由分派器.
 *
 * 整合路由匹配、中間件執行和控制器呼叫
 */
class RouteDispatcher
{
    public function __construct(
        private RouterInterface $router,
        private ControllerResolver $controllerResolver,
        private MiddlewareDispatcher $middlewareDispatcher,
        private ContainerInterface $container,
    ) {}

    /**
     * 分派請求到對應的路由處理器.
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        // 1. 路由匹配
        $matchResult = $this->router->dispatch($request);

        if (!$matchResult->isMatched()) {
            return $this->handleNotFound($request);
        }

        $route = $matchResult->getRoute();
        $parameters = $matchResult->getParameters();

        // 2. 準備中間件鏈
        $middlewares = $route->getMiddlewares();

        // 3. 建立最終處理器 (控制器)
        $finalHandler = new ClosureRequestHandler(
            function (ServerRequestInterface $request) use ($route, $parameters): ResponseInterface {
                return $this->controllerResolver->resolve($route, $request, $parameters);
            },
        );

        // 4. 執行中間件鏈
        return $this->middlewareDispatcher->dispatch($request, $middlewares, $finalHandler);
    }

    /**
     * 處理 404 Not Found.
     */
    private function handleNotFound(ServerRequestInterface $request): ResponseInterface
    {
        // 檢查是否有自訂的 404 處理器
        if ($this->container->has('app.handlers.not_found')) {
            $handler = $this->container->get('app.handlers.not_found');

            return $handler($request);
        }

        // 預設 404 回應
        $response = $this->container->get(ResponseInterface::class);
        $response->getBody()->write(json_encode([
            'error' => 'Not Found',
            'message' => '請求的路由不存在',
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'timestamp' => date('c'),
        ], JSON_UNESCAPED_UNICODE));

        return $response
            ->withStatus(404)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * 建立路由分派器實例的工廠方法.
     */
    public static function create(ContainerInterface $container): self
    {
        $router = $container->get(RouterInterface::class);
        $controllerResolver = new ControllerResolver($container);
        $middlewareDispatcher = $container->get(MiddlewareDispatcher::class);

        return new self($router, $controllerResolver, $middlewareDispatcher, $container);
    }
}
