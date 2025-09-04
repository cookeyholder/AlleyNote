<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 為 posts 表新增來源追蹤欄位
 * 
 * 這個 migration 為 posts 表新增來源追蹤功能，
 * 包含來源類型和來源詳細資訊，以支援統計功能。
 * 
 * @author GitHub Copilot
 * @version 1.0.0
 * @since 2025-09-04
 */
final class AddSourceTrackingToPosts extends AbstractMigration
{
    /**
     * 向上遷移：新增來源追蹤欄位
     */
    public function up(): void
    {
        $table = $this->table('posts');
        
        // 檢查 posts 表是否存在
        if (!$table->exists()) {
            $this->output->writeln('<error>Posts table does not exist. Please run initial schema migration first.</error>');
            return;
        }
        
        // 新增來源追蹤欄位
        $table->addColumn('source_type', 'string', [
                'limit' => 20,
                'default' => 'direct',
                'null' => false,
                'comment' => '文章來源類型：direct, search_engine, social_media, referral, email, advertisement, other'
            ])
            ->addColumn('source_detail', 'text', [
                'null' => true,
                'comment' => '來源詳細資訊（JSON格式），例如：搜尋關鍵字、社群平台名稱、參考網址等'
            ])
            ->save();
            
        // 新增索引
        $table->addIndex(['source_type'], [
                'name' => 'idx_posts_source_type'
            ])
            ->addIndex(['source_type', 'created_at'], [
                'name' => 'idx_posts_source_created'
            ])
            ->save();
            
        $this->output->writeln('<info>Successfully added source tracking columns and indexes to posts table.</info>');
    }

    /**
     * 向下遷移：移除來源追蹤欄位
     */
    public function down(): void
    {
        $table = $this->table('posts');
        
        if (!$table->exists()) {
            $this->output->writeln('<error>Posts table does not exist.</error>');
            return;
        }
        
        // 檢查並移除索引
        if ($table->hasIndex(['source_type', 'created_at'])) {
            $table->removeIndex(['source_type', 'created_at']);
        }
        
        if ($table->hasIndex(['source_type'])) {
            $table->removeIndex(['source_type']);
        }
        
        // 檢查並移除欄位
        if ($table->hasColumn('source_detail')) {
            $table->removeColumn('source_detail');
        }
        
        if ($table->hasColumn('source_type')) {
            $table->removeColumn('source_type');
        }
        
        $table->save();
        
        $this->output->writeln('<info>Successfully removed source tracking columns and indexes from posts table.</info>');
    }
}
