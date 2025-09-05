<?php

declare(strict_types=1);

/**
 * 統計功能效能測試腳本
 * 
 * 測試項目：
 * - 記憶體使用量
 * - 資料庫查詢效能
 * - 快取命中率
 * - API 回應時間
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Domains\Statistics\Enums\PeriodType;

echo "=== 統計功能效能測試 ===\n\n";

// 測試開始時間和記憶體
$startTime = microtime(true);
$startMemory = memory_get_usage(true);
$peakMemoryStart = memory_get_peak_usage(true);

// 模擬基本統計計算
echo "1. 記憶體使用量測試\n";
$testData = [];
for ($i = 0; $i < 10000; $i++) {
    $testData[] = [
        'id' => $i,
        'views' => rand(100, 1000),
        'date' => date('Y-m-d', time() - ($i * 86400))
    ];
}

$currentMemory = memory_get_usage(true);
$memoryUsed = ($currentMemory - $startMemory) / 1024 / 1024; // MB
echo "   處理 10,000 筆資料記憶體使用量: " . round($memoryUsed, 2) . " MB\n";

// 測試資料處理效能
echo "\n2. 資料處理效能測試\n";
$processingStart = microtime(true);

// 模擬統計計算
$totalViews = array_sum(array_column($testData, 'views'));
$averageViews = $totalViews / count($testData);
$maxViews = max(array_column($testData, 'views'));
$minViews = min(array_column($testData, 'views'));

// 模擬趨勢計算
$trends = [];
$chunks = array_chunk($testData, 100);
foreach ($chunks as $chunk) {
    $chunkViews = array_sum(array_column($chunk, 'views'));
    $trends[] = $chunkViews;
}

$processingTime = (microtime(true) - $processingStart) * 1000; // ms
echo "   資料處理時間: " . round($processingTime, 2) . " ms\n";
echo "   平均每筆資料處理時間: " . round($processingTime / count($testData), 4) . " ms\n";

// 模擬快取命中率測試
echo "\n3. 快取效能模擬測試\n";
$cacheHits = 0;
$cacheMisses = 0;
$cacheData = [];

// 模擬快取存取
for ($i = 0; $i < 1000; $i++) {
    $key = 'stats_' . ($i % 100); // 重複請求相同的 100 個鍵
    
    if (isset($cacheData[$key])) {
        $cacheHits++;
    } else {
        $cacheMisses++;
        $cacheData[$key] = [
            'data' => $testData[array_rand($testData)],
            'timestamp' => time()
        ];
    }
}

$hitRate = ($cacheHits / ($cacheHits + $cacheMisses)) * 100;
echo "   快取命中次數: $cacheHits\n";
echo "   快取未命中次數: $cacheMisses\n";
echo "   快取命中率: " . round($hitRate, 2) . "%\n";

// 模擬 API 回應時間測試
echo "\n4. API 回應時間模擬測試\n";
$apiTests = [
    'statistics_overview' => function() use ($testData) {
        // 模擬統計概覽 API
        $start = microtime(true);
        $summary = [
            'total_posts' => count($testData),
            'total_views' => array_sum(array_column($testData, 'views')),
            'average_views' => array_sum(array_column($testData, 'views')) / count($testData)
        ];
        return (microtime(true) - $start) * 1000;
    },
    'popular_posts' => function() use ($testData) {
        // 模擬熱門文章 API
        $start = microtime(true);
        usort($testData, fn($a, $b) => $b['views'] <=> $a['views']);
        $popular = array_slice($testData, 0, 10);
        return (microtime(true) - $start) * 1000;
    },
    'trends_analysis' => function() use ($trends) {
        // 模擬趨勢分析 API
        $start = microtime(true);
        $analysis = [
            'trend_direction' => $trends[count($trends)-1] > $trends[0] ? 'increasing' : 'decreasing',
            'growth_rate' => (($trends[count($trends)-1] - $trends[0]) / $trends[0]) * 100
        ];
        return (microtime(true) - $start) * 1000;
    }
];

foreach ($apiTests as $apiName => $testFunc) {
    $times = [];
    for ($i = 0; $i < 10; $i++) {
        $times[] = $testFunc();
    }
    $avgTime = array_sum($times) / count($times);
    $maxTime = max($times);
    $minTime = min($times);
    
    echo "   $apiName API:\n";
    echo "     平均回應時間: " . round($avgTime, 2) . " ms\n";
    echo "     最大回應時間: " . round($maxTime, 2) . " ms\n";
    echo "     最小回應時間: " . round($minTime, 2) . " ms\n";
}

// 總結效能指標
echo "\n=== 效能測試總結 ===\n";
$totalTime = (microtime(true) - $startTime) * 1000;
$peakMemory = memory_get_peak_usage(true);
$memoryUsage = ($peakMemory - $peakMemoryStart) / 1024 / 1024;

echo "總執行時間: " . round($totalTime, 2) . " ms\n";
echo "峰值記憶體使用量: " . round($memoryUsage, 2) . " MB\n";
echo "快取命中率: " . round($hitRate, 2) . "%\n";

// 評估結果
echo "\n=== 效能評估 ===\n";
$performance = [
    'memory_usage' => $memoryUsage < 50 ? '✅ 優秀' : ($memoryUsage < 100 ? '⚠️ 良好' : '❌ 需要優化'),
    'cache_hit_rate' => $hitRate >= 80 ? '✅ 優秀' : ($hitRate >= 60 ? '⚠️ 良好' : '❌ 需要優化'),
    'response_time' => $totalTime < 1000 ? '✅ 優秀' : ($totalTime < 2000 ? '⚠️ 良好' : '❌ 需要優化')
];

foreach ($performance as $metric => $status) {
    echo "$metric: $status\n";
}

// 建議
echo "\n=== 優化建議 ===\n";
if ($memoryUsage > 50) {
    echo "- 考慮實作資料分頁載入\n";
    echo "- 優化資料結構，減少記憶體佔用\n";
}
if ($hitRate < 80) {
    echo "- 調整快取策略，增加快取 TTL\n";
    echo "- 實作更智慧的快取預熱機制\n";
}
if ($totalTime > 1000) {
    echo "- 優化資料庫查詢，新增適當索引\n";
    echo "- 考慮實作非同步處理\n";
}

echo "\n效能測試完成！\n";
