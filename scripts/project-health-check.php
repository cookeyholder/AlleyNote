#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 專案健康檢查腳本工具
 *
 * 功能：
 * - 掃描整個專案架構
 * - 統計語法錯誤和類型錯誤
 * - 分析測試錯誤類型和數量
 * - 產生可讀的報告
 * - 提供優先修復建議
 */

class ProjectHealthChecker
{
    private string $projectRoot;
    private array $scanResults = [];
    private array $errorStats = [];
    private array $fileRelations = [];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    /**
     * 執行完整的專案健康檢查
     */
    public function runHealthCheck(): void
    {
        echo "🔍 AlleyNote 專案健康檢查工具\n";
        echo "=" . str_repeat("=", 50) . "\n\n";

        $this->scanProjectStructure();
        $this->analyzePhpSyntax();
        $this->analyzePhpStan();
        $this->analyzeTests();
        $this->analyzeFileRelations();
        $this->generateReport();
    }

    /**
     * 掃描專案結構
     */
    private function scanProjectStructure(): void
    {
        echo "📁 掃描專案結構...\n";

        $directories = [
            'app/Domains',
            'app/Infrastructure',
            'app/Application',
            'tests'
        ];

        foreach ($directories as $dir) {
            $fullPath = $this->projectRoot . '/' . $dir;
            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $dir);
            }
        }

        echo "   ✅ 已掃描 " . count($this->scanResults) . " 個檔案\n\n";
    }

    /**
     * 遞迴掃描目錄
     */
    private function scanDirectory(string $path, string $relativePath): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativeFilePath = str_replace($this->projectRoot . '/', '', $file->getPathname());
                $this->scanResults[$relativeFilePath] = [
                    'full_path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'domain' => $this->extractDomain($relativeFilePath),
                    'type' => $this->classifyFileType($relativeFilePath)
                ];
            }
        }
    }

    /**
     * 分析 PHP 語法錯誤
     */
    private function analyzePhpSyntax(): void
    {
        echo "🔧 分析 PHP 語法錯誤...\n";

        $syntaxErrors = [];
        $totalFiles = count($this->scanResults);
        $checkedFiles = 0;

        foreach ($this->scanResults as $file => $info) {
            $checkedFiles++;
            $output = [];
            $returnCode = 0;

            // 使用 php -l 檢查語法
            exec("php -l '{$info['full_path']}' 2>&1", $output, $returnCode);

            if ($returnCode !== 0) {
                $syntaxErrors[$file] = implode("\n", $output);
            }

            // 顯示進度
            if ($checkedFiles % 20 === 0 || $checkedFiles === $totalFiles) {
                echo "   進度: {$checkedFiles}/{$totalFiles} 檔案\r";
            }
        }

        $this->errorStats['syntax_errors'] = $syntaxErrors;
        echo "\n   ❌ 發現 " . count($syntaxErrors) . " 個語法錯誤檔案\n\n";
    }

    /**
     * 分析 PHPStan 錯誤
     */
    private function analyzePhpStan(): void
    {
        echo "🔍 分析 PHPStan 靜態分析錯誤...\n";

        $phpstanOutput = [];
        $returnCode = 0;

        // 執行 PHPStan 分析
        $command = "./vendor/bin/phpstan analyse --memory-limit=1G --error-format=json 2>/dev/null";
        exec($command, $phpstanOutput, $returnCode);

        $phpstanData = [];
        if (!empty($phpstanOutput)) {
            $jsonOutput = implode("\n", $phpstanOutput);
            $phpstanData = json_decode($jsonOutput, true) ?: [];
        }

        $this->errorStats['phpstan_errors'] = $this->categorizePhpStanErrors($phpstanData);

        $totalErrors = $phpstanData['totals']['file_errors'] ?? 0;
        echo "   ❌ PHPStan 發現 {$totalErrors} 個錯誤\n\n";
    }

    /**
     * 分析測試錯誤
     */
    private function analyzeTests(): void
    {
        echo "🧪 分析測試錯誤...\n";

        $testOutput = [];
        $returnCode = 0;

        // 嘗試執行測試
        $command = "./vendor/bin/phpunit --configuration=phpunit.xml --dry-run 2>&1";
        exec($command, $testOutput, $returnCode);

        $this->errorStats['test_errors'] = [
            'return_code' => $returnCode,
            'output' => implode("\n", $testOutput),
            'can_run' => $returnCode === 0
        ];

        if ($returnCode === 0) {
            echo "   ✅ 測試可以執行\n\n";
        } else {
            echo "   ❌ 測試無法執行\n\n";
        }
    }

    /**
     * 分析檔案關聯性
     */
    private function analyzeFileRelations(): void
    {
        echo "🔗 分析檔案關聯性...\n";

        foreach ($this->scanResults as $file => $info) {
            $content = file_get_contents($info['full_path']);
            $this->fileRelations[$file] = $this->extractFileRelations($content, $file);
        }

        echo "   ✅ 完成關聯性分析\n\n";
    }

    /**
     * 產生健康檢查報告
     */
    private function generateReport(): void
    {
        echo "📊 產生健康檢查報告\n";
        echo "=" . str_repeat("=", 50) . "\n\n";

        $this->printProjectOverview();
        $this->printErrorSummary();
        $this->printDomainAnalysis();
        $this->printPriorityRecommendations();
        $this->printDetailedErrors();

        // 儲存詳細報告到檔案
        $this->saveDetailedReport();
    }

    /**
     * 印出專案概覽
     */
    private function printProjectOverview(): void
    {
        echo "📈 專案概覽\n";
        echo "-" . str_repeat("-", 30) . "\n";

        $totalFiles = count($this->scanResults);
        $syntaxErrorFiles = count($this->errorStats['syntax_errors']);
        $healthyFiles = $totalFiles - $syntaxErrorFiles;
        $healthPercentage = $totalFiles > 0 ? round(($healthyFiles / $totalFiles) * 100, 1) : 0;

        echo "總檔案數: {$totalFiles}\n";
        echo "健康檔案: {$healthyFiles}\n";
        echo "語法錯誤檔案: {$syntaxErrorFiles}\n";
        echo "健康度: {$healthPercentage}%\n";

        // 按類型統計
        $typeStats = [];
        foreach ($this->scanResults as $file => $info) {
            $typeStats[$info['type']] = ($typeStats[$info['type']] ?? 0) + 1;
        }

        echo "\n檔案類型分布:\n";
        foreach ($typeStats as $type => $count) {
            echo "  {$type}: {$count}\n";
        }

        echo "\n";
    }

    /**
     * 印出錯誤摘要
     */
    private function printErrorSummary(): void
    {
        echo "❌ 錯誤摘要\n";
        echo "-" . str_repeat("-", 30) . "\n";

        // 語法錯誤統計
        $syntaxErrorsByDomain = [];
        foreach ($this->errorStats['syntax_errors'] as $file => $error) {
            $domain = $this->scanResults[$file]['domain'] ?? 'Unknown';
            $syntaxErrorsByDomain[$domain] = ($syntaxErrorsByDomain[$domain] ?? 0) + 1;
        }

        echo "語法錯誤 (按領域):\n";
        foreach ($syntaxErrorsByDomain as $domain => $count) {
            echo "  {$domain}: {$count} 個檔案\n";
        }

        // PHPStan 錯誤統計
        if (!empty($this->errorStats['phpstan_errors'])) {
            echo "\nPHPStan 錯誤類型:\n";
            foreach ($this->errorStats['phpstan_errors'] as $type => $errors) {
                echo "  {$type}: " . count($errors) . " 個錯誤\n";
            }
        }

        // 測試狀態
        echo "\n測試狀態: ";
        if ($this->errorStats['test_errors']['can_run']) {
            echo "✅ 可執行\n";
        } else {
            echo "❌ 無法執行\n";
        }

        echo "\n";
    }

    /**
     * 印出領域分析
     */
    private function printDomainAnalysis(): void
    {
        echo "🏗️ 領域分析\n";
        echo "-" . str_repeat("-", 30) . "\n";

        $domainStats = [];
        foreach ($this->scanResults as $file => $info) {
            $domain = $info['domain'];
            if (!isset($domainStats[$domain])) {
                $domainStats[$domain] = [
                    'total' => 0,
                    'syntax_errors' => 0,
                    'types' => []
                ];
            }

            $domainStats[$domain]['total']++;
            $domainStats[$domain]['types'][$info['type']] = ($domainStats[$domain]['types'][$info['type']] ?? 0) + 1;

            if (isset($this->errorStats['syntax_errors'][$file])) {
                $domainStats[$domain]['syntax_errors']++;
            }
        }

        foreach ($domainStats as $domain => $stats) {
            $healthPercentage = $stats['total'] > 0
                ? round((($stats['total'] - $stats['syntax_errors']) / $stats['total']) * 100, 1)
                : 0;

            echo "{$domain}:\n";
            echo "  檔案數: {$stats['total']}\n";
            echo "  語法錯誤: {$stats['syntax_errors']}\n";
            echo "  健康度: {$healthPercentage}%\n";
            echo "  檔案類型: " . implode(', ', array_keys($stats['types'])) . "\n\n";
        }
    }

    /**
     * 印出優先修復建議
     */
    private function printPriorityRecommendations(): void
    {
        echo "🎯 優先修復建議\n";
        echo "-" . str_repeat("-", 30) . "\n";

        $priorities = $this->calculatePriorities();

        echo "建議修復順序 (基於風險評估):\n\n";

        $batchNumber = 1;
        foreach ($priorities as $priority) {
            if (count($priority['files']) > 0) {
                echo "批次 {$batchNumber} - {$priority['category']} (風險: {$priority['risk']}):\n";
                foreach (array_slice($priority['files'], 0, 5) as $file) {
                    echo "  • {$file}\n";
                }
                if (count($priority['files']) > 5) {
                    echo "  ... 還有 " . (count($priority['files']) - 5) . " 個檔案\n";
                }
                echo "\n";
                $batchNumber++;
            }
        }

        // 給出具體的執行建議
        echo "執行建議:\n";
        echo "1. 優先修復核心業務邏輯的語法錯誤\n";
        echo "2. 每次批次處理不超過 5 個檔案\n";
        echo "3. 修復後立即執行驗證\n";
        echo "4. 使用 git 進行版本控制\n\n";
    }

    /**
     * 印出詳細錯誤資訊
     */
    private function printDetailedErrors(): void
    {
        echo "📋 詳細錯誤清單 (前10個)\n";
        echo "-" . str_repeat("-", 50) . "\n";

        $count = 0;
        foreach ($this->errorStats['syntax_errors'] as $file => $error) {
            if ($count >= 10) break;

            echo "檔案: {$file}\n";
            echo "錯誤: " . trim($error) . "\n";
            echo "類型: " . ($this->scanResults[$file]['type'] ?? 'Unknown') . "\n";
            echo "領域: " . ($this->scanResults[$file]['domain'] ?? 'Unknown') . "\n";
            echo "-" . str_repeat("-", 30) . "\n";

            $count++;
        }

        if (count($this->errorStats['syntax_errors']) > 10) {
            echo "... 還有 " . (count($this->errorStats['syntax_errors']) - 10) . " 個錯誤\n\n";
        }
    }

    /**
     * 儲存詳細報告到檔案
     */
    private function saveDetailedReport(): void
    {
        $reportPath = $this->projectRoot . '/docs/health-check-report.md';
        $reportDir = dirname($reportPath);

        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $content = $this->generateMarkdownReport();
        file_put_contents($reportPath, $content);

        echo "📄 詳細報告已儲存至: {$reportPath}\n";
    }

    /**
     * 提取檔案領域
     */
    private function extractDomain(string $filePath): string
    {
        if (preg_match('#app/Domains/([^/]+)#', $filePath, $matches)) {
            return $matches[1];
        }

        if (strpos($filePath, 'app/Infrastructure') !== false) {
            return 'Infrastructure';
        }

        if (strpos($filePath, 'app/Application') !== false) {
            return 'Application';
        }

        if (strpos($filePath, 'tests/') !== false) {
            return 'Tests';
        }

        return 'Other';
    }

    /**
     * 分類檔案類型
     */
    private function classifyFileType(string $filePath): string
    {
        if (strpos($filePath, '/Entities/') !== false) return 'Entity';
        if (strpos($filePath, '/DTOs/') !== false) return 'DTO';
        if (strpos($filePath, '/Services/') !== false) return 'Service';
        if (strpos($filePath, '/Repositories/') !== false) return 'Repository';
        if (strpos($filePath, '/Controllers/') !== false) return 'Controller';
        if (strpos($filePath, '/Middleware/') !== false) return 'Middleware';
        if (strpos($filePath, '/Enums/') !== false) return 'Enum';
        if (strpos($filePath, '/Exceptions/') !== false) return 'Exception';
        if (strpos($filePath, '/Contracts/') !== false) return 'Contract';
        if (strpos($filePath, '/Providers/') !== false) return 'Provider';
        if (strpos($filePath, 'tests/') !== false) return 'Test';
        if (strpos($filePath, '/Commands/') !== false) return 'Command';
        if (strpos($filePath, '/Console/') !== false) return 'Console';

        return 'Other';
    }

    /**
     * 分類 PHPStan 錯誤
     */
    private function categorizePhpStanErrors(array $phpstanData): array
    {
        $categories = [
            'syntax' => [],
            'type' => [],
            'undefined' => [],
            'other' => []
        ];

        if (isset($phpstanData['files'])) {
            foreach ($phpstanData['files'] as $file => $fileData) {
                foreach ($fileData['messages'] as $message) {
                    $msg = $message['message'];

                    if (strpos($msg, 'Syntax error') !== false) {
                        $categories['syntax'][] = ['file' => $file, 'message' => $msg];
                    } elseif (strpos($msg, 'not found') !== false || strpos($msg, 'undefined') !== false) {
                        $categories['undefined'][] = ['file' => $file, 'message' => $msg];
                    } elseif (strpos($msg, 'type') !== false) {
                        $categories['type'][] = ['file' => $file, 'message' => $msg];
                    } else {
                        $categories['other'][] = ['file' => $file, 'message' => $msg];
                    }
                }
            }
        }

        return $categories;
    }

    /**
     * 提取檔案關聯性
     */
    private function extractFileRelations(string $content, string $currentFile): array
    {
        $relations = [
            'uses' => [],
            'implements' => [],
            'extends' => [],
            'dependencies' => []
        ];

        // 提取 use 語句
        if (preg_match_all('/use\s+([^;]+);/', $content, $matches)) {
            $relations['uses'] = $matches[1];
        }

        // 提取 implements
        if (preg_match('/class\s+\w+.*?implements\s+([^{]+)/', $content, $matches)) {
            $relations['implements'] = array_map('trim', explode(',', $matches[1]));
        }

        // 提取 extends
        if (preg_match('/class\s+\w+.*?extends\s+(\w+)/', $content, $matches)) {
            $relations['extends'] = [$matches[1]];
        }

        return $relations;
    }

    /**
     * 計算修復優先級
     */
    private function calculatePriorities(): array
    {
        $coreServices = [];
        $repositories = [];
        $entities = [];
        $tests = [];
        $infrastructure = [];
        $other = [];

        foreach ($this->errorStats['syntax_errors'] as $file => $error) {
            $fileInfo = $this->scanResults[$file];
            $domain = $fileInfo['domain'];
            $type = $fileInfo['type'];

            // 根據檔案類型和領域分配優先級
            if (in_array($domain, ['Post', 'Security']) && in_array($type, ['Service', 'Repository'])) {
                $coreServices[] = $file;
            } elseif ($type === 'Repository') {
                $repositories[] = $file;
            } elseif ($type === 'Entity') {
                $entities[] = $file;
            } elseif ($type === 'Test') {
                $tests[] = $file;
            } elseif ($domain === 'Infrastructure') {
                $infrastructure[] = $file;
            } else {
                $other[] = $file;
            }
        }

        return [
            ['category' => '核心服務與儲存庫', 'risk' => '高', 'files' => $coreServices],
            ['category' => '實體類別', 'risk' => '高', 'files' => $entities],
            ['category' => '儲存庫', 'risk' => '中', 'files' => $repositories],
            ['category' => '基礎設施', 'risk' => '中', 'files' => $infrastructure],
            ['category' => '測試', 'risk' => '低', 'files' => $tests],
            ['category' => '其他', 'risk' => '低', 'files' => $other]
        ];
    }

    /**
     * 產生 Markdown 格式報告
     */
    private function generateMarkdownReport(): string
    {
        $report = "# AlleyNote 專案健康檢查報告\n\n";
        $report .= "報告生成時間: " . date('Y-m-d H:i:s') . "\n\n";

        // 專案概覽
        $totalFiles = count($this->scanResults);
        $syntaxErrorFiles = count($this->errorStats['syntax_errors']);
        $healthyFiles = $totalFiles - $syntaxErrorFiles;
        $healthPercentage = $totalFiles > 0 ? round(($healthyFiles / $totalFiles) * 100, 1) : 0;

        $report .= "## 專案概覽\n\n";
        $report .= "| 項目 | 數量 |\n";
        $report .= "|------|------|\n";
        $report .= "| 總檔案數 | {$totalFiles} |\n";
        $report .= "| 健康檔案 | {$healthyFiles} |\n";
        $report .= "| 語法錯誤檔案 | {$syntaxErrorFiles} |\n";
        $report .= "| 健康度 | {$healthPercentage}% |\n\n";

        // 詳細錯誤清單
        $report .= "## 語法錯誤詳細清單\n\n";
        foreach ($this->errorStats['syntax_errors'] as $file => $error) {
            $report .= "### {$file}\n\n";
            $report .= "```\n" . trim($error) . "\n```\n\n";
        }

        // 修復建議
        $report .= "## 修復建議\n\n";
        $priorities = $this->calculatePriorities();
        foreach ($priorities as $priority) {
            if (count($priority['files']) > 0) {
                $report .= "### {$priority['category']} (風險: {$priority['risk']})\n\n";
                foreach ($priority['files'] as $file) {
                    $report .= "- {$file}\n";
                }
                $report .= "\n";
            }
        }

        return $report;
    }
}

// 主程式執行
if (php_sapi_name() === 'cli') {
    $projectRoot = '/var/www/html';

    $checker = new ProjectHealthChecker($projectRoot);
    $checker->runHealthCheck();

    echo "\n✅ 健康檢查完成！\n";
    echo "💡 建議下一步：根據優先修復建議開始批次修復\n";
}
