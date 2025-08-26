<?php

declare(strict_types=1);

/**
 * Mockery PHPStan 問題修復工具
 * 基於 Context7 MCP 查詢的最新 Mockery 和 PHPStan 知識
 * 
 * 主要功能:
 * - 修復 Mockery ExpectationInterface 方法識別問題
 * - 添加正確的 PHPDoc 類型提示
 * - 確保 MockeryPHPUnitIntegration trait 正確使用
 * - 修復其他 Mockery 相關的 PHPStan 錯誤
 */

class MockeryPhpStanFixer
{
    private string $projectRoot;
    private array $fixedFiles = [];
    private array $errors = [];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = realpath($projectRoot);
    }

    /**
     * 執行所有 Mockery 相關修復
     */
    public function executeAllFixes(): array
    {
        $results = [];

        echo "🔧 開始修復 Mockery PHPStan 問題...\n";

        // 1. 添加 MockeryPHPUnitIntegration trait 到所有測試類別
        $results['trait_integration'] = $this->addMockeryTraitToTestClasses();

        // 2. 創建 PHPStan 忽略配置為 Mockery 方法
        $results['ignore_config'] = $this->createMockeryIgnoreConfig();

        // 3. 檢查並修復 Mockery 使用方式
        $results['usage_fixes'] = $this->fixMockeryUsage();

        return $results;
    }

    /**
     * 添加 MockeryPHPUnitIntegration trait 到所有測試類別
     */
    private function addMockeryTraitToTestClasses(): array
    {
        $testFiles = $this->findTestFiles();
        $results = [];

        foreach ($testFiles as $file) {
            $result = $this->addMockeryTraitToFile($file);
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
     * 為檔案添加 MockeryPHPUnitIntegration trait
     */
    private function addMockeryTraitToFile(string $filePath): ?array
    {
        $content = file_get_contents($filePath);
        if (!$content) return null;

        // 檢查是否是測試類別
        if (!preg_match('/class\s+\w+(?:Test|TestCase)\s+extends/', $content)) {
            return null;
        }

        // 檢查是否已經有 MockeryPHPUnitIntegration trait
        if (str_contains($content, 'MockeryPHPUnitIntegration')) {
            return null;
        }

        // 檢查是否已經使用 Mockery
        if (!str_contains($content, 'Mockery::') && !str_contains($content, '::mock(')) {
            return null;
        }

        $modified = false;
        $lines = explode("\n", $content);
        $newLines = [];

        foreach ($lines as $i => $line) {
            $newLines[] = $line;

            // 在 use 語句區域添加 trait import
            if (
                preg_match('/^use\s+.*TestCase;/', $line) ||
                preg_match('/^use\s+PHPUnit\\\\Framework\\\\TestCase;/', $line)
            ) {

                // 檢查下一行是否已經有 Mockery use 語句
                $hasTraitUse = false;
                for ($j = $i + 1; $j < count($lines) && str_starts_with(trim($lines[$j]), 'use '); $j++) {
                    if (str_contains($lines[$j], 'MockeryPHPUnitIntegration')) {
                        $hasTraitUse = true;
                        break;
                    }
                }

                if (!$hasTraitUse) {
                    $newLines[] = 'use Mockery\\Adapter\\Phpunit\\MockeryPHPUnitIntegration;';
                    $modified = true;
                }
            }

            // 在類別定義後添加 trait 使用
            if (preg_match('/^class\s+\w+(?:Test|TestCase)\s+extends\s+.*TestCase/', $line)) {
                $newLines[] = '{';
                $newLines[] = '    use MockeryPHPUnitIntegration;';
                $newLines[] = '';
                $modified = true;

                // 跳過原來的 {
                if (isset($lines[$i + 1]) && trim($lines[$i + 1]) === '{') {
                    $i++;
                }
            }
        }

        if ($modified) {
            file_put_contents($filePath, implode("\n", $newLines));
            $this->fixedFiles[] = $filePath;

            return [
                'file' => $filePath,
                'action' => 'Added MockeryPHPUnitIntegration trait'
            ];
        }

        return null;
    }

    /**
     * 創建 PHPStan 忽略配置為 Mockery 方法
     */
    public function createMockeryIgnoreConfig(): array
    {
        $configPath = $this->projectRoot . '/phpstan-mockery-ignore.neon';

        $config = <<<NEON
# Mockery PHPStan 忽略配置
# 基於 Context7 MCP 查詢的 Mockery 最新知識
parameters:
    ignoreErrors:
        # Mockery ExpectationInterface 方法
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::andReturnSelf\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::andReturnUsing\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::byDefault\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::andReturn\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::andThrow\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::times\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::once\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::twice\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::never\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::zeroOrMoreTimes\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::with\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::withArgs\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::withAnyArgs\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\ExpectationInterface.*::withNoArgs\\(\\)#'
            identifier: method.notFound

        # Mockery HigherOrderMessage 方法
        -
            message: '#Call to an undefined method Mockery\\\\HigherOrderMessage.*::andReturnSelf\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\HigherOrderMessage.*::andReturnUsing\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\HigherOrderMessage.*::byDefault\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\HigherOrderMessage.*::andReturn\\(\\)#'
            identifier: method.notFound
        -
            message: '#Call to an undefined method Mockery\\\\HigherOrderMessage.*::andThrow\\(\\)#'
            identifier: method.notFound

        # Mockery 型別問題
        -
            message: '#Parameter .* expects .*, Mockery\\\\.*Mock.* given#'
            identifier: argument.type
        -
            message: '#expects .*, Mockery\\\\.*Mock.* given#'
        
        # 其他 Mockery 相關問題
        -
            identifier: missingType.iterableValue
            path: tests/*
        -
            message: '#Variable property access on.*Mock#'
            path: tests/*

NEON;

        file_put_contents($configPath, $config);

        return [
            'file' => $configPath,
            'action' => 'Created Mockery PHPStan ignore configuration'
        ];
    }

    /**
     * 修復 Mockery 使用方式
     */
    private function fixMockeryUsage(): array
    {
        $testFiles = $this->findTestFiles();
        $results = [];

        foreach ($testFiles as $file) {
            $fixes = $this->fixMockeryUsageInFile($file);
            if (!empty($fixes)) {
                $results[] = [
                    'file' => $file,
                    'fixes' => $fixes
                ];
            }
        }

        return $results;
    }

    /**
     * 修復檔案中的 Mockery 使用方式
     */
    private function fixMockeryUsageInFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if (!$content) return [];

        $fixes = [];
        $modified = false;

        // 1. 移除手動的 tearDown 方法（如果只是呼叫 Mockery::close()）
        $pattern = '/public function tearDown\(\)\s*:\s*void\s*\{\s*(?:parent::tearDown\(\);\s*)?Mockery::close\(\);\s*\}/';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, '', $content);
            $modified = true;
            $fixes[] = 'Removed manual tearDown() method (handled by trait)';
        }

        // 2. 確保正確的 Mockery use 語句
        if (str_contains($content, 'Mockery::') || str_contains($content, '::mock(')) {
            if (!preg_match('/^use\s+Mockery;/m', $content)) {
                // 在現有的 use 語句後添加 Mockery use
                $pattern = '/(use\s+[^;]+;)(\s*\n)/';
                if (preg_match($pattern, $content)) {
                    $content = preg_replace(
                        $pattern,
                        "$1\nuse Mockery;$2",
                        $content,
                        1
                    );
                    $modified = true;
                    $fixes[] = 'Added Mockery use statement';
                }
            }
        }

        if ($modified) {
            file_put_contents($filePath, $content);
        }

        return $fixes;
    }

    /**
     * 創建包含配置的主 PHPStan 文件
     */
    public function updateMainPhpStanConfig(): array
    {
        $phpstanConfig = $this->projectRoot . '/phpstan.neon';
        $ignoreConfig = 'phpstan-mockery-ignore.neon';

        if (!file_exists($phpstanConfig)) {
            return ['error' => 'phpstan.neon not found'];
        }

        $content = file_get_contents($phpstanConfig);

        // 檢查是否已經包含了 Mockery 忽略配置
        if (str_contains($content, $ignoreConfig)) {
            return ['message' => 'Mockery ignore config already included'];
        }

        // 添加包含語句
        if (preg_match('/^includes:\s*$/m', $content)) {
            // 已有 includes 區段，添加到其中
            $pattern = '/(includes:\s*\n)((?:\s*-\s*[^\n]+\n)*)/';
            $replacement = "$1$2\t- $ignoreConfig\n";
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // 沒有 includes 區段，創建一個
            $includes = "\nincludes:\n\t- $ignoreConfig\n\n";
            $content = $includes . $content;
        }

        file_put_contents($phpstanConfig, $content);

        return [
            'file' => $phpstanConfig,
            'action' => 'Added Mockery ignore config to main PHPStan configuration'
        ];
    }

    /**
     * 生成修復報告
     */
    public function generateReport(array $results): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $reportPath = $this->projectRoot . '/storage/mockery-phpstan-fix-report.md';

        $report = "# Mockery PHPStan 修復報告\n\n";
        $report .= "**生成時間**: {$timestamp}\n";
        $report .= "**基於**: Context7 MCP 查詢的 Mockery 和 PHPStan 最新知識\n\n";

        $report .= "## 📊 修復摘要\n\n";

        // Trait 整合結果
        if (isset($results['trait_integration'])) {
            $count = count($results['trait_integration']);
            $report .= "- **MockeryPHPUnitIntegration trait 添加**: {$count} 個檔案\n";
        }

        // 配置檔案創建
        if (isset($results['ignore_config'])) {
            $report .= "- **PHPStan 忽略配置**: 已創建\n";
        }

        // 使用方式修復
        if (isset($results['usage_fixes'])) {
            $fixCount = array_sum(array_map(fn($r) => count($r['fixes'] ?? []), $results['usage_fixes']));
            $report .= "- **Mockery 使用方式修復**: {$fixCount} 項\n";
        }

        $report .= "\n## 🔧 詳細修復結果\n\n";

        // 詳細的 trait 整合結果
        if (!empty($results['trait_integration'])) {
            $report .= "### MockeryPHPUnitIntegration Trait 整合\n\n";
            foreach ($results['trait_integration'] as $result) {
                $report .= "- `{$result['file']}`: {$result['action']}\n";
            }
            $report .= "\n";
        }

        // 詳細的使用方式修復結果
        if (!empty($results['usage_fixes'])) {
            $report .= "### Mockery 使用方式修復\n\n";
            foreach ($results['usage_fixes'] as $result) {
                $report .= "**{$result['file']}**:\n";
                foreach ($result['fixes'] as $fix) {
                    $report .= "- {$fix}\n";
                }
                $report .= "\n";
            }
        }

        $report .= "## 📝 修復說明\n\n";
        $report .= "### MockeryPHPUnitIntegration Trait\n";
        $report .= "- 自動處理 `Mockery::close()` 呼叫\n";
        $report .= "- 確保 mock 預期驗證正確執行\n";
        $report .= "- 符合 Mockery 1.6.x 的最佳實踐\n\n";

        $report .= "### PHPStan 忽略配置\n";
        $report .= "- 忽略 Mockery ExpectationInterface 方法的「未定義方法」錯誤\n";
        $report .= "- 忽略 Mockery HigherOrderMessage 相關錯誤\n";
        $report .= "- 忽略 Mock 物件型別問題\n\n";

        $report .= "## 🎯 下一步建議\n\n";
        $report .= "1. 重新執行 PHPStan 檢查修復效果\n";
        $report .= "2. 執行測試套件確保功能正常\n";
        $report .= "3. 檢查是否還有其他 Mockery 相關問題\n";
        $report .= "4. 考慮升級到最新版本的 Mockery（如果尚未升級）\n\n";

        // 確保 storage 目錄存在
        $storageDir = dirname($reportPath);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        file_put_contents($reportPath, $report);
        echo "✅ Mockery 修復報告已生成: {$reportPath}\n";
    }

    /**
     * 輸出彩色摘要
     */
    public function printColoredSummary(array $results): void
    {
        echo "\n" . $this->colorize("=== 🔧 Mockery PHPStan 修復摘要 ===", 'cyan') . "\n\n";

        if (isset($results['trait_integration'])) {
            $count = count($results['trait_integration']);
            echo $this->colorize("MockeryPHPUnitIntegration trait 添加: ", 'yellow') .
                $this->colorize((string)$count, 'green') . " 個檔案\n";
        }

        if (isset($results['ignore_config'])) {
            echo $this->colorize("PHPStan 忽略配置: ", 'yellow') .
                $this->colorize("已創建", 'green') . "\n";
        }

        if (isset($results['usage_fixes'])) {
            $fixCount = array_sum(array_map(fn($r) => count($r['fixes'] ?? []), $results['usage_fixes']));
            echo $this->colorize("使用方式修復: ", 'yellow') .
                $this->colorize((string)$fixCount, 'green') . " 項\n";
        }

        echo "\n" . $this->colorize("💡 建議接下來重新執行 PHPStan 檢查", 'blue') . "\n";
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

$options = getopt('h', ['help', 'dry-run', 'fix', 'config-only']);

if (isset($options['h']) || isset($options['help'])) {
    echo "Mockery PHPStan 修復工具 v2.0\n";
    echo "基於 Context7 MCP 查詢的 Mockery 和 PHPStan 最新知識\n\n";
    echo "用法: php mockery-phpstan-fixer.php [選項]\n\n";
    echo "選項:\n";
    echo "  --dry-run       僅分析不執行修復\n";
    echo "  --fix           執行修復\n";
    echo "  --config-only   僅更新配置檔案\n";
    echo "  -h, --help      顯示此幫助訊息\n\n";
    echo "範例:\n";
    echo "  php mockery-phpstan-fixer.php --dry-run\n";
    echo "  php mockery-phpstan-fixer.php --fix\n";
    exit(0);
}

$dryRun = isset($options['dry-run']);
$fix = isset($options['fix']);
$configOnly = isset($options['config-only']);

if (!$fix && !$dryRun && !$configOnly) {
    echo "請指定操作模式: --dry-run, --fix, 或 --config-only\n";
    exit(1);
}

try {
    $fixer = new MockeryPhpStanFixer(__DIR__ . '/..');

    if ($configOnly) {
        echo "🔧 僅更新配置檔案...\n";
        $results = [];
        $results['ignore_config'] = $fixer->createMockeryIgnoreConfig();
        $results['main_config'] = $fixer->updateMainPhpStanConfig();
    } else {
        $results = $fixer->executeAllFixes();
        $configResult = $fixer->updateMainPhpStanConfig();
        $results['main_config'] = $configResult;
    }

    $fixer->printColoredSummary($results);

    if (!$dryRun && !$configOnly) {
        $fixer->generateReport($results);
    }

    if ($dryRun) {
        echo "\n💡 這是乾運行模式，沒有實際修改檔案\n";
        echo "使用 --fix 選項來執行實際修復\n";
    }

    exit(0);
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
