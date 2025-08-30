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
use Psr\Http\Message\StreamInterface;

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
        $stream = new class implements StreamInterface {
            private string $content = '';

            private int $position = 0;

            public function __toString(): string
            {
                return $this->content;
            }

            public function close(): void
            {
                // 實作關閉流
            }

            public function detach()
            {
                return null;
            }

            public function getSize(): int
            {
                return strlen($this->content);
            }

            public function tell(): int
            {
                return $this->position;
            }

            public function eof(): bool
            {
                return $this->position >= strlen($this->content);
            }

            public function isSeekable(): bool
            {
                return true;
            }

            public function seek(int $offset, int $whence = SEEK_SET): void
            {
                switch ($whence) {
                    case SEEK_SET:
                        $this->position = $offset;
                        break;
                    case SEEK_CUR:
                        $this->position += $offset;
                        break;
                    case SEEK_END:
                        $this->position = strlen($this->content) + $offset;
                        break;
                }
            }

            public function rewind(): void
            {
                $this->position = 0;
            }

            public function isWritable(): bool
            {
                return true;
            }

            public function write(string $string): int
            {
                $this->content .= $string;
                $this->position += strlen($string);

                return strlen($string);
            }

            public function isReadable(): bool
            {
                return true;
            }

            public function read(int $length): string
            {
                $result = substr($this->content, $this->position, $length);
                $this->position += strlen($result);

                return $result;
            }

            public function getContents(): string
            {
                return substr($this->content, $this->position);
            }

            /** @return array<mixed> */
            public function getMetadata(?string $key = null): mixed
            {
                return [];
            }
        };

        // 寫入錯誤訊息
        $errorJson = json_encode([
            'error' => 'Internal Server Error',
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
        ]);
        $stream->write($errorJson ?: '{"error": "JSON encoding failed"}');

        // 建立並返回 Response
        $response = new class ($stream) implements ResponseInterface {
            private StreamInterface $body;

            private int $statusCode = 500;

            public function __construct(StreamInterface $body)
            {
                $this->body = $body;
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
            {
                $new = clone $this;
                $new->statusCode = $code;

                return $new;
            }

            public function getReasonPhrase(): string
            {
                return 'Internal Server Error';
            }

            public function getProtocolVersion(): string
            {
                return '1.1';
            }

            public function withProtocolVersion(string $version): ResponseInterface
            {
                return $this;
            }

            /** @return array<mixed>> */
            public function getHeaders(): mixed
            {
                return ['Content-Type' => ['application/json']];
            }

            public function hasHeader(string $name): bool
            {
                return strtolower($name) === 'content-type';
            }

            /** @return array<mixed> */
            public function getHeader(string $name): mixed
            {
                return strtolower($name) === 'content-type' ? ['application/json'] : [];
            }

            public function getHeaderLine(string $name): string
            {
                return strtolower($name) === 'content-type' ? 'application/json' : '';
            }

            public function withHeader(string $name, $value): ResponseInterface
            {
                return $this;
            }

            public function withAddedHeader(string $name, $value): ResponseInterface
            {
                return $this;
            }

            public function withoutHeader(string $name): ResponseInterface
            {
                return $this;
            }

            public function getBody(): StreamInterface
            {
                return $this->body;
            }

            public function withBody(StreamInterface $body): ResponseInterface
            {
                $new = clone $this;
                $new->body = $body;

                return $new;
            }
        };

        return $response;
    }

    /**
     * 獲取 DI 容器實例.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * 獲取路由器實例.
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
