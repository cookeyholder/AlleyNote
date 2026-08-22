<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 建立文章列表與前台 Feed 複合索引.
 */
final class AddFeedCompositeIndexesToPosts extends AbstractMigration
{
    /**
     * 建立複合索引.
     */
    public function up(): void
    {
        $table = $this->table('posts');

        // 1. 前台文章列表複合索引 (狀態 + 軟刪除 + 置頂 + 發布時間)
        $table->addIndex(['status', 'deleted_at', 'is_pinned', 'published_at'], [
            'name' => 'idx_posts_feed'
        ]);

        // 2. 後台文章列表複合索引 (軟刪除 + 狀態 + 建立時間)
        $table->addIndex(['deleted_at', 'status', 'created_at'], [
            'name' => 'idx_posts_admin_list'
        ]);

        $table->update();
    }

    /**
     * 移除複合索引.
     */
    public function down(): void
    {
        $table = $this->table('posts');

        $indexes = ['idx_posts_feed', 'idx_posts_admin_list'];
        foreach ($indexes as $indexName) {
            try {
                $table->removeIndexByName($indexName);
            } catch (Exception $e) {
                // 忽略不存在的索引錯誤
            }
        }

        $table->update();
    }
}
