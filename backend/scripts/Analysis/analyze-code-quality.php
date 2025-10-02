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
    $output .= "- **合規率**: {$psr4['compliance_rate']}%\n";
    
    $complianceStatus = $psr4['compliance_rate'] >= 95 ? '✅ 優秀' : 
                       ($psr4['compliance_rate'] >= 90 ? '⚠️ 良好' : '❌ 需改善');
    $output .= "- **狀態**: {$complianceStatus}\n\n";

    if (!empty($report['issues']['psr4'])) {
        $output .= "### PSR-4 問題清單\n\n";
        $issuesByType = [];
        foreach ($report['issues']['psr4'] as $issue) {
            $type = $issue['type'];
            if (!isset($issuesByType[$type])) {
                $issuesByType[$type] = [];
            }
            $issuesByType[$type][] = $issue;
        }

        foreach ($issuesByType as $type => $issues) {
            $typeName = match($type) {
                'missing_strict_types' => '缺少 strict_types 聲明',
                'missing_namespace' => '缺少命名空間',
                'namespace_path_mismatch' => '命名空間路徑不符',
                'class_filename_mismatch' => '類別與檔案名稱不一致',
                default => $type
            };
            $output .= "\n#### {$typeName} (" . count($issues) . " 個)\n\n";
            foreach (array_slice($issues, 0, 5) as $issue) {
                $output .= "- **{$issue['file']}**: {$issue['message']}\n";
            }
            if (count($issues) > 5) {
                $remaining = count($issues) - 5;
                $output .= "- ... 還有 {$remaining} 個相同問題\n";
            }
        }
        $output .= "\n";
    }

    // 現代 PHP 特性
    $modernPhp = $report['metrics']['modern_php'];
    $output .= "## 🚀 現代 PHP 特性使用情況\n\n";
    $output .= "- **特性採用率**: {$modernPhp['adoption_rate']}%\n";
    $output .= "- **總使用次數**: {$modernPhp['total_feature_usage']}\n";
    $output .= "- **掃描檔案數**: {$modernPhp['total_files_scanned']}\n\n";

    $output .= "### 特性使用明細\n\n";
    $output .= "| 特性 | 使用次數 | 狀態 |\n";
    $output .= "|------|---------|------|\n";

    foreach ($modernPhp['features_used'] as $feature => $count) {
        $featureName = match($feature) {
            'enums' => '枚舉型別 (PHP 8.1+)',
            'readonly_properties' => '唯讀屬性 (PHP 8.1+)',
            'readonly_classes' => '唯讀類別 (PHP 8.2+)',
            'match_expressions' => 'Match 表達式 (PHP 8.0+)',
            'union_types' => '聯合型別 (PHP 8.0+)',
            'intersection_types' => '交集型別 (PHP 8.1+)',
            'constructor_promotion' => '建構子屬性提升 (PHP 8.0+)',
            'attributes' => '屬性標籤 (PHP 8.0+)',
            'nullsafe_operator' => '空安全運算子 (PHP 8.0+)',
            'named_arguments' => '具名參數 (PHP 8.0+)',
            'first_class_callable_syntax' => 'First-class Callable (PHP 8.1+)',
            default => $feature
        };
        
        $status = $count > 50 ? '✅' : ($count > 10 ? '⚠️' : ($count > 0 ? '🟡' : '❌'));
        $output .= "| {$featureName} | {$count} | {$status} |\n";
    }
    $output .= "\n";

    if (!empty($report['issues']['modern_php'])) {
        $output .= "### 可改善的檔案 (前10個)\n\n";
        $count = 0;
        foreach ($report['issues']['modern_php'] as $file => $issues) {
            if ($count++ >= 10) break;
            $output .= "**{$file}**:\n";
            foreach ($issues as $issue) {
                $icon = match($issue['type']) {
                    'can_use_match' => '🔄',
                    'missing_return_types' => '📝',
                    'can_use_readonly_class' => '🔒',
                    'can_use_constructor_promotion' => '⚡',
                    default => '💡'
                };
                $output .= "  {$icon} {$issue['message']} ({$issue['count']} 處)\n";
            }
            $output .= "\n";
        }
    }

    // DDD 結構
    $ddd = $report['metrics']['ddd'];
    $output .= "## 🏛️ DDD 架構分析\n\n";
    $output .= "- **完整性評分**: {$ddd['completeness_score']}%\n";
    $output .= "- **總組件數**: {$ddd['total_components']}\n\n";

    // 品質指標
    if (isset($ddd['quality_metrics'])) {
        $output .= "### 品質指標\n\n";
        $quality = $ddd['quality_metrics'];
        $output .= "- **值物件使用率**: {$quality['value_object_ratio']}%\n";
        $output .= "- **Repository 覆蓋率**: {$quality['repository_coverage']}%\n";
        $output .= "- **事件驅動準備度**: {$quality['event_driven_readiness']}%\n";
        $output .= "- **關注點分離度**: {$quality['separation_of_concerns']}%\n\n";
    }

    // 組件統計
    $output .= "### 組件統計\n\n";
    foreach ($ddd['components'] as $type => $components) {
        $typeName = match($type) {
            'entities' => '實體',
            'value_objects' => '值物件',
            'aggregates' => '聚合根',
            'repositories' => '儲存庫',
            'domain_services' => '領域服務',
            'domain_events' => '領域事件',
            'dtos' => 'DTO',
            'specifications' => '規格物件',
            'factories' => '工廠',
            default => $type
        };
        $count = count($components);
        $icon = $count > 0 ? '✅' : '❌';
        $output .= "- {$icon} **{$typeName}**: {$count} 個\n";
    }
    $output .= "\n";

    // 限界上下文
    if (isset($ddd['bounded_contexts']) && !empty($ddd['bounded_contexts'])) {
        $output .= "### 限界上下文分析\n\n";
        $output .= "| 上下文 | 完整度 | 實體 | 值物件 | 儲存庫 | 服務 | 事件 |\n";
        $output .= "|--------|--------|------|--------|--------|------|------|\n";
        
        foreach ($ddd['bounded_contexts'] as $name => $context) {
            $completeness = $context['completeness'];
            $status = $completeness >= 80 ? '✅' : ($completeness >= 50 ? '⚠️' : '❌');
            
            $output .= "| **{$name}** | {$status} {$completeness}% ";
            $output .= "| " . ($context['has_entities'] ? '✅' : '❌');
            $output .= "| " . ($context['has_value_objects'] ? '✅' : '❌');
            $output .= "| " . ($context['has_repositories'] ? '✅' : '❌');
            $output .= "| " . ($context['has_services'] ? '✅' : '❌');
            $output .= "| " . ($context['has_events'] ? '✅' : '❌') . " |\n";
        }
        $output .= "\n";
    }

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

    // 總結
    $output .= "## 📈 總體評估\n\n";
    $overallScore = ($psr4['compliance_rate'] + $modernPhp['adoption_rate'] + $ddd['completeness_score']) / 3;
    $output .= sprintf("**綜合評分**: %.2f/100\n\n", $overallScore);
    
    $grade = $overallScore >= 90 ? 'A (優秀)' : 
            ($overallScore >= 80 ? 'B (良好)' : 
            ($overallScore >= 70 ? 'C (及格)' : 
            ($overallScore >= 60 ? 'D (需改善)' : 'F (不及格)')));
    
    $output .= "**等級**: {$grade}\n\n";

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
