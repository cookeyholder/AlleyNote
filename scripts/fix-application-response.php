<?php

/**
 * 簡單修復 Application.php 的 ResponseInterface 實作
 */

$filePath = '/var/www/html/app/Application.php';
$content = file_get_contents($filePath);

// 重新實作 ResponseInterface 匿名類別
$newResponseClass = '        $response = new class implements ResponseInterface {
            private array $headers = [\'Content-Type\' => [\'application/json\']];
            private string $body = \'\';
            private int $statusCode = 500;
            private string $reasonPhrase = \'Internal Server Error\';
            private string $protocolVersion = \'1.1\';

            public function __construct()
            {
                // 初始化為空字串
            }

            public function getProtocolVersion(): string
            {
                return $this->protocolVersion;
            }

            public function withProtocolVersion(string $version): ResponseInterface
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
                return isset($this->headers[$name]);
            }

            public function getHeader(string $name): array
            {
                return $this->headers[$name] ?? [];
            }

            public function getHeaderLine(string $name): string
            {
                return implode(\', \', $this->getHeader($name));
            }

            public function withHeader(string $name, $value): ResponseInterface
            {
                $new = clone $this;
                $new->headers[$name] = (array) $value;
                return $new;
            }

            public function withAddedHeader(string $name, $value): ResponseInterface
            {
                $new = clone $this;
                if (!isset($new->headers[$name])) {
                    $new->headers[$name] = [];
                }
                $new->headers[$name] = array_merge($new->headers[$name], (array) $value);
                return $new;
            }

            public function withoutHeader(string $name): ResponseInterface
            {
                $new = clone $this;
                unset($new->headers[$name]);
                return $new;
            }

            public function getBody(): StreamInterface
            {
                return new class($this->body) implements StreamInterface {
                    private string $content;

                    public function __construct(string $content)
                    {
                        $this->content = $content;
                    }

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
                        return false;
                    }

                    public function write(string $string): int
                    {
                        return 0;
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
                };
            }

            public function withBody(StreamInterface $body): ResponseInterface
            {
                $new = clone $this;
                $new->body = (string) $body;
                return $new;
            }

            public function getStatusCode(): int
            {
                return $this->statusCode;
            }

            public function withStatus(int $code, string $reasonPhrase = \'\'): ResponseInterface
            {
                $new = clone $this;
                $new->statusCode = $code;
                if ($reasonPhrase !== \'\') {
                    $new->reasonPhrase = $reasonPhrase;
                }
                return $new;
            }

            public function getReasonPhrase(): string
            {
                return $this->reasonPhrase;
            }
        };';

// 查找並替換舊的匿名類別實作
$pattern = '/\$response = new class implements ResponseInterface \{.*?\};/s';
$content = preg_replace($pattern, $newResponseClass, $content);

// 確保語法正確後寫入檔案
$tempFile = tempnam(sys_get_temp_dir(), 'app_fix_');
file_put_contents($tempFile, $content);
$result = shell_exec("php -l $tempFile 2>&1");
unlink($tempFile);

if (strpos($result, 'No syntax errors detected') !== false) {
    file_put_contents($filePath, $content);
    echo "✅ 成功修復 Application.php 的 ResponseInterface 實作\n";
} else {
    echo "❌ 語法錯誤，修復失敗: $result\n";
}
