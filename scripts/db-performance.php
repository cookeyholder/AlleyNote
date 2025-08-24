<?php

declare(strict_types=1);

/**
 * 資料庫查詢效能監控工具
 *
 * 用於分析資料庫查詢效能、檢測慢查詢和優化建議
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\DatabaseConnection;

echo "🗄️ 資料庫查詢效能監控工具\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    // 取得資料庫連線
    $db = DatabaseConnection::getInstance();

    // 解析命令列參數
    $command = $argv[1] ?? 'analyze';

    switch ($command) {
        case 'analyze':
            analyzePerformance($db);
            break;

        case 'slow':
            analyzeSlowQueries($db);
            break;

        case 'indexes':
            analyzeIndexes($db);
            break;

        case 'test':
            runPerformanceTests($db);
            break;

        case 'optimize':
            suggestOptimizations($db);
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
 * 分析總體效能
 */
function analyzePerformance(\PDO $db): void
{
    echo "📊 資料庫效能分析\n";
    echo "-" . str_repeat("-", 30) . "\n";

    // 檢查資料表大小
    echo "📋 資料表大小分析:\n";

    $tables = ['posts', 'attachments', 'ip_lists', 'post_tags'];
    $totalRows = 0;

    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
            $count = $stmt->fetchColumn();
            $totalRows += $count;

            echo sprintf("  • %s: %s 筆記錄\n", $table, number_format($count));
        } catch (Exception $e) {
            echo sprintf("  • %s: 無法查詢 (%s)\n", $table, $e->getMessage());
        }
    }

    echo sprintf("\n📈 總記錄數: %s\n", number_format($totalRows));

    // 檢查 SQLite 狀態（如果是 SQLite）
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        analyzeSQLitePerformance($db);
    }
}

/**
 * 分析 SQLite 效能
 */
function analyzeSQLitePerformance(\PDO $db): void
{
    echo "\n🔍 SQLite 效能檢查:\n";

    // 檢查 PRAGMA 設定
    $pragmas = [
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'foreign_keys' => 'ON',
        'cache_size' => '-64000', // 64MB
        'temp_store' => 'MEMORY'
    ];

    foreach ($pragmas as $pragma => $recommended) {
        try {
            $stmt = $db->query("PRAGMA {$pragma}");
            $current = $stmt->fetchColumn();
            $status = strtoupper((string)$current) === strtoupper($recommended) ? "✅" : "⚠️";

            echo sprintf("  %s %s: %s (建議: %s)\n",
                $status, $pragma, $current, $recommended);
        } catch (Exception $e) {
            echo sprintf("  ❌ %s: 查詢失敗\n", $pragma);
        }
    }
}

/**
 * 分析慢查詢
 */
function analyzeSlowQueries(\PDO $db): void
{
    echo "🐌 慢查詢分析\n";
    echo "-" . str_repeat("-", 30) . "\n";

    // 測試常見查詢的執行時間
    $queries = [
        'SELECT 文章列表' => 'SELECT id, title, created_at FROM posts ORDER BY created_at DESC LIMIT 20',
        'JOIN 查詢附件' => 'SELECT p.title, COUNT(a.id) as attachment_count FROM posts p LEFT JOIN attachments a ON p.id = a.post_id GROUP BY p.id LIMIT 10',
        '條件查詢' => 'SELECT * FROM posts WHERE status = 1 AND is_pinned = 0 ORDER BY publish_date DESC LIMIT 10',
        '文字搜尋' => 'SELECT * FROM posts WHERE title LIKE "%test%" OR content LIKE "%test%" LIMIT 10'
    ];

    foreach ($queries as $name => $sql) {
        $startTime = microtime(true);

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            $count = count($results);

            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);

            $status = $duration > 100 ? "🐌" : ($duration > 50 ? "⚠️" : "✅");
            echo sprintf("  %s %s: %.2fms (%d 筆)\n", $status, $name, $duration, $count);

        } catch (Exception $e) {
            echo sprintf("  ❌ %s: 查詢失敗 - %s\n", $name, $e->getMessage());
        }
    }
}

/**
 * 分析索引使用情況
 */
function analyzeIndexes(\PDO $db): void
{
    echo "🔍 索引分析\n";
    echo "-" . str_repeat("-", 30) . "\n";

    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        // SQLite 索引檢查
        $stmt = $db->query("SELECT name, tbl_name FROM sqlite_master WHERE type = 'index' AND name NOT LIKE 'sqlite_%'");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "📋 現有索引:\n";
        foreach ($indexes as $index) {
            echo sprintf("  • %s (表: %s)\n", $index['name'], $index['tbl_name']);
        }

        // 建議缺失的索引
        echo "\n💡 建議新增的索引:\n";
        $suggestions = [
            'posts' => ['status', 'is_pinned', 'publish_date', 'user_id'],
            'attachments' => ['post_id'],
            'ip_lists' => ['ip_address', 'type']
        ];

        foreach ($suggestions as $table => $columns) {
            foreach ($columns as $column) {
                $indexName = "idx_{$table}_{$column}";
                echo sprintf("  • CREATE INDEX %s ON %s(%s);\n", $indexName, $table, $column);
            }
        }
    }
}

/**
 * 執行效能測試
 */
function runPerformanceTests(\PDO $db): void
{
    echo "🚀 效能測試\n";
    echo "-" . str_repeat("-", 30) . "\n";

    // 測試批量插入效能
    echo "📝 批量插入測試:\n";
    testBatchInsert($db);

    // 測試查詢效能
    echo "\n🔍 查詢效能測試:\n";
    testQueryPerformance($db);

    // 測試更新效能
    echo "\n✏️ 更新效能測試:\n";
    testUpdatePerformance($db);
}

/**
 * 測試批量插入效能
 */
function testBatchInsert(\PDO $db): void
{
    $testCount = 1000;

    try {
        $db->beginTransaction();

        $startTime = microtime(true);
        $stmt = $db->prepare("INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, status, publish_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        for ($i = 1; $i <= $testCount; $i++) {
            $stmt->execute([
                'test-' . uniqid(),
                1000000 + $i,
                "測試標題 {$i}",
                "測試內容 {$i}",
                1,
                '127.0.0.1',
                1,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s')
            ]);
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        $rate = round($testCount / ($duration / 1000), 2);

        echo sprintf("  ✅ 插入 %d 筆記錄: %.2fms (%.2f ops/sec)\n", $testCount, $duration, $rate);

        // 清理測試資料
        $db->exec("DELETE FROM posts WHERE uuid LIKE 'test-%'");
        $db->commit();

    } catch (Exception $e) {
        $db->rollBack();
        echo sprintf("  ❌ 批量插入測試失敗: %s\n", $e->getMessage());
    }
}

/**
 * 測試查詢效能
 */
function testQueryPerformance(\PDO $db): void
{
    $queries = [
        '簡單查詢' => 'SELECT COUNT(*) FROM posts',
        '排序查詢' => 'SELECT * FROM posts ORDER BY created_at DESC LIMIT 10',
        '條件查詢' => 'SELECT * FROM posts WHERE status = 1 LIMIT 10'
    ];

    foreach ($queries as $name => $sql) {
        $iterations = 100;
        $totalTime = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $db->query($sql)->fetchAll();
            $endTime = microtime(true);
            $totalTime += ($endTime - $startTime);
        }

        $avgTime = round(($totalTime / $iterations) * 1000, 2);
        $rate = round($iterations / $totalTime, 2);

        echo sprintf("  • %s: %.2fms 平均 (%.2f ops/sec)\n", $name, $avgTime, $rate);
    }
}

/**
 * 測試更新效能
 */
function testUpdatePerformance(\PDO $db): void
{
    try {
        // 準備測試資料
        $db->exec("INSERT OR IGNORE INTO posts (uuid, seq_number, title, content, user_id, status, publish_date, created_at, updated_at) VALUES ('perf-test', 999999, 'Performance Test', 'Content', 1, 1, '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s') . "')");

        $iterations = 100;
        $startTime = microtime(true);

        $stmt = $db->prepare("UPDATE posts SET title = ? WHERE uuid = 'perf-test'");
        for ($i = 0; $i < $iterations; $i++) {
            $stmt->execute(["Updated Title {$i}"]);
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        $rate = round($iterations / ($duration / 1000), 2);

        echo sprintf("  ✅ %d 次更新: %.2fms (%.2f ops/sec)\n", $iterations, $duration, $rate);

        // 清理測試資料
        $db->exec("DELETE FROM posts WHERE uuid = 'perf-test'");

    } catch (Exception $e) {
        echo sprintf("  ❌ 更新效能測試失敗: %s\n", $e->getMessage());
    }
}

/**
 * 建議優化措施
 */
function suggestOptimizations(\PDO $db): void
{
    echo "💡 優化建議\n";
    echo "-" . str_repeat("-", 30) . "\n";

    echo "🔧 **資料庫配置優化**:\n";
    echo "  • 啟用 WAL 模式以提升並行效能\n";
    echo "  • 增加快取大小至 64MB 以上\n";
    echo "  • 啟用外鍵約束確保資料完整性\n\n";

    echo "📊 **索引優化**:\n";
    echo "  • 為常用查詢欄位建立索引\n";
    echo "  • 避免過多索引影響寫入效能\n";
    echo "  • 定期分析索引使用情況\n\n";

    echo "🔍 **查詢優化**:\n";
    echo "  • 使用 LIMIT 限制結果集大小\n";
    echo "  • 避免 SELECT * 查詢所有欄位\n";
    echo "  • 合理使用 JOIN 和子查詢\n\n";

    echo "💾 **快取策略**:\n";
    echo "  • 快取頻繁查詢的結果\n";
    echo "  • 實作查詢結果分頁\n";
    echo "  • 使用適當的快取 TTL\n\n";

    echo "⚡ **應用層優化**:\n";
    echo "  • 使用預處理語句防止 SQL 注入\n";
    echo "  • 批量操作減少資料庫連線\n";
    echo "  • 定期清理過期資料\n";
}

/**
 * 顯示幫助資訊
 */
function showHelp(): void
{
    echo "🛠️ 資料庫效能監控工具使用說明\n";
    echo "=" . str_repeat("=", 50) . "\n\n";

    echo "用法: php db-performance.php [命令]\n\n";

    echo "可用命令:\n";
    echo "  analyze  - 分析總體資料庫效能 (預設)\n";
    echo "  slow     - 分析慢查詢\n";
    echo "  indexes  - 分析索引使用情況\n";
    echo "  test     - 執行效能測試\n";
    echo "  optimize - 顯示優化建議\n";
    echo "  help     - 顯示此幫助資訊\n\n";

    echo "範例:\n";
    echo "  php db-performance.php analyze\n";
    echo "  php db-performance.php slow\n";
    echo "  php db-performance.php test\n\n";
}
