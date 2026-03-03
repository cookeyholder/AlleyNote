<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 更新標籤表結構，添加更多欄位
 */
final class UpdateTagsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('tags');
        
        // 添加 slug 欄位
        if (!$table->hasColumn('slug')) {
            $table->addColumn('slug', 'string', [
                'limit' => 255,
                'null' => true,
                'after' => 'name'
            ]);
        }
        
        // 添加 description 欄位
        if (!$table->hasColumn('description')) {
            $table->addColumn('description', 'text', [
                'null' => true,
                'after' => 'slug'
            ]);
        }
        
        // 添加 color 欄位
        if (!$table->hasColumn('color')) {
            $table->addColumn('color', 'string', [
                'limit' => 7,
                'null' => true,
                'after' => 'description'
            ]);
        }
        
        // 添加 usage_count 欄位
        if (!$table->hasColumn('usage_count')) {
            $table->addColumn('usage_count', 'integer', [
                'default' => 0,
                'null' => false,
                'after' => 'color'
            ]);
        }
        
        // 添加 slug 的索引
        if (!$table->hasIndex('slug')) {
            $table->addIndex(['slug'], ['unique' => true, 'name' => 'idx_tags_slug']);
        }
        
        $table->update();
    }
}
