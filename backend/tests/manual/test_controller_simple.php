<?php

declare(strict_types=1);

// 自動載入 Composer 依賴
require_once __DIR__ . '/././vendor/autoload.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

// 簡單的 Mock 實作用於測試
class MockStream implements StreamInterface
{
    private string $content = '';

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void {}

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
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

    public function seek(int $offset, int $whence = SEEK_SET): void {}

    public function rewind(): void {}

    public function isWritable(): bool
    {
        return true;
    }

    public function write(string $string): int
    {
        $this->content .= $string;

        return strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        return $this->content;
    }

    public function getContents(): string
    {
        return $this->content;
    }

    public function getMetadata(?string $key = null)
    {
        return null;
    }
}

class MockResponse implements ResponseInterface
{
    private MockStream $body;

    private int $statusCode = 200;

    private array $headers = [];

    public function __construct()
    {
        $this->body = new MockStream();
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        $new = clone $this;
        $new->headers[$name] = is_array($value) ? $value : [$value];

        return $new;
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
        return $this;
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
        return '';
    }
}

class MockRequest implements ServerRequestInterface
{
    private array $attributes = [];

    public function __construct(string $method, private string $path) {}

    public function getRequestTarget(): string
    {
        return $this->path;
    }

    public function withRequestTarget(string $requestTarget): ServerRequestInterface
    {
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): ServerRequestInterface
    {
        return $this;
    }

    public function getUri(): object
    {
        return new class ($this->path) {
            public function __construct(private string $path) {}

            public function getPath(): string
            {
                return $this->path;
            }
        };
    }

    public function withUri($uri, bool $preserveHost = false): ServerRequestInterface
    {
        return $this;
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): ServerRequestInterface
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function hasHeader(string $name): bool
    {
        return false;
    }

    public function getHeader(string $name): array
    {
        return [];
    }

    public function getHeaderLine(string $name): string
    {
        return '';
    }

    public function withHeader(string $name, $value): ServerRequestInterface
    {
        return $this;
    }

    public function withAddedHeader(string $name, $value): ServerRequestInterface
    {
        return $this;
    }

    public function withoutHeader(string $name): ServerRequestInterface
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return new MockStream();
    }

    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        return $this;
    }

    public function getServerParams(): array
    {
        return [];
    }

    public function getCookieParams(): array
    {
        return [];
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return $this;
    }

    public function getQueryParams(): array
    {
        return [];
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        return $this;
    }

    public function getUploadedFiles(): array
    {
        return [];
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return $this;
    }

    public function getParsedBody()
    {
        return [];
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        return $this;
    }
}

echo '=== 控制器整合測試 ===

';

// 初始化應用程序
