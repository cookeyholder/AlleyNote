<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateStatisticsSnapshotsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('statistics_snapshots');
        $table
            ->addColumn('uuid', 'string', ['limit' => 36, 'null' => false, 'comment' => '統計快照唯一識別碼'])
            ->addColumn('snapshot_type', 'string', ['limit' => 20, 'null' => false, 'comment' => '快照類型'])
            ->addColumn('period_type', 'string', ['limit' => 10, 'null' => false, 'comment' => '統計週期類型'])
            ->addColumn('period_start', 'datetime', ['null' => false, 'comment' => '統計週期開始時間'])
            ->addColumn('period_end', 'datetime', ['null' => false, 'comment' => '統計週期結束時間'])
            ->addColumn('statistics_data', 'text', ['null' => false, 'comment' => '統計資料（JSON格式）'])
            ->addColumn('total_views', 'integer', ['default' => 0, 'null' => false, 'comment' => '總瀏覽次數'])
            ->addColumn('total_unique_viewers', 'integer', ['default' => 0, 'null' => false, 'comment' => '總獨特瀏覽者數'])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['uuid'], ['unique' => true])
            ->addIndex(['snapshot_type'])
            ->addIndex(['period_type'])
            ->addIndex(['period_start', 'period_end'])
            ->create();
    }
}
