<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\DTOs\CreateUserDTO;
use App\Domains\Auth\DTOs\UpdateUserDTO;
use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\Services\UserManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * 使用者管理服務單元測試.
 */
final class UserManagementServiceTest extends UnitTestCase
{
    private UserRepository&MockInterface $userRepository;

    private UserManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->service = new UserManagementService($this->userRepository);
    }

    /**
     * 測試取得使用者列表.
     */
    public function testListUsers(): void
    {
        $expectedResult = [
            'items'     => [['id' => 1, 'username' => 'testuser']],
            'total'     => 1,
            'page'      => 1,
            'per_page'  => 10,
            'last_page' => 1,
        ];

        $this->userRepository
            ->shouldReceive('paginate')
            ->once()
            ->with(1, 10, ['search' => 'test'])
            ->andReturn($expectedResult);

        $result = $this->service->listUsers(1, 10, ['search' => 'test']);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * 測試取得單一使用者成功並移除 password_hash.
     */
    public function testGetUserSuccess(): void
    {
        $userData = [
            'id'            => 1,
            'username'      => 'alice',
            'email'         => 'alice@example.com',
            'password_hash' => '$argon2id$hashedpassword',
            'roles'         => [['id' => 1, 'name' => 'admin']],
        ];

        $this->userRepository
            ->shouldReceive('findByIdWithRoles')
            ->once()
            ->with(1)
            ->andReturn($userData);

        $result = $this->service->getUser(1);
        $this->assertSame('alice', $result['username']);
        $this->assertArrayNotHasKey('password_hash', $result);
    }

    /**
     * 測試取得不存在的使用者應拋出 NotFoundException.
     */
    public function testGetUserNotFoundThrowsException(): void
    {
        $this->userRepository
            ->shouldReceive('findByIdWithRoles')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('使用者不存在');

        $this->service->getUser(999);
    }

    /**
     * 測試建立使用者成功（含指定角色）.
     */
    public function testCreateUserSuccess(): void
    {
        $dto = new CreateUserDTO(
            username: 'newuser',
            email: 'newuser@example.com',
            password: 'P@ssw0rd#9Km$2',
            roleIds: [1, 2],
        );

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->once()
            ->with('newuser')
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('newuser@example.com')
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['username'] === 'newuser'
                    && $data['email'] === 'newuser@example.com'
                    && !empty($data['password']);
            }))
            ->andReturn(['id' => 10, 'username' => 'newuser']);

        $this->userRepository
            ->shouldReceive('setUserRoles')
            ->once()
            ->with(10, [1, 2])
            ->andReturn(true);

        $this->userRepository
            ->shouldReceive('findByIdWithRoles')
            ->once()
            ->with(10)
            ->andReturn([
                'id'            => 10,
                'username'      => 'newuser',
                'email'         => 'newuser@example.com',
                'password_hash' => 'hash',
                'roles'         => [],
            ]);

        $result = $this->service->createUser($dto);
        $this->assertSame(10, $result['id']);
        $this->assertArrayNotHasKey('password_hash', $result);
    }

    /**
     * 測試建立使用者時名稱已被使用拋出 ValidationException.
     */
    public function testCreateUserUsernameTakenThrowsException(): void
    {
        $dto = new CreateUserDTO(
            username: 'existing',
            email: 'unique@example.com',
            password: 'P@ssw0rd#9Km$2',
        );

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->once()
            ->with('existing')
            ->andReturn(['id' => 2, 'username' => 'existing']);

        $this->expectException(ValidationException::class);
        $this->service->createUser($dto);
    }

    /**
     * 測試建立使用者時 Email 已被使用拋出 ValidationException.
     */
    public function testCreateUserEmailTakenThrowsException(): void
    {
        $dto = new CreateUserDTO(
            username: 'uniqueuser',
            email: 'taken@example.com',
            password: 'P@ssw0rd#9Km$2',
        );

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->once()
            ->with('uniqueuser')
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('taken@example.com')
            ->andReturn(['id' => 3, 'email' => 'taken@example.com']);

        $this->expectException(ValidationException::class);
        $this->service->createUser($dto);
    }

    /**
     * 測試更新使用者成功
     */
    public function testUpdateUserSuccess(): void
    {
        $dto = new UpdateUserDTO(
            username: 'updatedname',
            email: 'updated@example.com',
            password: 'N3wP@ssw0rd!#9Km',
            roleIds: [2],
        );

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'username' => 'oldname', 'email' => 'old@example.com']);

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->once()
            ->with('updatedname')
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('updated@example.com')
            ->andReturn(['id' => 1, 'email' => 'updated@example.com']); // 相同使用者

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->with('1', Mockery::on(function ($data) {
                return $data['username'] === 'updatedname'
                    && $data['email'] === 'updated@example.com'
                    && !empty($data['password']);
            }))
            ->andReturn(['id' => 1]);

        $this->userRepository
            ->shouldReceive('setUserRoles')
            ->once()
            ->with(1, [2])
            ->andReturn(true);

        $this->userRepository
            ->shouldReceive('findByIdWithRoles')
            ->once()
            ->with(1)
            ->andReturn([
                'id'       => 1,
                'username' => 'updatedname',
                'email'    => 'updated@example.com',
                'roles'    => [],
            ]);

        $result = $this->service->updateUser(1, $dto);
        $this->assertSame('updatedname', $result['username']);
    }

    /**
     * 測試更新不存在的使用者拋出 NotFoundException.
     */
    public function testUpdateUserNotFoundThrowsException(): void
    {
        $dto = new UpdateUserDTO(username: 'test');

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->updateUser(999, $dto);
    }

    /**
     * 測試更新使用者名稱已被其他人使用拋出 ValidationException.
     */
    public function testUpdateUserUsernameTakenThrowsException(): void
    {
        $dto = new UpdateUserDTO(username: 'otheruser');

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'username' => 'oldname']);

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->once()
            ->with('otheruser')
            ->andReturn(['id' => 2, 'username' => 'otheruser']);

        $this->expectException(ValidationException::class);
        $this->service->updateUser(1, $dto);
    }

    /**
     * 測試更新使用者 Email 已被其他人使用拋出 ValidationException.
     */
    public function testUpdateUserEmailTakenThrowsException(): void
    {
        $dto = new UpdateUserDTO(email: 'other@example.com');

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'username' => 'oldname', 'email' => 'old@example.com']);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('other@example.com')
            ->andReturn(['id' => 3, 'email' => 'other@example.com']);

        $this->expectException(ValidationException::class);
        $this->service->updateUser(1, $dto);
    }

    /**
     * 測試刪除使用者成功
     */
    public function testDeleteUserSuccess(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1]);

        $this->userRepository
            ->shouldReceive('delete')
            ->once()
            ->with('1')
            ->andReturn(true);

        $result = $this->service->deleteUser(1);
        $this->assertTrue($result);
    }

    /**
     * 測試刪除不存在的使用者拋出 NotFoundException.
     */
    public function testDeleteUserNotFoundThrowsException(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->deleteUser(999);
    }

    /**
     * 測試指派角色.
     */
    public function testAssignRolesSuccess(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1]);

        $this->userRepository
            ->shouldReceive('setUserRoles')
            ->once()
            ->with(1, [1, 2])
            ->andReturn(true);

        $result = $this->service->assignRoles(1, [1, 2]);
        $this->assertTrue($result);
    }

    /**
     * 測試指派角色給不存在的使用者.
     */
    public function testAssignRolesUserNotFound(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->assignRoles(999, [1]);
    }

    /**
     * 測試取得使用者的角色列表.
     */
    public function testGetUserRoles(): void
    {
        $this->userRepository
            ->shouldReceive('getUserRoleIds')
            ->once()
            ->with(1)
            ->andReturn([1, 3]);

        $result = $this->service->getUserRoles(1);
        $this->assertSame([1, 3], $result);
    }

    /**
     * 測試啟用使用者.
     */
    public function testActivateUser(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'is_active' => false]);

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->with('1', ['is_active' => true])
            ->andReturn(['id' => 1, 'is_active' => true]);

        $this->userRepository
            ->shouldReceive('findByIdWithRoles')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'is_active' => true, 'roles' => []]);

        $result = $this->service->activateUser(1);
        $this->assertTrue($result['is_active']);
    }

    /**
     * 測試啟用不存在的使用者拋出 NotFoundException.
     */
    public function testActivateUserNotFound(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->activateUser(999);
    }

    /**
     * 測試停用使用者.
     */
    public function testDeactivateUser(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'is_active' => true]);

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->with('1', ['is_active' => false])
            ->andReturn(['id' => 1, 'is_active' => false]);

        $this->userRepository
            ->shouldReceive('findByIdWithRoles')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'is_active' => false, 'roles' => []]);

        $result = $this->service->deactivateUser(1);
        $this->assertFalse($result['is_active']);
    }

    /**
     * 測試停用不存在的使用者拋出 NotFoundException.
     */
    public function testDeactivateUserNotFound(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->deactivateUser(999);
    }

    /**
     * 測試重設密碼成功
     */
    public function testResetPasswordSuccess(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1]);

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->with('1', Mockery::on(fn($data) => !empty($data['password'])))
            ->andReturn(['id' => 1]);

        $result = $this->service->resetPassword(1, 'NewValidPass123!');
        $this->assertTrue($result);
    }

    /**
     * 測試重設密碼但使用者不存在.
     */
    public function testResetPasswordUserNotFound(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->resetPassword(999, 'NewValidPass123!');
    }

    /**
     * 測試重設密碼無效拋出 ValidationException.
     */
    public function testResetPasswordInvalidFormat(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1]);

        $this->expectException(ValidationException::class);
        $this->service->resetPassword(1, 'short');
    }

    /**
     * 測試變更密碼成功
     */
    public function testChangePasswordSuccess(): void
    {
        $oldHash = password_hash('OldPassword123!', PASSWORD_ARGON2ID);

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'password_hash' => $oldHash]);

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->with('1', Mockery::on(fn($data) => !empty($data['password'])))
            ->andReturn(['id' => 1]);

        $result = $this->service->changePassword(1, 'OldPassword123!', 'NewPassword456!');
        $this->assertTrue($result);
    }

    /**
     * 測試變更密碼使用者不存在.
     */
    public function testChangePasswordUserNotFound(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->service->changePassword(999, 'OldPassword123!', 'NewPassword456!');
    }

    /**
     * 測試變更密碼時舊密碼錯誤.
     */
    public function testChangePasswordWrongCurrentPassword(): void
    {
        $oldHash = password_hash('CorrectOld123!', PASSWORD_ARGON2ID);

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'password_hash' => $oldHash]);

        $this->expectException(ValidationException::class);
        $this->service->changePassword(1, 'WrongOld123!', 'NewPassword456!');
    }

    /**
     * 測試變更密碼時新密碼格式不合法.
     */
    public function testChangePasswordInvalidNewPassword(): void
    {
        $oldHash = password_hash('CorrectOld123!', PASSWORD_ARGON2ID);

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'password_hash' => $oldHash]);

        $this->expectException(ValidationException::class);
        $this->service->changePassword(1, 'CorrectOld123!', 'invalid');
    }
}
