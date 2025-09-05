<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateExistingPostsSourceInfo extends AbstractMigration
{
    /**
     * 向上遷移：更新現有文章的來源資訊
     */
    public function up(): void
    {
        $this->output->writeln('開始更新現有文章的來源資訊...');
        
        // 記錄開始時間
        $startTime = microtime(true);
        
        // 檢查 posts 表是否存在 source_type 欄位
        if (!$this->hasTable('posts') || !$this->table('posts')->hasColumn('source_type')) {
            $this->output->writeln('<error>錯誤: posts 表不存在或缺少 source_type 欄位</error>');
            return;
        }
        
        // 計算現有文章總數
        $totalPosts = $this->fetchRow('SELECT COUNT(*) as count FROM posts');
        $totalCount = (int) $totalPosts['count'];
        
        if ($totalCount === 0) {
            $this->output->writeln('沒有現有文章需要更新');
            return;
        }
        
        $this->output->writeln("找到 {$totalCount} 篇文章需要更新來源資訊");
        
        // 批次更新現有文章的來源類型為 'web' (預設值)
        $batchSize = 1000;
        $updatedCount = 0;
        
        for ($offset = 0; $offset < $totalCount; $offset += $batchSize) {
            $currentBatch = min($batchSize, $totalCount - $offset);
            
            // 更新當前批次
            $this->execute("
                UPDATE posts 
                SET 
                    source_type = 'web',
                    source_detail = JSON_OBJECT(
                        'migration_updated', true,
                        'original_source', 'legacy',
                        'updated_at', NOW(),
                        'note', 'Updated during migration to add source tracking'
                    ),
                    published_at = COALESCE(published_at, created_at)
                WHERE source_type IS NULL 
                   OR source_type = ''
                LIMIT {$currentBatch}
            ");
            
            $updatedCount += $currentBatch;
            $progress = round(($updatedCount / $totalCount) * 100, 2);
            $this->output->writeln("已更新 {$updatedCount}/{$totalCount} 篇文章 ({$progress}%)");
        }
        
        // 驗證更新結果
        $verificationResult = $this->fetchRow("
            SELECT 
                COUNT(*) as total_posts,
                SUM(CASE WHEN source_type = 'web' THEN 1 ELSE 0 END) as web_source_posts,
                SUM(CASE WHEN source_detail IS NOT NULL THEN 1 ELSE 0 END) as posts_with_detail,
                SUM(CASE WHEN published_at IS NOT NULL THEN 1 ELSE 0 END) as posts_with_published_date
            FROM posts
        ");
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->output->writeln('');
        $this->output->writeln('=== 更新完成摘要 ===');
        $this->output->writeln("總文章數: {$verificationResult['total_posts']}");
        $this->output->writeln("設定為 web 來源: {$verificationResult['web_source_posts']}");
        $this->output->writeln("包含來源詳細資訊: {$verificationResult['posts_with_detail']}");
        $this->output->writeln("包含發布時間: {$verificationResult['posts_with_published_date']}");
        $this->output->writeln("執行時間: {$duration} 秒");
        
        // 記錄操作日誌到資料庫
        $this->execute("
            INSERT INTO migration_logs (migration_name, operation, records_affected, duration_seconds, executed_at, details)
            VALUES (
                'UpdateExistingPostsSourceInfo',
                'update_source_info',
                {$updatedCount},
                {$duration},
                NOW(),
                JSON_OBJECT(
                    'total_posts', {$verificationResult['total_posts']},
                    'web_source_posts', {$verificationResult['web_source_posts']},
                    'posts_with_detail', {$verificationResult['posts_with_detail']},
                    'posts_with_published_date', {$verificationResult['posts_with_published_date']},
                    'batch_size', {$batchSize}
                )
            )
        ");
        
        $this->output->writeln('<info>文章來源資訊更新完成！</info>');
    }
    
    /**
     * 向下遷移：回復文章來源資訊的變更
     */
    public function down(): void
    {
        $this->output->writeln('開始回復文章來源資訊變更...');
        
        // 檢查是否有遷移日誌可以參考
        $logExists = $this->hasTable('migration_logs');
        
        if ($logExists) {
            $migrationLog = $this->fetchRow("
                SELECT records_affected, details 
                FROM migration_logs 
                WHERE migration_name = 'UpdateExistingPostsSourceInfo' 
                  AND operation = 'update_source_info'
                ORDER BY executed_at DESC 
                LIMIT 1
            ");
            
            if ($migrationLog) {
                $this->output->writeln("找到遷移日誌，將回復 {$migrationLog['records_affected']} 筆記錄");
            }
        }
        
        // 將透過遷移更新的文章來源資訊清除
        $this->execute("
            UPDATE posts 
            SET 
                source_type = NULL,
                source_detail = NULL
            WHERE JSON_EXTRACT(source_detail, '$.migration_updated') = true
        ");
        
        // 記錄回滾操作
        if ($logExists) {
            $this->execute("
                INSERT INTO migration_logs (migration_name, operation, executed_at)
                VALUES ('UpdateExistingPostsSourceInfo', 'rollback', NOW())
            ");
        }
        
        $this->output->writeln('<info>文章來源資訊回復完成！</info>');
    }
    
    /**
     * Change Method - 不使用，改用 up/down 方法
     */
    public function change(): void
    {
        // 建立遷移日誌表（如果不存在）
        if (!$this->hasTable('migration_logs')) {
            $table = $this->table('migration_logs');
            $table->addColumn('migration_name', 'string', ['limit' => 255])
                  ->addColumn('operation', 'string', ['limit' => 100])
                  ->addColumn('records_affected', 'integer', ['default' => 0])
                  ->addColumn('duration_seconds', 'decimal', ['precision' => 10, 'scale' => 3, 'null' => true])
                  ->addColumn('executed_at', 'datetime')
                  ->addColumn('details', 'json', ['null' => true])
                  ->addIndex(['migration_name', 'operation'])
                  ->addIndex(['executed_at'])
                  ->create();
        }
    }
}
