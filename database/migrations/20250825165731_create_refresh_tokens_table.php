<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRefreshTokensTable extends AbstractMigration
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
        $table = $this->table('refresh_tokens', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('jti', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('user_id', 'integer', ['null' => false])
              ->addColumn('device_id', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('device_name', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('expires_at', 'datetime', ['null' => false])
              ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->addIndex(['jti'], ['unique' => true])
              ->addIndex(['user_id'])
              ->addIndex(['expires_at'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
