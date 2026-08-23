<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Services\AuthorizationService;
use App\Shared\Contracts\CacheServiceInterface;
use Mockery;
use Mockery\MockInterface;
use PDO;
use Tests\Support\UnitTestCase;

/**
 * 授權服務單元測試.
 */
final class AuthorizationServiceTest extends UnitTestCase
{
    private PDO $db;

    private CacheServiceInterface&MockInterface $cache;

    private AuthorizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createTables();
        $this->cache = Mockery::mock(CacheServiceInterface::class);
        $this->cache->shouldReceive('remember')->byDefault()->andReturnUsing(function ($key, $callback, $ttl = null) {
            return is_callable($callback) ? $callback() : $callback;
        });
        $this->cache->shouldReceive('delete')->byDefault()->andReturn(true);

        $this->service = new AuthorizationService($this->db, $this->cache);
    }

    private function createTables(): void
    {
        $this->db->exec('
            CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                description TEXT,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                resource TEXT,
                action TEXT,
                description TEXT,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE user_roles (
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                created_at TEXT,
                PRIMARY KEY (user_id, role_id)
            );

            CREATE TABLE role_permissions (
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                created_at TEXT,
                PRIMARY KEY (role_id, permission_id)
            );

            CREATE TABLE user_permissions (
                user_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                created_at TEXT,
                PRIMARY KEY (user_id, permission_id)
            );

            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                deleted_at TEXT
            );

            CREATE TABLE attachments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                post_id INTEGER NOT NULL,
                deleted_at TEXT
            );
        ');
    }

    /**
     * 測試分配與移除角色.
     */
    public function testAssignAndRemoveRole(): void
    {
        $this->db->exec("INSERT INTO roles (id, name) VALUES (1, 'editor')");

        // 分配角色
        $this->assertTrue($this->service->assignRole(10, 'editor'));
        // 重複分配應返回 true
        $this->assertTrue($this->service->assignRole(10, 'editor'));
        $this->assertTrue($this->service->hasRole(10, 'editor'));

        // 分配不存在的角色
        $this->assertFalse($this->service->assignRole(10, 'nonexistent'));

        // 移除角色
        $this->assertTrue($this->service->removeRole(10, 'editor'));
        $this->assertFalse($this->service->hasRole(10, 'editor'));
    }

    /**
     * 測試直接授予與撤銷使用者權限.
     */
    public function testGiveAndRevokePermission(): void
    {
        $this->db->exec("INSERT INTO permissions (id, name) VALUES (1, 'posts.publish')");

        // 授予權限
        $this->assertTrue($this->service->givePermission(10, 'posts.publish'));
        // 重複授予應返回 true
        $this->assertTrue($this->service->givePermission(10, 'posts.publish'));
        $this->assertTrue($this->service->hasPermission(10, 'posts.publish'));

        // 授予不存在的權限
        $this->assertFalse($this->service->givePermission(10, 'nonexistent.perm'));

        // 撤銷權限
        $this->assertTrue($this->service->revokePermission(10, 'posts.publish'));
        $this->assertFalse($this->service->hasPermission(10, 'posts.publish'));
    }

    /**
     * 測試角色權限繼承與 getUserPermissions 合併.
     */
    public function testGetUserPermissionsCombined(): void
    {
        $this->db->exec("
            INSERT INTO roles (id, name) VALUES (1, 'manager');
            INSERT INTO permissions (id, name) VALUES (1, 'posts.view'), (2, 'users.view');
            INSERT INTO user_roles (user_id, role_id) VALUES (5, 1);
            INSERT INTO role_permissions (role_id, permission_id) VALUES (1, 1);
            INSERT INTO user_permissions (user_id, permission_id) VALUES (5, 2);
        ");

        $permissions = $this->service->getUserPermissions(5);
        $this->assertContains('posts.view', $permissions);
        $this->assertContains('users.view', $permissions);
        $this->assertCount(2, $permissions);
    }

    /**
     * 測試 can 方法（一般使用者權限與超級管理員）.
     */
    public function testCanMethod(): void
    {
        // 1. 一般使用者無權限
        $this->assertFalse($this->service->can(1, 'posts', 'delete'));

        // 2. 一般使用者有權限
        $this->db->exec("INSERT INTO permissions (id, name) VALUES (10, 'posts:delete')");
        $this->service->givePermission(1, 'posts:delete');
        $this->assertTrue($this->service->can(1, 'posts', 'delete'));

        // 3. 超級管理員（具有 admin 角色）
        $this->db->exec("INSERT INTO roles (id, name) VALUES (99, 'admin')");
        $this->service->assignRole(2, 'admin');
        $this->assertTrue($this->service->isSuperAdmin(2));
        $this->assertTrue($this->service->can(2, 'anything', 'anyaction'));
    }

    /**
     * 測試附件上傳與刪除權限（擁有者與非擁有者與管理員）.
     */
    public function testAttachmentPermissions(): void
    {
        // 建立文章與附件
        $this->db->exec("
            INSERT INTO posts (id, user_id, deleted_at) VALUES (100, 1, NULL);
            INSERT INTO attachments (id, uuid, post_id, deleted_at) VALUES (1, 'att-uuid-1', 100, NULL);
            INSERT INTO roles (id, name) VALUES (99, 'admin');
        ");

        // 1. 文章作者可上傳與刪除附件
        $this->assertTrue($this->service->canUploadAttachment(1, 100));
        $this->assertTrue($this->service->canDeleteAttachment(1, 'att-uuid-1'));

        // 2. 非作者不可上傳與刪除附件
        $this->assertFalse($this->service->canUploadAttachment(2, 100));
        $this->assertFalse($this->service->canDeleteAttachment(2, 'att-uuid-1'));

        // 3. 不存在的文章或附件
        $this->assertFalse($this->service->canUploadAttachment(1, 999));
        $this->assertFalse($this->service->canDeleteAttachment(1, 'non-existent-uuid'));

        // 4. 超級管理員無論是否作者皆可操作
        $this->service->assignRole(3, 'admin');
        $this->assertTrue($this->service->canUploadAttachment(3, 100));
        $this->assertTrue($this->service->canDeleteAttachment(3, 'att-uuid-1'));
    }

    /**
     * 測試取得使用者的所有角色.
     */
    public function testGetUserRoles(): void
    {
        $this->db->exec("
            INSERT INTO roles (id, name, description) VALUES (1, 'role1', 'Desc 1'), (2, 'role2', 'Desc 2');
            INSERT INTO user_roles (user_id, role_id) VALUES (10, 1), (10, 2);
        ");

        $roles = $this->service->getUserRoles(10);
        $this->assertCount(2, $roles);
        $firstRole = $roles[0];
        $this->assertIsArray($firstRole);
        $this->assertSame('role1', $firstRole['name']);
        $secondRole = $roles[1];
        $this->assertIsArray($secondRole);
        $this->assertSame('role2', $secondRole['name']);
    }
}
