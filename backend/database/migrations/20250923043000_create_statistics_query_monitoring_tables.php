<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 建立統計查詢效能監控表.
 *
 * 建立用於監控慢查詢和查詢效能的資料表。
 */
final class CreateStatisticsQueryMonitoringTables extends AbstractMigration
{
    /**
     * 建立監控表.
     */
    public function up(): void
    {
        // 1. 查詢效能記錄表
        $queryPerformance = $this->table('statistics_query_performance', ['id' => false, 'primary_key' => 'id']);
        $queryPerformance
            ->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('query_hash', 'string', ['limit' => 32, 'null' => false, 'comment' => '查詢雜湊值'])
            ->addColumn('query_type', 'string', ['limit' => 50, 'null' => false, 'comment' => '查詢類型'])
            ->addColumn('execution_time', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => false, 'comment' => '執行時間(秒)'])
            ->addColumn('result_count', 'integer', ['default' => 0, 'null' => false, 'comment' => '結果數量'])
            ->addColumn('created_at', 'datetime', ['null' => false, 'comment' => '記錄時間'])
            ->addIndex(['query_hash'])
            ->addIndex(['query_type'])
            ->addIndex(['execution_time'])
            ->addIndex(['created_at'])
            ->addIndex(['query_type', 'created_at'])
            ->create();

        // 2. 慢查詢記錄表
        $slowQueries = $this->table('statistics_slow_queries', ['id' => false, 'primary_key' => 'id']);
        $slowQueries
            ->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('query_hash', 'string', ['limit' => 32, 'null' => false, 'comment' => '查詢雜湊值'])
            ->addColumn('query_type', 'string', ['limit' => 50, 'null' => false, 'comment' => '查詢類型'])
            ->addColumn('query_sql', 'text', ['null' => false, 'comment' => 'SQL查詢語句'])
            ->addColumn('execution_time', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => false, 'comment' => '執行時間(秒)'])
            ->addColumn('query_params', 'text', ['null' => true, 'comment' => '查詢參數(JSON)'])
            ->addColumn('created_at', 'datetime', ['null' => false, 'comment' => '記錄時間'])
            ->addIndex(['query_hash'])
            ->addIndex(['query_type'])
            ->addIndex(['execution_time'])
            ->addIndex(['created_at'])
            ->addIndex(['execution_time', 'created_at'])
            ->create();

        // 3. 失敗查詢記錄表
        $failedQueries = $this->table('statistics_failed_queries', ['id' => false, 'primary_key' => 'id']);
        $failedQueries
            ->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('query_hash', 'string', ['limit' => 32, 'null' => false, 'comment' => '查詢雜湊值'])
            ->addColumn('query_type', 'string', ['limit' => 50, 'null' => false, 'comment' => '查詢類型'])
            ->addColumn('query_sql', 'text', ['null' => false, 'comment' => 'SQL查詢語句'])
            ->addColumn('execution_time', 'decimal', ['precision' => 8, 'scale' => 4, 'null' => false, 'comment' => '執行時間(秒)'])
            ->addColumn('error_message', 'text', ['null' => false, 'comment' => '錯誤訊息'])
            ->addColumn('created_at', 'datetime', ['null' => false, 'comment' => '記錄時間'])
            ->addIndex(['query_hash'])
            ->addIndex(['query_type'])
            ->addIndex(['created_at'])
            ->addIndex(['query_type', 'created_at'])
            ->create();

        echo "已建立統計查詢效能監控表\n";
        $this->displayMonitoringInfo();
    }

    /**
     * 移除監控表.
     */
    public function down(): void
    {
        $this->table('statistics_failed_queries')->drop()->save();
        $this->table('statistics_slow_queries')->drop()->save();
        $this->table('statistics_query_performance')->drop()->save();

        echo "已移除統計查詢效能監控表\n";
    }

    /**
     * 顯示監控資訊.
     */
    private function displayMonitoringInfo(): void
    {
        echo "\n=== 統計查詢效能監控說明 ===\n";

        echo "\n監控表說明:\n";
        echo "• statistics_query_performance: 記錄所有查詢的效能指標\n";
        echo "• statistics_slow_queries: 記錄慢查詢詳細資訊 (>1秒)\n";
        echo "• statistics_failed_queries: 記錄失敗查詢和錯誤資訊\n";

        echo "\n監控功能:\n";
        echo "• 查詢執行時間追蹤\n";
        echo "• 慢查詢自動識別和記錄\n";
        echo "• 查詢效能趨勢分析\n";
        echo "• 效能問題預警\n";
        echo "• 查詢最佳化建議\n";

        echo "\n使用方式:\n";
        echo "• 透過 SlowQueryMonitoringService 執行監控查詢\n";
        echo "• 定期檢查慢查詢統計報告\n";
        echo "• 根據效能趨勢調整索引策略\n";
        echo "• 監控記錄會自動清理 (保留30天)\n";

        echo "\n維護建議:\n";
        echo "• 定期檢視慢查詢報告\n";
        echo "• 監控索引使用效率\n";
        echo "• 根據查詢模式調整最佳化策略\n";
        echo "• 設定慢查詢警告閾值\n";
    }
}
