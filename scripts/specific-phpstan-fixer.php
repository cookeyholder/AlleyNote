<?php
/**
 * 特定問題修復工具
 * 針對剩餘的 PHPStan Level 8 錯誤進行精準修復
 */

class SpecificPhpstanFixer
{
    private int $fixCount = 0;
    private array $processedFiles = [];

    public function run(): void
    {
        echo "開始特定問題修復...\n";
        
        // 修復特定檔案的特定問題
        $this->fixApplicationPhp();
        $this->fixAttachmentController();
        $this->fixAuthController(); 
        $this->fixIpController();
        
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

    private function fixApplicationPhp(): void
    {
        $filePath = '/var/www/html/app/Application.php';
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復匿名類別的屬性類型問題
        $content = preg_replace_callback(
            '/new class.*?implements.*?ResponseInterface.*?\{(.*?)\}/s',
            function($matches) {
                $classBody = $matches[1];
                
                // 修復 headers 屬性類型
                $classBody = preg_replace(
                    '/private array \$headers/',
                    'private array $headers',
                    $classBody
                );
                
                // 添加 headers 類型註解
                if (!preg_match('/@var\s+array<string,\s*string>\s+\$headers/', $classBody)) {
                    $classBody = preg_replace(
                        '/(private array \$headers[^;]*;)/',
                        "/** @var array<string, string> */\n    $1",
                        $classBody
                    );
                }
                
                // 修復 body 屬性的 Stringable 問題
                $classBody = preg_replace(
                    '/\$this->body = new class.*?\{.*?\};/s',
                    '$this->body = (string) $body;',
                    $classBody
                );
                
                // 修復 getBody 方法返回類型問題
                if (preg_match('/public function getBody\(\).*?\{(.*?)\}/s', $classBody, $bodyMatch)) {
                    $bodyMethod = str_replace(
                        'return $this->body;',
                        'return new class($this->body) implements \Psr\Http\Message\StreamInterface {
                            private string $content;
                            public function __construct(string $content) { $this->content = $content; }
                            public function __toString(): string { return $this->content; }
                            public function close(): void {}
                            public function detach() { return null; }
                            public function getSize(): ?int { return strlen($this->content); }
                            public function tell(): int { return 0; }
                            public function eof(): bool { return true; }
                            public function isSeekable(): bool { return false; }
                            public function seek(int $offset, int $whence = SEEK_SET): void {}
                            public function rewind(): void {}
                            public function isWritable(): bool { return false; }
                            public function write(string $string): int { return 0; }
                            public function isReadable(): bool { return true; }
                            public function read(int $length): string { return $this->content; }
                            public function getContents(): string { return $this->content; }
                            public function getMetadata(?string $key = null) { return null; }
                        };',
                        $bodyMatch[0]
                    );
                    $classBody = str_replace($bodyMatch[0], $bodyMethod, $classBody);
                }
                
                return str_replace($matches[1], $classBody, $matches[0]);
            },
            $content
        );

        if ($content !== $originalContent && $this->isValidPhp($content)) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = 'app/Application.php';
            $this->fixCount++;
            echo "  ✓ 修復 Application.php 的匿名類別問題\n";
        }
    }

    private function fixAttachmentController(): void
    {
        $filePath = '/var/www/html/app/Application/Controllers/Api/V1/AttachmentController.php';
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復 StreamInterface::write() 的 string|false 問題
        $patterns = [
            // json_encode 相關的 write 呼叫
            '/(\$[a-zA-Z_][a-zA-Z0-9_]*->write\()(json_encode\([^)]+\))(\))/i' => function($matches) {
                return $matches[1] . $matches[2] . ' ?: \'\'' . $matches[3];
            },
            
            // 其他可能返回 false 的函數呼叫
            '/(\$[a-zA-Z_][a-zA-Z0-9_]*->write\()(file_get_contents\([^)]+\)|fread\([^)]+\)|fgets\([^)]+\))(\))/i' => function($matches) {
                return $matches[1] . $matches[2] . ' ?: \'\'' . $matches[3];
            }
        ];

        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }

        // 修復方法參數的 array 類型
        $content = preg_replace(
            '/public function download\(array \$args\)/',
            'public function download(array $args): ResponseInterface',
            $content
        );

        // 添加 array 參數的類型註解
        if (!preg_match('/@param\s+array<string,\s*mixed>\s+\$args/', $content)) {
            $content = preg_replace(
                '/(\/\*\*[^}]*\*\/\s*public function download)\(array \$args\)/',
                "$1(array \$args)",
                $content
            );
        }

        if ($content !== $originalContent && $this->isValidPhp($content)) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = 'app/Application/Controllers/Api/V1/AttachmentController.php';
            $this->fixCount++;
            echo "  ✓ 修復 AttachmentController 的 StreamInterface 問題\n";
        }
    }

    private function fixAuthController(): void
    {
        $filePath = '/var/www/html/app/Application/Controllers/Api/V1/AuthController.php';
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復 array|object|null 轉換為 array<string, mixed> 的問題
        $patterns = [
            // RegisterUserDTO 構造函數呼叫
            '/new RegisterUserDTO\(\s*([^,]+),\s*(\$[a-zA-Z_][a-zA-Z0-9_]*)\s*\)/' => function($matches) {
                $secondParam = $matches[2];
                return "new RegisterUserDTO({$matches[1]}, (array) {$secondParam})";
            },
            
            // AuthService::login() 呼叫
            '/(\$[a-zA-Z_][a-zA-Z0-9_]*->login\()([\$a-zA-Z_][a-zA-Z0-9_]*)(\))/' => function($matches) {
                $param = $matches[2];
                return "{$matches[1]}(array) {$param}{$matches[3]}";
            }
        ];

        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }

        if ($content !== $originalContent && $this->isValidPhp($content)) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = 'app/Application/Controllers/Api/V1/AuthController.php';
            $this->fixCount++;
            echo "  ✓ 修復 AuthController 的類型轉換問題\n";
        }
    }

    private function fixIpController(): void
    {
        $filePath = '/var/www/html/app/Application/Controllers/Api/V1/IpController.php';
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復方法參數和返回類型的 array 規範
        $patterns = [
            // create 方法
            '/public function create\(array \$request\): array/' => 'public function create(array $request): array',
            
            // getByType 方法
            '/public function getByType\([^)]*\): array/' => function($matches) {
                return str_replace(': array', ': array', $matches[0]);
            }
        ];

        // 添加方法參數的詳細類型註解
        if (!preg_match('/@param\s+array<string,\s*mixed>\s+\$request.*create/', $content)) {
            $content = preg_replace(
                '/(\/\*\*[^}]*?)(\*\/\s*public function create\(array \$request\))/s',
                "$1     * @param array<string, mixed> \$request\n     $2",
                $content
            );
        }

        // 添加返回類型註解
        if (!preg_match('/@return\s+array<string,\s*mixed>.*create/', $content)) {
            $content = preg_replace(
                '/(\/\*\*[^}]*?)(\*\/\s*public function create\(array \$request\))/s',
                "$1     * @return array<string, mixed>\n     $2",
                $content
            );
        }

        if ($content !== $originalContent && $this->isValidPhp($content)) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = 'app/Application/Controllers/Api/V1/IpController.php';
            $this->fixCount++;
            echo "  ✓ 修復 IpController 的陣列類型問題\n";
        }
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

$fixer = new SpecificPhpstanFixer();
$fixer->run();