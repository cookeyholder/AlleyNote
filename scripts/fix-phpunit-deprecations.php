<?php

declare(strict_types=1);

/**
 * PHPUnit Deprecation 修正工具
 * 自動將舊的 doc-comment 註解轉換為新的 PHP 8 屬性格式
 */

class PhpUnitDeprecationFixer
{
    private array $replacements = [
        '/** @test */' => '#[Test]',
        '    /** @test */' => '    #[Test]',
        '     * @test' => '',  // 移除 docblock 內的 @test
        ' @test' => '',       // 移除簡單的 @test
        '/** @covers' => '#[CoversClass(',
        '/** @group' => '#[Group(',
        '/** @depends' => '#[Depends(',
        '/** @dataProvider' => '#[DataProvider(',
    ];

    private array $imports = [
        'PHPUnit\Framework\Attributes\Test',
        'PHPUnit\Framework\Attributes\CoversClass',
        'PHPUnit\Framework\Attributes\Group',
        'PHPUnit\Framework\Attributes\Depends',
        'PHPUnit\Framework\Attributes\DataProvider',
    ];

    public function run(): void
    {
        $testFiles = $this->findTestFiles();
        
        foreach ($testFiles as $file) {
            echo "處理檔案: {$file}\n";
            $this->processFile($file);
        }
        
        echo "完成！共處理了 " . count($testFiles) . " 個檔案\n";
    }

    private function findTestFiles(): array
    {
        $files = [];
        $directories = ['tests/Unit', 'tests/Integration', 'tests/Security', 'tests/UI'];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
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
            echo "無法讀取檔案: {$filePath}\n";
            return;
        }

        $originalContent = $content;
        
        // 檢查是否需要添加 use 語句
        $needsTestAttribute = strpos($content, '/** @test */') !== false || 
                             strpos($content, ' * @test') !== false;
        $needsCoversAttribute = strpos($content, '/** @covers') !== false;
        $needsGroupAttribute = strpos($content, '/** @group') !== false;
        
        // 添加必要的 use 語句
        if ($needsTestAttribute && strpos($content, 'use PHPUnit\Framework\Attributes\Test;') === false) {
            $content = $this->addUseStatement($content, 'PHPUnit\Framework\Attributes\Test');
        }
        
        if ($needsCoversAttribute && strpos($content, 'use PHPUnit\Framework\Attributes\CoversClass;') === false) {
            $content = $this->addUseStatement($content, 'PHPUnit\Framework\Attributes\CoversClass');
        }
        
        if ($needsGroupAttribute && strpos($content, 'use PHPUnit\Framework\Attributes\Group;') === false) {
            $content = $this->addUseStatement($content, 'PHPUnit\Framework\Attributes\Group');
        }

        // 處理 @test 註解
        $content = $this->replaceTestAnnotations($content);
        
        // 處理 @covers 註解 
        $content = $this->replaceCoversAnnotations($content);
        
        // 處理其他註解
        $content = $this->replaceOtherAnnotations($content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "已修正: {$filePath}\n";
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
        // 處理單行 @test
        $content = preg_replace('/^(\s*)\/\*\* @test \*\/$/m', '$1#[Test]', $content);
        
        // 處理 docblock 中的 @test
        $content = preg_replace('/^(\s*)\*\s*@test\s*$/m', '', $content);
        
        // 清理空的 docblock
        $content = preg_replace('/\/\*\*\s*\*\/\s*\n/m', '', $content);
        
        return $content;
    }

    private function replaceCoversAnnotations(string $content): string
    {
        // 處理 @covers ClassName
        $pattern = '/\/\*\* @covers\s+([^\s\*]+)\s*\*\//';
        $content = preg_replace($pattern, '#[CoversClass($1::class)]', $content);
        
        return $content;
    }

    private function replaceOtherAnnotations(string $content): string
    {
        // 處理其他常見的註解
        $patterns = [
            '/\/\*\* @group\s+([^\s\*]+)\s*\*\//' => '#[Group(\'$1\')]',
            '/\/\*\* @depends\s+([^\s\*]+)\s*\*\//' => '#[Depends(\'$1\')]',
            '/\/\*\* @dataProvider\s+([^\s\*]+)\s*\*\//' => '#[DataProvider(\'$1\')]',
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        return $content;
    }
}

// 執行修正
$fixer = new PhpUnitDeprecationFixer();
$fixer->run();