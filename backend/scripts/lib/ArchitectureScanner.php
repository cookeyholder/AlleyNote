<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Lib;

/**
 * 專案架構快速掃描腳本
 * 用於分析整個專案的結構、命名空間、類別關係等
 * 基於 Context7 MCP 查詢的最新分析技術和 DDD 最佳實踐
 */
final class ArchitectureScanner
{
    private array $analysis = [
        'overview' => [],
        'directories' => [],
        'namespaces' => [],
        'classes' => [],
        'interfaces' => [],
        'ddd_analysis' => [],
        'code_quality' => [],
        'modern_php_features' => [],
    ];

    private array $modernFeatures = [
        'match_expressions' => 0,
        'readonly_properties' => 0,
        'nullsafe_operator' => 0,
        'attributes' => 0,
        'union_types' => 0,
        'constructor_promotion' => 0,
        'enums' => 0,
        'named_arguments' => 0,
        'fibers' => 0,
    ];

    private array $codeQualityMetrics = [
        'total_classes' => 0,
        'total_interfaces' => 0,
        'total_traits' => 0,
        'psr4_compliant' => 0,
        'strict_types_usage' => 0,
        'return_type_declarations' => 0,
        'parameter_type_declarations' => 0,
    ];

    public function __construct(private readonly string $basePath)
    {
        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Base path does not exist: {$basePath}");
        }
    }

    public function scan(): self
    {
        $this->scanDirectories();
        $this->analyzeFiles();
        $this->calculateMetrics();

        return $this;
    }

    public function generateReport(string $format = 'markdown'): string
    {
        return match ($format) {
            'json' => $this->generateJsonReport(),
            'markdown' => $this->generateMarkdownReport(),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }

    private function scanDirectories(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath . '/app', \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $relativePath = str_replace($this->basePath . '/', '', $file->getPathname());
                $this->analysis['directories'][] = $relativePath;
            }
        }
    }

    private function analyzeFiles(): void
    {
        $phpFiles = $this->findPhpFiles($this->basePath . '/app');

        foreach ($phpFiles as $file) {
            $this->analyzeFile($file);
        }
    }

    private function findPhpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function analyzeFile(string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        $this->analyzePhpFeatures($content);
        $this->analyzeCodeQuality($content, $filePath);
        $this->extractNamespaceAndClasses($content, $filePath);
    }

    private function analyzePhpFeatures(string $content): void
    {
        // Match expressions
        $this->modernFeatures['match_expressions'] += preg_match_all('/\bmatch\s*\(/i', $content);

        // Readonly properties
        $this->modernFeatures['readonly_properties'] += preg_match_all('/\breadonly\s+(?:public|private|protected)\s+/i', $content);

        // Nullsafe operator
        $this->modernFeatures['nullsafe_operator'] += preg_match_all('/\?\->/', $content);

        // Attributes
        $this->modernFeatures['attributes'] += preg_match_all('/#\[\w+/', $content);

        // Union types
        $this->modernFeatures['union_types'] += preg_match_all('/\w+\|\w+/', $content);

        // Constructor promotion
        $this->modernFeatures['constructor_promotion'] += preg_match_all('/public\s+readonly\s+\w+\s+\$\w+/i', $content);

        // Enums
        $this->modernFeatures['enums'] += preg_match_all('/\benum\s+\w+/i', $content);
    }

    private function analyzeCodeQuality(string $content, string $filePath): void
    {
        // Strict types
        if (str_contains($content, 'declare(strict_types=1)')) {
            $this->codeQualityMetrics['strict_types_usage']++;
        }

        // Class/Interface/Trait counting
        $this->codeQualityMetrics['total_classes'] += preg_match_all('/\bclass\s+\w+/i', $content);
        $this->codeQualityMetrics['total_interfaces'] += preg_match_all('/\binterface\s+\w+/i', $content);
        $this->codeQualityMetrics['total_traits'] += preg_match_all('/\btrait\s+\w+/i', $content);

        // PSR-4 compliance check
        if ($this->isPsr4Compliant($content, $filePath)) {
            $this->codeQualityMetrics['psr4_compliant']++;
        }

        // Return type declarations
        $this->codeQualityMetrics['return_type_declarations'] += preg_match_all('/\):\s*\w+/', $content);

        // Parameter type declarations
        $this->codeQualityMetrics['parameter_type_declarations'] += preg_match_all('/\w+\s+\$\w+/', $content);
    }

    private function isPsr4Compliant(string $content, string $filePath): bool
    {
        // Extract namespace
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            return false;
        }

        $namespace = $namespaceMatches[1];
        $expectedPath = str_replace('\\', '/', str_replace('App\\', '', $namespace));
        $actualPath = str_replace($this->basePath . '/app/', '', dirname($filePath));

        return $expectedPath === $actualPath;
    }

    private function extractNamespaceAndClasses(string $content, string $filePath): void
    {
        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            $namespace = $namespaceMatches[1];

            if (!isset($this->analysis['namespaces'][$namespace])) {
                $this->analysis['namespaces'][$namespace] = [];
            }

            // Extract classes, interfaces, traits
            if (preg_match('/(?:class|interface|trait)\s+(\w+)/', $content, $classMatches)) {
                $this->analysis['namespaces'][$namespace][] = [
                    'name' => $classMatches[1],
                    'file' => str_replace($this->basePath . '/', '', $filePath),
                    'type' => $this->getClassType($content)
                ];
            }
        }
    }

    private function getClassType(string $content): string
    {
        if (preg_match('/\binterface\s+\w+/', $content)) {
            return 'interface';
        }

        if (preg_match('/\btrait\s+\w+/', $content)) {
            return 'trait';
        }

        if (preg_match('/\benum\s+\w+/', $content)) {
            return 'enum';
        }

        return 'class';
    }

    private function calculateMetrics(): void
    {
        $totalFiles = $this->codeQualityMetrics['total_classes'] +
                      $this->codeQualityMetrics['total_interfaces'] +
                      $this->codeQualityMetrics['total_traits'];

        $this->analysis['code_quality'] = [
            'total_files' => $totalFiles,
            'psr4_compliance_rate' => $totalFiles > 0 ? ($this->codeQualityMetrics['psr4_compliant'] / $totalFiles) * 100 : 0,
            'strict_types_adoption' => $totalFiles > 0 ? ($this->codeQualityMetrics['strict_types_usage'] / $totalFiles) * 100 : 0,
            'modern_php_score' => $this->calculateModernPhpScore(),
        ];

        $this->analysis['modern_php_features'] = $this->modernFeatures;
    }

    private function calculateModernPhpScore(): float
    {
        $totalFeatures = array_sum($this->modernFeatures);
        $totalFiles = $this->codeQualityMetrics['total_classes'] +
                      $this->codeQualityMetrics['total_interfaces'] +
                      $this->codeQualityMetrics['total_traits'];

        if ($totalFiles === 0) {
            return 0.0;
        }

        return ($totalFeatures / ($totalFiles * 3)) * 100; // Normalized to 100%
    }

    private function generateJsonReport(): string
    {
        return json_encode($this->analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function generateMarkdownReport(): string
    {
        $report = "# 專案架構分析報告（基於 Context7 MCP 最新技術）\n\n";
        $report .= "**生成時間**: " . date('Y-m-d H:i:s') . "\n\n";

        $report .= "## 📊 程式碼品質指標\n\n";
        $report .= "| 指標 | 數值 | 狀態 |\n";
        $report .= "|------|------|------|\n";
        $report .= sprintf("| 總類別數 | %d | - |\n", $this->analysis['code_quality']['total_files']);
        $report .= sprintf("| PSR-4 合規率 | %.2f%% | %s |\n",
            $this->analysis['code_quality']['psr4_compliance_rate'],
            $this->analysis['code_quality']['psr4_compliance_rate'] >= 90 ? '✅ 優秀' :
            ($this->analysis['code_quality']['psr4_compliance_rate'] >= 75 ? '⚠️ 可改善' : '❌ 需修正')
        );
        $report .= sprintf("| 現代 PHP 採用率 | %.2f%% | %s |\n",
            $this->analysis['code_quality']['modern_php_score'],
            $this->analysis['code_quality']['modern_php_score'] >= 80 ? '✅ 優秀' :
            ($this->analysis['code_quality']['modern_php_score'] >= 60 ? '⚠️ 可改善' : '❌ 需修正')
        );

        $report .= "\n## 🚀 現代 PHP 特性使用情況\n\n";
        $report .= "| 特性 | 使用次數 | 描述 |\n";
        $report .= "|------|----------|------|\n";

        $featureDescriptions = [
            'match_expressions' => 'Match 表達式 (PHP 8.0+)',
            'readonly_properties' => '唯讀屬性 (PHP 8.1+)',
            'nullsafe_operator' => '空安全運算子 (PHP 8.0+)',
            'attributes' => '屬性標籤 (PHP 8.0+)',
            'union_types' => '聯合型別 (PHP 8.0+)',
            'constructor_promotion' => '建構子屬性提升 (PHP 8.0+)',
            'enums' => '列舉型別 (PHP 8.1+)',
        ];

        foreach ($this->modernFeatures as $feature => $count) {
            if (isset($featureDescriptions[$feature]) && $count > 0) {
                $report .= sprintf("| %s | %d | ✅ %s |\n",
                    $featureDescriptions[$feature],
                    $count,
                    $this->getFeatureDescription($feature)
                );
            }
        }

        $report .= "\n## 🏷️ 命名空間分析\n\n";
        foreach ($this->analysis['namespaces'] as $namespace => $classes) {
            $report .= "### `{$namespace}`\n";
            foreach ($classes as $class) {
                $report .= "- {$class['file']}\n";
            }
            $report .= "\n";
        }

        return $report;
    }

    private function getFeatureDescription(string $feature): string
    {
        return match ($feature) {
            'match_expressions' => '更安全的條件分支',
            'readonly_properties' => '提升資料不變性',
            'nullsafe_operator' => '防止 null 指標異常',
            'attributes' => '現代化 metadata',
            'union_types' => '更靈活的型別定義',
            'constructor_promotion' => '減少樣板程式碼',
            'enums' => '型別安全的常數',
            default => '現代 PHP 特性'
        };
    }
}
