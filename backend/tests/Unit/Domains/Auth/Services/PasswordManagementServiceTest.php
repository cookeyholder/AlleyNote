<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\Services\PasswordManagementService;
use App\Shared\Exceptions\ValidationException;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * 密碼管理服務單元測試.
 */
final class PasswordManagementServiceTest extends UnitTestCase
{
    private UserRepository&MockInterface $userRepository;

    private PasswordSecurityServiceInterface&MockInterface $passwordService;

    private PasswordManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->passwordService = Mockery::mock(PasswordSecurityServiceInterface::class);
        $this->service = new PasswordManagementService(
            $this->userRepository,
            $this->passwordService,
        );
    }

    /**
     * 測試變更密碼成功
     */
    public function testChangePasswordSuccess(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'password' => 'hashed_old_password']);

        $this->passwordService
            ->shouldReceive('verifyPassword')
            ->once()
            ->with('OldPassword1!', 'hashed_old_password')
            ->andReturn(true);

        $this->passwordService
            ->shouldReceive('validatePassword')
            ->once()
            ->with('NewSecurePassword1!')
            ->andReturnNull();

        $this->userRepository
            ->shouldReceive('updatePassword')
            ->once()
            ->with(1, 'NewSecurePassword1!')
            ->andReturn(true);

        $result = $this->service->changePassword(1, 'OldPassword1!', 'NewSecurePassword1!');
        $this->assertTrue($result);
    }

    /**
     * 測試變更密碼時使用者不存在拋出 InvalidArgumentException.
     */
    public function testChangePasswordUserNotFound(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('找不到指定的使用者');
        $this->service->changePassword(999, 'OldPassword1!', 'NewSecurePassword1!');
    }

    /**
     * 測試變更密碼時目前密碼錯誤拋出 InvalidArgumentException.
     */
    public function testChangePasswordWrongCurrentPassword(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'password' => 'hashed_old_password']);

        $this->passwordService
            ->shouldReceive('verifyPassword')
            ->once()
            ->with('WrongPassword!', 'hashed_old_password')
            ->andReturn(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('目前密碼不正確');
        $this->service->changePassword(1, 'WrongPassword!', 'NewSecurePassword1!');
    }

    /**
     * 測試變更密碼時新密碼安全性驗證失敗拋出 ValidationException.
     */
    public function testChangePasswordValidationFails(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'password' => 'hashed_old_password']);

        $this->passwordService
            ->shouldReceive('verifyPassword')
            ->once()
            ->with('OldPassword1!', 'hashed_old_password')
            ->andReturn(true);

        $this->passwordService
            ->shouldReceive('validatePassword')
            ->once()
            ->with('weak')
            ->andThrow(ValidationException::fromSingleError('password', '密碼強度不足'));

        $this->expectException(ValidationException::class);
        $this->service->changePassword(1, 'OldPassword1!', 'weak');
    }

    /**
     * 測試重設密碼成功
     */
    public function testResetPasswordSuccess(): void
    {
        $this->passwordService
            ->shouldReceive('validatePassword')
            ->once()
            ->with('ResetPassword123!')
            ->andReturnNull();

        $this->userRepository
            ->shouldReceive('updatePassword')
            ->once()
            ->with(1, 'ResetPassword123!')
            ->andReturn(true);

        $result = $this->service->resetPassword(1, 'ResetPassword123!');
        $this->assertTrue($result);
    }

    /**
     * 測試檢查密碼強度.
     */
    public function testCheckPasswordStrength(): void
    {
        $strengthData = ['score' => 85, 'strength' => 'very_strong', 'feedback' => []];

        $this->passwordService
            ->shouldReceive('calculatePasswordStrength')
            ->once()
            ->with('StrongPass123!')
            ->andReturn($strengthData);

        $result = $this->service->checkPasswordStrength('StrongPass123!');
        $this->assertSame($strengthData, $result);
    }

    /**
     * 測試生成安全密碼
     */
    public function testGenerateSecurePassword(): void
    {
        $this->passwordService
            ->shouldReceive('generateSecurePassword')
            ->once()
            ->with(20)
            ->andReturn('generated_secure_password');

        $result = $this->service->generateSecurePassword(20);
        $this->assertSame('generated_secure_password', $result);
    }

    /**
     * 測試檢查密碼是否需要重新雜湊.
     */
    public function testNeedsRehash(): void
    {
        $this->passwordService
            ->shouldReceive('needsRehash')
            ->once()
            ->with('old_hash_string')
            ->andReturn(true);

        $result = $this->service->needsRehash('old_hash_string');
        $this->assertTrue($result);
    }

    /**
     * 測試升級密碼雜湊成功
     */
    public function testUpgradePasswordHashSuccess(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'password' => 'old_hash']);

        $this->passwordService
            ->shouldReceive('verifyPassword')
            ->once()
            ->with('plain_password', 'old_hash')
            ->andReturn(true);

        $this->passwordService
            ->shouldReceive('needsRehash')
            ->once()
            ->with('old_hash')
            ->andReturn(true);

        $this->userRepository
            ->shouldReceive('updatePassword')
            ->once()
            ->with(1, 'plain_password')
            ->andReturn(true);

        $result = $this->service->upgradePasswordHash(1, 'plain_password');
        $this->assertTrue($result);
    }

    /**
     * 測試升級密碼雜湊時使用者不存在返回 false.
     */
    public function testUpgradePasswordHashUserNotFound(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->service->upgradePasswordHash(999, 'plain_password');
        $this->assertFalse($result);
    }

    /**
     * 測試升級密碼雜湊時密碼驗證失敗或無需重新雜湊返回 false.
     */
    public function testUpgradePasswordHashNotNeededOrWrongPassword(): void
    {
        // 密碼不正確
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with('1')
            ->andReturn(['id' => 1, 'password' => 'current_hash']);

        $this->passwordService
            ->shouldReceive('verifyPassword')
            ->once()
            ->with('wrong_password', 'current_hash')
            ->andReturn(false);

        $result = $this->service->upgradePasswordHash(1, 'wrong_password');
        $this->assertFalse($result);

        // 密碼正確但不需要重新雜湊
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with('1')
            ->andReturn(['id' => 1, 'password' => 'current_hash']);

        $this->passwordService
            ->shouldReceive('verifyPassword')
            ->once()
            ->with('correct_password', 'current_hash')
            ->andReturn(true);

        $this->passwordService
            ->shouldReceive('needsRehash')
            ->once()
            ->with('current_hash')
            ->andReturn(false);

        $result = $this->service->upgradePasswordHash(1, 'correct_password');
        $this->assertFalse($result);
    }
}
