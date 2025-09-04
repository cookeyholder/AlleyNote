<?php

declare(strict_types=1);

/**
 * PHPUnit Deprecation 修正工具
 * 自動將舊的 doc-comment 註解轉換為新的 PHP 8 屬性格式
 */

class PhpUnitDeprecationFixer
{
    private int $fixCount = 0;
    private array<mixed> $processedFiles = [];

    public function run(): void
    {
        echo "開始修復 PHPUnit Deprecations...\n";

        $testFiles = $this->findTestFiles();

        foreach ($testFiles as $file) {
            echo "處理檔案: {$file}\n";
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

    private function findTestFiles(): array<mixed>
    {
        $files = [];
        $directories = ['tests'];  // 搜尋整個 tests 目錄

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private function processFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            echo "  無法讀取檔案: {$filePath}\n";
            return;
        }

        $originalContent = $content;

        // 檢查是否需要添加 use 語句
        $needsTestAttribute = strpos($content, '/** @test */') !== false ||
            preg_match('/^\s*\*\s*@test\s*$/m', $content);

        // 添加 Test attribute import
        if ($needsTestAttribute && strpos($content, 'use PHPUnit\Framework\Attributes\Test;') === false) {
            $content = $this->addUseStatement($content, 'PHPUnit\Framework\Attributes\Test');
        }

        // 處理 @test 註解
        $content = $this->replaceTestAnnotations($content);

        // 處理其他註解
        $content = $this->replaceOtherAnnotations($content);

        if ($content !== $originalContent && $this->isValidPhp($content)) {
            file_put_contents($filePath, $content);
            $relativePath = str_replace(getcwd() . '/', '', $filePath);
            $this->processedFiles[] = $relativePath;
            $this->fixCount++;
            echo "  ✓ 已修復: {$relativePath}\n";
        }
    }

    private function addUseStatement(string $content, string $useStatement): string
    {
        // 找到最後一個 use 語句的位置
        if (preg_match('/^use [^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $lastUsePos = $matches[0][1] + strlen($matches[0][0]);
            $content = substr_replace($content, "\nuse {$useStatement};", $lastUsePos, 0);
        } else {
            // 如果沒有 use 語句，在 namespace 後添加
            if (preg_match('/^namespace [^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $namespacePos = $matches[0][1] + strlen($matches[0][0]);
                $content = substr_replace($content, "\n\nuse {$useStatement};", $namespacePos, 0);
            }
        }

        return $content;
    }

    private function replaceTestAnnotations(string $content): string
    {
        // 處理單行 /** @test */ 註解
        $content = preg_replace('/^(\s*)\/\*\* @test \*\/\s*$/m', '$1#[Test]', $content);

        // 處理 docblock 中的 @test
        $content = preg_replace('/^\s*\*\s*@test\s*$/m', '', $content);

        // 清理空的 docblock
        $content = preg_replace('/\/\*\*\s*\n\s*\*\/\s*\n/m', '', $content);

        // 清理只剩空格的 docblock 
        $content = preg_replace('/\/\*\*\s*\n(\s*\*\s*\n)*\s*\*\/\s*\n/m', '', $content);

        return $content;
    }

    private function replaceOtherAnnotations(string $content): string
    {
        // 處理其他常見的註解
        $patterns = [
            '/\/\*\* @group\s+([^\s\*]+)\s*\*\//' => '#[Group(\'$1\')]',
            '/\/\*\* @depends\s+([^\s\*]+)\s*\*\//' => '#[Depends(\'$1\')]',
            '/\/\*\* @dataProvider\s+([^\s\*]+)\s*\*\//' => '#[DataProvider(\'$1\')]',
            '/\/\*\* @covers\s+([^\s\*]+)\s*\*\//' => '#[CoversClass($1::class)]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function isValidPhp(string $code): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit_fix_');
        file_put_contents($tempFile, $code);

        $result = shell_exec("php -l $tempFile 2>&1");
        unlink($tempFile);

        return strpos($result, 'No syntax errors detected') !== false;
    }
}

// 執行修正
$fixer = new PhpUnitDeprecationFixer();
$fixer->run();
