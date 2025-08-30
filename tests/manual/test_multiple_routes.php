<?php

declare(strict_types=1);

/**
 * 測試多種路由配置 (Task 2.2 進階測試).
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Application;
use Psr\Http\Message\ServerRequestInterface;

echo "=== 多種路由配置測試 ===\n\n";

$app = new Application();

// 測試不同的路由
$testCases = [
    [
        'name' => 'API 健康檢查',
        'method' => 'GET',
        'uri' => '/api/health',
    ],
    [
        'name' => 'API 資訊',
        'method' => 'GET',
        'uri' => '/api',
    ],
    [
        'name' => '首頁',
        'method' => 'GET',
        'uri' => '/',
    ],
    [
        'name' => '貼文清單',
        'method' => 'GET',
        'uri' => '/api/posts',
    ],
    [
        'name' => '單一貼文',
        'method' => 'GET',
        'uri' => '/api/posts/123',
    ],
    [
        'name' => '管理員系統資訊',
        'method' => 'GET',
        'uri' => '/api/admin/info/system',
    ],
    [
        'name' => '不存在的路由 (404 測試)',
        'method' => 'GET',
        'uri' => '/api/nonexistent',
    ],
];

foreach ($testCases as $index => $testCase) {
    echo '測試 ' . ($index + 1) . ': ' . ((is_array($testCase) ? $testCase['name'] : (is_object($testCase) ? $testCase->name : null)) ?? 'Unknown') . "\n";

    try {
        // 建立測試請求
        $request = new class (((is_array($testCase) ? $testCase['method'] : (is_object($testCase) ? $testCase->method : null)) ?? 'GET'), ((is_array($testCase) ? $testCase['uri'] : (is_object($testCase) ? $testCase->uri : null)) ?? '/')) implements ServerRequestInterface {
            private string $method;

            private string $uri;

            private array<mixed> $headers = [];

            public function __construct(string $method, string $uri)
            {
                $this->method = $method;
                $this->uri = $uri;
            }

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
                $new = clone $this;

                return $new;
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

        $response = $app->run($request);

        echo "   ✅ 狀態: {$response->getStatusCode()}\n";
        $bodyContent = (string) $response->getBody();
        echo '   📄 內容: ' . substr(str_replace(["\n", '    '], [' ', ' '], $bodyContent), 0, 100);
        if (strlen($bodyContent) > 100) {
            echo '...';
        }
        echo "\n";
    } catch (Exception $e) {
        echo '   ❌ 錯誤: ' . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "=== 測試完成 ===\n";
