<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 建立統計快照表
 *
 * 這個 migration 建立 statistics_snapshots 表來儲存統計資料快照，
 * 支援統計功能的核心資料結構。
 *
 * 表結構設計：
 * - id: 主鍵
 * - uuid: 唯一識別符
 * - snapshot_type: 快照類型 (posts, users, system 等)
 * - period_type: 週期類型 (daily, weekly, monthly, yearly)
 * - period_start: 週期開始時間
 * - period_end: 週期結束時間
 * - statistics_data: JSON 格式的統計資料
 * - total_views: 總觀看次數 (冗餘欄位，提升查詢效能)
 * - total_unique_viewers: 總不重複觀看者數 (冗餘欄位)
 * - created_at: 建立時間
 * - updated_at: 更新時間
 *
 * @author GitHub Copilot
 * @version 1.0.0
 * @since 2025-09-04
 */
final class CreateStatisticsSnapshotsTable extends AbstractMigration
{
    /**
     * 向上遷移：建立統計快照表
     */
    public function up(): void
    {
        // 建立統計快照表
        $table = $this->table('statistics_snapshots', [
            'id' => false,
            'primary_key' => 'id'
        ]);

        $table->addColumn('id', 'integer', [
                'identity' => true,
                'comment' => '主鍵 ID'
            ])
            ->addColumn('uuid', 'string', [
                'limit' => 36,
                'null' => false,
                'comment' => '快照唯一識別符 (UUID)'
            ])
            ->addColumn('snapshot_type', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => '快照類型：posts, users, system, custom'
            ])
            ->addColumn('period_type', 'string', [
                'limit' => 10,
                'null' => false,
                'comment' => '週期類型：daily, weekly, monthly, yearly'
            ])
            ->addColumn('period_start', 'datetime', [
                'null' => false,
                'comment' => '統計週期開始時間'
            ])
            ->addColumn('period_end', 'datetime', [
                'null' => false,
                'comment' => '統計週期結束時間'
            ])
            ->addColumn('statistics_data', 'text', [
                'null' => false,
                'comment' => '統計資料 JSON 格式，包含詳細的統計指標'
            ])
            ->addColumn('total_views', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => '總觀看次數 (冗餘欄位，提升查詢效能)'
            ])
            ->addColumn('total_unique_viewers', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => '總不重複觀看者數 (冗餘欄位，提升查詢效能)'
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'comment' => '快照建立時間'
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => true,
                'comment' => '快照更新時間'
            ])
            ->create();

        // 新增唯一索引
        $table->addIndex(['uuid'], [
                'unique' => true,
                'name' => 'uk_statistics_snapshots_uuid'
            ])
            // 新增複合唯一索引 - 防止同一週期重複快照
            ->addIndex(['snapshot_type', 'period_type', 'period_start', 'period_end'], [
                'unique' => true,
                'name' => 'uk_statistics_snapshots_period'
            ])
            // 新增查詢效能索引
            ->addIndex(['snapshot_type'], [
                'name' => 'idx_statistics_snapshots_type'
            ])
            ->addIndex(['period_type'], [
                'name' => 'idx_statistics_snapshots_period_type'
            ])
            ->addIndex(['period_start'], [
                'name' => 'idx_statistics_snapshots_period_start'
            ])
            ->addIndex(['period_end'], [
                'name' => 'idx_statistics_snapshots_period_end'
            ])
            ->addIndex(['created_at'], [
                'name' => 'idx_statistics_snapshots_created_at'
            ])
            // 新增複合查詢索引
            ->addIndex(['snapshot_type', 'period_type'], [
                'name' => 'idx_statistics_snapshots_type_period'
            ])
            ->addIndex(['snapshot_type', 'created_at'], [
                'name' => 'idx_statistics_snapshots_type_created'
            ])
            ->addIndex(['total_views'], [
                'name' => 'idx_statistics_snapshots_total_views'
            ])
            ->save();

        $this->output->writeln('<info>Successfully created statistics_snapshots table with indexes.</info>');
    }

    /**
     * 向下遷移：刪除統計快照表
     */
    public function down(): void
    {
        // 檢查表是否存在
        if ($this->hasTable('statistics_snapshots')) {
            $this->table('statistics_snapshots')->drop()->save();
            $this->output->writeln('<info>Successfully dropped statistics_snapshots table.</info>');
        } else {
            $this->output->writeln('<comment>Table statistics_snapshots does not exist, nothing to drop.</comment>');
        }
    }
}
