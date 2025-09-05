<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateStatisticsSnapshotsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('statistics_snapshots');
        
        // 基本識別欄位
        $table->addColumn('uuid', 'char', [
            'limit' => 36,
            'comment' => '統計快照唯一識別碼 (UUID)',
        ]);
        
        // 週期類型和時間範圍
        $table->addColumn('period_type', 'enum', [
            'values' => ['daily', 'weekly', 'monthly', 'yearly', 'custom'],
            'comment' => '統計週期類型',
        ]);
        
        $table->addColumn('start_date', 'datetime', [
            'comment' => '統計週期開始時間',
        ]);
        
        $table->addColumn('end_date', 'datetime', [
            'comment' => '統計週期結束時間',
        ]);
        
        // 統計資料 (JSON 格式)
        $table->addColumn('snapshot_data', 'json', [
            'comment' => '統計快照資料 (JSON格式)',
        ]);
        
        // 統計指標快速查詢欄位
        $table->addColumn('total_posts', 'integer', [
            'default' => 0,
            'comment' => '總文章數',
        ]);
        
        $table->addColumn('total_views', 'integer', [
            'default' => 0,
            'comment' => '總觀看次數',
        ]);
        
        $table->addColumn('total_users', 'integer', [
            'default' => 0,
            'comment' => '總使用者數',
        ]);
        
        // 來源統計快速查詢欄位
        $table->addColumn('primary_source', 'string', [
            'limit' => 50,
            'null' => true,
            'comment' => '主要來源類型',
        ]);
        
        // 計算相關欄位
        $table->addColumn('calculation_duration', 'integer', [
            'null' => true,
            'comment' => '計算耗時 (毫秒)',
        ]);
        
        $table->addColumn('data_accuracy', 'decimal', [
            'precision' => 5,
            'scale' => 2,
            'null' => true,
            'comment' => '資料準確度 (百分比)',
        ]);
        
        // 時間戳記
        $table->addColumn('created_at', 'datetime', [
            'comment' => '建立時間',
        ]);
        
        $table->addColumn('updated_at', 'datetime', [
            'null' => true,
            'comment' => '更新時間',
        ]);
        
        // 建立索引
        $table->addIndex(['uuid'], ['unique' => true, 'name' => 'uniq_statistics_snapshots_uuid']);
        $table->addIndex(['period_type'], ['name' => 'idx_statistics_snapshots_period_type']);
        $table->addIndex(['start_date', 'end_date'], ['name' => 'idx_statistics_snapshots_date_range']);
        $table->addIndex(['period_type', 'start_date'], ['name' => 'idx_statistics_snapshots_period_start']);
        $table->addIndex(['created_at'], ['name' => 'idx_statistics_snapshots_created']);
        $table->addIndex(['primary_source'], ['name' => 'idx_statistics_snapshots_source']);
        
        // 複合索引提升常用查詢效能
        $table->addIndex(['period_type', 'start_date', 'end_date'], ['name' => 'idx_statistics_snapshots_period_range']);
        $table->addIndex(['period_type', 'primary_source', 'start_date'], ['name' => 'idx_statistics_snapshots_complex']);
        
        $table->create();
        
        // 建立統計檢視表 (可選，提升查詢效能)
        $this->execute("
            CREATE VIEW statistics_summary AS
            SELECT 
                period_type,
                DATE(start_date) as snapshot_date,
                SUM(total_posts) as daily_posts,
                SUM(total_views) as daily_views,
                AVG(total_users) as avg_users,
                COUNT(*) as snapshot_count
            FROM statistics_snapshots 
            WHERE period_type = 'daily'
            GROUP BY period_type, DATE(start_date)
            ORDER BY snapshot_date DESC
        ");
    }
}
