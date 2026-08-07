<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 建立站內通知資料表.
 */
final class CreateNotificationsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('notifications', ['id' => false, 'primary_key' => 'id']);
        $table
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('uuid', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('user_id', 'integer', ['null' => true, 'comment' => '接收通知的使用者 ID (null 表示全體廣播)'])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false, 'comment' => '通知標題'])
            ->addColumn('message', 'text', ['null' => false, 'comment' => '通知內容'])
            ->addColumn('type', 'string', ['limit' => 50, 'default' => 'system', 'comment' => '通知類型 (system, post, security)'])
            ->addColumn('is_read', 'boolean', ['default' => false, 'null' => false, 'comment' => '是否已讀'])
            ->addColumn('read_at', 'timestamp', ['null' => true, 'comment' => '已讀時間'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'comment' => '建立時間'])
            ->addIndex('uuid', ['unique' => true, 'name' => 'idx_notifications_uuid'])
            ->addIndex('user_id', ['name' => 'idx_notifications_user_id'])
            ->addIndex('is_read', ['name' => 'idx_notifications_is_read'])
            ->create();

        // 插入預設通知範例資料
        $this->table('notifications')->insert([
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440001',
                'user_id' => null,
                'title' => '歡迎使用 AlleyNote 系統',
                'message' => 'AlleyNote 公告系統已完成建置與功能擴充，歡迎體驗使用！',
                'type' => 'system',
                'is_read' => false,
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440002',
                'user_id' => 1,
                'title' => '系統安全性通知',
                'message' => '您的管理員帳號權限已升級並生效。',
                'type' => 'security',
                'is_read' => false,
            ],
        ])->saveData();
    }
}
