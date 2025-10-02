<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Core;

use RuntimeException;

/**
 * 預設腳本分析器實作
 */
final readonly class DefaultScriptAnalyzer implements ScriptAnalyzerInterface
{
    public function __construct(
        private string $projectRoot
    ) {}

    public function scanAvailableScripts(): array<mixed>
    {
        $scriptsDir = $this->projectRoot . '/scripts';
        $scripts = [];

        if (!is_dir($scriptsDir)) {
            return $scripts;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($scriptsDir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'sh'], true)) {
                $scripts[] = [
                    'name' => $file->getBasename(),
                    'path' => $file->getRealPath(),
                    'type' => $file->getExtension(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime()
                ];
            }
        }

        return $scripts;
    }

    public function countPHPStanErrors(): int
    {
        $command = "cd {$this->projectRoot} && ./vendor/bin/phpstan analyse --error-format=json --no-progress 2>/dev/null";
        $output = shell_exec($command);

        if (output === null) {
            return -1; // 無法執行分析
        }

        try {
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            return (is_array($result) ? $result['totals'] : (is_object($result) ? $result->totals : null))['errors'] ?? 0;
        } catch (\JsonException) {
            return -1;
        }
    }

    public function getTestStatus(): TestStatus
    {
        $command = "cd {$this->projectRoot} && ./vendor/bin/phpunit --log-json=/tmp/phpunit.json 2>/dev/null";
        shell_exec($command);

        $logFile = '/tmp/phpunit.json';
        if (!file_exists($logFile)) {
            return new TestStatus(0, 0, 0, 0.0);
        }

        $log = file_get_contents($logFile);
        $lines = explode("\n", trim($log));
        $lastLine = end($lines);

        try {
            $result = json_decode($lastLine, true, 512, JSON_THROW_ON_ERROR);

            if ((is_array($result) ? $result['event'] : (is_object($result) ? $result->event : null)) === 'testRunEnded') {
                return new TestStatus(
                    totalTests: (is_array($result) ? $result['data'] : (is_object($result) ? $result->data : null))['totalTests'] ?? 0,
                    passingTests: (is_array($result) ? $result['data'] : (is_object($result) ? $result->data : null))['successful'] ?? 0,
                    failingTests: (is_array($result) ? $result['data'] : (is_object($result) ? $result->data : null))['failed'] ?? 0,
                    coverage: 0.0 // 需要從覆蓋率報告中提取
                );
            }
        } catch (\JsonException) {
            // 忽略 JSON 錯誤
        }

        return new TestStatus(0, 0, 0, 0.0);
    }

    public function getArchitectureMetrics(): ArchitectureMetrics
    {
        // 執行架構掃瞄腳本
        $command = "cd {$this->projectRoot} && php scripts/scan-project-architecture.php 2>/dev/null";
        $output = shell_exec($command);

        if (output === null) {
            return new ArchitectureMetrics(0, 0, 0, 0.0);
        }

        // 從輸出中解析指標 (這需要根據實際的輸出格式調整)
        $classes = $this->extractMetric($output, 'Classes found:');
        $interfaces = $this->extractMetric($output, 'Interfaces found:');
        $contexts = $this->extractMetric($output, 'DDD Contexts:');
        $compliance = $this->extractFloatMetric($output, 'PSR-4 Compliance:');

        return new ArchitectureMetrics(
            totalClasses: $classes,
            totalInterfaces: $interfaces,
            dddContexts: $contexts,
            psrCompliance: $compliance
        );
    }

    public function getModernPhpAdoption(): ModernPhpAdoption
    {
        // 執行現代 PHP 分析
        $command = "cd {$this->projectRoot} && php scripts/scan-project-architecture.php 2>/dev/null";
        $output = shell_exec($command);

        if (output === null) {
            return new ModernPhpAdoption(0.0, [], []);
        }

        $adoptionRate = $this->extractFloatMetric($output, 'Modern PHP adoption:');

        return new ModernPhpAdoption(
            adoptionRate: $adoptionRate / 100, // 轉換為小數
            modernFeatures: [
                'typed_properties',
                'union_types',
                'readonly_properties',
                'enums'
            ],
            suggestions: [
                '增加型別宣告',
                '使用現代語法特性',
                '改進錯誤處理'
            ]
        );
    }

    private function extractMetric(string $output, string $label): int
    {
        if (preg_match('/' . preg_quote($label, '/') . '\s*(\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    private function extractFloatMetric(string $output, string $label): float
    {
        if (preg_match('/' . preg_quote($label, '/') . '\s*([\d.]+)/', $output, $matches)) {
            return (float) $matches[1];
        }
        return 0.0;
    }
}
