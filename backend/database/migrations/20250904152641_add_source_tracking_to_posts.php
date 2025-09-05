<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSourceTrackingToPosts extends AbstractMigration
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
        $table = $this->table('posts');

        // 新增來源類型欄位
        $table->addColumn('source_type', 'enum', [
            'values' => ['web', 'mobile_app', 'api', 'rss_feed', 'email', 'social_media', 'search', 'direct', 'referral', 'unknown'],
            'default' => 'web',
            'comment' => '文章來源類型',
            'after' => 'content'
        ]);

        // 新增來源詳細資訊欄位
        $table->addColumn('source_detail', 'text', [
            'null' => true,
            'comment' => '來源詳細資訊 (JSON格式)',
            'after' => 'source_type'
        ]);

        // 新增發布時間追蹤欄位（用於統計分析）
        $table->addColumn('published_at', 'datetime', [
            'null' => true,
            'comment' => '文章發布時間',
            'after' => 'source_detail'
        ]);

        // 建立索引提升查詢效能
        $table->addIndex(['source_type'], ['name' => 'idx_posts_source_type']);
        $table->addIndex(['source_type', 'created_at'], ['name' => 'idx_posts_source_created']);
        $table->addIndex(['published_at'], ['name' => 'idx_posts_published_at']);
        $table->addIndex(['source_type', 'published_at'], ['name' => 'idx_posts_source_published']);

        $table->update();
    }
}
