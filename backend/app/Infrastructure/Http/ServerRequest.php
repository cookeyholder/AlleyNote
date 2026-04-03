<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest implements ServerRequestInterface
{
    private string $method;

    private UriInterface $uri;

    private array $headers = [];

    private array $serverParams = [];

    private array $cookieParams = [];

    private array $queryParams = [];

    private array $parsedBody = [];

    private array $attributes = [];

    private string $protocolVersion = '1.1';

    private StreamInterface $body;

    public function __construct(
        string $method,
        UriInterface $uri,
        array $headers = [],
        ?StreamInterface $body = null,
        string $version = '1.1',
        array $serverParams = [],
    ) {
        $this->method = $method;
        $this->uri = $uri;
        // 統一 Header 鍵值為小寫並合併同名標頭
        foreach ($headers as $name => $value) {
            $normalizedName = strtolower((string) $name);
            $newValues = is_array($value) ? $value : [$value];
            if (isset($this->headers[$normalizedName])) {
                $this->headers[$normalizedName] = array_merge($this->headers[$normalizedName], $newValues);
            } else {
                $this->headers[$normalizedName] = $newValues;
            }
        }
        // 確保 body 始終被初始化，即使傳入的是 null
        $this->body = $body ?? new Stream(fopen('php://temp', 'r+'));
        $this->protocolVersion = $version;
        $this->serverParams = $serverParams;
    }

    public function getRequestTarget(): string
    {
        return $this->uri->getPath();
    }

    public function withRequestTarget(string $requestTarget): self
    {
        $new = clone $this;
        $new->uri = $this->uri->withPath($requestTarget);

        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): self
    {
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): self
    {
        $new = clone $this;
        $new->uri = $uri;

        return $new;
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): self
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): self
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    public function getUploadedFiles(): array
    {
        return [];
    }

    public function withUploadedFiles(array $uploadedFiles): self
    {
        $new = clone $this;

        return $new;
    }

    public function getParsedBody(): ?array
    {
        return $this->parsedBody;
    }

    public function withParsedBody(mixed $data): self
    {
        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    public function withoutAttribute(string $name): self
    {
        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    // MessageInterface methods
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): self
    {
        $new = clone $this;
        $new->protocolVersion = $version;

        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, mixed $value): self
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = is_array($value) ? $value : [$value];

        return $new;
    }

    public function withAddedHeader(string $name, mixed $value): self
    {
        $new = clone $this;
        $name = strtolower($name);
        $new->headers[$name] = array_merge(
            $this->headers[$name] ?? [],
            is_array($value) ? $value : [$value],
        );

        return $new;
    }

    public function withoutHeader(string $name): self
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);

        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }
}
