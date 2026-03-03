<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 更新 roles 和 permissions 表，新增 display_name 欄位
 */
final class UpdateRolesAndPermissionsAddDisplayName extends AbstractMigration
{
    /**
     * 執行遷移
     */
    public function change(): void
    {
        // 為 roles 表新增 display_name 欄位
        $roles = $this->table('roles');
        if (!$roles->hasColumn('display_name')) {
            $roles->addColumn('display_name', 'string', [
                'limit' => 100,
                'null' => false,
                'default' => '',
                'after' => 'name',
                'comment' => '顯示名稱'
            ])->update();
        }

        // 為 permissions 表新增 display_name 欄位
        $permissions = $this->table('permissions');
        if (!$permissions->hasColumn('display_name')) {
            $permissions->addColumn('display_name', 'string', [
                'limit' => 100,
                'null' => false,
                'default' => '',
                'after' => 'name',
                'comment' => '顯示名稱'
            ])->update();
        }
    }
}
