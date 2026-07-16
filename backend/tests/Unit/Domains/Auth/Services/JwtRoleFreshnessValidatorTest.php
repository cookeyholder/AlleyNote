<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\Services\JwtRoleFreshnessValidator;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * JwtRoleFreshnessValidator 單元測試.
 */
final class JwtRoleFreshnessValidatorTest extends UnitTestCase
{
    private UserRepositoryInterface|MockInterface $userRepository;

    private JwtRoleFreshnessValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->validator = new JwtRoleFreshnessValidator($this->userRepository);
    }

    public function testValidateReturnsTrueWhenRoleUpdatedAtIsNull(): void
    {
        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'username' => 'test']);

        $result = $this->validator->validate(1, 1000);

        $this->assertTrue($result);
    }

    public function testValidateReturnsTrueWhenTokenIatAfterRoleUpdate(): void
    {
        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'role_updated_at' => '2024-01-01 00:00:00']);

        // iat = 2025-01-01 (later than 2024-01-01)
        $result = $this->validator->validate(1, 1735689600);

        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseWhenTokenIatBeforeRoleUpdate(): void
    {
        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'role_updated_at' => '2025-06-01 00:00:00']);

        // iat = 2025-01-01 (before 2025-06-01)
        $result = $this->validator->validate(1, 1735689600);

        $this->assertFalse($result);
    }

    public function testValidateReturnsTrueWhenTokenIatEqualsRoleUpdate(): void
    {
        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'role_updated_at' => '2025-01-01 00:00:00']);

        $result = $this->validator->validate(1, 1735689600);

        $this->assertTrue($result);
    }

    public function testValidateReturnsTrueWhenUserNotFound(): void
    {
        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->validator->validate(999, 1000);

        $this->assertTrue($result);
    }

    public function testGetUserRoleUpdatedAtReturnsNullWhenNoColumn(): void
    {
        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'username' => 'test']);

        $result = $this->validator->getUserRoleUpdatedAt(1);

        $this->assertNull($result);
    }

    public function testGetUserRoleUpdatedAtReturnsNullWhenUserNotFound(): void
    {
        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $result = $this->validator->getUserRoleUpdatedAt(999);

        $this->assertNull($result);
    }

    public function testGetUserRoleUpdatedAtReturnsTimestampForStringDate(): void
    {
        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'role_updated_at' => '2025-01-01 00:00:00']);

        $result = $this->validator->getUserRoleUpdatedAt(1);

        $this->assertSame(1735689600, $result);
    }

    public function testGetUserRoleUpdatedAtReturnsTimestampForIntTimestamp(): void
    {
        $this->userRepository->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'role_updated_at' => 1735689600]);

        $result = $this->validator->getUserRoleUpdatedAt(1);

        $this->assertSame(1735689600, $result);
    }
}
