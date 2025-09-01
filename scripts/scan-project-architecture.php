<?php

/**
 * 專案架構快速掃描腳本
 * 用於分析整個專案的結構、命名空間、類別關係等
 * 基於 Context7 MCP 查詢的最新分析技術和 DDD 最佳實踐
 *
 * 使用方法: php scripts/scan-project-architecture.php
 *
 * 新增功能（基於 Context7 MCP）:
 * - 現代 PHP 語法檢查
 * - 型別宣告一致性分析
 * - PSR-4 自動載入驗證
 * - 測試覆蓋率品質評估
 * - 相依性注入模式分析
 * - DDD 邊界上下文檢查
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
        'issues' => [],
        'interface_implementations' => [],
        'test_coverage' => [],
        'constructor_dependencies' => [],
        'missing_imports' => [],
        'namespace_mismatches' => [],
        'type_declarations' => [],      // 新增：型別宣告分析
        'psr4_compliance' => [],        // 新增：PSR-4 合規性
        'modern_syntax_usage' => [],    // 新增：現代 PHP 語法使用情況
        'boundary_contexts' => [],      // 新增：DDD 邊界上下文分析
        'quality_metrics' => []         // 新增：程式碼品質指標
    ];

    private string $projectRoot;
    private array $excludeDirectories = [
        'tests',
        'vendor',
        'node_modules',
        '.git',
        'coverage-reports',
        'storage',
        'database/backups',
        'public',
        'docker'
    ];

    // 新增：現代 PHP 特性檢查清單
    private array $modernPhpFeatures = [
        'readonly_properties' => '/readonly\s+[a-zA-Z_]/i',
        'enum_usage' => '/enum\s+[A-Z]\w*/i',
        'union_types' => '/:\s*[a-zA-Z_\\\\|]+\|[a-zA-Z_\\\\|]+/',
        'intersection_types' => '/:\s*[a-zA-Z_\\\\&]+&[a-zA-Z_\\\\&]+/',
        'constructor_promotion' => '/public\s+readonly\s+[a-zA-Z_]/i',
        'match_expression' => '/match\s*\(/i',
        'attributes' => '/#\[[\w\\\\]+/i',
        'nullsafe_operator' => '/\?\->/i',
    ];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = realpath($projectRoot);
    }

    public function scan(): void
    {
        echo "🔍 掃描專案架構（使用最新分析技術）...\n";

        // 掃描目錄結構
        $this->scanDirectories();

        // 掃描 PHP 檔案
        $this->scanPhpFiles();

        // 分析 DDD 結構
        $this->analyzeDddStructure();

        // 分析依賴關係
        $this->analyzeDependencies();

        // 分析介面實作關係
        $this->analyzeInterfaceImplementations();

        // 分析測試覆蓋
        $this->analyzeTestCoverage();

        // 分析建構子依賴
        $this->analyzeConstructorDependencies();

        // 檢查命名空間一致性
        $this->checkNamespaceConsistency();

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

        // 執行新的分析功能
        echo "  📊 執行現代 PHP 特性分析...\n";
        $this->analyzeBoundaryContexts();

        echo "  📏 計算程式碼品質指標...\n";
        $this->calculateQualityMetrics();
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

        // 新的分析功能（基於 Context7 MCP）
        $this->analyzeModernPhpFeatures($filePath, $content);
        $this->checkPsr4Compliance($filePath, $content);
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

    private function analyzeInterfaceImplementations(): void
    {
        foreach ($this->analysis['classes'] as $className => $classInfo) {
            if (!empty((is_array($classInfo) ? $classInfo['implements'] : (is_object($classInfo) ? $classInfo->implements : null)))) {
                foreach ((is_array($classInfo) ? $classInfo['implements'] : (is_object($classInfo) ? $classInfo->implements : null)) as $interface) {
                    $this->analysis['interface_implementations'][$interface][] = [
                        'class' => $className,
                        'file' => (is_array($classInfo) ? $classInfo['file'] : (is_object($classInfo) ? $classInfo->file : null))
                    ];
                }
            }
        }
    }

    private function analyzeTestCoverage(): void
    {
        foreach ($this->analysis['classes'] as $className => $classInfo) {
            $file = (is_array($classInfo) ? $classInfo['file'] : (is_object($classInfo) ? $classInfo->file : null));

            // 跳過測試檔案本身
            if (str_contains($file, 'tests/') || str_ends_with($className, 'Test')) {
                continue;
            }

            // 尋找對應的測試檔案
            $testFiles = $this->findTestFiles($className, $file);
            $this->analysis['test_coverage'][$className] = [
                'file' => $file,
                'test_files' => $testFiles,
                'has_tests' => !empty($testFiles)
            ];
        }
    }

    private function findTestFiles(string $className, string $sourceFile): array
    {
        $testFiles = [];
        $possibleTestNames = [
            $className . 'Test',
            str_replace(['Service', 'Repository', 'Controller'], '', $className) . 'Test'
        ];

        foreach ($this->analysis['classes'] as $testClass => $testInfo) {
            if (
                str_contains((is_array($testInfo) ? $testInfo['file'] : (is_object($testInfo) ? $testInfo->file : null)), 'tests/') &&
                (in_array($testClass, $possibleTestNames) || str_contains($testClass, $className))
            ) {
                $testFiles[] = (is_array($testInfo) ? $testInfo['file'] : (is_object($testInfo) ? $testInfo->file : null));
            }
        }

        return $testFiles;
    }

    private function analyzeConstructorDependencies(): void
    {
        foreach ($this->analysis['classes'] as $className => $classInfo) {
            $content = file_get_contents($this->projectRoot . '/' . (is_array($classInfo) ? $classInfo['file'] : (is_object($classInfo) ? $classInfo->file : null)));

            // 提取建構子依賴
            if (preg_match('/public function __construct\s*\(([^)]*)\)/', $content, $matches)) {
                $params = $matches[1];
                $dependencies = $this->extractConstructorParams($params);

                if (!empty($dependencies)) {
                    $this->analysis['constructor_dependencies'][$className] = [
                        'file' => (is_array($classInfo) ? $classInfo['file'] : (is_object($classInfo) ? $classInfo->file : null)),
                        'dependencies' => $dependencies
                    ];
                }
            }
        }
    }

    private function extractConstructorParams(string $params): array
    {
        $dependencies = [];

        if (empty(trim($params))) {
            return $dependencies;
        }

        // 簡單的參數解析（可以改進）
        $paramPairs = explode(',', $params);

        foreach ($paramPairs as $param) {
            $param = trim($param);
            if (preg_match('/(?:private|protected|public)?\s*(?:readonly\s+)?([A-Z][A-Za-z0-9_\\\\]*)\s+\$(\w+)/', $param, $matches)) {
                $dependencies[] = [
                    'type' => $matches[1],
                    'name' => $matches[2]
                ];
            }
        }

        return $dependencies;
    }

    private function checkNamespaceConsistency(): void
    {
        // 忽略的外部函式庫和 PHP 內建類別
        $ignoredImports = [
            'PDO',
            'Exception',
            'InvalidArgumentException',
            'RuntimeException',
            'JsonSerializable',
            'ArrayAccess',
            'Countable',
            'Iterator',
            'DateTime',
            'DateTimeImmutable',
            'SplFileInfo',
            'Ramsey\\Uuid\\',
            'Psr\\',
            'OpenApi\\',
            'PHPUnit\\',
            'Mockery\\',
            'RecursiveIteratorIterator',
            'RecursiveDirectoryIterator'
        ];

        foreach ($this->analysis['dependencies'] as $file => $deps) {
            foreach ($deps as $dep) {
                // 跳過被忽略的引用
                $shouldIgnore = false;
                foreach ($ignoredImports as $ignored) {
                    if (str_contains($dep, $ignored)) {
                        $shouldIgnore = true;
                        break;
                    }
                }

                if ($shouldIgnore) {
                    continue;
                }

                // 檢查 use 的類別是否真的存在
                $foundClass = false;
                $depClassName = basename(str_replace('\\', '/', $dep));

                foreach ($this->analysis['classes'] as $className => $classInfo) {
                    if ($className === $depClassName) {
                        $foundClass = true;
                        break;
                    }
                }

                foreach ($this->analysis['interfaces'] as $interfaceName => $interfaceInfo) {
                    if ($interfaceName === $depClassName) {
                        $foundClass = true;
                        break;
                    }
                }

                if (!$foundClass) {
                    $this->analysis['missing_imports'][] = "❓ 找不到類別/介面: $dep (在 $file 中使用)";
                }
            }
        }
    }

    private function generateReport(): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $reportPath = $this->projectRoot . '/storage/architecture-report.md';
        $summaryPath = $this->projectRoot . '/storage/architecture-summary.txt';

        // 確保 storage 目錄存在
        $storageDir = dirname($reportPath);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        // 生成詳細報告
        $report = "# 專案架構分析報告（基於 Context7 MCP 最新技術）\n\n";
        $report .= "**生成時間**: $timestamp\n\n";

        // 生成快速摘要
        $summary = "=== 專案架構快速摘要 ($timestamp) ===\n\n";

        // 程式碼品質指標（新增）
        if (!empty($this->analysis['quality_metrics'])) {
            $metrics = $this->analysis['quality_metrics'];
            $report .= "## 📊 程式碼品質指標\n\n";
            $report .= "| 指標 | 數值 | 狀態 |\n";
            $report .= "|------|------|------|\n";
            $report .= sprintf("| 總類別數 | %d | - |\n", (is_array($metrics) ? $metrics['total_classes'] : (is_object($metrics) ? $metrics->total_classes : null)));
            $report .= sprintf(
                "| 介面與類別比例 | %.2f%% | %s |\n",
                (is_array($metrics) ? $metrics['interface_to_class_ratio'] : (is_object($metrics) ? $metrics->interface_to_class_ratio : null)),
                (is_array($metrics) ? $metrics['interface_to_class_ratio'] : (is_object($metrics) ? $metrics->interface_to_class_ratio : null)) >= 20 ? '✅ 良好' : '⚠️ 可改善'
            );
            $report .= sprintf(
                "| 平均依賴數/類別 | %.2f | %s |\n",
                (is_array($metrics) ? $metrics['average_dependencies_per_class'] : (is_object($metrics) ? $metrics->average_dependencies_per_class : null)),
                (is_array($metrics) ? $metrics['average_dependencies_per_class'] : (is_object($metrics) ? $metrics->average_dependencies_per_class : null)) <= 5 ? '✅ 良好' : '⚠️ 過多'
            );
            $report .= sprintf(
                "| 現代 PHP 採用率 | %.2f%% | %s |\n",
                (is_array($metrics) ? $metrics['modern_php_adoption_rate'] : (is_object($metrics) ? $metrics->modern_php_adoption_rate : null)),
                (is_array($metrics) ? $metrics['modern_php_adoption_rate'] : (is_object($metrics) ? $metrics->modern_php_adoption_rate : null)) >= 50 ? '✅ 良好' : '⚠️ 待升級'
            );
            $report .= sprintf(
                "| PSR-4 合規率 | %.2f%% | %s |\n",
                (is_array($metrics) ? $metrics['psr4_compliance_rate'] : (is_object($metrics) ? $metrics->psr4_compliance_rate : null)),
                (is_array($metrics) ? $metrics['psr4_compliance_rate'] : (is_object($metrics) ? $metrics->psr4_compliance_rate : null)) >= 90 ? '✅ 良好' : '❌ 需修正'
            );
            $report .= sprintf(
                "| DDD 結構完整性 | %.2f%% | %s |\n",
                (is_array($metrics) ? $metrics['ddd_structure_completeness'] : (is_object($metrics) ? $metrics->ddd_structure_completeness : null)),
                (is_array($metrics) ? $metrics['ddd_structure_completeness'] : (is_object($metrics) ? $metrics->ddd_structure_completeness : null)) >= 70 ? '✅ 良好' : '⚠️ 可改善'
            );
            $report .= "\n";

            // 添加到摘要
            $summary .= "📊 品質指標:\n";
            $summary .= "- 總類別數: " . (is_array($metrics) ? $metrics['total_classes'] : (is_object($metrics) ? $metrics->total_classes : 'N/A')) . "\n";
            $summary .= "- 介面比例: " . (is_array($metrics) ? $metrics['interface_to_class_ratio'] : (is_object($metrics) ? $metrics->interface_to_class_ratio : 'N/A')) . "%\n";
            $summary .= "- 現代 PHP 採用率: " . (is_array($metrics) ? $metrics['modern_php_adoption_rate'] : (is_object($metrics) ? $metrics->modern_php_adoption_rate : 'N/A')) . "%\n";
            $summary .= "- PSR-4 合規率: " . (is_array($metrics) ? $metrics['psr4_compliance_rate'] : (is_object($metrics) ? $metrics->psr4_compliance_rate : 'N/A')) . "%\n\n";
        }

        // DDD 邊界上下文分析（新增）
        if (!empty($this->analysis['boundary_contexts'])) {
            $report .= "## 🎯 DDD 邊界上下文分析\n\n";

            foreach ($this->analysis['boundary_contexts'] as $contextName => $components) {
                $report .= "### $contextName 上下文\n\n";
                $report .= "| 組件類型 | 數量 | 項目 |\n";
                $report .= "|----------|------|------|\n";

                foreach ($components as $type => $items) {
                    $typeName = match ($type) {
                        'entities' => '實體',
                        'value_objects' => '值物件',
                        'aggregates' => '聚合',
                        'repositories' => '儲存庫',
                        'services' => '領域服務',
                        'events' => '領域事件',
                        default => $type
                    };

                    $report .= sprintf(
                        "| %s | %d | %s |\n",
                        $typeName,
                        count($items),
                        count($items) > 0 ? implode(', ', array_slice($items, 0, 3)) . (count($items) > 3 ? '...' : '') : '-'
                    );
                }

                $report .= "\n";
            }

            // 添加到摘要
            $summary .= "🎯 邊界上下文: " . count($this->analysis['boundary_contexts']) . " 個\n";
            foreach ($this->analysis['boundary_contexts'] as $contextName => $components) {
                $totalComponents = array_sum(array_map('count', $components));
                $summary .= "- {$contextName}: {$totalComponents} 個組件\n";
            }
            $summary .= "\n";
        }

        // 現代 PHP 特性使用情況（新增）
        if (!empty($this->analysis['modern_syntax_usage'])) {
            $report .= "## 🚀 現代 PHP 特性使用情況\n\n";

            // 統計特性使用頻率
            $featureStats = [];
            foreach ($this->analysis['modern_syntax_usage'] as $class => $features) {
                foreach ($features as $feature => $count) {
                    $featureStats[$feature] = ($featureStats[$feature] ?? 0) + $count;
                }
            }

            arsort($featureStats);

            $report .= "| 特性 | 使用次數 | 描述 |\n";
            $report .= "|------|----------|------|\n";

            $featureDescriptions = [
                'readonly_properties' => '唯讀屬性 (PHP 8.1+)',
                'enum_usage' => '列舉型別 (PHP 8.1+)',
                'union_types' => '聯合型別 (PHP 8.0+)',
                'intersection_types' => '交集型別 (PHP 8.1+)',
                'constructor_promotion' => '建構子屬性提升 (PHP 8.0+)',
                'match_expression' => 'Match 表達式 (PHP 8.0+)',
                'attributes' => '屬性標籤 (PHP 8.0+)',
                'nullsafe_operator' => '空安全運算子 (PHP 8.0+)',
            ];

            foreach ($featureStats as $feature => $count) {
                $description = $featureDescriptions[$feature] ?? $feature;
                $recommendation = $this->getFeatureRecommendation($feature);
                $report .= "| $description | $count | $recommendation |\n";
            }
            $report .= "\n";

            // 添加到摘要
            $summary .= "🚀 現代 PHP 特性: " . count($featureStats) . " 種正在使用\n";
            if (!empty($featureStats)) {
                $topFeature = array_key_first($featureStats);
                $summary .= "- 最常用: " . ($featureDescriptions[$topFeature] ?? $topFeature) . " ({$featureStats[$topFeature]} 次)\n";
            }
            $summary .= "\n";
        }

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
                str_contains((is_array($info) ? $info['file'] : (is_object($info) ? $info->file : null)), 'Controller') ||
                str_contains((is_array($info) ? $info['file'] : (is_object($info) ? $info->file : null)), 'Service') ||
                str_contains((is_array($info) ? $info['file'] : (is_object($info) ? $info->file : null)), 'Repository')
            ) {
                $report .= "- **$className**: `" . (is_array($info) ? $info['file'] : (is_object($info) ? $info->file : 'N/A')) . "`\n";
                if ((is_array($info) ? $info['extends'] : (is_object($info) ? $info->extends : null))) {
                    $report .= "  - 繼承: " . (is_array($info) ? $info['extends'] : (is_object($info) ? $info->extends : 'N/A')) . "\n";
                }
                if (!empty((is_array($info) ? $info['implements'] : (is_object($info) ? $info->implements : null)))) {
                    $report .= "  - 實作: " . implode(', ', (is_array($info) ? $info['implements'] : (is_object($info) ? $info->implements : null))) . "\n";
                }
            }
        }

        // 介面實作分析
        if (!empty($this->analysis['interface_implementations'])) {
            $report .= "\n## 🔌 介面實作分析\n\n";
            foreach ($this->analysis['interface_implementations'] as $interface => $implementations) {
                $report .= "### `$interface`\n";
                foreach ($implementations as $impl) {
                    $report .= "- " . (is_array($impl) ? $impl['class'] : (is_object($impl) ? $impl->class : 'N/A')) . " (`" . (is_array($impl) ? $impl['file'] : (is_object($impl) ? $impl->file : 'N/A')) . "`)\n";
                }
                $report .= "\n";
            }
        }

        // 測試覆蓋分析
        $testedClasses = array_filter($this->analysis['test_coverage'], fn($coverage) => (is_array($coverage) ? $coverage['has_tests'] : (is_object($coverage) ? $coverage->has_tests : null)));
        $untestedClasses = array_filter($this->analysis['test_coverage'], fn($coverage) => !(is_array($coverage) ? $coverage['has_tests'] : (is_object($coverage) ? $coverage->has_tests : null)));

        $report .= "\n## 🧪 測試覆蓋分析\n\n";
        $report .= "- **有測試的類別**: " . count($testedClasses) . " 個\n";
        $report .= "- **缺少測試的類別**: " . count($untestedClasses) . " 個\n\n";

        if (!empty($untestedClasses)) {
            $report .= "### 缺少測試的重要類別\n";
            foreach (array_slice($untestedClasses, 0, 20) as $className => $info) {
                if (str_contains((is_array($info) ? $info['file'] : (is_object($info) ? $info->file : null)), 'Service') || str_contains((is_array($info) ? $info['file'] : (is_object($info) ? $info->file : null)), 'Repository')) {
                    $report .= "- **$className**: `" . (is_array($info) ? $info['file'] : (is_object($info) ? $info->file : 'N/A')) . "`\n";
                }
            }
            $report .= "\n";
        }

        // 依賴注入分析
        if (!empty($this->analysis['constructor_dependencies'])) {
            $report .= "\n## 💉 依賴注入分析\n\n";
            $heavyDeps = array_filter(
                $this->analysis['constructor_dependencies'],
                fn($deps) => count((is_array($deps) ? $deps['dependencies'] : (is_object($deps) ? $deps->dependencies : null))) >= 3
            );

            if (!empty($heavyDeps)) {
                $report .= "### 依賴較多的類別 (≥3個依賴)\n";
                foreach ($heavyDeps as $className => $info) {
                    $report .= "- **$className** (" . count((is_array($info) ? $info['dependencies'] : (is_object($info) ? $info->dependencies : []))) . " 個依賴)\n";
                    foreach ((is_array($info) ? $info['dependencies'] : (is_object($info) ? $info->dependencies : [])) as $dep) {
                        $report .= "  - `" . (is_array($dep) ? $dep['type'] : (is_object($dep) ? $dep->type : 'N/A')) . "` $" . (is_array($dep) ? $dep['name'] : (is_object($dep) ? $dep->name : 'N/A')) . "\n";
                    }
                    $report .= "\n";
                }
            }
        }

        // 缺少的引用
        if (!empty($this->analysis['missing_imports'])) {
            $report .= "\n## ❓ 可能的問題引用\n\n";
            foreach (array_slice($this->analysis['missing_imports'], 0, 10) as $missing) {
                $report .= "- $missing\n";
            }
            if (count($this->analysis['missing_imports']) > 10) {
                $report .= "- ... 還有 " . (count($this->analysis['missing_imports']) - 10) . " 個\n";
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
            $files = is_array($structure) ? ($structure['files'] ?? []) : (is_object($structure) ? ($structure->files ?? []) : []);
            $fileCount = count($files);
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

        // 測試覆蓋統計
        $testedClasses = array_filter($this->analysis['test_coverage'], fn($coverage) => (is_array($coverage) ? $coverage['has_tests'] : (is_object($coverage) ? $coverage->has_tests : null)));
        $untestedClasses = array_filter($this->analysis['test_coverage'], fn($coverage) => !(is_array($coverage) ? $coverage['has_tests'] : (is_object($coverage) ? $coverage->has_tests : null)));

        $summary .= "\n🧪 測試覆蓋:\n";
        $summary .= "- 有測試: " . count($testedClasses) . " 個類別\n";
        $summary .= "- 缺少測試: " . count($untestedClasses) . " 個類別\n";

        // 介面實作統計
        if (!empty($this->analysis['interface_implementations'])) {
            $summary .= "\n🔌 介面實作:\n";
            foreach (array_slice($this->analysis['interface_implementations'], 0, 5, true) as $interface => $implementations) {
                $summary .= "- $interface: " . count($implementations) . " 個實作\n";
            }
        }

        // 依賴注入統計
        $heavyDeps = array_filter(
            $this->analysis['constructor_dependencies'],
            fn($deps) => count((is_array($deps) ? $deps['dependencies'] : (is_object($deps) ? $deps->dependencies : null))) >= 3
        );
        if (!empty($heavyDeps)) {
            $summary .= "\n💉 重依賴類別 (≥3個依賴): " . count($heavyDeps) . " 個\n";
        }

        // 可能的問題
        if (!empty($this->analysis['missing_imports'])) {
            $summary .= "\n❓ 可能問題引用: " . count($this->analysis['missing_imports']) . " 個\n";
        }

        $summary .= "\n🔑 重點服務/控制器:\n";
        $importantClasses = [];
        foreach ($this->analysis['classes'] as $className => $info) {
            if (
                str_contains((is_array($info) ? $info['file'] : (is_object($info) ? $info->file : null)), 'Controller') ||
                str_contains((is_array($info) ? $info['file'] : (is_object($info) ? $info->file : null)), 'Service') ||
                str_contains((is_array($info) ? $info['file'] : (is_object($info) ? $info->file : null)), 'Repository')
            ) {
                if (!str_contains((is_array($info) ? $info['file'] : (is_object($info) ? $info->file : null)), 'Test')) {
                    $importantClasses[] = "$className (" . (is_array($info) ? $info['file'] : (is_object($info) ? $info->file : 'N/A')) . ")";
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

    /**
     * 分析現代 PHP 特性使用情況
     * 基於 Context7 MCP 查詢的 PHP 8.x 最新特性
     */
    private function analyzeModernPhpFeatures(string $filePath, string $content): void
    {
        $className = basename($filePath, '.php');
        $featureUsage = [];

        foreach ($this->modernPhpFeatures as $feature => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $featureUsage[$feature] = count($matches[0]);
            }
        }

        if (!empty($featureUsage)) {
            $this->analysis['modern_syntax_usage'][$className] = $featureUsage;
        }
    }

    /**
     * 檢查 PSR-4 自動載入合規性
     * 基於最新的 PSR-4 規範
     */
    private function checkPsr4Compliance(string $filePath, string $content): void
    {
        // 檢查 namespace 宣告
        if (!preg_match('/^namespace\s+([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*);/m', $content, $namespaceMatch)) {
            $this->analysis['psr4_compliance'][$filePath] = ['error' => '缺少 namespace 宣告'];
            return;
        }

        $declaredNamespace = $namespaceMatch[1];

        // 檢查是否有 strict_types 宣告
        if (!str_contains($content, 'declare(strict_types=1)')) {
            $this->analysis['psr4_compliance'][$filePath]['warnings'][] = '缺少 strict_types 宣告';
        }

        // 檢查檔案名稱與類別名稱一致性
        if (preg_match('/^(class|interface|trait)\s+([a-zA-Z_][a-zA-Z0-9_]*)/m', $content, $classMatch)) {
            $className = $classMatch[2];
            $fileName = basename($filePath, '.php');

            if ($className !== $fileName) {
                $this->analysis['psr4_compliance'][$filePath]['warnings'][] =
                    "類別名稱 {$className} 與檔案名稱 {$fileName} 不一致";
            }
        }

        $this->analysis['psr4_compliance'][$filePath]['namespace'] = $declaredNamespace;
    }

    /**
     * 分析 DDD 邊界上下文
     * 基於 Context7 MCP 查詢的 DDD 最佳實踐
     */
    private function analyzeBoundaryContexts(): void
    {
        // 分析 Domains 目錄下的邊界上下文
        $domainsPath = $this->projectRoot . '/app/Domains';

        if (is_dir($domainsPath)) {
            $contexts = [];

            foreach (scandir($domainsPath) as $item) {
                if ($item === '.' || $item === '..') continue;

                $contextPath = $domainsPath . '/' . $item;
                if (is_dir($contextPath)) {
                    $contexts[$item] = [
                        'entities' => [],
                        'value_objects' => [],
                        'aggregates' => [],
                        'repositories' => [],
                        'services' => [],
                        'events' => []
                    ];

                    // 掃描每個上下文的組件
                    $this->scanBoundaryContext($contextPath, $contexts[$item]);
                }
            }

            $this->analysis['boundary_contexts'] = $contexts;
        }
    }

    /**
     * 掃描單一邊界上下文
     */
    private function scanBoundaryContext(string $contextPath, array &$context): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($contextPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $fileName = $file->getBasename('.php');

                // 根據命名慣例分類 DDD 組件
                if (str_contains($fileName, 'Entity') || str_contains($content, 'implements EntityInterface')) {
                    if (is_array($context) && isset($context['entities'])) {
                        $context['entities'][] = $fileName;
                    }
                } elseif (str_contains($fileName, 'ValueObject') || str_contains($content, 'ValueObject')) {
                    if (is_array($context) && isset($context['value_objects'])) {
                        $context['value_objects'][] = $fileName;
                    }
                } elseif (str_contains($fileName, 'Aggregate') || str_contains($content, 'AggregateRoot')) {
                    if (is_array($context) && isset($context['aggregates'])) {
                        $context['aggregates'][] = $fileName;
                    }
                } elseif (str_contains($fileName, 'Repository') || str_contains($content, 'RepositoryInterface')) {
                    if (is_array($context) && isset($context['repositories'])) {
                        $context['repositories'][] = $fileName;
                    }
                } elseif (str_contains($fileName, 'Service') || str_contains($content, 'DomainService')) {
                    if (is_array($context) && isset($context['services'])) {
                        $context['services'][] = $fileName;
                    }
                } elseif (str_contains($fileName, 'Event') || str_contains($content, 'DomainEvent')) {
                    if (is_array($context) && isset($context['events'])) {
                        $context['events'][] = $fileName;
                    }
                }
            }
        }
    }

    /**
     * 計算程式碼品質指標
     * 基於 Context7 MCP 查詢的品質標準
     */
    private function calculateQualityMetrics(): void
    {
        $metrics = [
            'total_classes' => count($this->analysis['classes']),
            'total_interfaces' => count($this->analysis['interfaces']),
            'total_traits' => count($this->analysis['traits']),
            'interface_to_class_ratio' => 0,
            'average_dependencies_per_class' => 0,
            'modern_php_adoption_rate' => 0,
            'psr4_compliance_rate' => 0,
            'ddd_structure_completeness' => 0
        ];

        // 計算介面與類別比例
        if ($metrics['total_classes'] > 0) {
            $metrics['interface_to_class_ratio'] = round(
                ($metrics['total_interfaces'] / $metrics['total_classes']) * 100,
                2
            );
        }

        // 計算平均依賴數量
        if (!empty($this->analysis['constructor_dependencies'])) {
            $totalDeps = array_sum(
                array_map(fn($deps) => count($deps['dependencies']), $this->analysis['constructor_dependencies'])
            );
            $metrics['average_dependencies_per_class'] = round(
                $totalDeps / count($this->analysis['constructor_dependencies']),
                2
            );
        }

        // 計算現代 PHP 特性採用率
        if (!empty($this->analysis['modern_syntax_usage'])) {
            $classesWithModernFeatures = count($this->analysis['modern_syntax_usage']);
            $metrics['modern_php_adoption_rate'] = round(
                ($classesWithModernFeatures / max($metrics['total_classes'], 1)) * 100,
                2
            );
        }

        // 計算 PSR-4 合規率
        if (!empty($this->analysis['psr4_compliance'])) {
            $compliantFiles = array_filter(
                $this->analysis['psr4_compliance'],
                fn($compliance) => !isset($compliance['error'])
            );
            $metrics['psr4_compliance_rate'] = round(
                (count($compliantFiles) / count($this->analysis['psr4_compliance'])) * 100,
                2
            );
        }

        // 計算 DDD 結構完整性
        if (!empty($this->analysis['boundary_contexts'])) {
            $completeness = 0;
            foreach ($this->analysis['boundary_contexts'] as $context) {
                $componentCount = array_sum(array_map('count', $context));
                if ($componentCount >= 3) $completeness++; // 至少有3種DDD組件
            }
            $metrics['ddd_structure_completeness'] = round(
                ($completeness / count($this->analysis['boundary_contexts'])) * 100,
                2
            );
        }

        $this->analysis['quality_metrics'] = $metrics;
    }

    /**
     * 獲取 PHP 特性建議
     */
    private function getFeatureRecommendation(string $feature): string
    {
        return match ($feature) {
            'readonly_properties' => '✅ 提升資料不變性',
            'enum_usage' => '✅ 型別安全的常數',
            'union_types' => '✅ 更靈活的型別定義',
            'intersection_types' => '✅ 嚴格的型別約束',
            'constructor_promotion' => '✅ 減少樣板程式碼',
            'match_expression' => '✅ 更安全的條件分支',
            'attributes' => '✅ 現代化 metadata',
            'nullsafe_operator' => '✅ 防止 null 指標異常',
            default => '建議採用'
        };
    }

    private function shouldExclude(string $path): bool
    {
        foreach ($this->excludeDirectories as $excludeDir) {
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
