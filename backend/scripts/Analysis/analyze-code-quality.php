<?php

declare(strict_types=1);

namespace AlleyNote\Scripts\Analysis;

use AlleyNote\Scripts\Lib\CodeQualityAnalyzer;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * 程式碼品質分析腳本
 * 分析當前專案的品質指標並提供改善建議
 */

function formatReport(array $report): string
{
    $output = "# 程式碼品質分析報告\n\n";
    $output .= "**生成時間**: " . date('Y-m-d H:i:s') . "\n\n";

    // PSR-4 指標
    $psr4 = $report['metrics']['psr4'];
    $output .= "## 📊 PSR-4 合規性\n\n";
    $output .= "- **總檔案數**: {$psr4['total_files']}\n";
    $output .= "- **合規檔案數**: {$psr4['compliant_files']}\n";
    $output .= "- **合規率**: {$psr4['compliance_rate']}%\n\n";

    if (!empty($report['issues']['psr4'])) {
        $output .= "### PSR-4 問題清單\n\n";
        foreach (array_slice($report['issues']['psr4'], 0, 10) as $issue) {
            $output .= "- **{$issue['file']}**: {$issue['message']}\n";
        }
        if (count($report['issues']['psr4']) > 10) {
            $remaining = count($report['issues']['psr4']) - 10;
            $output .= "- ... 還有 {$remaining} 個問題\n";
        }
        $output .= "\n";
    }

    // 現代 PHP 特性
    $modernPhp = $report['metrics']['modern_php'];
    $output .= "## 🚀 現代 PHP 特性使用情況\n\n";

    foreach ($modernPhp['features_used'] as $feature => $count) {
        $featureName = match($feature) {
            'enums' => '枚舉型別',
            'readonly_properties' => '唯讀屬性',
            'match_expressions' => 'Match 表達式',
            'union_types' => '聯合型別',
            'constructor_promotion' => '建構子屬性提升',
            'attributes' => '屬性標籤',
            'nullsafe_operator' => '空安全運算子',
            default => $feature
        };
        $output .= "- **{$featureName}**: {$count} 次使用\n";
    }
    $output .= "\n";

    if (!empty($report['issues']['modern_php'])) {
        $output .= "### 可改善的檔案 (前10個)\n\n";
        $count = 0;
        foreach ($report['issues']['modern_php'] as $file => $issues) {
            if ($count++ >= 10) break;
            $output .= "**{$file}**:\n";
            foreach ($issues as $issue) {
                $output .= "  - {$issue['message']} ({$issue['count']} 處)\n";
            }
            $output .= "\n";
        }
    }

    // DDD 結構
    $ddd = $report['metrics']['ddd'];
    $output .= "## 🏛️ DDD 架構分析\n\n";
    $output .= "- **完整性評分**: {$ddd['completeness_score']}%\n";
    $output .= "- **總組件數**: {$ddd['total_components']}\n\n";

    foreach ($ddd['components'] as $type => $components) {
        $typeName = match($type) {
            'entities' => '實體',
            'value_objects' => '值物件',
            'aggregates' => '聚合根',
            'repositories' => '儲存庫',
            'domain_services' => '領域服務',
            'domain_events' => '領域事件',
            default => $type
        };
        $output .= "- **{$typeName}**: " . count($components) . " 個\n";
    }
    $output .= "\n";

    // 建議事項
    if (!empty($report['recommendations'])) {
        $output .= "## 💡 改善建議\n\n";
        foreach ($report['recommendations'] as $rec) {
            $priority = match($rec['priority']) {
                'high' => '🔥 高',
                'medium' => '🟡 中',
                'low' => '🟢 低',
                default => $rec['priority']
            };

            $output .= "### {$rec['category']} (優先級: {$priority})\n\n";
            $output .= "**行動**: {$rec['action']}\n\n";
            $output .= "**具體步驟**:\n";
            foreach ($rec['details'] as $detail) {
                $output .= "- {$detail}\n";
            }
            $output .= "\n";
        }
    }

    return $output;
}

try {
    echo "🔍 開始分析程式碼品質...\n";

    $analyzer = new CodeQualityAnalyzer(dirname(__DIR__, 2));
    $analyzer->analyze();
    $report = $analyzer->getReport();

    // 輸出到檔案
    $reportContent = formatReport($report);
    $reportPath = dirname(__DIR__, 2) . '/storage/code-quality-analysis.md';

    // 確保目錄存在
    $storageDir = dirname($reportPath);
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }

    file_put_contents($reportPath, $reportContent);

    // 同時輸出到終端
    echo $reportContent;

    echo "\n📝 詳細報告已儲存至: {$reportPath}\n";
    echo "✅ 分析完成！\n";

} catch (Exception $e) {
    echo "❌ 分析失敗: " . $e->getMessage() . "\n";
    exit(1);
}
