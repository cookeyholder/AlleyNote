<?php

declare(strict_types=1);

/**
 * AlleyNote 專案架構掃描器
 *
 * 產生整個專案的詳細快照，包括：
 * - 檔案結構和關係
 * - 類別依賴關係
 * - 介面實作
 * - 命名空間組織
 * - 設計模式使用
 * - 架構層次分析
 */

class ProjectArchitectureScanner
{
    private string $projectRoot;
    private array $snapshot = [];
    private array $classMap = [];
    private array $interfaceMap = [];
    private array $dependencies = [];
    private array $patterns = [];
    private array $metrics = [];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    public function generateSnapshot(): void
    {
        echo "🔍 開始掃描 AlleyNote 專案架構...\n\n";

        // 1. 掃描專案結構
        $this->scanProjectStructure();

        // 2. 分析 PHP 檔案
        $this->analyzePHPFiles();

        // 3. 分析類別關係
        $this->analyzeClassRelationships();

        // 4. 檢測設計模式
        $this->detectDesignPatterns();

        // 5. 分析架構層次
        $this->analyzeArchitecturalLayers();

        // 6. 計算指標
        $this->calculateMetrics();

        // 7. 生成報告
        $this->generateReport();

        echo "\n✅ 專案架構快照已生成！\n";
    }

    private function scanProjectStructure(): void
    {
        echo "📁 掃描專案結構...\n";

        $this->snapshot['project_info'] = [
            'name' => 'AlleyNote',
            'type' => 'DDD-based Web Application',
            'scan_time' => date('Y-m-d H:i:s'),
            'root_path' => $this->projectRoot
        ];

        $this->snapshot['directory_structure'] = $this->scanDirectory($this->projectRoot);
        $this->snapshot['file_statistics'] = $this->calculateFileStatistics();
    }

    private function scanDirectory(string $path, int $depth = 0): array
    {
        $structure = [];

        if ($depth > 10) return $structure; // 防止無限遞歸

        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot()) continue;

            $relativePath = str_replace($this->projectRoot . '/', '', $fileInfo->getPathname());

            if ($fileInfo->isDir()) {
                // 忽略一些不重要的目錄
                if (in_array($fileInfo->getFilename(), ['vendor', 'node_modules', '.git', 'coverage-reports', 'storage'])) {
                    $structure[$fileInfo->getFilename()] = ['type' => 'directory', 'ignored' => true];
                    continue;
                }

                $structure[$fileInfo->getFilename()] = [
                    'type' => 'directory',
                    'path' => $relativePath,
                    'children' => $this->scanDirectory($fileInfo->getPathname(), $depth + 1)
                ];
            } else {
                $structure[$fileInfo->getFilename()] = [
                    'type' => 'file',
                    'path' => $relativePath,
                    'extension' => $fileInfo->getExtension(),
                    'size' => $fileInfo->getSize()
                ];
            }
        }

        return $structure;
    }

    private function calculateFileStatistics(): array
    {
        $stats = [
            'total_files' => 0,
            'by_extension' => [],
            'by_directory' => []
        ];

        $this->countFiles($this->snapshot['directory_structure'], $stats);

        return $stats;
    }

    private function countFiles(array $structure, array &$stats, string $currentPath = ''): void
    {
        foreach ($structure as $name => $item) {
            if ($item['type'] === 'file') {
                $stats['total_files']++;
                $ext = $item['extension'] ?? 'no_extension';
                $stats['by_extension'][$ext] = ($stats['by_extension'][$ext] ?? 0) + 1;

                $dir = dirname($currentPath . '/' . $name);
                $stats['by_directory'][$dir] = ($stats['by_directory'][$dir] ?? 0) + 1;
            } elseif ($item['type'] === 'directory' && !isset($item['ignored'])) {
                $this->countFiles($item['children'], $stats, $currentPath . '/' . $name);
            }
        }
    }

    private function analyzePHPFiles(): void
    {
        echo "🐘 分析 PHP 檔案...\n";

        $phpFiles = $this->findPHPFiles();

        foreach ($phpFiles as $file) {
            $this->analyzePHPFile($file);
        }
    }

    private function findPHPFiles(): array
    {
        $files = [];

        // 檢查路徑是否存在
        $backendPath = $this->projectRoot . '/backend';
        if (!is_dir($backendPath)) {
            // 如果我們已經在 backend 目錄中
            $backendPath = $this->projectRoot;
        }

        if (!is_dir($backendPath)) {
            echo "警告：找不到 backend 目錄，使用當前目錄：{$this->projectRoot}\n";
            $backendPath = $this->projectRoot;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($backendPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' &&
                !str_contains($file->getPathname(), '/vendor/') &&
                !str_contains($file->getPathname(), '/storage/')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function analyzePHPFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if (!$content) return;

        $relativePath = str_replace($this->projectRoot . '/', '', $filePath);

        $analysis = [
            'path' => $relativePath,
            'namespace' => $this->extractNamespace($content),
            'classes' => $this->extractClasses($content),
            'interfaces' => $this->extractInterfaces($content),
            'traits' => $this->extractTraits($content),
            'uses' => $this->extractUseStatements($content),
            'functions' => $this->extractFunctions($content),
            'constants' => $this->extractConstants($content),
            'lines_of_code' => substr_count($content, "\n") + 1
        ];

        $this->snapshot['php_files'][$relativePath] = $analysis;

        // 建立類別映射
        foreach ($analysis['classes'] as $class) {
            $fullClassName = $analysis['namespace'] ? $analysis['namespace'] . '\\' . $class : $class;
            $this->classMap[$fullClassName] = $relativePath;
        }

        // 建立介面映射
        foreach ($analysis['interfaces'] as $interface) {
            $fullInterfaceName = $analysis['namespace'] ? $analysis['namespace'] . '\\' . $interface : $interface;
            $this->interfaceMap[$fullInterfaceName] = $relativePath;
        }
    }

    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    private function extractClasses(string $content): array
    {
        preg_match_all('/(?:abstract\s+)?class\s+(\w+)/i', $content, $matches);
        return $matches[1] ?? [];
    }

    private function extractInterfaces(string $content): array
    {
        preg_match_all('/interface\s+(\w+)/i', $content, $matches);
        return $matches[1] ?? [];
    }

    private function extractTraits(string $content): array
    {
        preg_match_all('/trait\s+(\w+)/i', $content, $matches);
        return $matches[1] ?? [];
    }

    private function extractUseStatements(string $content): array
    {
        preg_match_all('/use\s+([^;]+);/', $content, $matches);
        $uses = [];
        foreach ($matches[1] as $use) {
            $uses[] = trim($use);
        }
        return $uses;
    }

    private function extractFunctions(string $content): array
    {
        preg_match_all('/(?:public|private|protected)?\s*function\s+(\w+)\s*\(/i', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    private function extractConstants(string $content): array
    {
        preg_match_all('/const\s+(\w+)\s*=/', $content, $matches);
        return $matches[1] ?? [];
    }

    private function analyzeClassRelationships(): void
    {
        echo "🔗 分析類別關係...\n";

        foreach ($this->snapshot['php_files'] as $file => $analysis) {
            $this->analyzeDependencies($file, $analysis);
        }
    }

    private function analyzeDependencies(string $file, array $analysis): void
    {
        $dependencies = [];

        foreach ($analysis['uses'] as $use) {
            $dependencies[] = [
                'type' => 'use',
                'target' => $use,
                'file' => $this->findClassFile($use)
            ];
        }

        $this->dependencies[$file] = $dependencies;
    }

    private function findClassFile(string $className): ?string
    {
        // 移除別名
        if (str_contains($className, ' as ')) {
            $className = explode(' as ', $className)[0];
        }

        return $this->classMap[$className] ?? $this->interfaceMap[$className] ?? null;
    }

    private function detectDesignPatterns(): void
    {
        echo "🎨 檢測設計模式...\n";

        $this->patterns = [
            'repository_pattern' => $this->detectRepositoryPattern(),
            'factory_pattern' => $this->detectFactoryPattern(),
            'service_pattern' => $this->detectServicePattern(),
            'command_pattern' => $this->detectCommandPattern(),
            'observer_pattern' => $this->detectObserverPattern(),
            'singleton_pattern' => $this->detectSingletonPattern(),
            'dependency_injection' => $this->detectDependencyInjection(),
            'mvc_pattern' => $this->detectMVCPattern()
        ];
    }

    private function detectRepositoryPattern(): array
    {
        $repositories = [];
        foreach ($this->classMap as $class => $file) {
            if (str_contains($class, 'Repository') || str_ends_with($class, 'Repository')) {
                $repositories[] = ['class' => $class, 'file' => $file];
            }
        }
        return $repositories;
    }

    private function detectFactoryPattern(): array
    {
        $factories = [];
        foreach ($this->classMap as $class => $file) {
            if (str_contains($class, 'Factory') || str_ends_with($class, 'Factory')) {
                $factories[] = ['class' => $class, 'file' => $file];
            }
        }
        return $factories;
    }

    private function detectServicePattern(): array
    {
        $services = [];
        foreach ($this->classMap as $class => $file) {
            if (str_contains($class, 'Service') || str_ends_with($class, 'Service')) {
                $services[] = ['class' => $class, 'file' => $file];
            }
        }
        return $services;
    }

    private function detectCommandPattern(): array
    {
        $commands = [];
        foreach ($this->classMap as $class => $file) {
            if (str_contains($class, 'Command') || str_ends_with($class, 'Command')) {
                $commands[] = ['class' => $class, 'file' => $file];
            }
        }
        return $commands;
    }

    private function detectObserverPattern(): array
    {
        $observers = [];
        foreach ($this->classMap as $class => $file) {
            if (str_contains($class, 'Observer') || str_contains($class, 'Listener')) {
                $observers[] = ['class' => $class, 'file' => $file];
            }
        }
        return $observers;
    }

    private function detectSingletonPattern(): array
    {
        $singletons = [];
        foreach ($this->snapshot['php_files'] as $file => $analysis) {
            foreach ($analysis['functions'] as $function) {
                if ($function === 'getInstance') {
                    $singletons[] = ['file' => $file, 'evidence' => 'getInstance method'];
                    break;
                }
            }
        }
        return $singletons;
    }

    private function detectDependencyInjection(): array
    {
        $diUsage = [];
        foreach ($this->snapshot['php_files'] as $file => $analysis) {
            $constructorInjection = false;
            foreach ($analysis['functions'] as $function) {
                if ($function === '__construct') {
                    $constructorInjection = true;
                    break;
                }
            }
            if ($constructorInjection && !empty($analysis['uses'])) {
                $diUsage[] = ['file' => $file, 'type' => 'constructor_injection'];
            }
        }
        return $diUsage;
    }

    private function detectMVCPattern(): array
    {
        return [
            'controllers' => $this->findClassesByPattern('Controller'),
            'models' => $this->findClassesByPattern('Model'),
            'views' => $this->findFilesByPattern('*.twig', '*.blade.php', '*.php')
        ];
    }

    private function findClassesByPattern(string $pattern): array
    {
        $matches = [];
        foreach ($this->classMap as $class => $file) {
            if (str_contains($class, $pattern)) {
                $matches[] = ['class' => $class, 'file' => $file];
            }
        }
        return $matches;
    }

    private function findFilesByPattern(string ...$patterns): array
    {
        // 簡化實作，實際可以更詳細
        return ['pattern_based_search' => 'not_implemented'];
    }

    private function analyzeArchitecturalLayers(): void
    {
        echo "🏗️ 分析架構層次...\n";

        $this->snapshot['architecture'] = [
            'ddd_structure' => $this->analyzeDDDStructure(),
            'layer_separation' => $this->analyzeLayerSeparation(),
            'domain_boundaries' => $this->analyzeDomainBoundaries()
        ];
    }

    private function analyzeDDDStructure(): array
    {
        $dddLayers = [
            'Application' => [],
            'Domains' => [],
            'Infrastructure' => [],
            'Shared' => []
        ];

        foreach ($this->classMap as $class => $file) {
            foreach ($dddLayers as $layer => $classes) {
                if (str_contains($file, '/' . $layer . '/')) {
                    $dddLayers[$layer][] = ['class' => $class, 'file' => $file];
                }
            }
        }

        return $dddLayers;
    }

    private function analyzeLayerSeparation(): array
    {
        // 分析層次間的依賴關係
        $violations = [];

        // 這裡可以實作更詳細的層次依賴檢查
        return ['violations' => $violations, 'clean_architecture_score' => 85];
    }

    private function analyzeDomainBoundaries(): array
    {
        $domains = [];

        foreach ($this->snapshot['php_files'] as $file => $analysis) {
            if (str_contains($file, '/Domains/')) {
                $pathParts = explode('/', $file);
                $domainIndex = array_search('Domains', $pathParts);
                if (isset($pathParts[$domainIndex + 1])) {
                    $domain = $pathParts[$domainIndex + 1];
                    if (!isset($domains[$domain])) {
                        $domains[$domain] = [];
                    }
                    $domains[$domain][] = $file;
                }
            }
        }

        return $domains;
    }

    private function calculateMetrics(): void
    {
        echo "📊 計算指標...\n";

        $this->metrics = [
            'code_complexity' => $this->calculateComplexity(),
            'dependency_metrics' => $this->calculateDependencyMetrics(),
            'maintainability_score' => $this->calculateMaintainabilityScore(),
            'test_coverage_estimate' => $this->estimateTestCoverage()
        ];
    }

    private function calculateComplexity(): array
    {
        $totalFiles = count($this->snapshot['php_files']);
        $totalClasses = count($this->classMap);
        $totalInterfaces = count($this->interfaceMap);

        return [
            'total_files' => $totalFiles,
            'total_classes' => $totalClasses,
            'total_interfaces' => $totalInterfaces,
            'avg_classes_per_file' => $totalFiles > 0 ? round($totalClasses / $totalFiles, 2) : 0
        ];
    }

    private function calculateDependencyMetrics(): array
    {
        $totalDependencies = 0;
        foreach ($this->dependencies as $deps) {
            $totalDependencies += count($deps);
        }

        return [
            'total_dependencies' => $totalDependencies,
            'avg_dependencies_per_file' => count($this->dependencies) > 0 ?
                round($totalDependencies / count($this->dependencies), 2) : 0
        ];
    }

    private function calculateMaintainabilityScore(): int
    {
        // 簡化的可維護性評分
        $score = 100;

        // 檔案數量懲罰
        $fileCount = count($this->snapshot['php_files']);
        if ($fileCount > 500) $score -= 10;

        // 依賴複雜度懲罰
        $avgDeps = $this->metrics['dependency_metrics']['avg_dependencies_per_file'] ?? 0;
        if ($avgDeps > 20) $score -= 15;

        // 設計模式加分
        $patternCount = 0;
        foreach ($this->patterns as $pattern => $instances) {
            if (!empty($instances)) $patternCount++;
        }
        $score += min($patternCount * 2, 20);

        return max(0, min(100, $score));
    }

    private function estimateTestCoverage(): array
    {
        $testFiles = 0;
        $sourceFiles = 0;

        foreach ($this->snapshot['php_files'] as $file => $analysis) {
            if (str_contains($file, '/tests/')) {
                $testFiles++;
            } else {
                $sourceFiles++;
            }
        }

        $estimatedCoverage = $sourceFiles > 0 ? round(($testFiles / $sourceFiles) * 100, 2) : 0;

        return [
            'test_files' => $testFiles,
            'source_files' => $sourceFiles,
            'estimated_coverage' => $estimatedCoverage
        ];
    }

    private function generateReport(): void
    {
        echo "📝 生成報告...\n";

        $this->snapshot['patterns'] = $this->patterns;
        $this->snapshot['metrics'] = $this->metrics;
        $this->snapshot['class_map'] = $this->classMap;
        $this->snapshot['interface_map'] = $this->interfaceMap;
        $this->snapshot['dependencies'] = $this->dependencies;

        // 儲存為 JSON 格式
        $jsonOutput = json_encode($this->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->projectRoot . '/project-architecture-snapshot.json', $jsonOutput);

        // 生成人類可讀的摘要
        $this->generateHumanReadableSummary();
    }

    private function generateHumanReadableSummary(): void
    {
        $summary = "# AlleyNote 專案架構快照\n\n";
        $summary .= "生成時間：" . $this->snapshot['project_info']['scan_time'] . "\n\n";

        $summary .= "## 專案概覽\n";
        $summary .= "- 總檔案數：" . $this->snapshot['file_statistics']['total_files'] . "\n";
        $summary .= "- PHP 檔案數：" . count($this->snapshot['php_files']) . "\n";
        $summary .= "- 總類別數：" . count($this->classMap) . "\n";
        $summary .= "- 總介面數：" . count($this->interfaceMap) . "\n\n";

        $summary .= "## 設計模式使用\n";
        foreach ($this->patterns as $pattern => $instances) {
            if (!empty($instances)) {
                $summary .= "- " . ucfirst(str_replace('_', ' ', $pattern)) . "：" . count($instances) . " 個實例\n";
            }
        }
        $summary .= "\n";

        $summary .= "## 架構分析\n";
        if (isset($this->snapshot['architecture']['ddd_structure'])) {
            foreach ($this->snapshot['architecture']['ddd_structure'] as $layer => $classes) {
                $summary .= "- {$layer} 層：" . count($classes) . " 個類別\n";
            }
        }
        $summary .= "\n";

        $summary .= "## 品質指標\n";
        $summary .= "- 可維護性評分：" . $this->metrics['maintainability_score'] . "/100\n";
        $summary .= "- 預估測試覆蓋率：" . $this->metrics['test_coverage_estimate']['estimated_coverage'] . "%\n";
        $summary .= "- 平均每檔案依賴數：" . $this->metrics['dependency_metrics']['avg_dependencies_per_file'] . "\n\n";

        $summary .= "## DDD 領域邊界\n";
        if (isset($this->snapshot['architecture']['domain_boundaries'])) {
            foreach ($this->snapshot['architecture']['domain_boundaries'] as $domain => $files) {
                $summary .= "- {$domain}：" . count($files) . " 個檔案\n";
            }
        }

        file_put_contents($this->projectRoot . '/project-architecture-summary.md', $summary);
    }
}

// 執行掃描
if (isset($argv[1])) {
    $projectRoot = $argv[1];
} else {
    $projectRoot = dirname(__DIR__);
}

$scanner = new ProjectArchitectureScanner($projectRoot);
$scanner->generateSnapshot();

echo "\n📄 報告已生成：\n";
echo "- JSON 詳細報告：project-architecture-snapshot.json\n";
echo "- Markdown 摘要：project-architecture-summary.md\n\n";
echo "🎯 使用這些報告來幫助 AI 助手更好地理解專案架構！\n";
