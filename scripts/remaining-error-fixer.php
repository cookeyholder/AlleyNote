<?php

/**
 * 針對剩餘 PHPStan Level 8 錯誤的專門修復工具
 * 處理 Application.php 的匿名類別問題和其他棘手的問題
 */

class RemainingErrorFixer
{
    private int $fixCount = 0;
    private array $processedFiles = [];

    public function run(): void
    {
        echo "開始修復剩餘的 PHPStan Level 8 錯誤...\n";

        // 修復 Application.php 的匿名類別問題
        $this->fixApplicationResponseInterface();

        // 修復 AttachmentController 的問題
        $this->fixAttachmentController();

        // 修復其他常見的 StreamInterface::write 問題
        $this->fixStreamWriteIssues();

        // 修復陣列參數類型問題
        $this->fixArrayParameterTypes();

        echo "\n修復完成！\n";
        echo "總修復次數: {$this->fixCount}\n";
        echo "修復的檔案數: " . count($this->processedFiles) . "\n";

        if (!empty($this->processedFiles)) {
            echo "已修復的檔案:\n";
            foreach ($this->processedFiles as $file) {
                echo "  - $file\n";
            }
        }
    }

    private function fixApplicationResponseInterface(): void
    {
        $filePath = '/var/www/html/app/Application.php';
        if (!file_exists($filePath)) {
            return;
        }

        echo "修復 Application.php 的 ResponseInterface 問題...\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 完全重寫 ResponseInterface 匿名類別實作
        $pattern = '/\$response = new class implements ResponseInterface \{.*?\};/s';

        $replacement = '$response = new class implements ResponseInterface {
            /** @var array<string, array<string>> */
            private array $headers = [\'Content-Type\' => [\'application/json\']];
            private string $body = \'\';
            private int $statusCode = 500;
            private string $reasonPhrase = \'Internal Server Error\';
            private string $protocolVersion = \'1.1\';

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

        $content = preg_replace($pattern, $replacement, $content);

        // 修復 json_encode 的 write 問題
        $content = preg_replace(
            '/\$stream->write\((json_encode\([^)]+\))\s*\?\?\s*\'\'\);/',
            '$stream->write(($1) ?: \'\');',
            $content
        );

        if ($content !== $originalContent && $this->isValidPhp($content)) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = 'app/Application.php';
            $this->fixCount++;
            echo "  ✓ 已修復 Application.php\n";
        }
    }

    private function fixAttachmentController(): void
    {
        $filePath = '/var/www/html/app/Application/Controllers/Api/V1/AttachmentController.php';
        if (!file_exists($filePath)) {
            return;
        }

        echo "修復 AttachmentController 的問題...\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復方法參數類型
        $content = preg_replace(
            '/public function download\(array \$args\):/',
            'public function download(array $args): ResponseInterface',
            $content
        );

        // 添加 @param 註解
        if (!preg_match('/@param\s+array<string,\s*mixed>\s+\$args/', $content)) {
            $content = preg_replace(
                '/(\/\*\*[^}]*?)(\*\/\s*public function download\(array \$args\))/s',
                "$1     * @param array<string, mixed> \$args\n     $2",
                $content
            );
        }

        // 修復所有 json_encode 的 write 呼叫
        $content = preg_replace(
            '/(\$[a-zA-Z_][a-zA-Z0-9_]*->write\()(json_encode\([^)]+\))(\))/i',
            '$1($2 ?: \'\')$3',
            $content
        );

        if ($content !== $originalContent && $this->isValidPhp($content)) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = 'app/Application/Controllers/Api/V1/AttachmentController.php';
            $this->fixCount++;
            echo "  ✓ 已修復 AttachmentController.php\n";
        }
    }

    private function fixStreamWriteIssues(): void
    {
        echo "修復 StreamInterface::write 問題...\n";

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;

            // 修復各種可能返回 false 的函數在 write() 中的使用
            $patterns = [
                // json_encode 問題
                '/(\$[a-zA-Z_][a-zA-Z0-9_]*->write\()(json_encode\([^)]+\))(\))/' => '$1($2 ?: \'\')$3',
                // file_get_contents 問題
                '/(\$[a-zA-Z_][a-zA-Z0-9_]*->write\()(file_get_contents\([^)]+\))(\))/' => '$1($2 ?: \'\')$3',
                // fread 問題
                '/(\$[a-zA-Z_][a-zA-Z0-9_]*->write\()(fread\([^)]+\))(\))/' => '$1($2 ?: \'\')$3',
            ];

            foreach ($patterns as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $content);
                if ($newContent !== $content) {
                    $content = $newContent;
                }
            }

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $relativePath = str_replace('/var/www/html/', '', $file);
                if (!in_array($relativePath, $this->processedFiles)) {
                    $this->processedFiles[] = $relativePath;
                    $this->fixCount++;
                }
            }
        }
    }

    private function fixArrayParameterTypes(): void
    {
        echo "修復陣列參數類型問題...\n";

        $files = $this->getPhpFiles();

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $originalContent = $content;

            // 修復方法參數中缺少類型規範的 array
            $patterns = [
                // 修復 function(array $param) 沒有泛型類型的問題
                '/(\s+function\s+[a-zA-Z_][a-zA-Z0-9_]*\([^)]*?)array(\s+\$[a-zA-Z_][a-zA-Z0-9_]*)([^)]*\))/' => '$1array<string, mixed>$2$3',
            ];

            foreach ($patterns as $pattern => $replacement) {
                // 只有當沒有已經指定泛型類型時才替換
                if (!preg_match('/array<[^>]+>/', $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                }
            }

            if ($content !== $originalContent && $this->isValidPhp($content)) {
                file_put_contents($file, $content);
                $relativePath = str_replace('/var/www/html/', '', $file);
                if (!in_array($relativePath, $this->processedFiles)) {
                    $this->processedFiles[] = $relativePath;
                    $this->fixCount++;
                }
            }
        }
    }

    private function getPhpFiles(): array
    {
        $files = [];

        $dirs = [
            '/var/www/html/app/Application/Controllers',
            '/var/www/html/app/Domains',
            '/var/www/html/app/Infrastructure',
            '/var/www/html/app/Shared'
        ];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }

        return $files;
    }

    private function isValidPhp(string $code): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpstan_fix_');
        file_put_contents($tempFile, $code);

        $result = shell_exec("php -l $tempFile 2>&1");
        unlink($tempFile);

        return strpos($result, 'No syntax errors detected') !== false;
    }
}

$fixer = new RemainingErrorFixer();
$fixer->run();
