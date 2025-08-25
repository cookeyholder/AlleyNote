<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * 簡單的 PSR-7 ServerRequest 實作.
 */
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

    private $body;

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
        $this->headers = $headers;
        $this->body = $body;
        $this->protocolVersion = $version;
        $this->serverParams = $serverParams;
    }

    public function getRequestTarget(): string
    {
        return $this->uri->getPath();
    }

    public function withRequestTarget($requestTarget): self
    {
        $new = clone $this;
        $new->uri = $this->uri->withPath($requestTarget);

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

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
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

    public function withParsedBody($data): self
    {
        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value): self
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    public function withoutAttribute($name): self
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
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader($name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = is_array($value) ? $value : [$value];

        return $new;
    }

    public function withAddedHeader($name, $value): self
    {
        $new = clone $this;
        $name = strtolower($name);
        $new->headers[$name] = array_merge(
            $this->headers[$name] ?? [],
            is_array($value) ? $value : [$value],
        );

        return $new;
    }

    public function withoutHeader($name): self
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);

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
}
