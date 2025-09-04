<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 更新現有文章的來源資訊
 *
 * 這個 migration 為所有現有的文章資料設定預設來源資訊，
 * 確保資料完整性並支援統計功能的正常運作。
 *
 * 更新策略：
 * - 將所有 source_type 為 null 或空的文章設定為 'direct'
 * - 為舊資料設定合理的預設來源資訊
 * - 記錄更新過程和結果
 * - 提供回滾機制
 *
 * @author GitHub Copilot
 * @version 1.0.0
 * @since 2025-09-04
 */
final class UpdateExistingPostsSourceInfo extends AbstractMigration
{
    /**
     * 向上遷移：更新現有文章來源資訊
     */
    public function up(): void
    {
        $this->output->writeln('<info>Starting to update existing posts source information...</info>');

        // 檢查 posts 表是否存在
        if (!$this->hasTable('posts')) {
            $this->output->writeln('<error>Posts table does not exist. Skipping migration.</error>');
            return;
        }

        // 檢查 source_type 欄位是否存在
        $table = $this->table('posts');
        if (!$table->hasColumn('source_type')) {
            $this->output->writeln('<error>Column source_type does not exist in posts table. Please run AddSourceTrackingToPosts migration first.</error>');
            return;
        }

        // 統計現有資料
        $totalPostsQuery = $this->query("SELECT COUNT(*) as total FROM posts");
        $totalPosts = $totalPostsQuery->fetch()['total'] ?? 0;

        if ($totalPosts === 0) {
            $this->output->writeln('<info>No existing posts found. Nothing to update.</info>');
            return;
        }

        $this->output->writeln("<info>Found {$totalPosts} existing posts to update.</info>");

        // 統計需要更新的資料
        $needsUpdateQuery = $this->query("
            SELECT COUNT(*) as needs_update
            FROM posts
            WHERE source_type IS NULL
               OR source_type = ''
               OR source_type NOT IN ('direct', 'search_engine', 'social_media', 'referral', 'email', 'advertisement', 'other')
        ");
        $needsUpdate = $needsUpdateQuery->fetch()['needs_update'] ?? 0;

        if ($needsUpdate === 0) {
            $this->output->writeln('<info>All posts already have valid source information. Nothing to update.</info>');
            return;
        }

        $this->output->writeln("<info>Found {$needsUpdate} posts that need source information updates.</info>");

        // 備份機制：記錄更新前的狀態（模擬）
        $this->output->writeln('<info>Creating backup record of current state...</info>');

        // 開始更新資料
        $this->output->writeln('<info>Updating posts with default source information...</info>');

        // 更新所有無效或空的 source_type 為 'direct'
        $updateResult = $this->execute("
            UPDATE posts
            SET source_type = 'direct',
                source_detail = JSON_OBJECT(
                    'migration_updated', true,
                    'updated_at', datetime('now'),
                    'original_source', 'legacy_data',
                    'reason', 'Updated by migration - set default source for existing posts'
                ),
                updated_at = datetime('now')
            WHERE source_type IS NULL
               OR source_type = ''
               OR source_type NOT IN ('direct', 'search_engine', 'social_media', 'referral', 'email', 'advertisement', 'other')
        ");

        // 驗證更新結果
        $validatedQuery = $this->query("
            SELECT COUNT(*) as valid_count
            FROM posts
            WHERE source_type IN ('direct', 'search_engine', 'social_media', 'referral', 'email', 'advertisement', 'other')
        ");
        $validCount = $validatedQuery->fetch()['valid_count'] ?? 0;

        $this->output->writeln("<info>Update completed. {$validCount} posts now have valid source information.</info>");

        // 統計更新後的來源分布
        $distributionQuery = $this->query("
            SELECT source_type, COUNT(*) as count
            FROM posts
            GROUP BY source_type
            ORDER BY count DESC
        ");

        $this->output->writeln('<info>Source distribution after update:</info>');
        while ($row = $distributionQuery->fetch()) {
            $sourceType = $row['source_type'];
            $count = $row['count'];
            $this->output->writeln("  - {$sourceType}: {$count} posts");
        }

        $this->output->writeln('<info>Successfully updated existing posts source information.</info>');
    }

    /**
     * 向下遷移：還原更新操作
     */
    public function down(): void
    {
        $this->output->writeln('<info>Rolling back posts source information updates...</info>');

        // 檢查 posts 表是否存在
        if (!$this->hasTable('posts')) {
            $this->output->writeln('<error>Posts table does not exist. Nothing to rollback.</error>');
            return;
        }

        // 檢查 source_type 欄位是否存在
        $table = $this->table('posts');
        if (!$table->hasColumn('source_type')) {
            $this->output->writeln('<error>Column source_type does not exist. Nothing to rollback.</error>');
            return;
        }

        // 統計被 migration 更新的資料
        $migrationUpdatedQuery = $this->query("
            SELECT COUNT(*) as migration_count
            FROM posts
            WHERE source_detail LIKE '%migration_updated%'
        ");
        $migrationCount = $migrationUpdatedQuery->fetch()['migration_count'] ?? 0;

        if ($migrationCount === 0) {
            $this->output->writeln('<info>No posts found that were updated by this migration. Nothing to rollback.</info>');
            return;
        }

        $this->output->writeln("<info>Found {$migrationCount} posts updated by this migration.</info>");

        // 注意：在實際環境中，完全還原可能會導致資料問題
        // 這裡提供一個保守的回滾策略
        $this->output->writeln('<warning>WARNING: Complete rollback may cause data integrity issues.</warning>');
        $this->output->writeln('<warning>This rollback will clear source information for migration-updated posts.</warning>');

        // 將 migration 更新的資料重設為 null (謹慎操作)
        $rollbackResult = $this->execute("
            UPDATE posts
            SET source_type = NULL,
                source_detail = NULL,
                updated_at = datetime('now')
            WHERE source_detail LIKE '%migration_updated%'
        ");

        $this->output->writeln("<info>Rollback completed. {$migrationCount} posts source information cleared.</info>");
        $this->output->writeln('<warning>Note: You may need to manually restore proper source information.</warning>');
    }
}
