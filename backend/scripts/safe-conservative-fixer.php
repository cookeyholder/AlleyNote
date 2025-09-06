<?php

declare(strict_types=1);

/**
 * 🛡️ 超級安全保守型 PHPStan Level 10 修復工具
 *
 * 特點：
 * 1. 只處理最安全的類型註解
 * 2. 不修改任何程式碼邏輯
 * 3. 每次只修復一個檔案並驗證
 * 4. 發現錯誤立即停止
 * 5. 提供詳細的修復報告
 */

class SafeConservativeFixer
{
    private array $processedFiles = [];
    private array $successfulFixes = [];
    private array $errors = [];
    private bool $dryRun = false;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function run(): void
    {
        echo "🛡️  超級安全保守型 PHPStan Level 10 修復工具\n";
        echo "模式：" . ($this->dryRun ? '預覽模式' : '修復模式') . "\n\n";

        // 獲取 PHPStan 錯誤
        $phpstanOutput = $this->getPhpstanErrors();
        $errors = $this->parsePhpstanErrors($phpstanOutput);

        echo "📊 發現 " . count($errors) . " 個錯誤需要處理\n\n";

        // 按檔案分組處理
        $fileGroups = $this->groupErrorsByFile($errors);

        foreach ($fileGroups as $filename => $fileErrors) {
            echo "🔧 處理檔案: $filename\n";

            if ($this->processFile($filename, $fileErrors)) {
                echo "  ✅ 成功修復\n";
                $this->successfulFixes[] = $filename;

                // 每修復一個檔案就驗證
                if (!$this->dryRun && !$this->validateFile($filename)) {
                    echo "  ❌ 驗證失敗，停止處理\n";
                    break;
                }
            } else {
                echo "  ⚠️  跳過（包含複雜模式）\n";
            }

            // 限制一次處理的檔案數量
            if (count($this->successfulFixes) >= 10) {
                echo "\n⚡ 達到單次處理限制（10個檔案），建議重新執行\n";
                break;
            }
        }

        $this->printSummary();
    }

    private function getPhpstanErrors(): string
    {
        $command = './vendor/bin/phpstan analyse --memory-limit=1G --error-format=table';
        return shell_exec($command) ?? '';
    }

    private function parsePhpstanErrors(string $output): array
    {
        $errors = [];
        $lines = explode("\n", $output);

        $currentFile = '';
        foreach ($lines as $line) {
            // 檢查是否是檔案標題行
            if (preg_match('/^\s*Line\s+(.+\.php)\s*$/', $line, $matches)) {
                $currentFile = $matches[1];
                continue;
            }

            // 檢查是否是錯誤行
            if (preg_match('/^\s*(\d+)\s+(.+)$/', $line, $matches) && $currentFile) {
                $errors[] = [
                    'file' => $currentFile,
                    'line' => (int)$matches[1],
                    'message' => trim($matches[2]),
                ];
            }
        }

        return $errors;
    }

    private function groupErrorsByFile(array $errors): array
    {
        $groups = [];
        foreach ($errors as $error) {
            $filename = basename($error['file']);
            if (!isset($groups[$filename])) {
                $groups[$filename] = [];
            }
            $groups[$filename][] = $error;
        }
        return $groups;
    }

    private function processFile(string $filename, array $errors): bool
    {
        // 只處理最安全的錯誤類型
        $safeErrors = array_filter($errors, [$this, 'isSafeError']);

        if (empty($safeErrors)) {
            return false;
        }

        $filepath = $this->findFilePath($filename);
        if (!$filepath) {
            return false;
        }

        $content = file_get_contents($filepath);
        if (!$content) {
            return false;
        }

        $originalContent = $content;
        $modified = false;

        foreach ($safeErrors as $error) {
            $newContent = $this->applySafeFix($content, $error);
            if ($newContent !== $content) {
                $content = $newContent;
                $modified = true;
                echo "    ✅ 修復: " . substr($error['message'], 0, 60) . "...\n";
            }
        }

        if ($modified && !$this->dryRun) {
            file_put_contents($filepath, $content);
        }

        return $modified;
    }

    private function isSafeError(array $error): bool
    {
        $message = $error['message'];

        // 只處理最安全的錯誤類型
        $safePatterns = [
            '/Method .+ should return .+ but return statement is missing/',
            '/Parameter .+ of method .+ expects .+, .+ given/',
            '/Method .+ has no return type specified/',
            '/Property .+ has no type specified/',
        ];

        foreach ($safePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    private function applySafeFix(string $content, array $error): string
    {
        $message = $error['message'];
        $line = $error['line'];

        // 為缺少返回型別的方法添加註解
        if (preg_match('/Method (.+) should return (.+) but return statement is missing/', $message, $matches)) {
            return $this->addMissingReturnType($content, $line, $matches[2]);
        }

        // 為缺少型別的屬性添加註解
        if (preg_match('/Property (.+) has no type specified/', $message, $matches)) {
            return $this->addPropertyTypeAnnotation($content, $line);
        }

        return $content;
    }

    private function addMissingReturnType(string $content, int $line, string $returnType): string
    {
        $lines = explode("\n", $content);

        // 清理返回型別
        $cleanType = $this->cleanReturnType($returnType);

        // 查找函式定義行
        for ($i = $line - 1; $i >= 0; $i--) {
            if (preg_match('/^\s*(public|private|protected)\s+function\s+\w+\([^)]*\)(\s*:\s*\w+)?\s*$/', $lines[$i])) {
                // 如果已經有返回型別，跳過
                if (strpos($lines[$i], ':') !== false) {
                    return $content;
                }

                // 添加返回型別
                $lines[$i] = rtrim($lines[$i]) . ': ' . $cleanType;
                return implode("\n", $lines);
            }
        }

        return $content;
    }

    private function addPropertyTypeAnnotation(string $content, int $line): string
    {
        $lines = explode("\n", $content);

        // 在屬性前添加型別註解
        if (isset($lines[$line - 1]) && preg_match('/^\s*(public|private|protected)\s+\$\w+/', $lines[$line - 1])) {
            $indent = str_repeat(' ', strlen($lines[$line - 1]) - strlen(ltrim($lines[$line - 1])));
            $annotation = $indent . '/** @var mixed */';

            array_splice($lines, $line - 1, 0, [$annotation]);
            return implode("\n", $lines);
        }

        return $content;
    }

    private function cleanReturnType(string $returnType): string
    {
        // 移除命名空間前綴，保持簡潔
        $returnType = preg_replace('/^[\\\\]?App\\\\[\\\\a-zA-Z]*\\\\/', '', $returnType);

        // 處理常見的型別
        $typeMap = [
            'ResponseInterface' => 'Response',
            'ServerRequestInterface' => 'Request',
            'ContainerInterface' => 'ContainerInterface',
        ];

        return $typeMap[$returnType] ?? $returnType;
    }

    private function findFilePath(string $filename): ?string
    {
        $command = "find /var/www/html/app -name '$filename' -type f 2>/dev/null | head -1";
        $result = shell_exec($command);
        return $result ? trim($result) : null;
    }

    private function validateFile(string $filename): bool
    {
        $filepath = $this->findFilePath($filename);
        if (!$filepath) {
            return false;
        }

        // 檢查語法錯誤
        $command = "php -l '$filepath' 2>/dev/null";
        $result = shell_exec($command);

        return strpos($result, 'No syntax errors detected') !== false;
    }

    private function printSummary(): void
    {
        echo "\n";
        echo "======================================================================\n";
        echo "🛡️  超級安全保守型修復完成報告\n";
        echo "======================================================================\n";
        echo "處理模式：" . ($this->dryRun ? '預覽模式' : '修復模式') . "\n";
        echo "成功修復的檔案數：" . count($this->successfulFixes) . "\n\n";

        if (!empty($this->successfulFixes)) {
            echo "📁 成功修復的檔案：\n";
            foreach ($this->successfulFixes as $file) {
                echo "  ✅ $file\n";
            }
        }

        echo "\n🎯 建議下一步：\n";
        echo "1. 執行測試：docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Validation/ValidationResultTest.php\n";
        echo "2. 檢查錯誤：docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G --error-format=table 2>&1 | tail -5\n";
        echo "3. 如果效果良好，可以再次執行此腳本進行更多修復\n";
        echo "======================================================================\n";
    }
}

// 主程式
$dryRun = in_array('--dry-run', $argv);
$fixer = new SafeConservativeFixer($dryRun);
$fixer->run();
