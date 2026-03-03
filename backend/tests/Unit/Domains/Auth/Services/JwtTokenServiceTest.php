<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Contracts\JwtProviderInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\Services\JwtTokenService;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\ValueObjects\TokenPair;
use App\Shared\Config\JwtConfig;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * JwtTokenService 單元測試.
 */
final class JwtTokenServiceTest extends UnitTestCase
{
    private JwtTokenService $service;

    private JwtProviderInterface|MockInterface $mockJwtProvider;

    private RefreshTokenRepositoryInterface|MockInterface $mockRefreshTokenRepository;

    private TokenBlacklistRepositoryInterface|MockInterface $mockBlacklistRepository;

    private JwtConfig $config;

    private DeviceInfo $deviceInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockJwtProvider = Mockery::mock(JwtProviderInterface::class);
        $this->mockRefreshTokenRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $this->mockBlacklistRepository = Mockery::mock(TokenBlacklistRepositoryInterface::class);
        $this->config = new JwtConfig();

        $this->service = new JwtTokenService(
            $this->mockJwtProvider,
            $this->mockRefreshTokenRepository,
            $this->mockBlacklistRepository,
            $this->config,
        );

        $this->deviceInfo = new DeviceInfo('test-device', 'Chrome', '127.0.0.1', 'UA', 'Linux', 'Chrome');
    }

    #[Test]
    public function testGenerateTokenPairSuccessfully(): void
    {
        // Arrange
        $userId = 123;
        $accessToken = 'access-token';
        $refreshToken = 'refresh-token';

        $this->mockJwtProvider->shouldReceive('generateAccessToken')->once()->andReturn($accessToken);
        $this->mockJwtProvider->shouldReceive('generateRefreshToken')->once()->andReturn($refreshToken);

        $this->mockJwtProvider->shouldReceive('parseTokenUnsafe')
            ->twice()
            ->andReturn([
                'jti' => 'test-jti',
                'sub' => (string) $userId,
                'iss' => 'test-issuer',
                'aud' => 'test-audience',
                'iat' => time(),
                'exp' => time() + 3600,
            ]);

        $this->mockRefreshTokenRepository->shouldReceive('create')->once()->andReturn(true);

        // Act
        $result = $this->service->generateTokenPair($userId, $this->deviceInfo);

        // Assert
        $this->assertInstanceOf(TokenPair::class, $result);
        $this->assertSame($accessToken, $result->getAccessToken());
    }
}
