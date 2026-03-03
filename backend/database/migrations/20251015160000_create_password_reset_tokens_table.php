<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePasswordResetTokensTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('password_reset_tokens')) {
            return;
        }

        $table = $this->table('password_reset_tokens');
        $table
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('expires_at', 'datetime', ['null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('requested_ip', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('requested_user_agent', 'text', ['null' => true])
            ->addColumn('used_at', 'datetime', ['null' => true])
            ->addColumn('used_ip', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('used_user_agent', 'text', ['null' => true])
            ->addIndex(['token_hash'], ['unique' => true])
            ->addIndex(['user_id'])
            ->addIndex(['expires_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
