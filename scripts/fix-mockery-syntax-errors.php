<?php

declare(strict_types=1);

/**
 * 清理 Mockery 修復腳本產生的語法錯誤
 * 修復重複的 { 符號和不當的 trait 插入位置
 */

class MockerySyntaxErrorFixer
{
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = realpath($projectRoot);
    }

    /**
     * 修復所有測試檔案中的語法錯誤
     */
    public function fixAllSyntaxErrors(): array
    {
        $testFiles = $this->findTestFiles();
        $results = [];

        foreach ($testFiles as $file) {
            $result = $this->fixFileStructure($file);
            if ($result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * 找到所有測試檔案
     */
    private function findTestFiles(): array
    {
        $testFiles = [];
        $testDir = $this->projectRoot . '/tests';

        if (!is_dir($testDir)) {
            return $testFiles;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($testDir)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $testFiles[] = $file->getPathname();
            }
        }

        return $testFiles;
    }

    /**
     * 修復檔案結構
     */
    private function fixFileStructure(string $filePath): ?array
    {
        $content = file_get_contents($filePath);
        if (!$content) return null;

        $originalContent = $content;
        $fixes = [];

        // 1. 修復重複的 { 符號
        $pattern = '/^class\s+\w+(?:Test|TestCase)\s+extends\s+.*TestCase.*\n\{\n\s*use MockeryPHPUnitIntegration;\n\n\{/m';
        if (preg_match($pattern, $content)) {
            $content = preg_replace_callback(
                $pattern,
                function ($matches) {
                    $classLine = preg_replace('/\n\{\n\s*use MockeryPHPUnitIntegration;\n\n\{/', '', $matches[0]);
                    return $classLine . "\n{\n    use MockeryPHPUnitIntegration;\n";
                },
                $content
            );
            $fixes[] = 'Fixed duplicate opening brace';
        }

        // 2. 確保正確的類別結構
        $lines = explode("\n", $content);
        $newLines = [];
        $insideClass = false;
        $classOpeningFound = false;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // 檢測類別定義
            if (preg_match('/^class\s+\w+(?:Test|TestCase)\s+extends\s+.*TestCase/', $line)) {
                $newLines[] = $line;
                $insideClass = true;
                $classOpeningFound = false;
                continue;
            }

            // 跳過重複的 { 和錯誤的 trait 插入
            if ($insideClass && !$classOpeningFound) {
                if (trim($line) === '{') {
                    if ($classOpeningFound) {
                        // 跳過重複的 {
                        continue;
                    } else {
                        $newLines[] = '{';
                        // 檢查是否需要添加 trait
                        if (!str_contains($content, 'use MockeryPHPUnitIntegration;')) {
                            $newLines[] = '    use MockeryPHPUnitIntegration;';
                            $newLines[] = '';
                            $fixes[] = 'Added MockeryPHPUnitIntegration trait';
                        }
                        $classOpeningFound = true;
                        continue;
                    }
                }

                // 跳過已經錯誤插入的 trait
                if (trim($line) === 'use MockeryPHPUnitIntegration;') {
                    continue;
                }
            }

            $newLines[] = $line;
        }

        $newContent = implode("\n", $newLines);

        // 如果內容有改變，寫回檔案
        if ($newContent !== $originalContent) {
            file_put_contents($filePath, $newContent);
            return [
                'file' => $filePath,
                'fixes' => $fixes
            ];
        }

        return null;
    }

    /**
     * 使用更簡單的方法重寫有問題的測試檔案
     */
    public function rewriteProblematicFiles(): array
    {
        $problematicFiles = [
            'tests/Integration/Http/PostControllerTest.php',
            'tests/Integration/PostControllerTest_new.php',
            'tests/Integration/Repositories/PostRepositoryTest.php',
            'tests/Unit/Controllers/IpControllerTest.php',
            'tests/Unit/Domains/Auth/Services/AuthServiceTest.php',
            'tests/Unit/Repositories/AttachmentRepositoryTest.php',
            'tests/Unit/Repository/IpRepositoryTest.php',
            'tests/Unit/Repository/PostRepositoryPerformanceTest.php',
            'tests/Unit/Repository/PostRepositoryTest.php',
            'tests/Unit/Services/AttachmentServiceTest.php',
            'tests/Unit/Services/AuthServiceTest.php',
            'tests/Unit/Services/PostServiceTest.php'
        ];

        $results = [];

        foreach ($problematicFiles as $relativeFile) {
            $fullPath = $this->projectRoot . '/' . $relativeFile;
            if (file_exists($fullPath)) {
                $result = $this->fixSpecificFile($fullPath);
                if ($result) {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    /**
     * 修復特定檔案
     */
    private function fixSpecificFile(string $filePath): ?array
    {
        $content = file_get_contents($filePath);
        if (!$content) return null;

        $originalContent = $content;

        // 移除重複的 { 和錯誤的 trait 插入
        $content = preg_replace(
            '/^class\s+(\w+(?:Test|TestCase))\s+extends\s+(.*TestCase.*)\n\{\n\s*use MockeryPHPUnitIntegration;\s*\n\n\{/m',
            'class $1 extends $2' . "\n{" . "\n    use MockeryPHPUnitIntegration;\n",
            $content
        );

        // 確保 trait import 存在
        if (
            str_contains($content, 'use MockeryPHPUnitIntegration;') &&
            !str_contains($content, 'use Mockery\\Adapter\\Phpunit\\MockeryPHPUnitIntegration;')
        ) {

            // 在最後一個 use 語句後添加 trait import
            $content = preg_replace(
                '/(use\s+[^;]+;)(\s*\n\s*class)/s',
                "$1\nuse Mockery\\Adapter\\Phpunit\\MockeryPHPUnitIntegration;$2",
                $content,
                1
            );
        }

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            return [
                'file' => $filePath,
                'action' => 'Fixed syntax errors and class structure'
            ];
        }

        return null;
    }

    /**
     * 輸出修復摘要
     */
    public function printSummary(array $results): void
    {
        echo "\n" . $this->colorize("=== 🔧 語法錯誤修復摘要 ===", 'cyan') . "\n\n";

        $fixedCount = count($results);
        echo $this->colorize("修復檔案數量: ", 'yellow') .
            $this->colorize((string)$fixedCount, 'green') . " 個檔案\n";

        if ($fixedCount > 0) {
            echo "\n" . $this->colorize("修復的檔案:", 'blue') . "\n";
            foreach ($results as $result) {
                $filename = basename($result['file']);
                echo "  ✅ " . $this->colorize($filename, 'white') . "\n";
            }
        }

        echo "\n" . $this->colorize("💡 建議重新執行 PHPStan 檢查", 'blue') . "\n";
    }

    /**
     * 輸出彩色文字
     */
    private function colorize(string $text, string $color): string
    {
        $colors = [
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'cyan' => '36',
            'white' => '37'
        ];

        $colorCode = $colors[$color] ?? '37';
        return "\033[{$colorCode}m{$text}\033[0m";
    }
}

// 主程式
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

try {
    $fixer = new MockerySyntaxErrorFixer(__DIR__ . '/..');

    echo "🔧 開始修復語法錯誤...\n";

    // 使用特定的修復方法處理已知的有問題檔案
    $results = $fixer->rewriteProblematicFiles();

    $fixer->printSummary($results);

    echo "\n✅ 語法錯誤修復完成！\n";
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
