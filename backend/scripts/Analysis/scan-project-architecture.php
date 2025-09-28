<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Analysis;

use AlleyNote\Scripts\Lib\ArchitectureScanner;

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    echo "🔍 開始掃描專案架構...\n";
    $scanner = new ArchitectureScanner(dirname(__DIR__, 2));
    $scanner->scan();

    // 產生 markdown 報告內容
    $markdownReport = $scanner->generateReport('markdown');
    // 產生摘要內容
    $summaryReport = $scanner->generateReport('summary');

    // 寫入 architecture-report.md
    $reportPath = __DIR__ . '/../../storage/architecture-report.md';
    file_put_contents($reportPath, $markdownReport);

    // 寫入 architecture-summary.txt
    $summaryPath = __DIR__ . '/../../storage/architecture-summary.txt';
    file_put_contents($summaryPath, $summaryReport);

    // 同時 echo 結果
    echo $markdownReport;
    echo "\n---\n";
    echo $summaryReport;
    echo "\n✅ 架構掃描完成！\n";
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
