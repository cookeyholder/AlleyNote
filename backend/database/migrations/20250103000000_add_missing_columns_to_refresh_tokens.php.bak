<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMissingColumnsToRefreshTokens extends AbstractMigration
{
    /**
     * 加入缺少的欄位到 refresh_tokens 表格.
     * 這些欄位在程式碼中被使用但是在原始遷移中缺少.
     */
    public function change(): void
    {
        $table = $this->table('refresh_tokens');

        // 檢查欄位是否已經存在，避免重複建立
        if (!$table->hasColumn('device_type')) {
            $table->addColumn('device_type', 'string', ['limit' => 50, 'null' => true, 'default' => null]);
        }

        if (!$table->hasColumn('user_agent')) {
            $table->addColumn('user_agent', 'text', ['null' => true]);
        }

        if (!$table->hasColumn('ip_address')) {
            $table->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true]);
        }

        if (!$table->hasColumn('platform')) {
            $table->addColumn('platform', 'string', ['limit' => 50, 'null' => true]);
        }

        if (!$table->hasColumn('browser')) {
            $table->addColumn('browser', 'string', ['limit' => 100, 'null' => true]);
        }

        $table->update();
    }
}
