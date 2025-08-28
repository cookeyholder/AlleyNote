#!/usr/bin/env php
<?php
/**
 * 匿名類別修復工具
 * 專門處理 PSR-7 ResponseInterface 和 StreamInterface 相關的匿名類別問題
 * 
 * 使用方式:
 * php scripts/anonymous-class-fixer.php [--dry-run]
 */

class AnonymousClassFixer
{
    private bool $dryRun = false;
    private int $fixCount = 0;

    public function __construct(array $args)
    {
        $this->dryRun = in_array('--dry-run', $args);
    }

    public function run(): void
    {
        echo "🔧 啟動匿名類別修復工具\n";
        echo "模式: " . ($this->dryRun ? "預覽模式" : "修復模式") . "\n\n";

        $this->fixApplicationPhp();

        echo "\n✅ 修復完成！修復次數: {$this->fixCount}\n";

        if ($this->dryRun) {
            echo "💡 這是預覽模式，要真正修復請移除 --dry-run 參數。\n";
        }
    }

    private function fixApplicationPhp(): void
    {
        $filePath = 'app/Application.php';

        if (!file_exists($filePath)) {
            echo "⚠️ 檔案不存在: $filePath\n";
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復 ResponseInterface 匿名類別
        $content = $this->fixResponseInterface($content);

        // 修復 StreamInterface 匿名類別  
        $content = $this->fixStreamInterface($content);

        if ($content !== $originalContent) {
            echo "📝 修復檔案: $filePath\n";

            if (!$this->dryRun) {
                file_put_contents($filePath, $content);
            }
        }
    }

    private function fixResponseInterface(string $content): string
    {
        // 尋找 ResponseInterface 匿名類別的模式
        $pattern = '/new\s+class.*?implements\s+ResponseInterface\s*\{.*?\}/s';

        if (preg_match($pattern, $content, $matches)) {
            $oldClass = $matches[0];

            // 生成完整的 ResponseInterface 實作
            $newClass = $this->generateResponseInterface();

            $content = str_replace($oldClass, $newClass, $content);
            $this->fixCount++;

            echo "🔄 修復 ResponseInterface 匿名類別\n";
        }

        return $content;
    }

    private function generateResponseInterface(): string
    {
        return 'new class implements ResponseInterface {
    private string $protocolVersion = \'1.1\';
    /** @var array<string, array<string>> */
    private array $headers = [];
    private StreamInterface $body;
    private int $statusCode = 200;
    private string $reasonPhrase = \'OK\';

    public function __construct()
    {
        $this->body = new class implements StreamInterface {
            private string $content = \'\';
            private int $position = 0;

            public function __toString(): string
            {
                return $this->content;
            }

            public function close(): void
            {
                // 實作關閉流
            }

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
                return $this->position;
            }

            public function eof(): bool
            {
                return $this->position >= strlen($this->content);
            }

            public function isSeekable(): bool
            {
                return true;
            }

            public function seek(int $offset, int $whence = SEEK_SET): void
            {
                switch ($whence) {
                    case SEEK_SET:
                        $this->position = $offset;
                        break;
                    case SEEK_CUR:
                        $this->position += $offset;
                        break;
                    case SEEK_END:
                        $this->position = strlen($this->content) + $offset;
                        break;
                }
            }

            public function rewind(): void
            {
                $this->position = 0;
            }

            public function isWritable(): bool
            {
                return true;
            }

            public function write(string $string): int
            {
                $this->content .= $string;
                $this->position += strlen($string);
                return strlen($string);
            }

            public function isReadable(): bool
            {
                return true;
            }

            public function read(int $length): string
            {
                $result = substr($this->content, $this->position, $length);
                $this->position += strlen($result);
                return $result;
            }

            public function getContents(): string
            {
                return substr($this->content, $this->position);
            }

            /** @return array<string, mixed> */
            public function getMetadata(?string $key = null): array
            {
                return [];
            }
        };
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

    /** @return array<string, array<string>> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /** @return array<string> */
    public function getHeader(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(\', \', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = is_array($value) ? $value : [$value];
        return $new;
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        $new = clone $this;
        $headerName = strtolower($name);
        $new->headers[$headerName] = array_merge(
            $this->headers[$headerName] ?? [],
            is_array($value) ? $value : [$value]
        );
        return $new;
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        $new = clone $this;
        $new->body = $body;
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
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}';
    }

    private function fixStreamInterface(string $content): string
    {
        // 尋找獨立的 StreamInterface 匿名類別
        $pattern = '/new\s+class.*?implements\s+StreamInterface\s*\{.*?\}/s';

        $content = preg_replace_callback($pattern, function ($matches) {
            $this->fixCount++;
            echo "🔄 修復 StreamInterface 匿名類別\n";

            return $this->generateStreamInterface();
        }, $content);

        return $content;
    }

    private function generateStreamInterface(): string
    {
        return 'new class implements StreamInterface {
    private string $content = \'\';
    private int $position = 0;

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void
    {
        // 實作關閉流
    }

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
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                break;
            case SEEK_CUR:
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = strlen($this->content) + $offset;
                break;
        }
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write(string $string): int
    {
        $this->content .= $string;
        $this->position += strlen($string);
        return strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $result = substr($this->content, $this->position, $length);
        $this->position += strlen($result);
        return $result;
    }

    public function getContents(): string
    {
        return substr($this->content, $this->position);
    }

    /** @return array<string, mixed> */
    public function getMetadata(?string $key = null): array
    {
        return [];
    }
}';
    }
}

// 執行腳本
if (php_sapi_name() === 'cli') {
    $fixer = new AnonymousClassFixer($argv);
    $fixer->run();
}
