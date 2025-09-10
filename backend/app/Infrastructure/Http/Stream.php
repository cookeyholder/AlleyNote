<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * PSR-7 Stream 實作.
 */
class Stream implements StreamInterface
{
    /** @var resource|null */
    private mixed $stream;

    private bool $seekable;

    private bool $readable;

    private bool $writable;

    /**
     * @param string|resource $body 初始內容
     */
    public function __construct($body = '')
    {
        if (is_string($body)) {
            $resource = fopen('php://temp', 'r+');
            if ($resource === false) {
                throw new RuntimeException('Unable to create temp stream');
            }
            $this->stream = $resource;
            fwrite($this->stream, $body);
            rewind($this->stream);
        } elseif (is_resource($body)) {
            $this->stream = $body;
        } else {
            throw new InvalidArgumentException('Body must be a string or resource');
        }

        $metadata = $this->getMetadata();
        $this->seekable = (bool) ($metadata['seekable'] ?? false);

        $mode = (string) ($metadata['mode'] ?? '');
        $this->readable = str_contains($mode, 'r') || str_contains($mode, '+');
        $this->writable = str_contains($mode, 'w') || str_contains($mode, 'a') || str_contains($mode, 'x') || str_contains($mode, 'c') || str_contains($mode, '+');
    }

    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->seek(0);
        }

        return $this->getContents();
    }

    public function close(): void
    {
        if ($this->stream !== null && is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->detach();
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;

        return is_resource($stream) ? $stream : null;
    }

    public function getSize(): ?int
    {
        if ($this->stream === null || !is_resource($this->stream)) {
            return null;
        }

        $stats = fstat($this->stream);
        if ($stats !== false) {
            return $stats['size'];
        }

        return null;
    }

    public function tell(): int
    {
        if ($this->stream === null || !is_resource($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        $result = ftell($this->stream);
        if ($result === false) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function eof(): bool
    {
        if ($this->stream === null || !is_resource($this->stream)) {
            return true;
        }

        return feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->stream === null || !is_resource($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek to stream position');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write(string $string): int
    {
        if ($this->stream === null || !is_resource($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->writable) {
            throw new RuntimeException('Stream is not writable');
        }

        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read(int $length): string
    {
        if ($this->stream === null || !is_resource($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        if ($length <= 0) {
            return '';
        }

        $result = fread($this->stream, $length);
        if ($result === false) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    public function getContents(): string
    {
        if ($this->stream === null || !is_resource($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }

        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * @return array<string, mixed>|mixed|null
     */
    public function getMetadata(?string $key = null)
    {
        if ($this->stream === null || !is_resource($this->stream)) {
            return $key ? null : [];
        }

        $metadata = stream_get_meta_data($this->stream);

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }
}
