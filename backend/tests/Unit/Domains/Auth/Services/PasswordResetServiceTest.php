<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Contracts\PasswordResetTokenRepositoryInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\DTOs\ResetPasswordDTO;
use App\Domains\Auth\Entities\PasswordResetToken;
use App\Domains\Auth\Services\PasswordManagementService;
use App\Domains\Auth\Services\PasswordResetService;
use App\Domains\Auth\ValueObjects\PasswordResetResult;
use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\Validator;
use DateInterval;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

final class PasswordResetServiceTest extends TestCase
{
    private UserRepositoryInterface&MockInterface $userRepository;

    private PasswordResetTokenRepositoryInterface&MockInterface $tokenRepository;

    private PasswordManagementService&MockInterface $passwordManagementService;

    private ActivityLoggingServiceInterface&MockInterface $activityLogger;

    private PasswordResetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->tokenRepository = Mockery::mock(PasswordResetTokenRepositoryInterface::class);
        $this->passwordManagementService = Mockery::mock(PasswordManagementService::class);
        $this->activityLogger = Mockery::mock(ActivityLoggingServiceInterface::class);

        $this->service = new PasswordResetService(
            $this->userRepository,
            $this->tokenRepository,
            $this->passwordManagementService,
            $this->activityLogger,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRequestResetGeneratesTokenForExistingUser(): void
    {
        $userId = 42;
        $email = 'user@example.com';
        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn(['id' => $userId, 'email' => $email]);

        $this->tokenRepository
            ->shouldReceive('invalidateForUser')
            ->once()
            ->with($userId);

        $this->tokenRepository
            ->shouldReceive('cleanupExpired')
            ->once()
            ->with(Mockery::type(DateTimeImmutable::class));

        $this->tokenRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (PasswordResetToken $token) use ($userId): bool {
                return $token->getUserId() === $userId
                    && preg_match('/^[a-f0-9]{64}$/', $token->getTokenHash()) === 1
                    && $token->getExpiresAt() > new DateTimeImmutable();
            }))
            ->andReturnUsing(function (PasswordResetToken $token): PasswordResetToken {
                return $token->withPersistenceState(10);
            });

        $this->activityLogger
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(CreateActivityLogDTO::class));

        $result = $this->service->requestReset($email, '127.0.0.1', 'PHPUnit');

        $this->assertInstanceOf(PasswordResetResult::class, $result);
        $this->assertTrue($result->userFound());
        $this->assertNotNull($result->getPlainToken());
        $this->assertNotNull($result->getExpiresAt());
        $this->assertSame(64, strlen((string) $result->getPlainToken()));
    }

    public function testRequestResetReturnsNeutralResultWhenUserMissing(): void
    {
        $email = 'missing@example.com';
        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn(null);

        $this->tokenRepository->shouldNotReceive('create');
        $this->activityLogger->shouldNotReceive('log');

        $result = $this->service->requestReset($email, '127.0.0.1', 'PHPUnit');

        $this->assertFalse($result->userFound());
        $this->assertNull($result->getPlainToken());
        $this->assertNull($result->getExpiresAt());
    }

    public function testResetPasswordWithValidToken(): void
    {
        $tokenPlain = 'valid-reset-token-1234567890';
        $tokenHash = hash('sha256', $tokenPlain);
        $userId = 99;

        $issuedAt = new DateTimeImmutable();
        $expiresAt = $issuedAt->add(new DateInterval('PT3600S'));
        $storedToken = new PasswordResetToken(
            id: 15,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
            createdAt: $issuedAt,
        );

        $this->tokenRepository
            ->shouldReceive('findValidByHash')
            ->once()
            ->with($tokenHash, Mockery::type(DateTimeImmutable::class))
            ->andReturn($storedToken);

        $this->passwordManagementService
            ->shouldReceive('resetPassword')
            ->once()
            ->with($userId, 'Password123!');

        $this->tokenRepository
            ->shouldReceive('markAsUsed')
            ->once()
            ->with(Mockery::on(function (PasswordResetToken $token) use ($userId): bool {
                return $token->getUserId() === $userId && $token->isUsed();
            }));

        $this->activityLogger
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(CreateActivityLogDTO::class));

        $validator = new Validator();
        $dto = new ResetPasswordDTO($validator, [
            'token' => $tokenPlain,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->service->resetPassword($dto, '127.0.0.1', 'PHPUnit');

        $this->addToAssertionCount(1);
    }

    public function testResetPasswordThrowsWhenTokenInvalid(): void
    {
        $tokenPlain = 'invalid-token-1234567890';
        $tokenHash = hash('sha256', $tokenPlain);

        $this->tokenRepository
            ->shouldReceive('findValidByHash')
            ->once()
            ->with($tokenHash, Mockery::type(DateTimeImmutable::class))
            ->andReturn(null);

        $validator = new Validator();
        $dto = new ResetPasswordDTO($validator, [
            'token' => $tokenPlain,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('密碼重設連結無效或已過期');

        $this->service->resetPassword($dto, '127.0.0.1', 'PHPUnit');
    }
}
