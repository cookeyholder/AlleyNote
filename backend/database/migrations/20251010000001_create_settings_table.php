<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 建立系統設定資料表.
 */
final class CreateSettingsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('settings', ['id' => false, 'primary_key' => 'id']);
        $table
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('key', 'string', ['limit' => 100, 'comment' => '設定鍵'])
            ->addColumn('value', 'text', ['null' => true, 'comment' => '設定值'])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => 'string', 'comment' => '資料類型（string, integer, boolean, json, array）'])
            ->addColumn('description', 'string', ['limit' => 255, 'null' => true, 'comment' => '設定描述'])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'comment' => '建立時間'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'comment' => '更新時間'])
            ->addIndex('key', ['unique' => true, 'name' => 'idx_settings_key'])
            ->create();

        // 插入預設設定
        $this->table('settings')->insert([
            [
                'key' => 'site_name',
                'value' => 'AlleyNote',
                'type' => 'string',
                'description' => '網站名稱',
            ],
            [
                'key' => 'site_description',
                'value' => 'AlleyNote 公布欄系統',
                'type' => 'string',
                'description' => '網站描述',
            ],
            [
                'key' => 'posts_per_page',
                'value' => '20',
                'type' => 'integer',
                'description' => '每頁文章數量',
            ],
            [
                'key' => 'enable_registration',
                'value' => '1',
                'type' => 'boolean',
                'description' => '允許使用者註冊',
            ],
            [
                'key' => 'enable_comments',
                'value' => '1',
                'type' => 'boolean',
                'description' => '允許留言',
            ],
            [
                'key' => 'max_upload_size',
                'value' => '10485760',
                'type' => 'integer',
                'description' => '最大上傳檔案大小（位元組）',
            ],
            [
                'key' => 'allowed_file_types',
                'value' => '["jpg","jpeg","png","gif","pdf","doc","docx"]',
                'type' => 'json',
                'description' => '允許的檔案類型',
            ],
        ])->saveData();
    }
}
