<?php

declare(strict_types=1);

/**
 * 精準 PHPStan Level 10 修復器
 * 基於 Context7 MCP 指導，專注於核心類型錯誤
 */

class PrecisePHPStanLevel10Fixer
{
    private string $baseDir;
    private array $appliedFixes = [];
    private array $errorLog = [];

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/');
    }

    public function fixCoreErrors(): void
    {
        echo "🎯 開始精準修復 PHPStan Level 10 核心錯誤...\n\n";

        // 1. 修復 Repository PDO mixed types - 最高優先級
        $this->fixRepositoryPDOTypes();

        // 2. 修復 Console parameter types
        $this->fixConsoleParameterTypes();

        // 3. 修復核心 Service 方法返回類型
        $this->fixCoreServiceReturnTypes();

        // 4. 修復基本的 array access 問題（保守方式）
        $this->fixBasicArrayAccess();

        $this->generateReport();
    }

    private function fixRepositoryPDOTypes(): void
    {
        echo "🏛️ 修復 Repository PDO mixed types...\n";

        $repositoryFiles = [
            'app/Infrastructure/Repositories/Statistics/StatisticsRepository.php',
            'app/Infrastructure/Repositories/Statistics/SystemStatisticsRepository.php',
            'app/Infrastructure/Repositories/Statistics/UserStatisticsRepository.php',
            'app/Infrastructure/Repositories/Statistics/PostStatisticsRepository.php'
        ];

        foreach ($repositoryFiles as $file) {
            $this->fixSpecificRepositoryFile($file);
        }
    }

    private function fixSpecificRepositoryFile(string $file): void
    {
        $filePath = $this->baseDir . '/' . $file;
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 修復 PDO fetch/fetchAll 返回類型
        $pdoFixes = [
            // 為 fetch 結果添加類型檢查
            '/(\$row\s*=\s*\$stmt->fetch\([^)]*\));/' =>
                '$1' . "\n" . '        if (!is_array($row)) {' . "\n" . '            return null;' . "\n" . '        }',

            // 為 fetchAll 結果添加類型檢查
            '/(\$rows\s*=\s*\$stmt->fetchAll\([^)]*\));/' =>
                '$1' . "\n" . '        if (!is_array($rows)) {' . "\n" . '            return [];' . "\n" . '        }',

            // 為 PDO 準備添加斷言
            '/(\$stmt\s*=\s*\$this->pdo->prepare\([^)]*\));/' =>
                '$1' . "\n" . '        assert($stmt instanceof \\PDOStatement);',

            // 修復數組訪問（基本版本）
            '/\$row\[(["\'][a-zA-Z_][a-zA-Z0-9_]*["\'])\]/' =>
                '(is_array($row) && array_key_exists($1, $row) ? $row[$1] : null)',

            // 添加方法級別的返回類型註解
            '/\/\*\*\s*\n\s*\*\s*@param[^*]*\*\/\s*\n\s*public function ([a-zA-Z_][a-zA-Z0-9_]*)\([^)]*\)\s*:\s*\?array/' =>
                '/**' . "\n" . '     * @return array<string, mixed>|null' . "\n" . '     */' . "\n" . '    public function $1($2): ?array'
        ];

        foreach ($pdoFixes as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $this->appliedFixes[] = "PDO fix in {$file}: " . substr($pattern, 0, 50) . "...";
            }
        }

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "  ✅ 修復 {$file}\n";
        }
    }

    private function fixConsoleParameterTypes(): void
    {
        echo "🖥️ 修復 Console parameter types...\n";

        $consoleFile = 'app/Domains/Statistics/Console/StatisticsCalculationConsole.php';
        $this->fixConsoleFile($consoleFile);
    }

    private function fixConsoleFile(string $file): void
    {
        $filePath = $this->baseDir . '/' . $file;
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Console 參數類型修復
        $consoleFixes = [
            // 修復 $argv 訪問
            '/\$argv\[([0-9]+)\]/' =>
                '(isset($argv[$1]) && is_string($argv[$1]) ? $argv[$1] : \'\')',

            // 修復方法參數類型
            '/public function __construct\(\s*\)/' =>
                'public function __construct()',

            // 添加參數驗證方法
            '/class ([a-zA-Z_][a-zA-Z0-9_]*Console)/' =>
                'class $1' . "\n" . '{' . "\n" . '    private function validateStringParam(?string $param): string {' . "\n" . '        return is_string($param) ? $param : \'\';' . "\n" . '    }'
        ];

        foreach ($consoleFixes as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $this->appliedFixes[] = "Console fix in {$file}: " . substr($pattern, 0, 50) . "...";
            }
        }

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "  ✅ 修復 {$file}\n";
        }
    }

    private function fixCoreServiceReturnTypes(): void
    {
        echo "⚙️ 修復 Service return types...\n";

        $serviceFiles = [
            'app/Application/Services/StatisticsCacheService.php'
        ];

        foreach ($serviceFiles as $file) {
            $this->fixServiceFile($file);
        }
    }

    private function fixServiceFile(string $file): void
    {
        $filePath = $this->baseDir . '/' . $file;
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // Service 返回類型修復
        $serviceFixes = [
            // 添加具體的返回類型註解
            '/public function ([a-zA-Z_][a-zA-Z0-9_]*)\([^)]*\)\s*:\s*array/' =>
                '/**' . "\n" . '     * @return array<string, mixed>' . "\n" . '     */' . "\n" . '    public function $1($2): array',

            // 修復方法返回 null 的情況
            '/return null;/' =>
                'return [];'
        ];

        foreach ($serviceFixes as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $this->appliedFixes[] = "Service fix in {$file}: " . substr($pattern, 0, 50) . "...";
            }
        }

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "  ✅ 修復 {$file}\n";
        }
    }

    private function fixBasicArrayAccess(): void
    {
        echo "📋 修復基本 array access 問題...\n";

        // 只修復核心應用程式檔案，不動測試檔案
        $appFiles = glob($this->baseDir . '/app/**/*.php');

        foreach ($appFiles as $file) {
            $this->fixBasicArrayAccessInFile($file);
        }
    }

    private function fixBasicArrayAccessInFile(string $filePath): void
    {
        if (!file_exists($filePath) || strpos($filePath, '/tests/') !== false) {
            return;
        }

        $content = file_get_contents($filePath);
        $originalContent = $content;

        // 基本的 array access 修復
        $arrayFixes = [
            // 修復簡單的 isset 檢查
            '/isset\((\$[a-zA-Z_][a-zA-Z0-9_]*)\[(["\'][a-zA-Z_][a-zA-Z0-9_]*["\'])\]\)/' =>
                '(is_array($1) && array_key_exists($2, $1))',

            // 修復空的合併運算符
            '/(\$[a-zA-Z_][a-zA-Z0-9_]*)\[(["\'][a-zA-Z_][a-zA-Z0-9_]*["\'])\]\s*\?\?\s*null/' =>
                '(is_array($1) ? ($1[$2] ?? null) : null)'
        ];

        foreach ($arrayFixes as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== null && $newContent !== $content) {
                $content = $newContent;
                $this->appliedFixes[] = "Array access fix in " . basename($filePath) . ": " . substr($pattern, 0, 30) . "...";
            }
        }

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
        }
    }

    private function generateReport(): void
    {
        echo "\n📊 精準修復報告:\n";
        echo "總共應用了 " . count($this->appliedFixes) . " 個修復\n\n";

        $fixCategories = [];
        foreach ($this->appliedFixes as $fix) {
            $category = explode(':', $fix)[0];
            $fixCategories[$category] = ($fixCategories[$category] ?? 0) + 1;
        }

        foreach ($fixCategories as $category => $count) {
            echo "  {$category}: {$count} 個修復\n";
        }

        if (!empty($this->errorLog)) {
            echo "\n⚠️ 錯誤記錄:\n";
            foreach ($this->errorLog as $error) {
                echo "  - {$error}\n";
            }
        }

        echo "\n✅ 精準修復完成！請執行 PHPStan 分析檢查結果。\n";
    }
}

// 執行修復
$fixer = new PrecisePHPStanLevel10Fixer(__DIR__ . '/..');
$fixer->fixCoreErrors();
