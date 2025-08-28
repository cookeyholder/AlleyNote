<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 建立使用者行為紀錄表
 * 支援完整的使用者行為追蹤與稽核
 */
final class CreateUserActivityLogsTable extends AbstractMigration
{
    public function change(): void
    {
        // 建立使用者活動日誌表
        $table = $this->table('user_activity_logs', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('uuid', 'string', ['limit' => 36, 'null' => false])
              
              // 使用者相關資訊
              ->addColumn('user_id', 'integer', ['null' => true]) // null = 匿名使用者/系統操作
              ->addColumn('session_id', 'string', ['limit' => 128, 'null' => true])
              
              // 行為類型與目標
              ->addColumn('action_type', 'string', ['limit' => 50, 'null' => false])
              ->addColumn('action_category', 'string', ['limit' => 30, 'null' => false]) // auth, post, admin, security 等
              ->addColumn('target_type', 'string', ['limit' => 50, 'null' => true]) // post, user, attachment 等
              ->addColumn('target_id', 'string', ['limit' => 50, 'null' => true]) // 目標資源的 ID
              
              // 行為結果與詳細資訊
              ->addColumn('status', 'string', ['limit' => 20, 'default' => 'success', 'null' => false]) // success, failed, error
              ->addColumn('description', 'text', ['null' => true]) // 人類可讀的描述
              ->addColumn('metadata', 'json', ['null' => true]) // 額外的結構化資料
              
              // 請求相關資訊
              ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('user_agent', 'text', ['null' => true])
              ->addColumn('request_method', 'string', ['limit' => 10, 'null' => true]) // GET, POST, PUT, DELETE
              ->addColumn('request_path', 'string', ['limit' => 500, 'null' => true])
              
              // 時間戳記
              ->addColumn('created_at', 'datetime', ['null' => false])
              ->addColumn('occurred_at', 'datetime', ['null' => false]) // 事件實際發生時間（可能與記錄時間不同）
              
              // 索引設計
              ->addIndex(['uuid'], ['unique' => true])
              ->addIndex(['user_id'])
              ->addIndex(['session_id'])
              ->addIndex(['action_type'])
              ->addIndex(['action_category'])
              ->addIndex(['target_type', 'target_id'])
              ->addIndex(['status'])
              ->addIndex(['ip_address'])
              ->addIndex(['created_at'])
              ->addIndex(['occurred_at'])
              ->addIndex(['action_category', 'action_type']) // 複合索引用於查詢
              ->addIndex(['user_id', 'occurred_at'])         // 複合索引用於使用者行為分析
              
              // 外鍵約束
              ->addForeignKey('user_id', 'users', 'id', [
                  'delete' => 'SET_NULL', // 使用者刪除後保留日誌但設為 NULL
                  'update' => 'CASCADE'
              ])
              ->create();
    }

    public function down(): void
    {
        $this->table('user_activity_logs')->drop()->save();
    }
}