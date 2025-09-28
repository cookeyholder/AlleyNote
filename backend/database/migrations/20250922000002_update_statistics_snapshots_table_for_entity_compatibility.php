<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateStatisticsSnapshotsTableForEntityCompatibility extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('statistics_snapshots');

        // 添加 metadata 欄位
        $table->addColumn('metadata', 'text', [
            'after' => 'statistics_data',
            'null' => false,
            'default' => '{}',
            'comment' => '快照元資料（JSON格式）'
        ]);

        // 添加 expires_at 欄位
        $table->addColumn('expires_at', 'datetime', [
            'after' => 'metadata',
            'null' => true,
            'comment' => '快照過期時間'
        ]);

        $table->update();
    }
}
