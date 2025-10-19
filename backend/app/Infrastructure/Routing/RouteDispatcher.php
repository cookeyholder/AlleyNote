<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;
use App\Infrastructure\Routing\Middleware\MiddlewareResolver;
use Exception;
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
    private MiddlewareResolver $middlewareResolver;

    public function __construct(
        private RouterInterface $router,
        private ControllerResolver $controllerResolver,
        private MiddlewareDispatcher $middlewareDispatcher,
        private ContainerInterface $container,
    ) {
        $this->middlewareResolver = new MiddlewareResolver($container);
    }

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
        if ($route === null) {
            return $this->handleNotFound($request);
        }

        $parameters = $matchResult->getParameters();

        // 2. 準備中間件鏈（解析字串別名）
        $middlewares = $route->getMiddlewares();
        $resolvedMiddlewares = [];

        foreach ($middlewares as $middleware) {
            try {
                if (is_string($middleware)) {
                    // 解析字串別名
                    $resolvedMiddlewares[] = $this->middlewareResolver->resolve($middleware);
                } else {
                    // 已經是實例，直接使用
                    $resolvedMiddlewares[] = $middleware;
                }
            } catch (Exception $e) {
                // 記錄錯誤但繼續執行，避免因為中介軟體問題導致整個請求失敗
                error_log("Failed to resolve middleware '{$middleware}': " . $e->getMessage());
            }
        }

        // 3. 建立最終處理器 (控制器)
        $finalHandler = new ClosureRequestHandler(
            function (ServerRequestInterface $request) use ($route, $parameters): ResponseInterface {
                return $this->controllerResolver->resolve($route, $request, $parameters);
            },
        );

        // 4. 執行中間件鏈（使用解析後的中介軟體）
        return $this->middlewareDispatcher->dispatch($request, $resolvedMiddlewares, $finalHandler);
    }

    /**
     * 處理 404 Not Found.
     */
    private function handleNotFound(ServerRequestInterface $request): ResponseInterface
    {
        // 檢查是否有自訂的 404 處理器
        if ($this->container->has('app.handlers.not_found')) {
            $handler = $this->container->get('app.handlers.not_found');

            if (is_callable($handler)) {
                $result = $handler($request);
                if ($result instanceof ResponseInterface) {
                    return $result;
                }
            }
        }

        // 預設 404 回應
        $response = $this->container->get(ResponseInterface::class);

        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException('Container must provide a valid ResponseInterface instance');
        }

        $body = $response->getBody();
        $jsonContent = json_encode([
            'error' => 'Not Found',
            'message' => '請求的路由不存在',
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'timestamp' => date('c'),
        ], JSON_UNESCAPED_UNICODE);

        if ($jsonContent === false) {
            $jsonContent = '{"error":"Not Found","message":"請求的路由不存在"}';
        }

        $body->write($jsonContent);

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
        if (!$router instanceof RouterInterface) {
            throw new \RuntimeException('Container must provide a valid RouterInterface instance');
        }

        $controllerResolver = new ControllerResolver($container);

        $middlewareDispatcher = $container->get(MiddlewareDispatcher::class);
        if (!$middlewareDispatcher instanceof MiddlewareDispatcher) {
            throw new \RuntimeException('Container must provide a valid MiddlewareDispatcher instance');
        }

        return new self($router, $controllerResolver, $middlewareDispatcher, $container);
    }
}
