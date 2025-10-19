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

    /** @var array<string, array<string>> */
    private array $headers = [];

    /** @var array<string, string> */
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
            $this->body = new Stream($body ?? '');
        }

        foreach ($headers as $name => $value) {
            if (is_string($name)) {
                $normalizedName = strtolower($name);
                $this->headerNames[$normalizedName] = $name;

                if (is_array($value)) {
                    /** @var array<string> $value */
                    $this->headers[$name] = $value;
                } elseif (is_string($value)) {
                    $this->headers[$name] = [$value];
                } elseif (is_scalar($value)) {
                    $this->headers[$name] = [(string) $value];
                }
            }
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

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
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

        if (is_array($value)) {
            /** @var array<string> $value */
            $clone->headers[$name] = $value;
        } elseif (is_string($value)) {
            $clone->headers[$name] = [$value];
        } elseif (is_scalar($value)) {
            $clone->headers[$name] = [(string) $value];
        } else {
            $clone->headers[$name] = [];
        }

        return $clone;
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        $clone = clone $this;
        $normalizedName = strtolower($name);

        if (isset($clone->headerNames[$normalizedName])) {
            $name = $clone->headerNames[$normalizedName];
            $existingValues = $clone->headers[$name] ?? [];

            if (is_array($value)) {
                /** @var array<string> $value */
                $newValues = $value;
            } elseif (is_string($value)) {
                $newValues = [$value];
            } elseif (is_scalar($value)) {
                $newValues = [(string) $value];
            } else {
                $newValues = [];
            }

            $clone->headers[$name] = array_merge($existingValues, $newValues);
        } else {
            $clone->headerNames[$normalizedName] = $name;

            if (is_array($value)) {
                /** @var array<string> $value */
                $clone->headers[$name] = $value;
            } elseif (is_string($value)) {
                $clone->headers[$name] = [$value];
            } elseif (is_scalar($value)) {
                $clone->headers[$name] = [(string) $value];
            } else {
                $clone->headers[$name] = [];
            }
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
