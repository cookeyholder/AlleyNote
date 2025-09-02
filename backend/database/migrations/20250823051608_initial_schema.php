<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSchema extends AbstractMigration
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
        // Create posts table
        $posts = $this->table('posts', ['id' => false, 'primary_key' => 'id']);
        $posts->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('uuid', 'string', ['limit' => 36, 'null' => false])
              ->addColumn('seq_number', 'integer', ['null' => false])
              ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('content', 'text', ['null' => false])
              ->addColumn('user_id', 'integer', ['null' => false])
              ->addColumn('user_ip', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('views', 'integer', ['default' => 0, 'null' => false])
              ->addColumn('is_pinned', 'boolean', ['default' => false, 'null' => false])
              ->addColumn('status', 'string', ['limit' => 20, 'default' => 'draft', 'null' => false])
              ->addColumn('publish_date', 'datetime', ['null' => true])
              ->addColumn('created_at', 'datetime', ['null' => false])
              ->addColumn('updated_at', 'datetime', ['null' => true])
              ->addColumn('deleted_at', 'datetime', ['null' => true])
              ->addIndex(['uuid'], ['unique' => true])
              ->addIndex(['seq_number'], ['unique' => true])
              ->addIndex(['title'])
              ->addIndex(['publish_date'])
              ->addIndex(['is_pinned'])
              ->addIndex(['user_id'])
              ->addIndex(['status'])
              ->addIndex(['views'])
              ->addIndex(['deleted_at'])
              ->create();

        // Create tags table
        $tags = $this->table('tags', ['id' => false, 'primary_key' => 'id']);
        $tags->addColumn('id', 'integer', ['identity' => true])
             ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
             ->addColumn('created_at', 'datetime', ['null' => false])
             ->addColumn('updated_at', 'datetime', ['null' => true])
             ->addIndex(['name'], ['unique' => true])
             ->create();

        // Create post_tags table
        $postTags = $this->table('post_tags', ['id' => false, 'primary_key' => ['post_id', 'tag_id']]);
        $postTags->addColumn('post_id', 'integer', ['null' => false])
                 ->addColumn('tag_id', 'integer', ['null' => false])
                 ->addColumn('created_at', 'datetime', ['null' => false])
                 ->addForeignKey('post_id', 'posts', 'id', ['delete' => 'CASCADE'])
                 ->addForeignKey('tag_id', 'tags', 'id', ['delete' => 'CASCADE'])
                 ->addIndex(['tag_id'])
                 ->addIndex(['created_at'])
                 ->create();

        // Create post_views table
        $postViews = $this->table('post_views', ['id' => false, 'primary_key' => 'id']);
        $postViews->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('uuid', 'string', ['limit' => 36, 'null' => false])
                  ->addColumn('post_id', 'integer', ['null' => false])
                  ->addColumn('user_id', 'integer', ['null' => true])
                  ->addColumn('user_ip', 'string', ['limit' => 45, 'null' => false])
                  ->addColumn('view_date', 'datetime', ['null' => false])
                  ->addIndex(['uuid'], ['unique' => true])
                  ->addIndex(['post_id'])
                  ->addIndex(['user_id'])
                  ->addIndex(['user_ip'])
                  ->addIndex(['view_date'])
                  ->addForeignKey('post_id', 'posts', 'id', ['delete' => 'CASCADE'])
                  ->create();

        // Create roles table
        $roles = $this->table('roles', ['id' => false, 'primary_key' => 'id']);
        $roles->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('description', 'text', ['null' => true])
              ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['null' => true])
              ->addIndex(['name'], ['unique' => true])
              ->create();

        // Create permissions table
        $permissions = $this->table('permissions', ['id' => false, 'primary_key' => 'id']);
        $permissions->addColumn('id', 'integer', ['identity' => true])
                    ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                    ->addColumn('description', 'text', ['null' => true])
                    ->addColumn('resource', 'string', ['limit' => 255, 'null' => false])
                    ->addColumn('action', 'string', ['limit' => 255, 'null' => false])
                    ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                    ->addColumn('updated_at', 'datetime', ['null' => true])
                    ->addIndex(['name'], ['unique' => true])
                    ->addIndex(['resource'])
                    ->addIndex(['action'])
                    ->create();

        // Create role_permissions table
        $rolePermissions = $this->table('role_permissions', ['id' => false, 'primary_key' => 'id']);
        $rolePermissions->addColumn('id', 'integer', ['identity' => true])
                        ->addColumn('role_id', 'integer', ['null' => false])
                        ->addColumn('permission_id', 'integer', ['null' => false])
                        ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                        ->addIndex(['role_id', 'permission_id'], ['unique' => true])
                        ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE'])
                        ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE'])
                        ->create();

        // Create users table (basic structure for foreign keys)
        $users = $this->table('users', ['id' => false, 'primary_key' => 'id']);
        $users->addColumn('id', 'integer', ['identity' => true])
              ->addColumn('username', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
              ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'datetime', ['null' => true])
              ->addIndex(['username'], ['unique' => true])
              ->addIndex(['email'], ['unique' => true])
              ->create();

        // Create user_roles table
        $userRoles = $this->table('user_roles', ['id' => false, 'primary_key' => 'id']);
        $userRoles->addColumn('id', 'integer', ['identity' => true])
                  ->addColumn('user_id', 'integer', ['null' => false])
                  ->addColumn('role_id', 'integer', ['null' => false])
                  ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                  ->addIndex(['user_id', 'role_id'], ['unique' => true])
                  ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
                  ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE'])
                  ->create();

        // Create user_permissions table
        $userPermissions = $this->table('user_permissions', ['id' => false, 'primary_key' => 'id']);
        $userPermissions->addColumn('id', 'integer', ['identity' => true])
                        ->addColumn('user_id', 'integer', ['null' => false])
                        ->addColumn('permission_id', 'integer', ['null' => false])
                        ->addColumn('created_at', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
                        ->addIndex(['user_id', 'permission_id'], ['unique' => true])
                        ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
                        ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE'])
                        ->create();

        // Create ip_lists table
        $ipLists = $this->table('ip_lists', ['id' => false, 'primary_key' => 'id']);
        $ipLists->addColumn('id', 'integer', ['identity' => true])
                ->addColumn('uuid', 'string', ['limit' => 36, 'null' => false])
                ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => false])
                ->addColumn('type', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('unit_id', 'integer', ['null' => true])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('created_at', 'datetime', ['null' => false])
                ->addColumn('updated_at', 'datetime', ['null' => true])
                ->addIndex(['uuid'], ['unique' => true])
                ->addIndex(['ip_address'])
                ->addIndex(['type'])
                ->addIndex(['unit_id'])
                ->addIndex(['created_at'])
                ->create();
    }
}
