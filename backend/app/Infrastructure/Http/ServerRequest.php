<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * 簡單的 PSR-7 ServerRequest 實作.
 */
class ServerRequest implements ServerRequestInterface
{
    private string $method;

    private UriInterface $uri;

    /** @var array<string, array<string>> */
    private array $headers = [];

    private array $serverParams = [];

    private array $cookieParams = [];

    private array $queryParams = [];

    /** @var array<mixed>|object|null */
    private $parsedBody = [];

    private array $attributes = [];

    private string $protocolVersion = '1.1';

    /** @var mixed */
    private $body;

    /**
     * @param mixed $body
     */
    public function __construct(
        string $method,
        UriInterface $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = [],
    ) {
        $this->method = $method;
        $this->uri = $uri;

        // 驗證並轉換 headers
        foreach ($headers as $name => $value) {
            if (is_string($name)) {
                $normalizedName = strtolower($name);
                if (is_array($value)) {
                    /** @var array<string> $value */
                    $this->headers[$normalizedName] = $value;
                } elseif (is_string($value)) {
                    $this->headers[$normalizedName] = [$value];
                }
            }
        }

        $this->body = $body;
        $this->protocolVersion = $version;
        $this->serverParams = $serverParams;
    }

    public function getRequestTarget(): string
    {
        return $this->uri->getPath();
    }

    public function withRequestTarget(mixed $requestTarget): self
    {
        $new = clone $this;
        $new->uri = $this->uri->withPath($requestTarget);

        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(mixed $method): self
    {
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, mixed $preserveHost = false): self
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

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody(mixed $data): self
    {
        $new = clone $this;

        if (is_array($data) || is_object($data) || $data === null) {
            $new->parsedBody = $data;
        }

        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, mixed $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, mixed $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    public function withoutAttribute(mixed $name): self
    {
        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    // ResponseInterface methods
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(mixed $version): self
    {
        $new = clone $this;
        $new->protocolVersion = $version;

        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(mixed $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(mixed $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine(mixed $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, mixed $value): self
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = is_array($value) ? $value : [$value];

        return $new;
    }

    public function withAddedHeader($name, mixed $value): self
    {
        $new = clone $this;
        $name = strtolower($name);
        $existingValues = $this->headers[$name] ?? [];
        $newValues = is_array($value) ? $value : [$value];
        /** @var array<string> $newValues */
        $new->headers[$name] = array_merge($existingValues, $newValues);

        return $new;
    }

    public function withoutHeader(mixed $name): self
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);

        return $new;
    }

    public function getBody()
    {
        if ($this->body instanceof StreamInterface) {
            return $this->body;
        }

        // 如果 body 不是 StreamInterface，建立一個空的 Stream
        return new Stream('');
    }

    public function withBody(mixed $body): self
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }
}
