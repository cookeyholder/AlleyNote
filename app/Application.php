<?php

declare(strict_types=1);

namespace App;

use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\ControllerResolver;
use App\Infrastructure\Routing\Core\Router;
use App\Infrastructure\Routing\Middleware\MiddlewareDispatcher;
use App\Infrastructure\Routing\RouteDispatcher;
use App\Infrastructure\Routing\RouteLoader;
use DI\ContainerBuilder;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * 應用程式核心類別.
 *
 * 負責初始化和配置整個應用程式
 */
class Application
{
    private ContainerInterface $container;

    private RouterInterface $router;

    private RouteDispatcher $routeDispatcher;

    public function __construct()
    {
        $this->initializeContainer();
        $this->initializeRouter();
        $this->initializeRouteDispatcher();
        $this->loadRoutes();
    }

    /**
     * 執行應用程式.
     */
    public function run(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->handleRequest($request);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
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
     * 初始化路由分派器.
     */
    private function initializeRouteDispatcher(): void
    {
        $middlewareDispatcher = $this->container->get(MiddlewareDispatcher::class);
        $controllerResolver = new ControllerResolver($this->container);

        $this->routeDispatcher = new RouteDispatcher(
            $this->router,
            $controllerResolver,
            $middlewareDispatcher,
            $this->container,
        );
    }

    /**
     * 載入路由配置.
     */
    private function loadRoutes(): void
    {
        $routeLoader = new RouteLoader();

        try {
            // 載入各種路由配置檔案
            $routeLoader
                ->addRouteFile(__DIR__ . '/../config/routes/api.php', 'api')
                ->addRouteFile(__DIR__ . '/../config/routes/web.php', 'web')
                ->addRouteFile(__DIR__ . '/../config/routes/auth.php', 'auth')
                ->addRouteFile(__DIR__ . '/../config/routes/admin.php', 'admin');

            // 載入所有路由到路由器
            $routeLoader->loadRoutes($this->router);
        } catch (Throwable $e) {
            // 記錄路由載入錯誤並回退到基本配置
            error_log('路由載入失敗: ' . $e->getMessage());

            // 嘗試載入舊版路由檔案作為回退
            $legacyRoutesFile = __DIR__ . '/../config/routes.php';
            if (file_exists($legacyRoutesFile)) {
                $routeDefinitions = require $legacyRoutesFile;
                if (is_callable($routeDefinitions)) {
                    $routeDefinitions($this->router);
                }
            }
        }
    }

    /**
     * 處理 HTTP 請求
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->routeDispatcher->dispatch($request);
    }

    /**
     * 處理例外狀況.
     */
    private function handleException(Exception $e): ResponseInterface
    {
        // 建立基本的錯誤回應（使用匿名類別實作）
        $response = new class implements ResponseInterface {
            private array $headers = ['Content-Type' => ['application/json']];

            private $body;

            private int $statusCode = 500;

            private string $reasonPhrase = 'Internal Server Error';

            private string $protocolVersion = '1.1';

            public function __construct()
            {
                $this->body = new class {
                    private string $content = '';

                    public function write(string $string): int
                    {
                        $this->content .= $string;

                        return strlen($string);
                    }

                    public function __toString(): string
                    {
                        return $this->content;
                    }
                };
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            public function withStatus($code, $reasonPhrase = ''): self
            {
                $new = clone $this;
                $new->statusCode = $code;
                if ($reasonPhrase) {
                    $new->reasonPhrase = $reasonPhrase;
                }

                return $new;
            }

            public function getReasonPhrase(): string
            {
                return $this->reasonPhrase;
            }

            public function getProtocolVersion(): string
            {
                return $this->protocolVersion;
            }

            public function withProtocolVersion($version): self
            {
                $new = clone $this;
                $new->protocolVersion = $version;

                return $new;
            }

            public function getHeaders(): array
            {
                return $this->headers;
            }

            public function hasHeader($name): bool
            {
                return isset($this->headers[$name]);
            }

            public function getHeader($name): array
            {
                return $this->headers[$name] ?? [];
            }

            public function getHeaderLine($name): string
            {
                return implode(', ', $this->getHeader($name));
            }

            public function withHeader($name, $value): self
            {
                $new = clone $this;
                $new->headers[$name] = is_array($value) ? $value : [$value];

                return $new;
            }

            public function withAddedHeader($name, $value): self
            {
                $new = clone $this;
                $new->headers[$name] = array_merge($this->getHeader($name), is_array($value) ? $value : [$value]);

                return $new;
            }

            public function withoutHeader($name): self
            {
                $new = clone $this;
                unset($new->headers[$name]);

                return $new;
            }

            public function getBody()
            {
                return $this->body;
            }

            public function withBody($body): self
            {
                $new = clone $this;
                $new->body = $body;

                return $new;
            }
        };

        $errorData = [
            'error' => true,
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
        ];

        // 在除錯模式下提供詳細資訊
        if ($this->isDebugMode()) {
            $errorData['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        $response->getBody()->write(json_encode($errorData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
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
