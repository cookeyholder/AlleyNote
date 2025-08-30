<?php

declare(strict_types=1);

/**
 * 測試完整應用程式與路由載入系統 (Task 2.2).
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Application;
use Psr\Http\Message\ServerRequestInterface;

echo "=== 完整應用程式路由載入測試 ===\n\n";

try {
    echo "測試 1: 建立應用程式實例\n";
    $app = new Application();
    echo "✅ 應用程式初始化成功\n";

    echo "\n測試 2: 檢查路由器\n";
    $router = $app->getRouter();
    $routes = $router->getRoutes();
    echo "✅ 路由器初始化成功\n";
    echo '   - 路由集合類型: ' . get_class($routes) . "\n";

    echo "\n測試 3: 建立測試請求\n";

    // 建立 PSR-7 相容的請求
    $request = new class implements ServerRequestInterface {
        private string $method = 'GET';

        private string $uri = '/api/health';

        private array<mixed> $headers = [];

        public function getServerParams(): array<mixed>
        {
            return [];
        }

        public function getCookieParams(): array<mixed>
        {
            return [];
        }

        public function withCookieParams(array<mixed> $cookies): self
        {
            return $this;
        }

        public function getQueryParams(): array<mixed>
        {
            return [];
        }

        public function withQueryParams(array<mixed> $query): self
        {
            return $this;
        }

        public function getUploadedFiles(): array<mixed>
        {
            return [];
        }

        public function withUploadedFiles(array<mixed> $uploadedFiles): self
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

        public function getAttributes(): array<mixed>
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

        public function getHeaders(): array<mixed>
        {
            return $this->headers;
        }

        public function hasHeader($name): bool
        {
            return isset($this->headers[$name]);
        }

        public function getHeader($name): array<mixed>
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

    echo "✅ 測試請求建立成功\n";
    echo '   - 方法: ' . $request->getMethod() . "\n";
    echo '   - URI: ' . $request->getRequestTarget() . "\n";

    echo "\n測試 4: 處理請求\n";
    $response = $app->run($request);
    echo "✅ 請求處理成功\n";
    echo '   - 回應狀態: ' . $response->getStatusCode() . "\n";
    echo '   - 內容類型: ' . $response->getHeaderLine('Content-Type') . "\n";
    echo '   - 回應內容: ' . $response->getBody() . "\n";
} catch (Exception $e) {
    echo '❌ 測試失敗: ' . $e->getMessage() . "\n";
    echo '   - 檔案: ' . $e->getFile() . ':' . $e->getLine() . "\n";
    if (method_exists($e, 'getTraceAsString')) {
        echo '   - 追蹤: ' . substr($e->getTraceAsString(), 0, 500) . "...\n";
    }
}

echo "\n=== 測試完成 ===\n";
