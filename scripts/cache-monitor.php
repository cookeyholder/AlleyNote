<?php

declare(strict_types=1);

/**
 * 快取監控和管理腳本
 *
 * 用於監控快取使用情況、清理過期快取和分析快取效能
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\ContainerFactory;
use App\Infrastructure\Services\CacheService;

echo "📊 快取監控與管理工具\n";
echo "=" . str_repeat("=", 40) . "\n\n";

try {
    // 取得快取服務
    $container = ContainerFactory::create();
    $cache = $container->get(CacheService::class);

    // 解析命令列參數
    $command = $argv[1] ?? 'stats';

    switch ($command) {
        case 'stats':
            showCacheStats($cache);
            break;

        case 'clean':
            cleanExpiredCache($cache);
            break;

        case 'clear':
            clearAllCache($cache);
            break;

        case 'monitor':
            monitorCache($cache);
            break;

        case 'test':
            testCachePerformance($cache);
            break;

        default:
            showHelp();
            break;
    }

} catch (Exception $e) {
    echo "❌ 錯誤: {$e->getMessage()}\n";
    echo "📍 位置: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

/**
 * 顯示快取統計資訊
 */
function showCacheStats(CacheService $cache): void
{
    echo "📈 快取統計資訊\n";
    echo "-" . str_repeat("-", 30) . "\n";

    $stats = $cache->getStats();

    echo sprintf("💾 命中率: %.2f%% (%d 命中 / %d 總請求)\n",
        $stats['hit_rate'],
        $stats['hits'],
        $stats['hits'] + $stats['misses']
    );

    echo sprintf("📁 快取檔案: %d 個\n", $stats['file_count']);
    echo sprintf("💽 總大小: %s\n", formatBytes($stats['total_size']));
    echo sprintf("📝 寫入次數: %d\n", $stats['sets']);
    echo sprintf("🗑️ 刪除次數: %d\n", $stats['deletes']);
    echo sprintf("📂 快取目錄: %s\n", $stats['cache_path']);

    // 顯示快取使用建議
    if ($stats['hit_rate'] < 50) {
        echo "\n⚠️ 快取命中率偏低，建議檢查:\n";
        echo "   • 快取 TTL 設定是否合適\n";
        echo "   • 是否有太多一次性查詢\n";
        echo "   • 考慮增加快取時間\n";
    } elseif ($stats['hit_rate'] > 90) {
        echo "\n✅ 快取效能優異！\n";
    } else {
        echo "\n👍 快取效能良好\n";
    }
}

/**
 * 清理過期快取
 */
function cleanExpiredCache(CacheService $cache): void
{
    echo "🧹 清理過期快取...\n";

    $startTime = microtime(true);
    $cleaned = $cache->cleanExpired();
    $endTime = microtime(true);

    $duration = round(($endTime - $startTime) * 1000, 2);

    echo sprintf("✅ 清理完成: 移除了 %d 個過期快取檔案\n", $cleaned);
    echo sprintf("⏱️ 執行時間: %sms\n", $duration);

    // 顯示清理後的統計
    echo "\n📊 清理後統計:\n";
    showCacheStats($cache);
}

/**
 * 清空所有快取
 */
function clearAllCache(CacheService $cache): void
{
    echo "🗑️ 清空所有快取...\n";

    // 確認操作
    echo "⚠️ 這將刪除所有快取檔案，確定要繼續嗎？ [y/N]: ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    if (trim(strtolower($line)) !== 'y') {
        echo "❌ 操作已取消\n";
        return;
    }

    $startTime = microtime(true);
    $result = $cache->clear();
    $endTime = microtime(true);

    $duration = round(($endTime - $startTime) * 1000, 2);

    if ($result) {
        echo "✅ 所有快取已清空\n";
        echo sprintf("⏱️ 執行時間: %sms\n", $duration);
        $cache->resetStats();
    } else {
        echo "❌ 清空快取失敗\n";
    }
}

/**
 * 監控快取使用情況
 */
function monitorCache(CacheService $cache): void
{
    echo "👀 開始監控快取使用情況 (按 Ctrl+C 停止)\n";
    echo "-" . str_repeat("-", 50) . "\n";

    $previousStats = $cache->getStats();
    $startTime = time();

    while (true) {
        sleep(5); // 每 5 秒檢查一次

        $currentStats = $cache->getStats();
        $currentTime = time();
        $elapsed = $currentTime - $startTime;

        // 計算變化量
        $hitsDiff = $currentStats['hits'] - $previousStats['hits'];
        $missesDiff = $currentStats['misses'] - $previousStats['misses'];
        $setsDiff = $currentStats['sets'] - $previousStats['sets'];
        $deletesDiff = $currentStats['deletes'] - $previousStats['deletes'];

        // 清屏並顯示當前狀態
        system('clear');
        echo "📊 快取即時監控 (執行時間: {$elapsed}s)\n";
        echo "=" . str_repeat("=", 50) . "\n";

        echo sprintf("💾 命中率: %.2f%% | 檔案數: %d | 大小: %s\n",
            $currentStats['hit_rate'],
            $currentStats['file_count'],
            formatBytes($currentStats['total_size'])
        );

        echo sprintf("📈 最近 5 秒變化: +%d 命中, +%d 未命中, +%d 寫入, +%d 刪除\n",
            $hitsDiff, $missesDiff, $setsDiff, $deletesDiff
        );

        $previousStats = $currentStats;
    }
}

/**
 * 測試快取效能
 */
function testCachePerformance(CacheService $cache): void
{
    echo "🚀 快取效能測試\n";
    echo "-" . str_repeat("-", 30) . "\n";

    $iterations = 1000;
    $testData = [
        'small' => str_repeat('a', 100),           // 100 bytes
        'medium' => str_repeat('b', 10000),        // 10KB
        'large' => str_repeat('c', 100000),        // 100KB
    ];

    foreach ($testData as $size => $data) {
        echo "測試 {$size} 資料 (" . formatBytes(strlen($data)) . "):\n";

        // 寫入測試
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $cache->set("test_{$size}_{$i}", $data, 300);
        }
        $writeTime = microtime(true) - $startTime;
        $writeRate = round($iterations / $writeTime, 2);

        // 讀取測試
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $cache->get("test_{$size}_{$i}");
        }
        $readTime = microtime(true) - $startTime;
        $readRate = round($iterations / $readTime, 2);

        echo sprintf("  寫入: %.3fs (%s ops/sec)\n", $writeTime, $writeRate);
        echo sprintf("  讀取: %.3fs (%s ops/sec)\n", $readTime, $readRate);

        // 清理測試資料
        for ($i = 0; $i < $iterations; $i++) {
            $cache->delete("test_{$size}_{$i}");
        }

        echo "\n";
    }

    echo "✅ 效能測試完成\n";
}

/**
 * 格式化位元組大小
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * 顯示幫助資訊
 */
function showHelp(): void
{
    echo "🛠️ 快取管理工具使用說明\n";
    echo "=" . str_repeat("=", 40) . "\n\n";

    echo "用法: php cache-monitor.php [命令]\n\n";

    echo "可用命令:\n";
    echo "  stats   - 顯示快取統計資訊 (預設)\n";
    echo "  clean   - 清理過期的快取檔案\n";
    echo "  clear   - 清空所有快取 (需要確認)\n";
    echo "  monitor - 即時監控快取使用情況\n";
    echo "  test    - 執行快取效能測試\n";
    echo "  help    - 顯示此幫助資訊\n\n";

    echo "範例:\n";
    echo "  php cache-monitor.php stats\n";
    echo "  php cache-monitor.php clean\n";
    echo "  php cache-monitor.php monitor\n\n";
}
