<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTokenBlacklistTable extends AbstractMigration
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
        $table = $this->table('token_blacklist', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true])
            ->addColumn('jti', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('token_type', 'string', ['limit' => 20, 'null' => false])
            ->addColumn('user_id', 'integer', ['null' => true])
            ->addColumn('expires_at', 'datetime', ['null' => false])
            ->addColumn('blacklisted_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('reason', 'string', ['limit' => 255, 'null' => true])
            ->addIndex(['jti'], ['unique' => true])
            ->addIndex(['expires_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
