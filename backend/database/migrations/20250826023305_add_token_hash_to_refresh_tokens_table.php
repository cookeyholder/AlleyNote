<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTokenHashToRefreshTokensTable extends AbstractMigration
{
    /**
     * 加入 token_hash 欄位到 refresh_tokens 表.
     */
    public function change(): void
    {
        $table = $this->table('refresh_tokens');
        $table->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'active', 'null' => false])
            ->addColumn('revoked_at', 'datetime', ['null' => true])
            ->addColumn('revoked_reason', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('last_used_at', 'datetime', ['null' => true])
            ->addColumn('parent_token_jti', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['token_hash'], ['unique' => true])
            ->addIndex(['status'])
            ->update();
    }
}
