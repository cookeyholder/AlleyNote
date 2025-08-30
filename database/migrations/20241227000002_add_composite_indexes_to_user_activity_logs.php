<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCompositeIndexesToUserActivityLogs extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('user_activity_logs');
        
        // 為常見查詢模式新增複合索引
        $table->addIndex(['user_id', 'action_category'], [
            'name' => 'user_activity_logs_user_id_action_category_index'
        ]);
        
        $table->addIndex(['user_id', 'status'], [
            'name' => 'user_activity_logs_user_id_status_index'
        ]);
        
        $table->addIndex(['action_category', 'occurred_at'], [
            'name' => 'user_activity_logs_action_category_occurred_at_index'
        ]);
        
        $table->save();
    }

    public function down(): void
    {
        $table = $this->table('user_activity_logs');
        
        $table->removeIndex(['user_id', 'action_category']);
        $table->removeIndex(['user_id', 'status']);
        $table->removeIndex(['action_category', 'occurred_at']);
        
        $table->save();
    }
}