<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSourceTrackingToPosts extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up(): void
    {
        $table = $this->table('posts');

        // 新增文章建立來源欄位 (技術來源，區別於流量來源 source_type)
        $table->addColumn('creation_source', 'string', [
            'limit' => 20,
            'default' => 'web',
            'null' => false,
            'comment' => '文章建立來源: web, api, mobile, admin, import, migration',
            'after' => 'source_detail'
        ]);

        // 新增建立來源的詳細資訊欄位
        $table->addColumn('creation_source_detail', 'text', [
            'null' => true,
            'comment' => '建立來源詳細資訊 (JSON格式): API版本、用戶端資訊、匯入批次等',
            'after' => 'creation_source'
        ]);

        // 為提升統計查詢效能，建立複合索引
        $table->addIndex(['creation_source'], ['name' => 'idx_posts_creation_source']);
        $table->addIndex(['creation_source', 'created_at'], ['name' => 'idx_posts_creation_source_created']);
        $table->addIndex(['creation_source', 'status'], ['name' => 'idx_posts_creation_source_status']);

        $table->update();

        // 添加 CHECK 約束來模擬 enum 行為 (SQLite 支援)
        $this->execute("
            CREATE TRIGGER posts_creation_source_check_insert
            BEFORE INSERT ON posts
            WHEN NEW.creation_source NOT IN ('web', 'api', 'mobile', 'admin', 'import', 'migration')
            BEGIN
                SELECT RAISE(ABORT, 'Invalid creation_source. Must be one of: web, api, mobile, admin, import, migration');
            END;
        ");

        $this->execute("
            CREATE TRIGGER posts_creation_source_check_update
            BEFORE UPDATE ON posts
            WHEN NEW.creation_source NOT IN ('web', 'api', 'mobile', 'admin', 'import', 'migration')
            BEGIN
                SELECT RAISE(ABORT, 'Invalid creation_source. Must be one of: web, api, mobile, admin, import, migration');
            END;
        ");
    }

    /**
     * Migrate Down.
     */
    public function down(): void
    {
        // 刪除 triggers
        $this->execute("DROP TRIGGER IF EXISTS posts_creation_source_check_insert;");
        $this->execute("DROP TRIGGER IF EXISTS posts_creation_source_check_update;");

        $table = $this->table('posts');

        // 刪除索引
        $table->removeIndex(['creation_source']);
        $table->removeIndex(['creation_source', 'created_at']);
        $table->removeIndex(['creation_source', 'status']);

        // 刪除欄位
        $table->removeColumn('creation_source_detail');
        $table->removeColumn('creation_source');

        $table->update();
    }
}
