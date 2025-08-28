<?php
/**
 * Enhanced PHPStan Level 8 修復工具
 * 處理更複雜的類型問題
 */

class EnhancedPhpstanFixer
{
    private int $fixCount = 0;
    private array $processedFiles = [];

    public function run(): void
    {
        echo "開始增強型 PHPStan 修復...\n";
        
        // 獲取所有需要修復的檔案
        $files = $this->getPhpFiles();
        
        foreach ($files as $file) {
            echo "處理檔案: $file\n";
            $this->processFile($file);
        }
        
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

    private function getPhpFiles(): array
    {
        $files = [];
        
        // 應用層檔案
        $appDirs = [
            '/var/www/html/app',
            '/var/www/html/config',
            '/var/www/html/tests'
        ];
        
        foreach ($appDirs as $dir) {
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

    private function processFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }
        
        $originalContent = file_get_contents($filePath);
        $content = $originalContent;
        $hasChanges = false;
        
        // 1. 修復 StreamInterface write 問題
        $content = $this->fixStreamInterfaceWrite($content, $hasChanges);
        
        // 2. 修復陣列參數類型
        $content = $this->fixArrayParameterTypes($content, $hasChanges);
        
        // 3. 修復匿名類別屬性類型
        $content = $this->fixAnonymousClassProperties($content, $hasChanges);
        
        // 4. 修復 iterableValue 問題
        $content = $this->fixIterableValueTypes($content, $hasChanges);
        
        // 5. 修復缺少的屬性類型
        $content = $this->fixMissingPropertyTypes($content, $hasChanges);
        
        // 6. 修復方法參數和返回類型
        $content = $this->fixMethodSignatures($content, $hasChanges);
        
        // 7. 修復類別屬性初始化
        $content = $this->fixPropertyInitialization($content, $hasChanges);
        
        if ($hasChanges && $this->isValidPhp($content)) {
            file_put_contents($filePath, $content);
            $this->processedFiles[] = str_replace('/var/www/html/', '', $filePath);
            echo "  ✓ 已修復\n";
        }
    }

    private function fixStreamInterfaceWrite(string $content, bool &$hasChanges): string
    {
        // 修復 StreamInterface::write() 的 string|false 問題
        $pattern = '/(\$[a-zA-Z_][a-zA-Z0-9_]*->write\()([^)]+)(\))/';
        
        $content = preg_replace_callback($pattern, function($matches) use (&$hasChanges) {
            $param = trim($matches[2]);
            
            // 如果參數可能是 false，添加類型轉換
            if (strpos($param, 'json_encode') !== false) {
                $newParam = "$param ?: ''";
                $hasChanges = true;
                $this->fixCount++;
                return $matches[1] . $newParam . $matches[3];
            }
            
            // 如果是其他可能返回 false 的函數
            if (preg_match('/file_get_contents|fread|fgets/', $param)) {
                $newParam = "$param ?: ''";
                $hasChanges = true;
                $this->fixCount++;
                return $matches[1] . $newParam . $matches[3];
            }
            
            return $matches[0];
        }, $content);
        
        return $content;
    }

    private function fixArrayParameterTypes(string $content, bool &$hasChanges): string
    {
        // 修復方法參數中的 array 類型
        $patterns = [
            // 公開方法參數
            '/public function ([a-zA-Z_][a-zA-Z0-9_]*)\(([^)]*\barray\b[^)]*)\)/' => function($matches) use (&$hasChanges) {
                $params = $matches[2];
                if (strpos($params, 'array<') === false && strpos($params, '/** @var') === false) {
                    $newParams = str_replace('array $', 'array<string, mixed> $', $params);
                    if ($newParams !== $params) {
                        $hasChanges = true;
                        $this->fixCount++;
                        return 'public function ' . $matches[1] . '(' . $newParams . ')';
                    }
                }
                return $matches[0];
            },
            
            // 私有方法參數
            '/private function ([a-zA-Z_][a-zA-Z0-9_]*)\(([^)]*\barray\b[^)]*)\)/' => function($matches) use (&$hasChanges) {
                $params = $matches[2];
                if (strpos($params, 'array<') === false) {
                    $newParams = str_replace('array $', 'array<string, mixed> $', $params);
                    if ($newParams !== $params) {
                        $hasChanges = true;
                        $this->fixCount++;
                        return 'private function ' . $matches[1] . '(' . $newParams . ')';
                    }
                }
                return $matches[0];
            },
            
            // 受保護的方法參數
            '/protected function ([a-zA-Z_][a-zA-Z0-9_]*)\(([^)]*\barray\b[^)]*)\)/' => function($matches) use (&$hasChanges) {
                $params = $matches[2];
                if (strpos($params, 'array<') === false) {
                    $newParams = str_replace('array $', 'array<string, mixed> $', $params);
                    if ($newParams !== $params) {
                        $hasChanges = true;
                        $this->fixCount++;
                        return 'protected function ' . $matches[1] . '(' . $newParams . ')';
                    }
                }
                return $matches[0];
            }
        ];
        
        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }
        
        return $content;
    }

    private function fixAnonymousClassProperties(string $content, bool &$hasChanges): string
    {
        // 修復匿名類別中的屬性類型
        if (preg_match('/new class.*?implements.*?ResponseInterface.*?\{(.*?)\}/s', $content, $matches)) {
            $classBody = $matches[1];
            $originalClassBody = $classBody;
            
            // 修復 headers 屬性
            if (strpos($classBody, 'private $headers') !== false && strpos($classBody, 'array<string, mixed>') === false) {
                $classBody = preg_replace(
                    '/private \$headers([^;]*);/',
                    'private array $headers$1;',
                    $classBody
                );
            }
            
            // 修復 body 屬性
            if (strpos($classBody, 'private $body') !== false && strpos($classBody, ': string') === false) {
                $classBody = preg_replace(
                    '/private \$body([^;]*);/',
                    'private string $body$1;',
                    $classBody
                );
            }
            
            if ($classBody !== $originalClassBody) {
                $content = str_replace($originalClassBody, $classBody, $content);
                $hasChanges = true;
                $this->fixCount++;
            }
        }
        
        return $content;
    }

    private function fixIterableValueTypes(string $content, bool &$hasChanges): string
    {
        // 修復 iterableValue 問題
        $patterns = [
            // 屬性宣告
            '/private array \$([a-zA-Z_][a-zA-Z0-9_]*);/' => function($matches) use (&$hasChanges) {
                $propName = $matches[1];
                // 常見的陣列屬性類型推斷
                $commonTypes = [
                    'headers' => 'array<string, string>',
                    'config' => 'array<string, mixed>',
                    'data' => 'array<string, mixed>',
                    'params' => 'array<string, mixed>',
                    'options' => 'array<string, mixed>',
                    'attributes' => 'array<string, mixed>',
                    'metadata' => 'array<string, mixed>',
                    'rules' => 'array<string, mixed>',
                    'filters' => 'array<string, mixed>',
                ];
                
                $type = $commonTypes[$propName] ?? 'array<string, mixed>';
                $hasChanges = true;
                $this->fixCount++;
                return "private {$type} \${$propName};";
            }
        ];
        
        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }
        
        return $content;
    }

    private function fixMissingPropertyTypes(string $content, bool &$hasChanges): string
    {
        // 修復缺少類型的屬性
        $patterns = [
            // 私有屬性
            '/private \$([a-zA-Z_][a-zA-Z0-9_]*);/' => function($matches) use (&$hasChanges) {
                $propName = $matches[1];
                // 根據屬性名稱推斷類型
                $typeMap = [
                    'id' => 'int',
                    'name' => 'string',
                    'title' => 'string',
                    'content' => 'string',
                    'message' => 'string',
                    'status' => 'string',
                    'type' => 'string',
                    'path' => 'string',
                    'url' => 'string',
                    'token' => 'string',
                    'key' => 'string',
                    'value' => 'string',
                    'count' => 'int',
                    'size' => 'int',
                    'length' => 'int',
                    'timestamp' => 'int',
                    'created_at' => 'string',
                    'updated_at' => 'string',
                    'is_active' => 'bool',
                    'enabled' => 'bool',
                    'visible' => 'bool',
                ];
                
                $type = $typeMap[$propName] ?? 'mixed';
                $hasChanges = true;
                $this->fixCount++;
                return "private {$type} \${$propName};";
            }
        ];
        
        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }
        
        return $content;
    }

    private function fixMethodSignatures(string $content, bool &$hasChanges): string
    {
        // 修復缺少返回類型的方法
        $pattern = '/public function ([a-zA-Z_][a-zA-Z0-9_]*)\([^)]*\)\s*\{/';
        
        $content = preg_replace_callback($pattern, function($matches) use (&$hasChanges) {
            $methodName = $matches[1];
            
            // 如果已經有返回類型，跳過
            if (strpos($matches[0], '):') !== false) {
                return $matches[0];
            }
            
            // 根據方法名稱推斷返回類型
            $returnTypes = [
                'getId' => ': int',
                'getName' => ': string',
                'getTitle' => ': string',
                'getContent' => ': string',
                'getMessage' => ': string',
                'getStatus' => ': string',
                'getType' => ': string',
                'getPath' => ': string',
                'getUrl' => ': string',
                'isActive' => ': bool',
                'isEnabled' => ': bool',
                'isVisible' => ': bool',
                'exists' => ': bool',
                'has' => ': bool',
                'count' => ': int',
                'size' => ': int',
                'length' => ': int',
                'toArray' => ': array',
                'getAll' => ': array',
                'getList' => ': array',
            ];
            
            if (isset($returnTypes[$methodName])) {
                $newSignature = str_replace('{', $returnTypes[$methodName] . ' {', $matches[0]);
                $hasChanges = true;
                $this->fixCount++;
                return $newSignature;
            }
            
            return $matches[0];
        }, $content);
        
        return $content;
    }

    private function fixPropertyInitialization(string $content, bool &$hasChanges): string
    {
        // 修復未初始化的屬性
        $patterns = [
            // 陣列屬性初始化
            '/private array<([^>]+)> \$([a-zA-Z_][a-zA-Z0-9_]*);/' => function($matches) use (&$hasChanges) {
                $type = $matches[1];
                $propName = $matches[2];
                $hasChanges = true;
                $this->fixCount++;
                return "private array \${$propName} = [];";
            }
        ];
        
        foreach ($patterns as $pattern => $callback) {
            $content = preg_replace_callback($pattern, $callback, $content);
        }
        
        return $content;
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

$fixer = new EnhancedPhpstanFixer();
$fixer->run();