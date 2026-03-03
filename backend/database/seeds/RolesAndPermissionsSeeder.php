<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * 初始化角色與權限資料
 * 
 * 建立基本的角色與權限，並分配預設的權限給角色
 */
class RolesAndPermissionsSeeder extends AbstractSeed
{
    /**
     * 執行 Seeder
     */
    public function run(): void
    {
        // 1. 建立角色
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => '超級管理員',
                'description' => '擁有系統所有權限，可以管理所有功能',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'admin',
                'display_name' => '管理員',
                'description' => '擁有大部分管理權限，可以管理文章、使用者等',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'editor',
                'display_name' => '編輯',
                'description' => '可以建立、編輯和發布文章',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'author',
                'display_name' => '作者',
                'description' => '可以建立和編輯自己的文章',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'user',
                'display_name' => '一般使用者',
                'description' => '只能瀏覽公開內容',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->table('roles')->insert($roles)->saveData();

        // 2. 建立權限
        $permissions = [
            // 使用者管理權限
            ['name' => 'users.create', 'display_name' => '建立使用者', 'resource' => 'users', 'action' => 'create', 'description' => '可以新增使用者帳號', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'users.read', 'display_name' => '查看使用者', 'resource' => 'users', 'action' => 'read', 'description' => '可以查看使用者列表和詳情', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'users.update', 'display_name' => '更新使用者', 'resource' => 'users', 'action' => 'update', 'description' => '可以修改使用者資料', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'users.delete', 'display_name' => '刪除使用者', 'resource' => 'users', 'action' => 'delete', 'description' => '可以刪除使用者帳號', 'created_at' => date('Y-m-d H:i:s')],
            
            // 文章管理權限
            ['name' => 'posts.create', 'display_name' => '建立文章', 'resource' => 'posts', 'action' => 'create', 'description' => '可以新增文章', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'posts.read', 'display_name' => '查看文章', 'resource' => 'posts', 'action' => 'read', 'description' => '可以查看文章列表和內容', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'posts.update', 'display_name' => '更新文章', 'resource' => 'posts', 'action' => 'update', 'description' => '可以修改文章內容', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'posts.delete', 'display_name' => '刪除文章', 'resource' => 'posts', 'action' => 'delete', 'description' => '可以刪除文章', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'posts.publish', 'display_name' => '發布文章', 'resource' => 'posts', 'action' => 'publish', 'description' => '可以發布或取消發布文章', 'created_at' => date('Y-m-d H:i:s')],
            
            // 角色權限管理
            ['name' => 'roles.create', 'display_name' => '建立角色', 'resource' => 'roles', 'action' => 'create', 'description' => '可以新增角色', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'roles.read', 'display_name' => '查看角色', 'resource' => 'roles', 'action' => 'read', 'description' => '可以查看角色列表', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'roles.update', 'display_name' => '更新角色', 'resource' => 'roles', 'action' => 'update', 'description' => '可以修改角色設定', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'roles.delete', 'display_name' => '刪除角色', 'resource' => 'roles', 'action' => 'delete', 'description' => '可以刪除角色', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'roles.assign', 'display_name' => '分配角色', 'resource' => 'roles', 'action' => 'assign', 'description' => '可以分配角色給使用者', 'created_at' => date('Y-m-d H:i:s')],
            
            // 標籤管理權限
            ['name' => 'tags.create', 'display_name' => '建立標籤', 'resource' => 'tags', 'action' => 'create', 'description' => '可以新增標籤', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'tags.read', 'display_name' => '查看標籤', 'resource' => 'tags', 'action' => 'read', 'description' => '可以查看標籤列表', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'tags.update', 'display_name' => '更新標籤', 'resource' => 'tags', 'action' => 'update', 'description' => '可以修改標籤', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'tags.delete', 'display_name' => '刪除標籤', 'resource' => 'tags', 'action' => 'delete', 'description' => '可以刪除標籤', 'created_at' => date('Y-m-d H:i:s')],
            
            // 統計報表權限
            ['name' => 'statistics.read', 'display_name' => '查看統計', 'resource' => 'statistics', 'action' => 'read', 'description' => '可以查看統計報表', 'created_at' => date('Y-m-d H:i:s')],
            
            // 系統設定權限
            ['name' => 'settings.read', 'display_name' => '查看設定', 'resource' => 'settings', 'action' => 'read', 'description' => '可以查看系統設定', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'settings.update', 'display_name' => '更新設定', 'resource' => 'settings', 'action' => 'update', 'description' => '可以修改系統設定', 'created_at' => date('Y-m-d H:i:s')],
        ];

        $this->table('permissions')->insert($permissions)->saveData();

        // 3. 分配權限給角色
        // super_admin：所有權限
        $allPermissionIds = range(1, count($permissions));
        $superAdminPermissions = array_map(fn($permId) => [
            'role_id' => 1, // super_admin
            'permission_id' => $permId,
            'created_at' => date('Y-m-d H:i:s'),
        ], $allPermissionIds);

        // admin：除了系統設定外的所有權限
        $adminPermissionIds = range(1, 20); // 不包含 settings.update
        $adminPermissions = array_map(fn($permId) => [
            'role_id' => 2, // admin
            'permission_id' => $permId,
            'created_at' => date('Y-m-d H:i:s'),
        ], $adminPermissionIds);

        // editor：文章和標籤的完整權限
        $editorPermissions = [
            ['role_id' => 3, 'permission_id' => 5, 'created_at' => date('Y-m-d H:i:s')],  // posts.create
            ['role_id' => 3, 'permission_id' => 6, 'created_at' => date('Y-m-d H:i:s')],  // posts.read
            ['role_id' => 3, 'permission_id' => 7, 'created_at' => date('Y-m-d H:i:s')],  // posts.update
            ['role_id' => 3, 'permission_id' => 8, 'created_at' => date('Y-m-d H:i:s')],  // posts.delete
            ['role_id' => 3, 'permission_id' => 9, 'created_at' => date('Y-m-d H:i:s')],  // posts.publish
            ['role_id' => 3, 'permission_id' => 15, 'created_at' => date('Y-m-d H:i:s')], // tags.create
            ['role_id' => 3, 'permission_id' => 16, 'created_at' => date('Y-m-d H:i:s')], // tags.read
            ['role_id' => 3, 'permission_id' => 17, 'created_at' => date('Y-m-d H:i:s')], // tags.update
            ['role_id' => 3, 'permission_id' => 18, 'created_at' => date('Y-m-d H:i:s')], // tags.delete
            ['role_id' => 3, 'permission_id' => 19, 'created_at' => date('Y-m-d H:i:s')], // statistics.read
        ];

        // author：自己文章的權限
        $authorPermissions = [
            ['role_id' => 4, 'permission_id' => 5, 'created_at' => date('Y-m-d H:i:s')],  // posts.create
            ['role_id' => 4, 'permission_id' => 6, 'created_at' => date('Y-m-d H:i:s')],  // posts.read
            ['role_id' => 4, 'permission_id' => 7, 'created_at' => date('Y-m-d H:i:s')],  // posts.update (僅自己的)
            ['role_id' => 4, 'permission_id' => 16, 'created_at' => date('Y-m-d H:i:s')], // tags.read
        ];

        // user：只有讀取權限
        $userPermissions = [
            ['role_id' => 5, 'permission_id' => 6, 'created_at' => date('Y-m-d H:i:s')],  // posts.read
            ['role_id' => 5, 'permission_id' => 16, 'created_at' => date('Y-m-d H:i:s')], // tags.read
        ];

        $this->table('role_permissions')
            ->insert(array_merge(
                $superAdminPermissions,
                $adminPermissions,
                $editorPermissions,
                $authorPermissions,
                $userPermissions
            ))
            ->saveData();

        // 4. 給現有的 admin 使用者分配 super_admin 角色
        $this->table('user_roles')
            ->insert([
                'user_id' => 1, // 假設 ID 1 是 admin
                'role_id' => 1, // super_admin
                'assigned_at' => date('Y-m-d H:i:s'),
            ])
            ->saveData();
    }
}
