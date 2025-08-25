<?php

/**
 * 專案架構快速掃描腳本
 * 用於分析整個專案的結構、命名空間、類別關係等
 * 
 * 使用方法: php scripts/scan-project-architecture.php
 */

class ProjectArchitectureScanner
{
    private array $analysis = [
        'directories' => [],
        'namespaces' => [],
        'classes' => [],
        'interfaces' => [],
        'traits' => [],
        'dependencies' => [],
        'ddd_structure' => [],
        'issues' => []
    ];

    private string $projectRoot;
    private array $excludeDirs = [
        'vendor',
        'node_modules',
        '.git',
        'coverage_report',
        'coverage-reports',
        'storage',
        'database/backups',
        'public',
        'docker'
    ];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = realpath($projectRoot);
    }

    public function scan(): void
    {
        echo "🔍 掃描專案架構...\n";

        // 掃描目錄結構
        $this->scanDirectories();

        // 掃描 PHP 檔案
        $this->scanPhpFiles();

        // 分析 DDD 結構
        $this->analyzeDddStructure();

        // 分析依賴關係
        $this->analyzeDependencies();

        // 輸出結果
        $this->generateReport();
    }

    private function scanDirectories(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir() && !$this->shouldExclude($file->getPathname())) {
                $relativePath = str_replace($this->projectRoot . '/', '', $file->getPathname());
                $this->analysis['directories'][] = $relativePath;
            }
        }
    }

    private function scanPhpFiles(): void
    {
        $phpFiles = $this->findPhpFiles();

        foreach ($phpFiles as $file) {
            $this->analyzePhpFile($file);
        }
    }

    private function findPhpFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && !$this->shouldExclude($file->getPathname())) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function analyzePhpFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $relativePath = str_replace($this->projectRoot . '/', '', $filePath);

        // 提取命名空間
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
            $this->analysis['namespaces'][$namespace][] = $relativePath;
        }

        // 提取類別、介面、Trait
        $this->extractClassInfo($content, $relativePath);

        // 提取 use 語句
        $this->extractUseStatements($content, $relativePath);
    }

    private function extractClassInfo(string $content, string $filePath): void
    {
        // 類別
        if (preg_match_all('/class\s+(\w+)(?:\s+extends\s+(\w+))?(?:\s+implements\s+([^{]+))?/m', $content, $matches)) {
            foreach ($matches[1] as $i => $className) {
                $this->analysis['classes'][$className] = [
                    'file' => $filePath,
                    'extends' => $matches[2][$i] ?? null,
                    'implements' => isset($matches[3][$i]) ? array_map('trim', explode(',', $matches[3][$i])) : []
                ];
            }
        }

        // 介面
        if (preg_match_all('/interface\s+(\w+)(?:\s+extends\s+([^{]+))?/m', $content, $matches)) {
            foreach ($matches[1] as $i => $interfaceName) {
                $this->analysis['interfaces'][$interfaceName] = [
                    'file' => $filePath,
                    'extends' => isset($matches[2][$i]) ? array_map('trim', explode(',', $matches[2][$i])) : []
                ];
            }
        }

        // Traits
        if (preg_match_all('/trait\s+(\w+)/m', $content, $matches)) {
            foreach ($matches[1] as $traitName) {
                $this->analysis['traits'][$traitName] = ['file' => $filePath];
            }
        }
    }

    private function extractUseStatements(string $content, string $filePath): void
    {
        if (preg_match_all('/use\s+([^;]+);/', $content, $matches)) {
            foreach ($matches[1] as $use) {
                $use = trim($use);
                if (!isset($this->analysis['dependencies'][$filePath])) {
                    $this->analysis['dependencies'][$filePath] = [];
                }
                $this->analysis['dependencies'][$filePath][] = $use;
            }
        }
    }

    private function analyzeDddStructure(): void
    {
        $dddPaths = [
            'Application' => 'app/Application',
            'Domains' => 'app/Domains',
            'Infrastructure' => 'app/Infrastructure',
            'Shared' => 'app/Shared'
        ];

        foreach ($dddPaths as $layer => $path) {
            $fullPath = $this->projectRoot . '/' . $path;
            if (is_dir($fullPath)) {
                $this->analysis['ddd_structure'][$layer] = $this->scanDddLayer($fullPath, $path);
            }
        }
    }

    private function scanDddLayer(string $fullPath, string $relativePath): array
    {
        $structure = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $subPath = str_replace($fullPath . '/', '', $file->getPathname());
                if ($subPath !== '.') {
                    $structure['directories'][] = $subPath;
                }
            } elseif ($file->getExtension() === 'php') {
                $subPath = str_replace($fullPath . '/', '', $file->getPathname());
                $structure['files'][] = $subPath;
            }
        }

        return $structure;
    }

    private function analyzeDependencies(): void
    {
        // 檢查可能的架構問題
        foreach ($this->analysis['dependencies'] as $file => $deps) {
            // 檢查是否有違反 DDD 分層的依賴
            if (str_contains($file, 'app/Domains/')) {
                foreach ($deps as $dep) {
                    if (str_contains($dep, 'App\\Infrastructure\\')) {
                        $this->analysis['issues'][] = "❌ Domain層不應依賴Infrastructure層: $file -> $dep";
                    }
                }
            }

            // 檢查是否有循環依賴的可能
            if (str_contains($file, 'app/Application/')) {
                foreach ($deps as $dep) {
                    if (str_contains($dep, 'App\\Application\\')) {
                        $this->analysis['issues'][] = "⚠️  可能的循環依賴: $file -> $dep";
                    }
                }
            }
        }
    }

    private function generateReport(): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $reportPath = $this->projectRoot . '/storage/architecture-report.md';
        $summaryPath = $this->projectRoot . '/storage/architecture-summary.txt';

        // 生成詳細報告
        $report = "# 專案架構分析報告\n\n";
        $report .= "**生成時間**: $timestamp\n\n";

        // 生成快速摘要
        $summary = "=== 專案架構快速摘要 ($timestamp) ===\n\n";

        // 目錄結構
        $report .= "## 📁 目錄結構\n\n";
        foreach ($this->analysis['directories'] as $dir) {
            $report .= "- `$dir`\n";
        }

        // 命名空間分析
        $report .= "\n## 🏷️ 命名空間分析\n\n";
        foreach ($this->analysis['namespaces'] as $namespace => $files) {
            $report .= "### `$namespace`\n";
            foreach ($files as $file) {
                $report .= "- $file\n";
            }
            $report .= "\n";
        }

        // DDD 結構
        $report .= "\n## 🏗️ DDD 架構分析\n\n";
        foreach ($this->analysis['ddd_structure'] as $layer => $structure) {
            $report .= "### $layer 層\n";
            if (isset($structure['directories'])) {
                $report .= "**子目錄**: " . implode(', ', $structure['directories']) . "\n";
            }
            if (isset($structure['files'])) {
                $report .= "**檔案數量**: " . count($structure['files']) . "\n";
            }
            $report .= "\n";
        }

        // 類別統計
        $report .= "\n## 📊 類別統計\n\n";
        $report .= "- **類別總數**: " . count($this->analysis['classes']) . "\n";
        $report .= "- **介面總數**: " . count($this->analysis['interfaces']) . "\n";
        $report .= "- **Trait 總數**: " . count($this->analysis['traits']) . "\n";

        // 架構問題
        if (!empty($this->analysis['issues'])) {
            $report .= "\n## ⚠️ 發現的架構問題\n\n";
            foreach ($this->analysis['issues'] as $issue) {
                $report .= "- $issue\n";
            }
        }

        // 重要類別清單
        $report .= "\n## 🔑 重要類別清單\n\n";
        foreach ($this->analysis['classes'] as $className => $info) {
            if (
                str_contains($info['file'], 'Controller') ||
                str_contains($info['file'], 'Service') ||
                str_contains($info['file'], 'Repository')
            ) {
                $report .= "- **$className**: `{$info['file']}`\n";
                if ($info['extends']) {
                    $report .= "  - 繼承: {$info['extends']}\n";
                }
                if (!empty($info['implements'])) {
                    $report .= "  - 實作: " . implode(', ', $info['implements']) . "\n";
                }
            }
        }

        file_put_contents($reportPath, $report);

        // 快速摘要 (重構時快速查閱用)
        $summary .= "📊 統計資訊:\n";
        $summary .= "- 類別: " . count($this->analysis['classes']) . " 個\n";
        $summary .= "- 介面: " . count($this->analysis['interfaces']) . " 個\n";
        $summary .= "- 命名空間: " . count($this->analysis['namespaces']) . " 個\n\n";

        $summary .= "🏗️ DDD 架構:\n";
        foreach ($this->analysis['ddd_structure'] as $layer => $structure) {
            $fileCount = isset($structure['files']) ? count($structure['files']) : 0;
            $summary .= "- $layer: $fileCount 個檔案\n";
        }

        if (!empty($this->analysis['issues'])) {
            $summary .= "\n❌ 架構問題 (" . count($this->analysis['issues']) . " 個):\n";
            foreach (array_slice($this->analysis['issues'], 0, 10) as $issue) {
                $summary .= "- " . str_replace(['❌ ', '⚠️  '], '', $issue) . "\n";
            }
            if (count($this->analysis['issues']) > 10) {
                $summary .= "... 還有 " . (count($this->analysis['issues']) - 10) . " 個問題\n";
            }
        }

        $summary .= "\n🔑 重點服務/控制器:\n";
        $importantClasses = [];
        foreach ($this->analysis['classes'] as $className => $info) {
            if (
                str_contains($info['file'], 'Controller') ||
                str_contains($info['file'], 'Service') ||
                str_contains($info['file'], 'Repository')
            ) {
                if (!str_contains($info['file'], 'Test')) {
                    $importantClasses[] = "$className ({$info['file']})";
                }
            }
        }
        foreach (array_slice($importantClasses, 0, 15) as $class) {
            $summary .= "- $class\n";
        }

        file_put_contents($summaryPath, $summary);
        file_put_contents($reportPath, $report);

        // 輸出摘要到控制台
        echo "\n" . $summary;
        echo "\n📝 詳細報告: $reportPath\n";
        echo "⚡ 快速摘要: $summaryPath\n";
    }

    private function shouldExclude(string $path): bool
    {
        foreach ($this->excludeDirs as $excludeDir) {
            if (str_contains($path, '/' . $excludeDir . '/') || str_ends_with($path, '/' . $excludeDir)) {
                return true;
            }
        }
        return false;
    }
}

// 執行掃描
try {
    $scanner = new ProjectArchitectureScanner(__DIR__ . '/..');
    $scanner->scan();
    echo "\n✅ 架構掃描完成！\n";
} catch (Exception $e) {
    echo "❌ 掃描失敗: " . $e->getMessage() . "\n";
    exit(1);
}
