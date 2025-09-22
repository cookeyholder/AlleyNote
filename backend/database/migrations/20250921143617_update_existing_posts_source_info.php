<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateExistingPostsSourceInfo extends AbstractMigration
{
    /**
     * 更新現有文章的來源資訊
     *
     * 此 Migration 會為所有尚未設定來源的文章設定預設值：
     * - creation_source = 'web' (網頁介面建立)
     * - creation_source_detail = null (無額外詳細資訊)
     *
     * 此操作是安全且可重複執行的：
     * 1. 只會更新沒有設定來源的文章
     * 2. 不會覆蓋已有的來源資訊
     * 3. 包含統計資訊輸出以便驗證
     * 4. 支援回滾操作
     *
     * 注意：我們不使用 change() 方法，因為這是純資料操作，需要使用 up/down 方法
     */

    /**
     * 執行資料更新 (向上遷移)
     */
    public function up(): void
    {
        // 查詢需要更新的記錄數
        $sql = "SELECT COUNT(*) as count FROM posts WHERE creation_source IS NULL OR creation_source = ''";
        $result = $this->query($sql)->fetch();
        $recordsToUpdate = $result['count'];

        $this->output->writeln("找到 {$recordsToUpdate} 筆文章需要更新來源資訊");

        if ($recordsToUpdate > 0) {
            // 記錄更新前的狀態作為備份資訊
            $this->output->writeln("準備更新文章來源資訊...");

            // 更新沒有設定來源的文章
            $updateSql = "
                UPDATE posts
                SET
                    creation_source = 'web',
                    creation_source_detail = NULL,
                    updated_at = CURRENT_TIMESTAMP
                WHERE
                    creation_source IS NULL
                    OR creation_source = ''
            ";

            $this->execute($updateSql);

            $this->output->writeln("✅ 成功更新 {$recordsToUpdate} 筆文章的來源資訊");
        } else {
            $this->output->writeln("✅ 所有文章都已設定來源資訊，無需更新");
        }

        // 驗證更新結果
        $verifySQL = "
            SELECT
                creation_source,
                COUNT(*) as count
            FROM posts
            GROUP BY creation_source
            ORDER BY creation_source
        ";

        $results = $this->query($verifySQL)->fetchAll();

        $this->output->writeln("\n📊 更新後的來源分佈統計：");
        foreach ($results as $row) {
            $source = $row['creation_source'] ?? 'NULL';
            $count = $row['count'];
            $this->output->writeln("  - {$source}: {$count} 筆");
        }

        // 檢查是否還有未設定來源的文章
        $remainingSQL = "SELECT COUNT(*) as count FROM posts WHERE creation_source IS NULL OR creation_source = ''";
        $remaining = $this->query($remainingSQL)->fetch();

        if ($remaining['count'] > 0) {
            throw new \Exception("⚠️  仍有 {$remaining['count']} 筆文章未正確設定來源資訊");
        }

        $this->output->writeln("✅ 驗證完成：所有文章都已正確設定來源資訊");
    }

    /**
     * 回滾資料更新 (向下遷移)
     *
     * 由於 creation_source 欄位有 NOT NULL 約束和觸發器保護，
     * 我們不進行實際的回滾操作，只記錄資訊
     */
    public function down(): void
    {
        $this->output->writeln("⚠️  回滾操作說明：");
        $this->output->writeln("由於 creation_source 欄位有 NOT NULL 約束和觸發器保護，");
        $this->output->writeln("無法將來源資訊重設為 NULL。");
        $this->output->writeln("如需重設資料，請手動操作或暫時停用約束。");

        // 顯示目前的來源分佈統計
        $verifySQL = "
            SELECT
                creation_source,
                COUNT(*) as count
            FROM posts
            GROUP BY creation_source
            ORDER BY creation_source
        ";

        $results = $this->query($verifySQL)->fetchAll();

        $this->output->writeln("\n📊 目前的來源分佈統計：");
        foreach ($results as $row) {
            $source = $row['creation_source'] ?? 'NULL';
            $count = $row['count'];
            $this->output->writeln("  - {$source}: {$count} 筆");
        }

        $this->output->writeln("✅ 回滾操作完成（僅記錄，無實際資料變更）");
    }
}
