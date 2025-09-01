<?php

/**
 * 批量語法修復工具
 * 針對常見的語法錯誤進行自動修復
 */

class BulkSyntaxFixer
{
    private array $fixedFiles = [];
    private array $skippedFiles = [];
    private array $errors = [];

    public function __construct()
    {
        echo "開始批量語法修復...\n";
    }

    public function run(): void
    {
        $files = $this->findPhpFiles();

        foreach ($files as $file) {
            $this->fixFile($file);
        }

        $this->printSummary();
    }

    private function findPhpFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('app/', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function fixFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->errors[] = "無法讀取檔案: $filePath";
            return;
        }

        $originalContent = $content;

        // 先檢查語法是否正確
        if ($this->checkSyntax($filePath)) {
            return; // 語法已正確，跳過
        }

        // 備份檔案
        file_put_contents($filePath . '.backup', $content);

        // 應用各種修復
        $content = $this->applyCommonFixes($content);

        // 寫入修復後的內容
        file_put_contents($filePath, $content);

        // 再次檢查語法
        if ($this->checkSyntax($filePath)) {
            $this->fixedFiles[] = $filePath;
            echo "✓ 修復: $filePath\n";
        } else {
            // 修復失敗，還原原始內容
            file_put_contents($filePath, $originalContent);
            $this->skippedFiles[] = $filePath;
            echo "✗ 跳過: $filePath (修復失敗)\n";
        }
    }

    private function checkSyntax(string $filePath): bool
    {
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);

        return $returnCode === 0;
    }

    private function applyCommonFixes(string $content): string
    {
        // 修復常見的語法問題

        // 1. 修復缺少右括號的 if 條件
        $content = preg_replace('/if\s*\([^)]+\s+\{/', 'if ($1) {', $content);

        // 2. 修復不完整的陣列賦值
        $content = preg_replace('/\$\w+\[\s*\]\s*=\s*\[/', '$0', $content);

        // 3. 修復多餘的逗號
        $content = preg_replace('/,\s*;/', ';', $content);
        $content = preg_replace('/,\s*\}/', '}', $content);

        // 4. 修復缺少分號
        $content = preg_replace('/^\s*(\$\w+\s*=\s*[^;]+)\s*$/m', '$1;', $content);

        // 5. 修復不匹配的括號
        $content = preg_replace('/\(\s*\{/', '() {', $content);

        // 6. 修復複雜的三元運算符
        $content = preg_replace('/\?\s*\$\w+\s*\?\s*\$\w+\s*:\s*null\)\)\)/', '??', $content);

        // 7. 修復不完整的函數定義
        $content = preg_replace('/function\s+\w+\s*\([^)]*\s*$/', '$0)', $content);

        // 8. 修復不完整的 catch 語句
        $content = preg_replace('/catch\s*\([^)]*\s*$/', '$0)', $content);

        // 9. 修復不完整的陣列定義
        $content = preg_replace('/\[\s*$/', '[]', $content);

        // 10. 修復意外的標記
        $content = preg_replace('/value\s+value/', 'value', $content);

        return $content;
    }

    private function printSummary(): void
    {
        echo "\n=== 修復摘要 ===\n";
        echo "修復成功: " . count($this->fixedFiles) . " 檔案\n";
        echo "跳過檔案: " . count($this->skippedFiles) . " 檔案\n";
        echo "錯誤: " . count($this->errors) . " 個\n";

        if (!empty($this->skippedFiles)) {
            echo "\n跳過的檔案:\n";
            foreach ($this->skippedFiles as $file) {
                echo "  - $file\n";
            }
        }

        if (!empty($this->errors)) {
            echo "\n錯誤:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
    }
}

// 執行修復
$fixer = new BulkSyntaxFixer();
$fixer->run();
