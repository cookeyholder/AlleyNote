<?php

declare(strict_types=1);

/**
 * 修復類結構語法錯誤的腳本
 *
 * 主要修復以下問題：
 * - "unexpected T_PUBLIC" 錯誤
 * - "unexpected T_PRIVATE" 錯誤
 * - "unexpected T_PROTECTED" 錯誤
 * - 類方法結構問題
 * - 類屬性宣告問題
 */

class ClassStructureSyntaxFixer
{
    private int $filesProcessed = 0;
    private int $issuesFixed = 0;
    private array $fixedPatterns = [];

    public function run(): void
    {
        echo "🔧 修復類結構語法錯誤...\n";

        $this->processAllPhpFiles();

        echo "\n✅ 類結構語法修復完成！\n";
        echo "📊 處理了 {$this->filesProcessed} 個檔案，修正了 {$this->issuesFixed} 個問題\n";

        if (!empty($this->fixedPatterns)) {
            echo "\n🎯 修復的模式：\n";
            foreach ($this->fixedPatterns as $pattern => $count) {
                echo "  - {$pattern}: {$count} 次\n";
            }
        }
    }

    private function processAllPhpFiles(): void
    {
        $directories = [
            __DIR__ . '/../app',
            __DIR__ . '/../tests',
        ];

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->processDirectory($dir);
            }
        }
    }

    private function processDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }

    private function processFile(string $filePath): void
    {
        $originalContent = file_get_contents($filePath);
        if ($originalContent === false) {
            return;
        }

        $content = $originalContent;
        $hasChanges = false;

        // 修復各種類結構語法錯誤
        $content = $this->fixClassStructureSyntax($content, $hasChanges, $filePath);

        if ($hasChanges) {
            file_put_contents($filePath, $content);
            echo "修復檔案: " . str_replace(__DIR__ . '/../', '', $filePath) . "\n";
        }

        $this->filesProcessed++;
    }

    private function fixClassStructureSyntax(string $content, bool &$hasChanges, string $filePath): string
    {
        $patterns = [
            // 修復缺少方法結束大括號的問題
            // } public function -> }\n\n    public function
            '/\}\s*(public|private|protected)\s+(function|static)/s' => "}\n\n    $1 $2",

            // 修復缺少類結束大括號的問題
            // } class -> }\n\nclass
            '/\}\s*(class|interface|trait)\s+/s' => "}\n\n$1 ",

            // 修復方法之間缺少適當間距的問題
            '/(\})\s*(\/\*\*.*?\*\/\s*)?(public|private|protected)/s' => "$1\n\n    $2$3",

            // 修復屬性宣告語法錯誤
            // public $var; private $var2; -> public $var;\n    private $var2;
            '/;(\s*)(public|private|protected)\s+(static\s+)?\$/s' => ";\n\n    $2 $3$",

            // 修復常數宣告語法錯誤
            // ; public const -> ;\n\n    public const
            '/;\s*(public|private|protected)\s+(const)/s' => ";\n\n    $1 $2",

            // 修復方法可見性關鍵字重複問題
            '/\b(public|private|protected)\s+(public|private|protected)\s+/s' => '$1 ',

            // 修復靜態關鍵字位置錯誤
            '/\b(function)\s+(static)\s+/s' => '$2 $1 ',

            // 修復缺少方法名的問題
            '/(public|private|protected)\s+(static\s+)?function\s*\(/s' => '$1 $2function unnamed(',

            // 修復多餘的分號在方法結束後
            '/\}\s*;\s*(public|private|protected)/s' => "}\n\n    $1",

            // 修復類開始大括號前的語法錯誤
            '/\bclass\s+(\w+)([^{]*)\s*\{\s*(public|private|protected)/s' => "class $1$2\n{\n    $3",

            // 修復 EOF 語法錯誤 - 確保檔案以換行結束
            '/([^}\s])\s*$/s' => "$1\n",

            // 修復不正確的方法結尾
            '/\}\s*\}\s*(public|private|protected)/s' => "}\n\n    $1",

            // 修復缺少逗號的參數列表
            '/\$(\w+)\s+\$(\w+)\s*\)/s' => '$$$1, $$$2)',

            // 修復重複的訪問修飾符
            '/(public|private|protected)\s+\1\s+/s' => '$1 ',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $matches = [];
                preg_match_all($pattern, $content, $matches);
                $count = count($matches[0]);

                if ($count > 0) {
                    $patternKey = substr($pattern, 0, 30) . '...';
                    $this->fixedPatterns[$patternKey] = ($this->fixedPatterns[$patternKey] ?? 0) + $count;
                    $this->issuesFixed += $count;
                    $hasChanges = true;
                    $content = $newContent;

                    echo "  修復 {$count} 個類結構問題在 " . basename($filePath) . "\n";
                }
            }
        }

        // 特殊處理控制器檔案
        if (strpos($filePath, 'Controller.php') !== false) {
            $content = $this->fixControllerSpecificSyntax($content, $hasChanges, $filePath);
        }

        // 特殊處理DTO檔案
        if (strpos($filePath, 'DTO.php') !== false || strpos($filePath, 'DTOs') !== false) {
            $content = $this->fixDTOSpecificSyntax($content, $hasChanges, $filePath);
        }

        return $content;
    }

    private function fixControllerSpecificSyntax(string $content, bool &$hasChanges, string $filePath): string
    {
        $controllerPatterns = [
            // 修復控制器方法之間的結構問題
            '/\}\s*#\[OA\\\\/s' => "}\n\n    #[OA\\",

            // 修復OpenAPI屬性前的語法
            '/\]\s*(public|private|protected)\s+function/s' => "]\n    $1 function",

            // 修復控制器方法的可見性聲明
            '/\bfunction\s+(\w+)\s*\(/s' => 'public function $1(',

            // 修復缺少方法結束的問題
            '/return\s+\$response[^}]*$/s' => function($matches) {
                return $matches[0] . "\n    }";
            },
        ];

        foreach ($controllerPatterns as $pattern => $replacement) {
            if (is_callable($replacement)) {
                $newContent = preg_replace_callback($pattern, $replacement, $content);
            } else {
                $newContent = preg_replace($pattern, $replacement, $content);
            }

            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $hasChanges = true;
                $this->issuesFixed++;
                echo "  修復控制器特定語法在 " . basename($filePath) . "\n";
            }
        }

        return $content;
    }

    private function fixDTOSpecificSyntax(string $content, bool &$hasChanges, string $filePath): string
    {
        $dtoPatterns = [
            // 修復DTO屬性宣告
            '/\bpublic\s+\$(\w+)\s*;\s*public\s+\$(\w+)/s' => "public \$$1;\n    public \$$2",

            // 修復DTO方法結構
            '/\}\s*public\s+function\s+(\w+)/s' => "}\n\n    public function $1",

            // 修復DTO構造函數
            '/public\s+function\s+__construct\s*\(/s' => "\n    public function __construct(",
        ];

        foreach ($dtoPatterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $hasChanges = true;
                $this->issuesFixed++;
                echo "  修復DTO特定語法在 " . basename($filePath) . "\n";
            }
        }

        return $content;
    }

    private function fixMethodStructure(string $content): string
    {
        // 修復方法結構的特殊情況
        $lines = explode("\n", $content);
        $fixedLines = [];
        $inClass = false;
        $braceLevel = 0;

        foreach ($lines as $lineNumber => $line) {
            $trimmed = trim($line);

            // 計算大括號層級
            $braceLevel += substr_count($line, '{') - substr_count($line, '}');

            // 檢查是否在類內部
            if (preg_match('/\bclass\s+\w+/', $trimmed)) {
                $inClass = true;
            }

            // 如果在類內部且發現訪問修飾符但前面沒有適當的結束
            if ($inClass && preg_match('/^\s*(public|private|protected)\s+/', $trimmed)) {
                $prevLine = $fixedLines[count($fixedLines) - 1] ?? '';

                // 如果前一行不是以}結尾，添加缺少的}
                if (!preg_match('/\}\s*$/', $prevLine) && !empty(trim($prevLine))) {
                    $fixedLines[] = "    }";
                    $fixedLines[] = "";
                }
            }

            $fixedLines[] = $line;

            // 如果大括號層級回到0，表示類結束
            if ($inClass && $braceLevel <= 0) {
                $inClass = false;
            }
        }

        return implode("\n", $fixedLines);
    }
}

// 執行修復
if (php_sapi_name() === 'cli') {
    $fixer = new ClassStructureSyntaxFixer();
    $fixer->run();
} else {
    echo "此腳本只能在命令列執行\n";
}
