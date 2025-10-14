<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAttachmentLimitSetting extends AbstractMigration
{
    public function up(): void
    {
        // 新增「單篇文章附件數量上限」設定
        $this->execute("
            INSERT INTO settings (key, value, type, description, created_at, updated_at)
            VALUES (
                'max_attachments_per_post',
                '10',
                'integer',
                '單篇文章可附加的檔案數量上限',
                datetime('now'),
                datetime('now')
            )
        ");
    }

    public function down(): void
    {
        // 移除設定
        $this->execute("DELETE FROM settings WHERE key = 'max_attachments_per_post'");
    }
}
