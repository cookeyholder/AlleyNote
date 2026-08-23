<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Repositories;

use App\Domains\Auth\Repositories\RoleRepository;
use PDO;
use Tests\Support\UnitTestCase;

/**
 * 角色儲存庫單元測試.
 */
final class RoleRepositoryTest extends UnitTestCase
{
    private PDO $db;

    private RoleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec('
            CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                display_name TEXT,
                description TEXT,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE role_permissions (
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                created_at TEXT,
                PRIMARY KEY (role_id, permission_id)
            );
        ');

        $this->repository = new RoleRepository($this->db);
    }

    /**
     * 測試建立角色與查找.
     */
    public function testCreateAndFindRole(): void
    {
        $role = $this->repository->create('editor', '內容編輯', '負責管理文章');
        $this->assertGreaterThan(0, $role->getId());
        $this->assertSame('editor', $role->getName());
        $this->assertSame('內容編輯', $role->getDisplayName());
        $this->assertSame('負責管理文章', $role->getDescription());

        // findById
        $found = $this->repository->findById($role->getId());
        $this->assertNotNull($found);
        $this->assertSame('editor', $found->getName());

        // findByName
        $foundByName = $this->repository->findByName('editor');
        $this->assertNotNull($foundByName);
        $this->assertSame($role->getId(), $foundByName->getId());

        // 不存在的角色
        $this->assertNull($this->repository->findById(999));
        $this->assertNull($this->repository->findByName('nonexistent'));
    }

    /**
     * 測試 findAll 與 findByIds.
     */
    public function testFindAllAndFindByIds(): void
    {
        $this->assertSame([], $this->repository->findAll());
        $this->assertSame([], $this->repository->findByIds([]));

        $role1 = $this->repository->create('role1', '角色1');
        $role2 = $this->repository->create('role2', '角色2');
        $role3 = $this->repository->create('role3', '角色3');

        $all = $this->repository->findAll();
        $this->assertCount(3, $all);

        $selected = $this->repository->findByIds([$role1->getId(), $role3->getId()]);
        $this->assertCount(2, $selected);
        $this->assertSame('role1', $selected[0]->getName());
        $this->assertSame('role3', $selected[1]->getName());
    }

    /**
     * 測試更新角色.
     */
    public function testUpdateRole(): void
    {
        $role = $this->repository->create('writer', '原作者');

        // 即使未傳入其他欄位，更新 updated_at 也會執行
        $this->assertTrue($this->repository->update($role->getId()));

        // 更新顯示名稱與描述
        $updated = $this->repository->update($role->getId(), '新作者名稱', '更新後的描述');
        $this->assertTrue($updated);

        $found = $this->repository->findById($role->getId());
        $this->assertNotNull($found);
        $this->assertSame('新作者名稱', $found->getDisplayName());
        $this->assertSame('更新後的描述', $found->getDescription());
    }

    /**
     * 測試刪除角色.
     */
    public function testDeleteRole(): void
    {
        $role = $this->repository->create('temp_role', '暫存角色');
        $this->assertTrue($this->repository->delete($role->getId()));
        $this->assertNull($this->repository->findById($role->getId()));
    }

    /**
     * 測試設定與取得角色權限關聯.
     */
    public function testSetAndGetRolePermissions(): void
    {
        $role = $this->repository->create('moderator', '版主');

        $this->assertSame([], $this->repository->getRolePermissionIds($role->getId()));

        // 設定權限
        $this->assertTrue($this->repository->setRolePermissions($role->getId(), [10, 20, 30]));
        $this->assertSame([10, 20, 30], $this->repository->getRolePermissionIds($role->getId()));

        // 重新覆寫權限（含空清單）
        $this->assertTrue($this->repository->setRolePermissions($role->getId(), [40]));
        $this->assertSame([40], $this->repository->getRolePermissionIds($role->getId()));

        $this->assertTrue($this->repository->setRolePermissions($role->getId(), []));
        $this->assertSame([], $this->repository->getRolePermissionIds($role->getId()));
    }
}
