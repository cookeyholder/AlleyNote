#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 智能 missing_iterable_value_type 錯誤批量修復腳本
 * 
 * 基於既有經驗和 PHPStan 輸出，自動識別並修復 missing_iterable_value_type 錯誤
 * 
 * 功能特色：
 * - 自動掃描 PHPStan 錯誤
 * - 智能模式識別和修復
 * - 批量處理同類檔案
 * - 詳細修復報告
 * - 安全回滾機制
 */

class SmartIterableValueTypeFixer
{
    private string $baseDir;
    private array $appliedFixes = [];
    private array $errorLog = [];
    private array $backupFiles = [];
    private int $totalFixes = 0;

    // 常見的陣列型別映射
    private array $commonArrayTypes = [
        'args' => 'array<string, mixed>',
        'data' => 'array<string, mixed>',
        'params' => 'array<string, mixed>',
        'options' => 'array<string, mixed>',
        'config' => 'array<string, mixed>',
        'metadata' => 'array<string, mixed>',
        'attributes' => 'array<string, mixed>',
        'request' => 'array<string, mixed>',
        'response' => 'array<string, mixed>',
        'results' => 'array<int, mixed>',
        'items' => 'array<int, mixed>',
        'list' => 'array<int, mixed>',
        'statistics' => 'array<string, mixed>',
        'metrics' => 'array<string, mixed>',
    ];

    // DTO 方法的返回型別映射
    private array $dtoReturnTypes = [
        'toArray' => 'array<string, mixed>',
        'jsonSerialize' => 'array<string, mixed>',
        'getFormattedData' => 'array<string, mixed>',
        'getFormattedOverview' => 'array<string, mixed>',
        'getFormattedDistribution' => 'array<string, mixed>',
        'getSummary' => 'array<string, mixed>',
        'getDistributionSummary' => 'array<string, mixed>',
        'compareWith' => 'array<string, mixed>',
        'getActivitySummary' => 'array<string, mixed>',
        'getTopSources' => 'array<int, SourceStatistics>',
        'getSourceRanking' => 'array<int, array<string, mixed>>',
    ];

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🤖 啟動智能 missing_iterable_value_type 錯誤修復器...\n\n";

        try {
            // 1. 獲取 PHPStan 錯誤
            $errors = $this->getPHPStanErrors();
            echo "📊 發現 " . count($errors) . " 個 missing_iterable_value_type 錯誤\n\n";

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
            $this->rollbackChanges();
        }
    }

    private function getPHPStanErrors(): array
    {
        echo "🔍 掃描 PHPStan missing_iterable_value_type 錯誤...\n";

        // 在 Docker 容器內執行 PHPStan
        $command = './vendor/bin/phpstan analyse --memory-limit=1G --error-format=json 2>/dev/null';
        $output = shell_exec($command);

        if (!$output) {
            echo "⚠️  直接執行失敗，嘗試使用完整路徑...\n";
            $command = '/var/www/html/vendor/bin/phpstan analyse --memory-limit=1G --error-format=json 2>/dev/null';
            $output = shell_exec($command);
        }

        if (!$output) {
            // 如果仍然失敗，嘗試讀取現有的錯誤報告
            echo "⚠️  PHPStan 執行失敗，嘗試讀取現有錯誤報告...\n";
            $errorFile = $this->baseDir . '/test-error-analysis-snapshot.json';
            if (file_exists($errorFile)) {
                $output = file_get_contents($errorFile);
                echo "✅ 使用現有錯誤報告: $errorFile\n";
            }
        }

        if (!$output) {
            throw new Exception('無法獲取 PHPStan 錯誤資料');
        }

        $data = json_decode($output, true);
        if (!$data) {
            throw new Exception('PHPStan 輸出格式錯誤: ' . json_last_error_msg());
        }

        $iterableValueErrors = [];
        
        // 處理不同格式的 PHPStan 輸出
        if (isset($data['files'])) {
            // 標準 PHPStan JSON 格式
            foreach ($data['files'] as $file => $fileData) {
                if (isset($fileData['messages'])) {
                    foreach ($fileData['messages'] as $message) {
                        if (isset($message['identifier']) && $message['identifier'] === 'missingType.iterableValue') {
                            $iterableValueErrors[] = [
                                'file' => $file,
                                'line' => $message['line'],
                                'message' => $message['message'],
                            ];
                        }
                    }
                }
            }
        } elseif (isset($data['error_categories'])) {
            // 我們的錯誤報告格式
            if (isset($data['error_categories']['missing_iterable_value_type']['files'])) {
                foreach ($data['error_categories']['missing_iterable_value_type']['files'] as $file => $fileData) {
                    foreach ($fileData['errors'] as $error) {
                        $iterableValueErrors[] = [
                            'file' => $this->baseDir . '/' . $file,
                            'line' => $error['line'],
                            'message' => $error['message'],
                        ];
                    }
                }
            }
        }

        echo "發現 " . count($iterableValueErrors) . " 個 missing_iterable_value_type 錯誤\n";
        return $iterableValueErrors;
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

        echo "🔧 修復檔案: $file (發現 " . count($errors) . " 個錯誤)\n";

        // 創建備份
        $this->createBackup($filePath);

        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fixes = 0;

        // 按行號排序錯誤（從高到低，避免行號偏移）
        usort($errors, fn($a, $b) => $b['line'] <=> $a['line']);

        foreach ($errors as $error) {
            $newContent = $this->fixError($content, $error, $file);
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

    private function fixError(string $content, array $error, string $file): string
    {
        $lines = explode("\n", $content);
        $lineIndex = $error['line'] - 1;

        if (!isset($lines[$lineIndex])) {
            return $content;
        }

        $currentLine = $lines[$lineIndex];
        $message = $error['message'];

        // 檢測錯誤類型並應用相應修復
        
        // 1. 修復方法參數中的陣列型別
        if (preg_match('/parameter \$(\w+) .* iterable type array/', $message, $matches)) {
            $paramName = $matches[1];
            $newContent = $this->fixMethodParameter($content, $lineIndex, $paramName);
            if ($newContent !== $content) {
                return $newContent;
            }
        }

        // 2. 修復方法返回型別
        if (preg_match('/return type .* iterable type array/', $message)) {
            $newContent = $this->fixMethodReturnType($content, $lineIndex, $file);
            if ($newContent !== $content) {
                return $newContent;
            }
        }

        // 3. 修復屬性型別
        if (preg_match('/property .* iterable type array/', $message)) {
            $newContent = $this->fixPropertyType($content, $lineIndex);
            if ($newContent !== $content) {
                return $newContent;
            }
        }

        return $content;
    }

    private function fixMethodParameter(string $content, int $lineIndex, string $paramName): string
    {
        $lines = explode("\n", $content);
        
        // 確定適當的型別
        $arrayType = $this->commonArrayTypes[$paramName] ?? 'array<string, mixed>';

        // 在方法上方添加 @param 註解
        $methodLineIndex = $this->findMethodLine($lines, $lineIndex);
        if ($methodLineIndex !== -1) {
            $docBlockStart = $this->findOrCreateDocBlock($lines, $methodLineIndex);
            $paramLine = "     * @param {$arrayType} \${$paramName}";
            
            // 檢查是否已經存在該參數的註解
            $docBlockEnd = $methodLineIndex;
            for ($i = $docBlockStart; $i < $methodLineIndex; $i++) {
                if (strpos($lines[$i], "@param") !== false && strpos($lines[$i], "\${$paramName}") !== false) {
                    // 更新現有註解
                    $lines[$i] = $paramLine;
                    return implode("\n", $lines);
                }
            }
            
            // 添加新的參數註解
            array_splice($lines, $methodLineIndex - 1, 0, [$paramLine]);
        }

        return implode("\n", $lines);
    }

    private function fixMethodReturnType(string $content, int $lineIndex, string $file): string
    {
        $lines = explode("\n", $content);
        
        // 檢測方法名稱
        $methodName = $this->extractMethodName($lines[$lineIndex]);
        if (!$methodName) {
            return $content;
        }

        // 確定返回型別
        $returnType = $this->dtoReturnTypes[$methodName] ?? 'array<string, mixed>';

        // 檢查是否是特殊情況
        if (strpos($file, 'DTO') !== false) {
            if (strpos($methodName, 'getTop') !== false || strpos($methodName, 'Ranking') !== false) {
                $returnType = 'array<int, mixed>';
            }
        }

        // 在方法上方添加 @return 註解
        $methodLineIndex = $this->findMethodLine($lines, $lineIndex);
        if ($methodLineIndex !== -1) {
            $docBlockStart = $this->findOrCreateDocBlock($lines, $methodLineIndex);
            $returnLine = "     * @return {$returnType}";
            
            // 檢查是否已經存在返回型別註解
            for ($i = $docBlockStart; $i < $methodLineIndex; $i++) {
                if (strpos($lines[$i], "@return") !== false) {
                    // 更新現有註解
                    $lines[$i] = $returnLine;
                    return implode("\n", $lines);
                }
            }
            
            // 添加新的返回型別註解
            array_splice($lines, $methodLineIndex - 1, 0, [$returnLine]);
        }

        return implode("\n", $lines);
    }

    private function fixPropertyType(string $content, int $lineIndex): string
    {
        $lines = explode("\n", $content);
        $currentLine = $lines[$lineIndex];

        // 修復屬性型別（通常在建構函式中）
        if (preg_match('/public\s+array\s+\$(\w+)/', $currentLine, $matches)) {
            $propertyName = $matches[1];
            $arrayType = $this->commonArrayTypes[$propertyName] ?? 'array<string, mixed>';
            
            // 在屬性上方添加 @var 註解
            $propertyDocLine = "    /** @var {$arrayType} */";
            array_splice($lines, $lineIndex, 0, [$propertyDocLine]);
        }

        return implode("\n", $lines);
    }

    private function findMethodLine(array $lines, int $startIndex): int
    {
        // 向下尋找方法定義行
        for ($i = $startIndex; $i < count($lines) && $i < $startIndex + 5; $i++) {
            if (preg_match('/public\s+function\s+\w+/', $lines[$i]) || 
                preg_match('/private\s+function\s+\w+/', $lines[$i]) ||
                preg_match('/protected\s+function\s+\w+/', $lines[$i]) ||
                preg_match('/public\s+static\s+function\s+\w+/', $lines[$i])) {
                return $i;
            }
        }
        return -1;
    }

    private function findOrCreateDocBlock(array &$lines, int $methodLineIndex): int
    {
        // 檢查方法上方是否已有文檔塊
        $docBlockStart = $methodLineIndex - 1;
        
        // 如果已經有 */ 結束的文檔塊，找到開始位置
        if (isset($lines[$docBlockStart]) && strpos($lines[$docBlockStart], '*/') !== false) {
            while ($docBlockStart > 0 && strpos($lines[$docBlockStart], '/**') === false) {
                $docBlockStart--;
            }
            return $docBlockStart;
        }

        // 如果沒有文檔塊，創建一個
        $newDocBlock = [
            '    /**',
            '     */',
        ];
        array_splice($lines, $methodLineIndex, 0, $newDocBlock);
        return $methodLineIndex;
    }

    private function extractMethodName(string $line): ?string
    {
        if (preg_match('/function\s+(\w+)/', $line, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function createBackup(string $filePath): void
    {
        $backupPath = $filePath . '.backup.' . date('YmdHis');
        copy($filePath, $backupPath);
        $this->backupFiles[] = $backupPath;
    }

    private function rollbackChanges(): void
    {
        echo "🔄 回滾變更...\n";
        foreach ($this->backupFiles as $backupPath) {
            $originalPath = preg_replace('/\.backup\.\d+$/', '', $backupPath);
            if (file_exists($backupPath)) {
                copy($backupPath, $originalPath);
                unlink($backupPath);
            }
        }
    }

    private function generateReport(): void
    {
        echo "\n📋 修復報告\n";
        echo "=" . str_repeat("=", 50) . "\n";
        echo "總修復數量: {$this->totalFixes}\n";
        echo "修復檔案數: " . count($this->appliedFixes) . "\n\n";

        if (!empty($this->appliedFixes)) {
            echo "修復詳情:\n";
            foreach ($this->appliedFixes as $file => $fixes) {
                echo "  • $file: $fixes 個修復\n";
            }
        }

        echo "\n✅ 修復完成！\n";
        echo "💡 建議執行 PHPStan 確認修復效果\n";
        
        // 清理備份檔案
        foreach ($this->backupFiles as $backupPath) {
            if (file_exists($backupPath)) {
                unlink($backupPath);
            }
        }
    }
}

// 執行修復
$fixer = new SmartIterableValueTypeFixer();
$fixer->run();
