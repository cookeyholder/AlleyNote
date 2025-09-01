<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Http\Message\UriInterface;

/**
 * 簡單的 PSR-7 URI 實作.
 */
class Uri implements UriInterface
{
    private string $scheme = '';

    private string $host = '';

    private ?int $port = null;

    private string $path = '';

    private string $query = '';

    private string $fragment = '';

    private string $userInfo = '';

    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $this->parseUri($uri);
        }
    }

    private function parseUri(string $uri): void
    {
        $parts = parse_url($uri);

        $this->scheme = $parts['scheme'] ?? '';
        $this->host = $parts['host'] ?? '';
        $this->port = $parts['port'] ?? null;
        $this->path = $parts['path'] ?? '/';
        $this->query = $parts['query'] ?? '';
        $this->fragment = $parts['fragment'] ?? '';

        if (isset($parts['user'])) {
            $this->userInfo = $parts['user'];
            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
        }
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = '';

        if ($this->userInfo !== '') {
            $authority .= $this->userInfo . '@';
        }

        $authority .= $this->host;

        if ($this->port !== null && !$this->isDefaultPort()) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    private function isDefaultPort(): bool
    {
        return ($this->scheme === 'http' && $this->port === 80)
            || ($this->scheme === 'https' && $this->port === 443);
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(mixed $scheme): self
    {
        $new = clone $this;
        $new->scheme = strtolower($scheme);

        return $new;
    }

    public function withUserInfo($user, mixed $password = null): self
    {
        $new = clone $this;
        $new->userInfo = $user;
        if ($password !== null) {
            $new->userInfo .= ':' . $password;
        }

        return $new;
    }

    public function withHost(mixed $host): self
    {
        $new = clone $this;
        $new->host = strtolower($host);

        return $new;
    }

    public function withPort(mixed $port): self
    {
        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    public function withPath(mixed $path): self
    {
        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    public function withQuery(mixed $query): self
    {
        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    public function withFragment(mixed $fragment): self
    {
        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        if ($this->getAuthority() !== '') {
            $uri .= '//' . $this->getAuthority();
        }

        $uri .= $this->path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }
}
