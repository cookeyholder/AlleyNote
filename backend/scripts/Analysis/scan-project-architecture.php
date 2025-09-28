<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Analysis;

use AlleyNote\Scripts\Lib\ArchitectureScanner;

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    echo "🔍 開始掃描專案架構...\n";
    $scanner = new ArchitectureScanner(dirname(__DIR__, 2));
    $scanner->scan();
    echo $scanner->generateReport('markdown');
    echo "\n✅ 架構掃描完成！\n";
} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
