<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Repositories;

use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\Repositories\UserRepository;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PDO;
use Tests\Support\UnitTestCase;

/**
 * 使用者儲存庫單元測試.
 */
final class UserRepositoryTest extends UnitTestCase
{
    private PDO $db;

    private PasswordSecurityServiceInterface&MockInterface $passwordService;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid TEXT NOT NULL UNIQUE,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                status TEXT DEFAULT "active",
                role TEXT DEFAULT "user",
                is_active INTEGER DEFAULT 1,
                last_login TEXT,
                deleted_at TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                display_name TEXT,
                description TEXT
            );

            CREATE TABLE user_roles (
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                created_at TEXT,
                PRIMARY KEY (user_id, role_id)
            );
        ');

        $this->passwordService = Mockery::mock(PasswordSecurityServiceInterface::class);
        $this->repository = new UserRepository($this->db, $this->passwordService);
    }

    /**
     * 測試建立與各維度查詢使用者.
     */
    public function testCreateAndFindUser(): void
    {
        $user = $this->repository->create([
            'username' => 'alice',
            'email'    => 'alice@example.com',
            'password' => 'hashed_password_123',
        ]);

        $this->assertNotEmpty($user['id']);
        $this->assertNotEmpty($user['uuid']);
        $this->assertSame('alice', $user['username']);
        $this->assertSame('alice@example.com', $user['email']);

        // findById
        $byId = $this->repository->findById((int) $user['id']);
        $this->assertNotNull($byId);
        $this->assertSame('alice', $byId['username']);

        // findByUuid
        $byUuid = $this->repository->findByUuid($user['uuid']);
        $this->assertNotNull($byUuid);
        $this->assertSame('alice', $byUuid['username']);

        // findByUsername
        $byUsername = $this->repository->findByUsername('alice');
        $this->assertNotNull($byUsername);
        $this->assertSame('alice@example.com', $byUsername['email']);

        // findByEmail
        $byEmail = $this->repository->findByEmail('alice@example.com');
        $this->assertNotNull($byEmail);
        $this->assertSame('alice', $byEmail['username']);

        // 查無資料
        $this->assertNull($this->repository->findById(999));
        $this->assertNull($this->repository->findByUuid('nonexistent'));
        $this->assertNull($this->repository->findByUsername('nonexistent'));
        $this->assertNull($this->repository->findByEmail('nonexistent@example.com'));
    }

    /**
     * 測試更新使用者與刪除.
     */
    public function testUpdateAndDeleteUser(): void
    {
        $user = $this->repository->create([
            'username' => 'bob',
            'email'    => 'bob@example.com',
            'password' => 'hashed',
        ]);

        $id = (string) $user['id'];

        // 空欄位更新直接返回現有資料
        $noChanges = $this->repository->update($id, []);
        $this->assertSame('bob', $noChanges['username']);

        // 更新欄位
        $updated = $this->repository->update($id, [
            'username' => 'bob_updated',
            'password' => 'new_hashed',
        ]);
        $this->assertSame('bob_updated', $updated['username']);
        $this->assertSame('new_hashed', $updated['password_hash']);

        // 更新最後登入時間
        $this->assertTrue($this->repository->updateLastLogin($id));
        $reloaded = $this->repository->findById((int) $id);
        $this->assertNotNull($reloaded['last_login']);

        // 刪除使用者
        $this->assertTrue($this->repository->delete($id));
        $this->assertNull($this->repository->findById((int) $id));
    }

    /**
     * 測試更新密碼成功與異常.
     */
    public function testUpdatePassword(): void
    {
        $oldHash = password_hash('OldPassword123!', PASSWORD_ARGON2ID);
        $user = $this->repository->create([
            'username' => 'charlie',
            'email'    => 'charlie@example.com',
            'password' => $oldHash,
        ]);
        $id = (int) $user['id'];

        // 1. 使用者不存在
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('找不到指定的使用者');
        $this->repository->updatePassword(999, 'AnyPass123!');
    }

    /**
     * 測試更新密碼與目前密碼相同時拋出例外.
     */
    public function testUpdatePasswordSameAsCurrentThrowsException(): void
    {
        $oldHash = password_hash('SamePassword123!', PASSWORD_ARGON2ID);
        $user = $this->repository->create([
            'username' => 'david',
            'email'    => 'david@example.com',
            'password' => $oldHash,
        ]);
        $id = (int) $user['id'];

        $this->passwordService->shouldReceive('validatePassword')->once()->with('SamePassword123!');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('新密碼不能與目前的密碼相同');
        $this->repository->updatePassword($id, 'SamePassword123!');
    }

    /**
     * 測試更新密碼成功（透過 passwordService 雜湊）.
     */
    public function testUpdatePasswordSuccess(): void
    {
        $oldHash = password_hash('OldPassword123!', PASSWORD_ARGON2ID);
        $user = $this->repository->create([
            'username' => 'eve',
            'email'    => 'eve@example.com',
            'password' => $oldHash,
        ]);
        $id = (int) $user['id'];

        $this->passwordService->shouldReceive('validatePassword')->once()->with('NewPassword456!');
        $this->passwordService->shouldReceive('hashPassword')->once()->with('NewPassword456!')->andReturn('argon_hashed_new');

        $this->assertTrue($this->repository->updatePassword($id, 'NewPassword456!'));

        $updatedUser = $this->repository->findById($id);
        $this->assertSame('argon_hashed_new', $updatedUser['password_hash']);
    }

    /**
     * 測試分頁與搜尋使用者.
     */
    public function testPaginateAndSearch(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->repository->create([
                'username' => "user_{$i}",
                'email'    => "user_{$i}@example.com",
                'password' => 'hash',
            ]);
        }

        // 第 1 頁，每頁 10 筆
        $page1 = $this->repository->paginate(1, 10);
        $this->assertSame(15, $page1['total']);
        $this->assertCount(10, $page1['items']);
        $this->assertSame(2.0, (float) $page1['last_page']);

        // 搜尋篩選
        $filtered = $this->repository->paginate(1, 10, ['search' => 'user_1']);
        // user_1, user_10, user_11, user_12, user_13, user_14, user_15 -> 7 users
        $this->assertSame(7, $filtered['total']);
    }

    /**
     * 測試角色關聯 (setUserRoles, getUserRoleIds, findByIdWithRoles).
     */
    public function testUserRoleRelations(): void
    {
        $this->db->exec("
            INSERT INTO roles (id, name, display_name) VALUES
            (1, 'admin', '管理員'),
            (2, 'editor', '編輯者');
        ");

        $user = $this->repository->create([
            'username' => 'frank',
            'email'    => 'frank@example.com',
            'password' => 'hash',
        ]);
        $id = (int) $user['id'];

        $this->assertSame([], $this->repository->getUserRoleIds($id));

        // 設定角色
        $this->assertTrue($this->repository->setUserRoles($id, [1, 2]));
        $this->assertSame([1, 2], $this->repository->getUserRoleIds($id));

        // findByIdWithRoles
        $withRoles = $this->repository->findByIdWithRoles($id);
        $this->assertNotNull($withRoles);
        $this->assertCount(2, $withRoles['roles']);
        $this->assertSame('admin', $withRoles['roles'][0]['name']);
        $this->assertArrayNotHasKey('password_hash', $withRoles);

        // 查無資料
        $this->assertNull($this->repository->findByIdWithRoles(999));
    }
}
