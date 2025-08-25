<?php

declare(strict_types=1);

namespace App;

use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\Providers\RoutingServiceProvider;
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

        // 載入容器配置檔案
        $containerConfig = require __DIR__ . '/../config/container.php';
        $builder->addDefinitions($containerConfig);

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
        $this->routeDispatcher = $this->container->get(RouteDispatcher::class);
    }

    /**
     * 載入路由配置.
     */
    private function loadRoutes(): void
    {
        // 使用路由服務提供者載入路由
        RoutingServiceProvider::loadRoutes($this->container);
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
