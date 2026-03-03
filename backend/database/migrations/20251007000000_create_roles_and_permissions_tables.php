<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 建立角色與權限管理相關表格
 * 
 * 此遷移建立完整的 RBAC (Role-Based Access Control) 系統所需的表格：
 * - roles: 角色表
 * - permissions: 權限表  
 * - user_roles: 使用者角色關聯表
 * - role_permissions: 角色權限關聯表
 */
final class CreateRolesAndPermissionsTables extends AbstractMigration
{
    /**
     * 執行遷移
     */
    public function change(): void
    {
        // 1. 建立 roles 表
        $roles = $this->table('roles');
        $roles->addColumn('name', 'string', ['limit' => 50, 'null' => false, 'comment' => '角色名稱（唯一）'])
            ->addColumn('display_name', 'string', ['limit' => 100, 'null' => false, 'comment' => '顯示名稱'])
            ->addColumn('description', 'text', ['null' => true, 'comment' => '角色描述'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addIndex(['name'], ['unique' => true, 'name' => 'roles_name_unique'])
            ->create();

        // 2. 建立 permissions 表
        $permissions = $this->table('permissions');
        $permissions->addColumn('name', 'string', ['limit' => 100, 'null' => false, 'comment' => '權限名稱（唯一）'])
            ->addColumn('display_name', 'string', ['limit' => 100, 'null' => false, 'comment' => '顯示名稱'])
            ->addColumn('description', 'text', ['null' => true, 'comment' => '權限描述'])
            ->addColumn('resource', 'string', ['limit' => 50, 'null' => false, 'comment' => '資源名稱（posts, users等）'])
            ->addColumn('action', 'string', ['limit' => 50, 'null' => false, 'comment' => '動作（create, read, update, delete等）'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['name'], ['unique' => true, 'name' => 'permissions_name_unique'])
            ->addIndex(['resource', 'action'], ['name' => 'permissions_resource_action_index'])
            ->create();

        // 3. 建立 user_roles 表（使用者角色關聯）
        $userRoles = $this->table('user_roles', ['id' => false, 'primary_key' => ['user_id', 'role_id']]);
        $userRoles->addColumn('user_id', 'integer', ['null' => false, 'comment' => '使用者ID'])
            ->addColumn('role_id', 'integer', ['null' => false, 'comment' => '角色ID'])
            ->addColumn('assigned_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '分配時間'])
            ->addColumn('assigned_by', 'integer', ['null' => true, 'comment' => '分配者ID（哪個管理員分配的）'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('assigned_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addIndex(['user_id'], ['name' => 'user_roles_user_id_index'])
            ->addIndex(['role_id'], ['name' => 'user_roles_role_id_index'])
            ->create();

        // 4. 建立 role_permissions 表（角色權限關聯）
        $rolePermissions = $this->table('role_permissions', ['id' => false, 'primary_key' => ['role_id', 'permission_id']]);
        $rolePermissions->addColumn('role_id', 'integer', ['null' => false, 'comment' => '角色ID'])
            ->addColumn('permission_id', 'integer', ['null' => false, 'comment' => '權限ID'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
