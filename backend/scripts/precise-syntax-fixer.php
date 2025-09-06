#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 精確語法錯誤修復器
 *
 * 專門修復剩餘的 22 個語法錯誤
 */

class PreciseSyntaxFixer
{
    private string $baseDir;
    private array $fixedFiles = [];

    public function __construct(string $baseDir = '/var/www/html')
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function run(): void
    {
        echo "🔧 啟動精確語法錯誤修復器...\n\n";

        // 直接修復問題檔案
        $problemFiles = [
            'app/Infrastructure/Services/CacheService.php',
            'app/Shared/Cache/Providers/CacheServiceProvider.php',
            'app/Shared/Config/JwtConfig.php',
            'app/Shared/Exceptions/ValidationException.php',
            'tests/UI/CrossBrowserTest.php',
            'tests/Unit/DTOs/BaseDTOTest.php',
        ];

        foreach ($problemFiles as $file) {
            $this->fixSpecificFile($file);
        }

        echo "\n✅ 精確語法修復完成！\n";
        echo "修復檔案數: " . count($this->fixedFiles) . "\n";
    }

    private function fixSpecificFile(string $file): void
    {
        $filePath = $this->baseDir . '/' . $file;
        if (!file_exists($filePath)) {
            echo "⚠️  檔案不存在: $file\n";
            return;
        }

        echo "🔧 修復: $file\n";

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 執行各種修復
        $content = $this->fixDocumentationBlocks($content);
        $content = $this->fixEscapeSequences($content);
        $content = $this->fixStructuralIssues($content);
        $content = $this->validateAndFixSyntax($content, $file);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $this->fixedFiles[] = $file;
            echo "  ✅ 已修復\n";
        } else {
            echo "  ℹ️  無需修復\n";
        }
    }

    private function fixDocumentationBlocks(string $content): string
    {
        // 修復錯誤的文檔塊格式
        $content = preg_replace('/\/\*\*\s*\n\s*\*\s*\n\s*\*\s*@/', '/**\n     * @', $content);

        // 修復孤立的星號
        $content = preg_replace('/^\s*\*\s*$\n/m', '', $content);

        // 修復重複的文檔塊
        $content = preg_replace('/\/\*\*\s*\n\s*\/\*\*/', '/**', $content);
        $content = preg_replace('/\*\/\s*\n\s*\*\//', '*/', $content);

        return $content;
    }

    private function fixEscapeSequences(string $content): string
    {
        // 修復轉義序列
        $content = str_replace('\\n      *', "\n      *", $content);
        $content = str_replace('\\n     *', "\n     *", $content);
        $content = str_replace('\\n    *', "\n    *", $content);
        $content = str_replace('\\n', "\n", $content);

        return $content;
    }

    private function fixStructuralIssues(string $content): string
    {
        $lines = explode("\n", $content);
        $fixedLines = [];
        $inClass = false;
        $braceCount = 0;

        foreach ($lines as $i => $line) {
            // 跟蹤類和方法結構
            if (preg_match('/^\s*class\s+/', $line)) {
                $inClass = true;
            }

            // 計算大括號
            $braceCount += substr_count($line, '{') - substr_count($line, '}');

            // 修復註解中的問題
            if (preg_match('/^\s*\/\*\*.*\*\s*@/', $line)) {
                // 分離文檔塊開始和註解
                $line = preg_replace('/^(\s*)\/\*\*.*\*\s*@(.+)/', '$1/**\n$1 * @$2', $line);
            }

            // 修復方法前的註解問題
            if (preg_match('/^\s*\/\/.*\s+(public|private|protected)\s+/', $line)) {
                // 移除方法前的單行註解
                $line = preg_replace('/^(\s*)\/\/.*\s+(public|private|protected)\s+/', '$1$2 ', $line);
            }

            $fixedLines[] = $line;
        }

        // 確保檔案正確結束
        if ($inClass && $braceCount !== 0) {
            // 可能需要添加或移除大括號
            $lastLine = end($fixedLines);
            if (trim($lastLine) !== '}') {
                $fixedLines[] = '}';
            }
        }

        return implode("\n", $fixedLines);
    }

    private function validateAndFixSyntax(string $content, string $filename): string
    {
        // 特定檔案的修復
        switch ($filename) {
            case 'app/Shared/Exceptions/ValidationException.php':
                return $this->fixValidationException($content);

            case 'tests/Unit/DTOs/BaseDTOTest.php':
                return $this->fixBaseDTOTest($content);

            case 'app/Infrastructure/Services/CacheService.php':
                return $this->fixCacheService($content);

            default:
                return $content;
        }
    }

    private function fixValidationException(string $content): string
    {
        // 修復 ValidationException 的特定問題
        $content = preg_replace('/^\s*\/\/.*\s+(public\s+function)/m', '    $1', $content);

        // 確保方法之間有正確的結構
        $content = preg_replace('/(\})\s*\n\s*\/\*\*/', '$1\n\n    /**', $content);

        return $content;
    }

    private function fixBaseDTOTest(string $content): string
    {
        // 修復 BaseDTOTest 的問題
        $content = preg_replace('/(\{)\s*\n\s*\/\*\*/', '$1', $content);

        // 移除測試方法前的錯誤註解
        $content = preg_replace('/^\s*\/\/.*\s+(public\s+function\s+test)/m', '    $1', $content);

        return $content;
    }

    private function fixCacheService(string $content): string
    {
        // 修復 CacheService 的問題
        $lines = explode("\n", $content);
        $fixedLines = [];

        foreach ($lines as $line) {
            // 跳過可能有問題的註解行
            if (preg_match('/^\s*\/\/.*public/', $line)) {
                // 提取方法定義
                $methodMatch = [];
                if (preg_match('/(public\s+function\s+\w+.*)/', $line, $methodMatch)) {
                    $fixedLines[] = '    ' . $methodMatch[1];
                }
            } else {
                $fixedLines[] = $line;
            }
        }

        return implode("\n", $fixedLines);
    }
}

// 執行修復
$fixer = new PreciseSyntaxFixer();
$fixer->run();
