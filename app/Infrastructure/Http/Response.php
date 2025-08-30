<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 Response 實作.
 */
class Response implements ResponseInterface
{
    private string $protocolVersion = '1.1';

    private int $statusCode = 200;

    private string $reasonPhrase = '';

    private array $headers = [];

    private array $headerNames = [];

    private StreamInterface $body;

    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $protocolVersion = '1.1',
        string $reasonPhrase = '',
    ) {
        $this->statusCode = $statusCode;
        $this->protocolVersion = $protocolVersion;
        $this->reasonPhrase = $reasonPhrase;

        if ($body instanceof StreamInterface) {
            $this->body = $body;
        } else {
            $this->body = new Stream($body);
        }

        foreach ($headers as $name => $value) {
            $this->withHeader($name, $value);
        }
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    public function getHeaders(): mixed
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): mixed
    {
        $name = strtolower($name);
        if (!isset($this->headerNames[$name])) {
            return [];
        }

        return $this->headers[$this->headerNames[$name]];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        $clone = clone $this;
        $normalizedName = strtolower($name);
        $clone->headerNames[$normalizedName] = $name;
        $clone->headers[$name] = is_array($value) ? $value : [$value];

        return $clone;
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        $clone = clone $this;
        $normalizedName = strtolower($name);

        if (isset($clone->headerNames[$normalizedName])) {
            $name = $clone->headerNames[$normalizedName];
            $clone->headers[$name] = array_merge($clone->headers[$name], is_array($value) ? $value : [$value]);
        } else {
            $clone->headerNames[$normalizedName] = $name;
            $clone->headers[$name] = is_array($value) ? $value : [$value];
        }

        return $clone;
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        $clone = clone $this;
        $normalizedName = strtolower($name);

        if (!isset($clone->headerNames[$normalizedName])) {
            return $clone;
        }

        $originalName = $clone->headerNames[$normalizedName];
        unset($clone->headers[$originalName], $clone->headerNames[$normalizedName]);

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase;

        return $clone;
    }

    public function getReasonPhrase(): string
    {
        if ($this->reasonPhrase) {
            return $this->reasonPhrase;
        }

        return $this->getDefaultReasonPhrase($this->statusCode);
    }

    private function getDefaultReasonPhrase(int $code): string
    {
        $phrases = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
        ];

        return $phrases[$code] ?? '';
    }
}
