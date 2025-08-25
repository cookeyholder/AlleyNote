<?php

declare(strict_types=1);

/**
 * 測試 DI 容器整合 (Task 3.1).
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Application;
use App\Infrastructure\Routing\Contracts\RouterInterface;
use App\Infrastructure\Routing\ControllerResolver;
use App\Infrastructure\Routing\Providers\RoutingServiceProvider;
use App\Infrastructure\Routing\RouteDispatcher;
use App\Infrastructure\Routing\RouteLoader;
use App\Infrastructure\Routing\RouteValidator;
use Psr\Http\Message\ServerRequestInterface;

echo "=== DI 容器整合測試 ===\n\n";

try {
    echo "測試 1: 建立應用程式實例和容器\n";
    $app = new Application();
    $container = $app->getContainer();
    echo "✅ 應用程式和容器初始化成功\n";

    echo "\n測試 2: 檢查路由系統服務註冊\n";

    // 測試路由器服務
    $router = $container->get(RouterInterface::class);
    echo '✅ RouterInterface: ' . get_class($router) . "\n";

    // 測試路由驗證器
    $validator = $container->get(RouteValidator::class);
    echo '✅ RouteValidator: ' . get_class($validator) . "\n";

    // 測試路由載入器
    $routeLoader = $container->get(RouteLoader::class);
    echo '✅ RouteLoader: ' . get_class($routeLoader) . "\n";

    // 測試控制器解析器
    $controllerResolver = $container->get(ControllerResolver::class);
    echo '✅ ControllerResolver: ' . get_class($controllerResolver) . "\n";

    // 測試路由分派器
    $routeDispatcher = $container->get(RouteDispatcher::class);
    echo '✅ RouteDispatcher: ' . get_class($routeDispatcher) . "\n";

    echo "\n測試 3: 檢查應用程式配置\n";

    // 測試應用程式配置
    $appName = $container->get('app.name');
    $appVersion = $container->get('app.version');
    $appDebug = $container->get('app.debug');

    echo "✅ 應用程式名稱: {$appName}\n";
    echo "✅ 應用程式版本: {$appVersion}\n";
    echo '✅ 除錯模式: ' . ($appDebug ? 'enabled' : 'disabled') . "\n";

    echo "\n測試 4: 測試路由統計功能\n";

    $routingStats = RoutingServiceProvider::getRoutingStats($container);
    if (isset($routingStats['error'])) {
        echo "⚠️  路由統計暫時無法取得: {$routingStats['error']}\n";
    } else {
        echo "✅ 路由統計:\n";
        echo "   - 總路由數: {$routingStats['total_routes']}\n";
        echo "   - 載入檔案數: {$routingStats['files_loaded']}\n";
        echo '   - 路由群組: ' . implode(', ', array_keys($routingStats['groups'])) . "\n";

        foreach ($routingStats['groups'] as $group => $count) {
            echo "     * {$group}: {$count} 條路由\n";
        }
    }

    echo "\n測試 5: 測試服務實例化正確性\n";

    // 測試同一服務的多次取得是否為單例
    $router1 = $container->get(RouterInterface::class);
    $router2 = $container->get(RouterInterface::class);

    if ($router1 === $router2) {
        echo "✅ 路由器服務單例模式正確\n";
    } else {
        echo "⚠️  路由器服務非單例模式\n";
    }

    $validator1 = $container->get(RouteValidator::class);
    $validator2 = $container->get(RouteValidator::class);

    if ($validator1 === $validator2) {
        echo "✅ 驗證器服務單例模式正確\n";
    } else {
        echo "⚠️  驗證器服務非單例模式\n";
    }

    echo "\n測試 6: 測試路由檔案配置\n";

    $routeFiles = RoutingServiceProvider::getRouteFiles();
    echo "✅ 路由檔案配置:\n";
    foreach ($routeFiles as $group => $filePath) {
        $exists = file_exists($filePath) ? '✅' : '❌';
        echo "   {$exists} {$group}: {$filePath}\n";
    }

    echo "\n測試 7: 測試完整請求處理\n";

    // 建立測試請求
    $request = new class implements ServerRequestInterface {
        private string $method = 'GET';

        private string $uri = '/api/health';

        private array $headers = [];

        public function getServerParams(): array
        {
            return [];
        }

        public function getCookieParams(): array
        {
            return [];
        }

        public function withCookieParams(array $cookies): self
        {
            return $this;
        }

        public function getQueryParams(): array
        {
            return [];
        }

        public function withQueryParams(array $query): self
        {
            return $this;
        }

        public function getUploadedFiles(): array
        {
            return [];
        }

        public function withUploadedFiles(array $uploadedFiles): self
        {
            return $this;
        }

        public function getParsedBody()
        {
            return null;
        }

        public function withParsedBody($data): self
        {
            return $this;
        }

        public function getAttributes(): array
        {
            return [];
        }

        public function getAttribute($name, $default = null)
        {
            return $default;
        }

        public function withAttribute($name, $value): self
        {
            return $this;
        }

        public function withoutAttribute($name): self
        {
            return $this;
        }

        public function getRequestTarget(): string
        {
            return $this->uri;
        }

        public function withRequestTarget($requestTarget): self
        {
            $new = clone $this;
            $new->uri = $requestTarget;

            return $new;
        }

        public function getMethod(): string
        {
            return $this->method;
        }

        public function withMethod($method): self
        {
            $new = clone $this;
            $new->method = $method;

            return $new;
        }

        public function getUri()
        {
            return new class ($this->uri) {
                private string $uri;

                public function __construct(string $uri)
                {
                    $this->uri = $uri;
                }

                public function __toString(): string
                {
                    return $this->uri;
                }

                public function getScheme(): string
                {
                    return 'http';
                }

                public function getAuthority(): string
                {
                    return 'localhost';
                }

                public function getUserInfo(): string
                {
                    return '';
                }

                public function getHost(): string
                {
                    return 'localhost';
                }

                public function getPort(): ?int
                {
                    return null;
                }

                public function getPath(): string
                {
                    return $this->uri;
                }

                public function getQuery(): string
                {
                    return '';
                }

                public function getFragment(): string
                {
                    return '';
                }

                public function withScheme($scheme): self
                {
                    return $this;
                }

                public function withUserInfo($user, $password = null): self
                {
                    return $this;
                }

                public function withHost($host): self
                {
                    return $this;
                }

                public function withPort($port): self
                {
                    return $this;
                }

                public function withPath($path): self
                {
                    return $this;
                }

                public function withQuery($query): self
                {
                    return $this;
                }

                public function withFragment($fragment): self
                {
                    return $this;
                }
            };
        }

        public function withUri($uri, $preserveHost = false): self
        {
            return $this;
        }

        public function getProtocolVersion(): string
        {
            return '1.1';
        }

        public function withProtocolVersion($version): self
        {
            return $this;
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
            return $this;
        }

        public function withAddedHeader($name, $value): self
        {
            return $this;
        }

        public function withoutHeader($name): self
        {
            return $this;
        }

        public function getBody()
        {
            return new class {
                public function __toString(): string
                {
                    return '';
                }

                public function close(): void {}

                public function detach()
                {
                    return null;
                }

                public function getSize(): ?int
                {
                    return 0;
                }

                public function tell(): int
                {
                    return 0;
                }

                public function eof(): bool
                {
                    return true;
                }

                public function isSeekable(): bool
                {
                    return false;
                }

                public function seek($offset, $whence = SEEK_SET): void {}

                public function rewind(): void {}

                public function isWritable(): bool
                {
                    return false;
                }

                public function write($string): int
                {
                    return 0;
                }

                public function isReadable(): bool
                {
                    return false;
                }

                public function read($length): string
                {
                    return '';
                }

                public function getContents(): string
                {
                    return '';
                }

                public function getMetadata($key = null)
                {
                    return null;
                }
            };
        }

        public function withBody($body): self
        {
            return $this;
        }
    };

    $response = $app->run($request);
    echo "✅ 請求處理成功\n";
    echo "   - 回應狀態: {$response->getStatusCode()}\n";
    echo '   - 內容類型: ' . $response->getHeaderLine('Content-Type') . "\n";
} catch (Exception $e) {
    echo '❌ 測試失敗: ' . $e->getMessage() . "\n";
    echo '   - 檔案: ' . $e->getFile() . ':' . $e->getLine() . "\n";
    if (method_exists($e, 'getTraceAsString')) {
        echo '   - 追蹤: ' . substr($e->getTraceAsString(), 0, 500) . "...\n";
    }
}

echo "\n=== 測試完成 ===\n";
