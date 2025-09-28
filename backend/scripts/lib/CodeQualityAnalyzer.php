<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Lib;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * 程式碼品質改善分析器
 * 協助分析當前專案的品質指標並提供具體改善建議
 */
final class CodeQualityAnalyzer
{
    private array $issues = [];
    private array $metrics = [];
    private array $recommendations = [];

    public function __construct(private readonly string $basePath)
    {
        if (!is_dir($basePath)) {
            throw new \InvalidArgumentException("Base path does not exist: {$basePath}");
        }
    }

    public function analyze(): self
    {
        $this->analyzePsr4Compliance();
        $this->analyzeModernPhpUsage();
        $this->analyzeDddStructure();
        $this->generateRecommendations();

        return $this;
    }

    public function getReport(): array
    {
        return [
            'metrics' => $this->metrics,
            'issues' => $this->issues,
            'recommendations' => $this->recommendations,
        ];
    }

    private function analyzePsr4Compliance(): void
    {
        $totalFiles = 0;
        $compliantFiles = 0;
        $issues = [];

        $phpFiles = $this->getPhpFiles();

        foreach ($phpFiles as $file) {
            $totalFiles++;
            $content = file_get_contents($file);

            // 檢查 strict_types 聲明
            if (!str_contains($content, 'declare(strict_types=1)')) {
                $issues[] = [
                    'file' => $this->getRelativePath($file),
                    'type' => 'missing_strict_types',
                    'message' => '缺少 declare(strict_types=1) 聲明',
                ];
            }

            // 檢查命名空間
            if (!preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
                $issues[] = [
                    'file' => $this->getRelativePath($file),
                    'type' => 'missing_namespace',
                    'message' => '缺少命名空間宣告',
                ];
                continue;
            }

            $namespace = $matches[1];
            $expectedPath = $this->getExpectedPath($namespace);
            $actualPath = $this->getRelativePath($file);

            if (!$this->isPathCompliant($expectedPath, $actualPath)) {
                $issues[] = [
                    'file' => $actualPath,
                    'type' => 'namespace_path_mismatch',
                    'message' => "命名空間 {$namespace} 與檔案路徑不符",
                    'expected' => $expectedPath,
                    'actual' => $actualPath,
                ];
            } else {
                $compliantFiles++;
            }

            // 檢查類別名稱與檔案名稱一致性
            if (preg_match('/(?:class|interface|trait|enum)\s+(\w+)/', $content, $classMatches)) {
                $className = $classMatches[1];
                $fileName = pathinfo($file, PATHINFO_FILENAME);

                if ($className !== $fileName) {
                    $issues[] = [
                        'file' => $this->getRelativePath($file),
                        'type' => 'class_filename_mismatch',
                        'message' => "類別名稱 {$className} 與檔案名稱 {$fileName} 不一致",
                    ];
                }
            }
        }

        $this->metrics['psr4'] = [
            'total_files' => $totalFiles,
            'compliant_files' => $compliantFiles,
            'compliance_rate' => $totalFiles > 0 ? round(($compliantFiles / $totalFiles) * 100, 2) : 0,
        ];

        $this->issues['psr4'] = $issues;
    }

    private function analyzeModernPhpUsage(): void
    {
        $features = [
            'enums' => 0,
            'readonly_properties' => 0,
            'match_expressions' => 0,
            'union_types' => 0,
            'constructor_promotion' => 0,
            'attributes' => 0,
            'nullsafe_operator' => 0,
        ];

        $improvableFiles = [];
        $phpFiles = $this->getPhpFiles();

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $relativePath = $this->getRelativePath($file);
            $fileIssues = [];

            // 檢查現代特性使用
            if (preg_match('/\benum\s+\w+/i', $content)) {
                $features['enums']++;
            }

            if (preg_match('/\breadonly\s+/i', $content)) {
                $features['readonly_properties']++;
            }

            if (preg_match('/\bmatch\s*\(/i', $content)) {
                $features['match_expressions']++;
            }

            if (preg_match('/\w+\|\w+/', $content)) {
                $features['union_types']++;
            }

            // 檢查可改善的項目
            if (preg_match_all('/switch\s*\(/i', $content, $matches)) {
                $fileIssues[] = [
                    'type' => 'can_use_match',
                    'count' => count($matches[0]),
                    'message' => '可以將 switch 語句改為 match 表達式',
                ];
            }

            if (preg_match_all('/function\s+\w+\([^)]*\)\s*(?::\s*\w+)?\s*\{/', $content, $matches)) {
                $functionsWithoutReturnType = 0;
                foreach ($matches[0] as $match) {
                    if (!str_contains($match, ':')) {
                        $functionsWithoutReturnType++;
                    }
                }

                if ($functionsWithoutReturnType > 0) {
                    $fileIssues[] = [
                        'type' => 'missing_return_types',
                        'count' => $functionsWithoutReturnType,
                        'message' => '缺少回傳型別宣告的函式',
                    ];
                }
            }

            if (!empty($fileIssues)) {
                $improvableFiles[$relativePath] = $fileIssues;
            }
        }

        $this->metrics['modern_php'] = [
            'features_used' => $features,
            'total_files_scanned' => count($phpFiles),
            'files_with_modern_features' => count(array_filter($features)),
        ];

        $this->issues['modern_php'] = $improvableFiles;
    }

    private function analyzeDddStructure(): void
    {
        $dddComponents = [
            'entities' => [],
            'value_objects' => [],
            'aggregates' => [],
            'repositories' => [],
            'domain_services' => [],
            'domain_events' => [],
        ];

        $issues = [];
        $domainPath = $this->basePath . '/app/Domains';

        if (is_dir($domainPath)) {
            $this->scanDomainDirectory($domainPath, $dddComponents);
        }

        // 分析 DDD 結構問題
        $this->analyzeDddIssues($dddComponents, $issues);

        $this->metrics['ddd'] = [
            'components' => $dddComponents,
            'total_components' => array_sum(array_map('count', $dddComponents)),
            'completeness_score' => $this->calculateDddCompleteness($dddComponents),
        ];

        $this->issues['ddd'] = $issues;
    }

    private function scanDomainDirectory(string $path, array &$components): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $relativePath = $this->getRelativePath($file->getPathname());

                // 根據檔案模式和內容識別 DDD 組件
                if (str_contains($relativePath, '/Entities/')) {
                    $components['entities'][] = $relativePath;
                } elseif (str_contains($relativePath, '/ValueObjects/')) {
                    $components['value_objects'][] = $relativePath;
                } elseif (str_contains($relativePath, '/Repositories/')) {
                    $components['repositories'][] = $relativePath;
                } elseif (str_contains($relativePath, '/Services/')) {
                    $components['domain_services'][] = $relativePath;
                } elseif (str_contains($relativePath, '/Events/')) {
                    $components['domain_events'][] = $relativePath;
                }

                // 根據內容特徵識別聚合根
                if (str_contains($content, 'AggregateRoot') ||
                    str_contains($content, 'implements.*Entity.*Interface')) {
                    $components['aggregates'][] = $relativePath;
                }
            }
        }
    }

    private function analyzeDddIssues(array $components, array &$issues): void
    {
        // 檢查是否缺少核心 DDD 組件
        if (empty($components['entities'])) {
            $issues[] = [
                'type' => 'missing_entities',
                'message' => '缺少明確定義的實體 (Entities)',
                'recommendation' => '建議建立 Entities 目錄並定義核心業務實體',
            ];
        }

        if (empty($components['value_objects'])) {
            $issues[] = [
                'type' => 'insufficient_value_objects',
                'message' => '值物件使用不足',
                'recommendation' => '建議將更多原始型別包裝為值物件',
            ];
        }

        if (empty($components['domain_events'])) {
            $issues[] = [
                'type' => 'missing_domain_events',
                'message' => '缺少領域事件機制',
                'recommendation' => '建議建立完整的領域事件系統',
            ];
        }
    }

    private function calculateDddCompleteness(array $components): float
    {
        $expectedComponents = ['entities', 'value_objects', 'repositories', 'domain_services', 'domain_events'];
        $presentComponents = array_filter($expectedComponents, fn($comp) => !empty($components[$comp]));

        return count($presentComponents) / count($expectedComponents) * 100;
    }

    private function generateRecommendations(): void
    {
        $recommendations = [];

        // PSR-4 建議
        if ($this->metrics['psr4']['compliance_rate'] < 90) {
            $recommendations[] = [
                'category' => 'PSR-4',
                'priority' => 'high',
                'action' => '修復 PSR-4 合規性問題',
                'details' => [
                    '為所有 PHP 檔案添加 declare(strict_types=1)',
                    '確保命名空間與檔案路徑一致',
                    '檢查類別名稱與檔案名稱一致性',
                ],
            ];
        }

        // 現代 PHP 建議
        if (count(array_filter($this->metrics['modern_php']['features_used'])) < 4) {
            $recommendations[] = [
                'category' => 'Modern PHP',
                'priority' => 'medium',
                'action' => '採用更多現代 PHP 特性',
                'details' => [
                    '將常數群組轉換為枚舉',
                    '使用 match 表達式取代 switch',
                    '採用建構子屬性提升',
                    '增加聯合型別的使用',
                ],
            ];
        }

        // DDD 建議
        if ($this->metrics['ddd']['completeness_score'] < 70) {
            $recommendations[] = [
                'category' => 'DDD Structure',
                'priority' => 'medium',
                'action' => '完善 DDD 架構設計',
                'details' => [
                    '建立更多值物件',
                    '完善聚合根設計',
                    '實作領域事件機制',
                    '明確定義限界上下文',
                ],
            ];
        }

        $this->recommendations = $recommendations;
    }

    private function getPhpFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->basePath . '/app')
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        // 也包含 scripts 目錄
        if (is_dir($this->basePath . '/scripts')) {
            $scriptsIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->basePath . '/scripts')
            );

            foreach ($scriptsIterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private function getRelativePath(string $absolutePath): string
    {
        return str_replace($this->basePath . '/', '', $absolutePath);
    }

    private function getExpectedPath(string $namespace): string
    {
        // 移除根命名空間
        $path = str_replace(['App\\', 'AlleyNote\\Scripts\\'], ['app/', 'scripts/'], $namespace);
        return str_replace('\\', '/', $path);
    }

    private function isPathCompliant(string $expectedPath, string $actualPath): bool
    {
        $expectedDir = dirname($expectedPath);
        $actualDir = dirname($actualPath);

        return $expectedDir === $actualDir ||
               str_starts_with($actualDir, $expectedDir);
    }
}
