<?php

declare(strict_types=1);

/**
 * 監控系統使用範例
 * 
 * 展示如何在 AlleyNote 應用程式中使用監控服務
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Shared\Monitoring\Contracts\SystemMonitorInterface;
use App\Shared\Monitoring\Contracts\PerformanceMonitorInterface;
use App\Shared\Monitoring\Contracts\ErrorTrackerInterface;
use DI\ContainerBuilder;

// 建立容器
$builder = new ContainerBuilder();
$containerConfig = require __DIR__ . '/../config/container.php';
$builder->addDefinitions($containerConfig);
$container = $builder->build();

// 取得監控服務
$systemMonitor = $container->get(SystemMonitorInterface::class);
$performanceMonitor = $container->get(PerformanceMonitorInterface::class);
$errorTracker = $container->get(ErrorTrackerInterface::class);

echo "=== AlleyNote 監控系統展示 ===\n\n";

// 1. 系統監控展示
echo "1. 系統監控資訊:\n";
$systemMetrics = $systemMonitor->getSystemMetrics();
echo "   - PHP 版本: {$systemMetrics['php_version']}\n";
echo "   - 記憶體使用量: " . round($systemMetrics['memory_usage'] / 1024 / 1024, 2) . " MB\n";
echo "   - 磁碟使用率: {$systemMetrics['disk_usage_percent']}%\n";
echo "   - 系統負載: {$systemMetrics['load_average'][0]}\n\n";

$healthStatus = $systemMonitor->getSystemHealth();
echo "   系統健康狀態: {$healthStatus['status']} ({$healthStatus['score']}/100)\n";
echo "   健康評估: {$healthStatus['message']}\n\n";

// 2. 效能監控展示
echo "2. 效能監控:\n";

// 模擬一些操作並記錄效能
$performanceMonitor->startOperation('database_query');
usleep(100000); // 模擬 100ms 的資料庫查詢
$queryTime = $performanceMonitor->endOperation('database_query');
echo "   - 資料庫查詢時間: " . round($queryTime * 1000, 2) . " ms\n";

$performanceMonitor->startOperation('api_call');
usleep(250000); // 模擬 250ms 的 API 呼叫
$apiTime = $performanceMonitor->endOperation('api_call');
echo "   - API 呼叫時間: " . round($apiTime * 1000, 2) . " ms\n";

// 記錄自定義指標
$performanceMonitor->recordMetric('user_login', 1, ['user_id' => 123]);
$performanceMonitor->recordMetric('page_view', 1, ['page' => '/dashboard']);

$performanceStats = $performanceMonitor->getPerformanceStats();
echo "   - 總操作數: {$performanceStats['total_operations']}\n";
echo "   - 平均回應時間: " . round($performanceStats['average_response_time'] * 1000, 2) . " ms\n";

$slowOperations = $performanceMonitor->getSlowOperations();
if (!empty($slowOperations)) {
    echo "   - 慢速操作: " . count($slowOperations) . " 個\n";
}
echo "\n";

// 3. 錯誤追蹤展示
echo "3. 錯誤追蹤:\n";

// 記錄不同類型的錯誤
$errorTracker->recordInfo('使用者登入', ['user_id' => 123, 'ip' => '127.0.0.1']);
$errorTracker->recordWarning('磁碟空間不足警告', ['disk_usage' => '85%']);

try {
    // 模擬一個錯誤
    throw new \Exception('模擬的應用程式錯誤', 500);
} catch (\Exception $e) {
    $errorTracker->recordError($e, ['context' => 'demo_error']);
}

try {
    // 模擬一個關鍵錯誤
    throw new \RuntimeException('關鍵系統故障', 503);
} catch (\Exception $e) {
    $errorTracker->recordCriticalError($e, ['urgency' => 'high']);
}

$errorStats = $errorTracker->getErrorStats(24);
echo "   - 24小時內總錯誤數: {$errorStats['total_errors']}\n";
echo "   - 每小時錯誤率: " . round($errorStats['error_rate_per_hour'], 2) . "\n";

if (!empty($errorStats['levels'])) {
    echo "   - 錯誤等級分佈:\n";
    foreach ($errorStats['levels'] as $level => $count) {
        echo "     * {$level}: {$count}\n";
    }
}

$recentErrors = $errorTracker->getRecentErrors(3);
echo "   - 最近 3 個錯誤:\n";
foreach ($recentErrors as $error) {
    echo "     * [{$error['level']}] {$error['message']} ({$error['formatted_time']})\n";
}

$errorSummary = $errorTracker->getErrorSummary(24);
echo "   - 系統健康狀態: {$errorSummary['health_status']['status']}\n";
echo "   - 健康評分: {$errorSummary['health_status']['score']}/100\n";
echo "   - 健康描述: {$errorSummary['health_status']['message']}\n\n";

// 4. 趨勢分析展示
echo "4. 趨勢分析:\n";

$errorTrends = $errorTracker->getErrorTrends(7);
echo "   - 7天內錯誤趨勢:\n";
echo "   - 總錯誤數: {$errorTrends['total_errors']}\n";
echo "   - 平均每日錯誤數: " . round($errorTrends['average_errors_per_day'], 2) . "\n";

if (!empty($errorTrends['daily_counts'])) {
    echo "   - 每日錯誤分佈:\n";
    foreach (array_slice($errorTrends['daily_counts'], -3, 3, true) as $date => $count) {
        echo "     * {$date}: {$count}\n";
    }
}

// 5. 監控整合展示
echo "\n5. 監控服務整合狀態:\n";

// 檢查所有服務是否正常工作
$systemHealth = $systemMonitor->checkHealth();
$hasErrors = $errorTracker->hasCriticalErrors(5);
$performanceIssues = count($performanceMonitor->getSlowOperations()) > 0;

echo "   - 系統監控: " . ($systemHealth ? '✓ 正常' : '✗ 異常') . "\n";
echo "   - 錯誤追蹤: " . ($hasErrors ? '✗ 發現關鍵錯誤' : '✓ 無關鍵錯誤') . "\n";
echo "   - 效能監控: " . ($performanceIssues ? '⚠ 發現效能問題' : '✓ 效能正常') . "\n";

$overallStatus = $systemHealth && !$hasErrors && !$performanceIssues ? '健康' : '需要關注';
echo "   - 整體狀態: {$overallStatus}\n\n";

// 6. 清理展示
echo "6. 維護操作:\n";

// 清理舊記錄
$cleanedCount = $errorTracker->cleanupOldErrors(30);
echo "   - 清理了 {$cleanedCount} 條過期錯誤記錄\n";

echo "   - 監控系統展示完成\n";

echo "\n=== 展示結束 ===\n";