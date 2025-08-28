<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use AlleyNote\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use AlleyNote\Domains\Auth\Services\RefreshTokenService;
use AlleyNote\Domains\Auth\ValueObjects\JwtPayload;
use DateTimeImmutable;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RefreshTokenServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private RefreshTokenService $service;

    private JwtTokenServiceInterface&MockInterface $jwtTokenService;

    private RefreshTokenRepositoryInterface&MockInterface $refreshTokenRepository;

    private TokenBlacklistRepositoryInterface&MockInterface $blacklistRepository;

    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtTokenService = Mockery::mock(JwtTokenServiceInterface::class);
        $this->refreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $this->blacklistRepository = Mockery::mock(TokenBlacklistRepositoryInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->service = new RefreshTokenService(
            $this->jwtTokenService,
            $this->refreshTokenRepository,
            $this->blacklistRepository,
            $this->logger,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(RefreshTokenService::class, $this->service);
    }

    public function testCleanupExpiredTokensSuccess(): void
    {
        $this->refreshTokenRepository
            ->shouldReceive('cleanup')
            ->once()
            ->andReturn(5);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Expired refresh tokens cleaned up', ['cleaned_count' => 5]);

        $result = $this->service->cleanupExpiredTokens();

        $this->assertSame(5, $result);
    }

    public function testCleanupExpiredTokensException(): void
    {
        $this->refreshTokenRepository
            ->shouldReceive('cleanup')
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to cleanup expired tokens', ['error' => 'Database error']);

        $result = $this->service->cleanupExpiredTokens();

        $this->assertSame(0, $result);
    }

    public function testGetUserTokenStatsSuccess(): void
    {
        $userId = 123;
        $mockTokens = [
            ['device_id' => 'device-1', 'status' => 'active'],
            ['device_id' => 'device-1', 'status' => 'active'],
            ['device_id' => 'device-2', 'status' => 'expired'],
        ];

        $this->refreshTokenRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($mockTokens);

        $result = $this->service->getUserTokenStats($userId);

        $expected = [
            'total' => 3,
            'by_device' => [
                'device-1' => 2,
                'device-2' => 1,
            ],
            'by_status' => [
                'active' => 2,
                'expired' => 1,
            ],
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetUserTokenStatsException(): void
    {
        $userId = 123;

        $this->refreshTokenRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andThrow(new Exception('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to get user token stats', Mockery::any());

        $result = $this->service->getUserTokenStats($userId);

        $expected = [
            'total' => 0,
            'by_device' => [],
            'by_status' => [],
        ];

        $this->assertSame($expected, $result);
    }

    public function testRevokeTokenSuccess(): void
    {
        $refreshToken = 'test-refresh-token';
        $payload = new JwtPayload(
            'jwt-token-id-123456789',
            '123',
            'alleynote',
            ['alleynote-web'],
            new DateTimeImmutable('-1 hour'),
            new DateTimeImmutable('+7 days'),
        );

        $this->jwtTokenService
            ->shouldReceive('extractPayload')
            ->times(2) // 可能在 addToBlacklist 中也會被呼叫
            ->with($refreshToken)
            ->andReturn($payload);

        $this->refreshTokenRepository
            ->shouldReceive('revoke')
            ->once()
            ->with('jwt-token-id-123456789', 'manual_revocation')
            ->andReturn(true);

        $this->blacklistRepository
            ->shouldReceive('addToBlacklist')
            ->once()
            ->andReturn(true);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Refresh token revoked', [
                'jti' => 'jwt-token-id-123456789',
                'reason' => 'manual_revocation',
            ]);

        $result = $this->service->revokeToken($refreshToken);

        $this->assertTrue($result);
    }

    public function testRevokeTokenException(): void
    {
        $refreshToken = 'invalid-token';

        $this->jwtTokenService
            ->shouldReceive('extractPayload')
            ->once()
            ->with($refreshToken)
            ->andThrow(new Exception('Invalid token'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to revoke refresh token', [
                'error' => 'Invalid token',
                'reason' => 'manual_revocation',
            ]);

        $result = $this->service->revokeToken($refreshToken);

        $this->assertFalse($result);
    }
}
