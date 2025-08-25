<?php

declare(strict_types=1);

namespace App;

use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\Core\Router;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;
use App\Infrastructure\Routing\RouteDispatcher;
use DI\ContainerBuilder;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 應用程式核心類別.
 *
 * 負責初始化和配置整個應用程式
 */
class Application
{
    private ContainerInterface $container;

    private RouterInterface $router;

    private RouteDispatcher $dispatcher;

    public function __construct()
    {
        $this->initializeContainer();
        $this->initializeRouter();
        $this->initializeDispatcher();
        $this->loadRoutes();
    }

    /**
     * 初始化 DI 容器.
     */
    private function initializeContainer(): void
    {
        $builder = new ContainerBuilder();

        // 基本服務定義
        $builder->addDefinitions([
            RouterInterface::class => \DI\create(Router::class),
            MiddlewareDispatcher::class => \DI\create(),
        ]);

        $this->container = $builder->build();
    }

    /**
     * 初始化路由器.
     */
    private function initializeRouter(): void
    {
        $this->router = $this->container->get(RouterInterface::class);
    }

    /**
     * 初始化分派器.
     */
    private function initializeDispatcher(): void
    {
        $this->dispatcher = RouteDispatcher::create($this->container);
    }

    /**
     * 載入路由定義.
     */
    private function loadRoutes(): void
    {
        $routesFile = __DIR__ . '/../config/routes.php';

        if (file_exists($routesFile)) {
            $routeDefinitions = require $routesFile;
            if (is_callable($routeDefinitions)) {
                $routeDefinitions($this->router);
            }
        }
    }

    /**
     * 處理 HTTP 請求
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->dispatcher->dispatch($request);
        } catch (Exception $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * 處理例外狀況
     */
    private function handleException(Exception $e, ServerRequestInterface $request): ResponseInterface
    {
        // 記錄錯誤
        error_log('應用程式錯誤: ' . $e->getMessage() . ' 在 ' . $e->getFile() . ':' . $e->getLine());

        // 建立錯誤回應
        $response = $this->container->get(ResponseInterface::class);

        $errorData = [
            'error' => 'Internal Server Error',
            'message' => $e->getMessage(),
            'timestamp' => date('c'),
        ];

        // 在除錯模式下加入更多資訊
        if ($this->isDebugMode()) {
            $errorData['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        $response->getBody()->write(json_encode($errorData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * 檢查是否為除錯模式.
     */
    private function isDebugMode(): bool
    {
        return $_ENV['APP_DEBUG'] ?? false;
    }

    /**
     * 取得容器實例.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * 取得路由器實例.
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
