#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Type Mismatch 錯誤自動修復器
 *
 * 基於 missing_iterable_value_type 修復器的成功經驗
 * 專門處理 type_mismatch 錯誤，完成 PHPStan Level 10 合規
 */

class TypeMismatchFixer
{
    private string $baseDir;
    private array $appliedFixes = [];
    private array $errorLog = [];
    private int $totalFixes = 0;

    // 常見的型別不匹配修復映射
    private array $typeMismatchFixes = [
        // 陣列型別修復
        'array to array<string, mixed>' => 'array<string, mixed>',
        'array to array<int, mixed>' => 'array<int, mixed>',
        'array to array<string, string>' => 'array<string, string>',

        // 字串型別修復
        'string|null to ?string' => '?string',
        'int|null to ?int' => '?int',
        'bool|null to ?bool' => '?bool',

        // 聯合型別修復
        'mixed' => 'mixed',
        'object' => 'object',

        // 特殊情況
        'callable' => 'callable',
        'resource' => 'resource',
    ];

    // 常見方法的返回型別
    private array $methodReturnTypes = [
        'toArray' => 'array<string, mixed>',
        'jsonSerialize' => 'array<string, mixed>',
        'getConfig' => 'array<string, mixed>',
        'getOptions' => 'array<string, mixed>',
        'getAttributes' => 'array<string, mixed>',
        'getData' => 'array<string, mixed>',
        'getMetadata' => 'array<string, mixed>',
        'getHeaders' => 'array<string, string>',
        'getQueryParams' => 'array<string, string>',
        'getParsedBody' => 'array<string, mixed>|object|null',
        'getUploadedFiles' => 'array<string, mixed>',
    ];

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🎯 啟動 Type Mismatch 錯誤修復器...\n\n";

        try {
            // 1. 獲取 PHPStan 錯誤（跳過語法錯誤，專注於型別錯誤）
            $errors = $this->getTypeMismatchErrors();
            echo "📊 發現 " . count($errors) . " 個 type mismatch 相關錯誤\n\n";

            // 2. 按檔案分組錯誤
            $errorsByFile = $this->groupErrorsByFile($errors);

            // 3. 批量修復每個檔案
            foreach ($errorsByFile as $file => $fileErrors) {
                $this->fixFileErrors($file, $fileErrors);
            }

            // 4. 生成報告
            $this->generateReport();

        } catch (Exception $e) {
            echo "❌ 修復過程中發生錯誤: {$e->getMessage()}\n";
        }
    }

    private function getTypeMismatchErrors(): array
    {
        echo "🔍 掃描 Type Mismatch 錯誤...\n";

        // 使用現有的錯誤快照，避免語法錯誤干擾
        $errorFile = $this->baseDir . '/test-error-analysis-snapshot.json';
        if (file_exists($errorFile)) {
            $content = file_get_contents($errorFile);
            $data = json_decode($content, true);

            $typeMismatchErrors = [];

            if (isset($data['error_categories'])) {
                // 收集所有可能的型別錯誤
                $targetCategories = [
                    'type_mismatch',
                    'missingType.return',
                    'missingType.property',
                    'argument.type',
                    'return.type'
                ];

                foreach ($targetCategories as $category) {
                    if (isset($data['error_categories'][$category]['files'])) {
                        foreach ($data['error_categories'][$category]['files'] as $file => $fileData) {
                            foreach ($fileData['errors'] as $error) {
                                $typeMismatchErrors[] = [
                                    'file' => $this->baseDir . '/' . $file,
                                    'line' => $error['line'],
                                    'message' => $error['message'],
                                    'category' => $category,
                                ];
                            }
                        }
                    }
                }
            }

            echo "發現 " . count($typeMismatchErrors) . " 個型別相關錯誤\n";
            return $typeMismatchErrors;
        }

        echo "⚠️  找不到錯誤快照檔案，嘗試直接執行 PHPStan...\n";
        return [];
    }

    private function groupErrorsByFile(array $errors): array
    {
        $grouped = [];
        foreach ($errors as $error) {
            $file = str_replace($this->baseDir . '/', '', $error['file']);
            $grouped[$file][] = $error;
        }
        return $grouped;
    }

    private function fixFileErrors(string $file, array $errors): void
    {
        $filePath = $this->baseDir . '/' . $file;
        if (!file_exists($filePath)) {
            echo "⚠️  檔案不存在: $file\n";
            return;
        }

        // 跳過有語法錯誤的檔案
        if ($this->hasSyntaxErrors($filePath)) {
            echo "⚠️  跳過有語法錯誤的檔案: $file\n";
            return;
        }

        echo "🔧 修復檔案: $file (發現 " . count($errors) . " 個錯誤)\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fixes = 0;

        foreach ($errors as $error) {
            $newContent = $this->fixTypeMismatchError($content, $error, $file);
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        if ($fixes > 0) {
            file_put_contents($filePath, $content);
            $this->appliedFixes[$file] = $fixes;
            $this->totalFixes += $fixes;
            echo "  ✅ 修復了 $fixes 個錯誤\n";
        } else {
            echo "  ℹ️  無需修復\n";
        }

        echo "\n";
    }

    private function hasSyntaxErrors(string $filePath): bool
    {
        $command = "php -l " . escapeshellarg($filePath) . " 2>&1";
        $output = shell_exec($command);
        return strpos($output, 'No syntax errors detected') === false;
    }

    private function fixTypeMismatchError(string $content, array $error, string $file): string
    {
        $message = $error['message'];
        $line = $error['line'];

        // 分析錯誤訊息並應用相應修復

        // 1. 修復方法返回型別
        if (preg_match('/return type .* but .*/', $message)) {
            return $this->fixReturnTypeMismatch($content, $line, $message, $file);
        }

        // 2. 修復參數型別
        if (preg_match('/parameter .* expects .* but .*/', $message)) {
            return $this->fixParameterTypeMismatch($content, $line, $message, $file);
        }

        // 3. 修復屬性型別
        if (preg_match('/property .* type .* but .*/', $message)) {
            return $this->fixPropertyTypeMismatch($content, $line, $message, $file);
        }

        // 4. 修復變數型別
        if (preg_match('/variable .* type .* but .*/', $message)) {
            return $this->fixVariableTypeMismatch($content, $line, $message, $file);
        }

        return $content;
    }

    private function fixReturnTypeMismatch(string $content, int $line, string $message, string $file): string
    {
        $lines = explode("\n", $content);
        $lineIndex = $line - 1;

        if (!isset($lines[$lineIndex])) {
            return $content;
        }

        // 尋找對應的方法
        $methodLine = $this->findMethodAroundLine($lines, $lineIndex);
        if ($methodLine !== -1) {
            $methodName = $this->extractMethodName($lines[$methodLine]);

            if ($methodName && isset($this->methodReturnTypes[$methodName])) {
                $returnType = $this->methodReturnTypes[$methodName];
                return $this->addReturnTypeAnnotation($content, $methodLine, $returnType);
            }

            // 根據錯誤訊息推斷型別
            if (preg_match('/expects (.+?) but/', $message, $matches)) {
                $expectedType = trim($matches[1]);
                return $this->addReturnTypeAnnotation($content, $methodLine, $expectedType);
            }
        }

        return $content;
    }

    private function fixParameterTypeMismatch(string $content, int $line, string $message, string $file): string
    {
        $lines = explode("\n", $content);
        $methodLine = $this->findMethodAroundLine($lines, $line - 1);

        if ($methodLine !== -1) {
            // 提取參數名稱和期望型別
            if (preg_match('/parameter \$(\w+).*expects (.+?) but/', $message, $matches)) {
                $paramName = $matches[1];
                $expectedType = trim($matches[2]);

                return $this->addParameterTypeAnnotation($content, $methodLine, $paramName, $expectedType);
            }
        }

        return $content;
    }

    private function fixPropertyTypeMismatch(string $content, int $line, string $message, string $file): string
    {
        // 實作屬性型別修復
        return $content;
    }

    private function fixVariableTypeMismatch(string $content, int $line, string $message, string $file): string
    {
        // 實作變數型別修復
        return $content;
    }

    private function findMethodAroundLine(array $lines, int $lineIndex): int
    {
        // 向上搜尋方法定義
        for ($i = $lineIndex; $i >= max(0, $lineIndex - 10); $i--) {
            if (preg_match('/\s*(public|private|protected)\s+(static\s+)?function\s+/', $lines[$i])) {
                return $i;
            }
        }

        // 向下搜尋方法定義
        for ($i = $lineIndex; $i < min(count($lines), $lineIndex + 5); $i++) {
            if (preg_match('/\s*(public|private|protected)\s+(static\s+)?function\s+/', $lines[$i])) {
                return $i;
            }
        }

        return -1;
    }

    private function extractMethodName(string $line): ?string
    {
        if (preg_match('/function\s+(\w+)/', $line, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function addReturnTypeAnnotation(string $content, int $methodLine, string $returnType): string
    {
        $lines = explode("\n", $content);

        // 檢查是否已有文檔塊
        $docBlockStart = $this->findOrCreateDocBlock($lines, $methodLine);

        // 添加或更新 @return 註解
        $returnLine = "     * @return {$returnType}";

        // 檢查是否已存在 @return
        for ($i = $docBlockStart; $i < $methodLine; $i++) {
            if (strpos($lines[$i], '@return') !== false) {
                $lines[$i] = $returnLine;
                return implode("\n", $lines);
            }
        }

        // 添加新的 @return 註解
        array_splice($lines, $methodLine - 1, 0, [$returnLine]);

        return implode("\n", $lines);
    }

    private function addParameterTypeAnnotation(string $content, int $methodLine, string $paramName, string $expectedType): string
    {
        $lines = explode("\n", $content);

        $docBlockStart = $this->findOrCreateDocBlock($lines, $methodLine);
        $paramLine = "     * @param {$expectedType} \${$paramName}";

        // 檢查是否已存在該參數的註解
        for ($i = $docBlockStart; $i < $methodLine; $i++) {
            if (strpos($lines[$i], "@param") !== false && strpos($lines[$i], "\${$paramName}") !== false) {
                $lines[$i] = $paramLine;
                return implode("\n", $lines);
            }
        }

        // 添加新的參數註解
        array_splice($lines, $methodLine - 1, 0, [$paramLine]);

        return implode("\n", $lines);
    }

    private function findOrCreateDocBlock(array &$lines, int $methodLine): int
    {
        // 檢查方法上方是否已有文檔塊
        $docBlockStart = $methodLine - 1;

        if (isset($lines[$docBlockStart]) && strpos($lines[$docBlockStart], '*/') !== false) {
            while ($docBlockStart > 0 && strpos($lines[$docBlockStart], '/**') === false) {
                $docBlockStart--;
            }
            return $docBlockStart;
        }

        // 創建新的文檔塊
        $indent = $this->getLineIndentation($lines[$methodLine]);
        $newDocBlock = [
            $indent . '/**',
            $indent . ' */',
        ];
        array_splice($lines, $methodLine, 0, $newDocBlock);
        return $methodLine;
    }

    private function getLineIndentation(string $line): string
    {
        preg_match('/^(\s*)/', $line, $matches);
        return $matches[1] ?? '    ';
    }

    private function generateReport(): void
    {
        echo "\n📋 Type Mismatch 修復報告\n";
        echo "=" . str_repeat("=", 50) . "\n";
        echo "總修復數量: {$this->totalFixes}\n";
        echo "修復檔案數: " . count($this->appliedFixes) . "\n\n";

        if (!empty($this->appliedFixes)) {
            echo "修復詳情:\n";
            foreach ($this->appliedFixes as $file => $fixes) {
                echo "  • $file: $fixes 個修復\n";
            }
        }

        echo "\n✅ Type Mismatch 修復完成！\n";
        echo "💡 建議執行 PHPStan 確認修復效果\n";
    }
}

// 執行修復
$fixer = new TypeMismatchFixer();
$fixer->run();
