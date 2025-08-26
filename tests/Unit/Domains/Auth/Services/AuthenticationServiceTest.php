<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use AlleyNote\Domains\Auth\DTOs\LoginRequestDTO;
use AlleyNote\Domains\Auth\DTOs\LoginResponseDTO;
use AlleyNote\Domains\Auth\DTOs\LogoutRequestDTO;
use AlleyNote\Domains\Auth\DTOs\RefreshRequestDTO;
use AlleyNote\Domains\Auth\Exceptions\AuthenticationException;
use AlleyNote\Domains\Auth\Exceptions\InvalidTokenException;
use AlleyNote\Domains\Auth\Exceptions\TokenExpiredException;
use AlleyNote\Domains\Auth\Services\AuthenticationService;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use AlleyNote\Domains\Auth\ValueObjects\JwtPayload;
use AlleyNote\Domains\Auth\ValueObjects\TokenPair;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * AuthenticationService 單元測試.
 *
 * 測試 AuthenticationService 的所有方法，包括正常流程和異常情況。
 * 涵蓋登入、登出、權杖管理等核心認證功能。
 */
final class AuthenticationServiceTest extends TestCase
{
    private AuthenticationService $authenticationService;

    /** @var JwtTokenServiceInterface&MockObject */
    private JwtTokenServiceInterface $jwtTokenService;

    /** @var RefreshTokenRepositoryInterface&MockObject */
    private RefreshTokenRepositoryInterface $refreshTokenRepository;

    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $userRepository;

    private DeviceInfo $deviceInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtTokenService = $this->createMock(JwtTokenServiceInterface::class);
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->authenticationService = new AuthenticationService(
            $this->jwtTokenService,
            $this->refreshTokenRepository,
            $this->userRepository,
        );

        $this->deviceInfo = new DeviceInfo(
            deviceId: 'test-device-id',
            deviceName: 'Test Device',
            userAgent: 'Test User Agent',
            ipAddress: '127.0.0.1',
            platform: 'Other',
        );
    }

    #[Test]
    public function login_成功登入_應該返回登入回應(): void
    {
        // Arrange
        $request = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
            rememberMe: false,
            scopes: ['read', 'write'],
        );

        $userData = [
            'id' => 1,
            'email' => 'test@example.com',
            'deleted_at' => null,
        ];

        $tokenPair = $this->createMockTokenPair();
        $payload = $this->createMockJwtPayload();

        // Setup mocks
        $this->userRepository
            ->expects($this->once())
            ->method('validateCredentials')
            ->with('test@example.com', 'password123')
            ->willReturn($userData);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanup')
            ->willReturn(0);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with(1, false)
            ->willReturn([]);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('generateTokenPair')
            ->with(1, $this->deviceInfo, [
                'email' => 'test@example.com',
                'scopes' => ['read', 'write'],
            ])
            ->willReturn($tokenPair);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->with($tokenPair->getRefreshToken())
            ->willReturn($payload);

        $this->userRepository
            ->expects($this->once())
            ->method('updateLastLogin')
            ->with(1)
            ->willReturn(true);

        // Act
        $response = $this->authenticationService->login($request, $this->deviceInfo);

        // Assert
        $this->assertSame($tokenPair, $response->tokens);
        $this->assertSame(1, $response->userId);
        $this->assertSame('test@example.com', $response->userEmail);
        $this->assertSame('test-jti', $response->sessionId);
        $this->assertSame(['read', 'write'], $response->permissions);
    }

    #[Test]
    public function login_使用者不存在_應該拋出認證例外(): void
    {
        // Arrange
        $request = new LoginRequestDTO(
            email: 'nonexistent@example.com',
            password: 'password123',
        );

        $this->userRepository
            ->expects($this->once())
            ->method('validateCredentials')
            ->with('nonexistent@example.com', 'password123')
            ->willReturn(null);

        // Act & Assert
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials provided');

        $this->authenticationService->login($request, $this->deviceInfo);
    }

    #[Test]
    public function login_使用者已被軟刪除_應該拋出認證例外(): void
    {
        // Arrange
        $request = new LoginRequestDTO(
            email: 'deleted@example.com',
            password: 'password123',
        );

        $userData = [
            'id' => 1,
            'email' => 'deleted@example.com',
            'deleted_at' => '2023-01-01 00:00:00',
        ];

        $this->userRepository
            ->expects($this->once())
            ->method('validateCredentials')
            ->with('deleted@example.com', 'password123')
            ->willReturn($userData);

        // Act & Assert
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User account has been deactivated');

        $this->authenticationService->login($request, $this->deviceInfo);
    }

    #[Test]
    public function login_超過最大Token數量限制_應該撤銷最舊的Token(): void
    {
        // Arrange
        $request = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
        );

        $userData = [
            'id' => 1,
            'email' => 'test@example.com',
            'deleted_at' => null,
        ];

        $existingTokens = array_fill(0, 50, ['jti' => 'old-token']);
        $tokenPair = $this->createMockTokenPair();
        $payload = $this->createMockJwtPayload();

        // Setup mocks
        $this->userRepository
            ->expects($this->once())
            ->method('validateCredentials')
            ->willReturn($userData);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanup')
            ->willReturn(0);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with(1, false)
            ->willReturn($existingTokens);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revoke')
            ->with('old-token', 'max_tokens_exceeded')
            ->willReturn(true);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('generateTokenPair')
            ->willReturn($tokenPair);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->willReturn($payload);

        $this->userRepository
            ->expects($this->once())
            ->method('updateLastLogin')
            ->willReturn(true);

        // Act
        $response = $this->authenticationService->login($request, $this->deviceInfo);

        // Assert
        $this->assertInstanceOf(LoginResponseDTO::class, $response);
    }

    #[Test]
    public function refresh_成功重新整理_應該返回新的Token對(): void
    {
        // Arrange
        $request = new RefreshRequestDTO(
            refreshToken: 'old-refresh-token',
            scopes: ['read'],
        );

        $newTokenPair = $this->createMockTokenPair();
        $oldPayload = $this->createMockJwtPayload();
        $newPayload = $this->createMockJwtPayload('new-jti');

        // Setup mocks
        $this->jwtTokenService
            ->expects($this->once())
            ->method('refreshTokens')
            ->with('old-refresh-token', $this->deviceInfo)
            ->willReturn($newTokenPair);

        $this->jwtTokenService
            ->expects($this->exactly(2))
            ->method('extractPayload')
            ->willReturnOnConsecutiveCalls($newPayload, $oldPayload);

        // Act
        $response = $this->authenticationService->refresh($request, $this->deviceInfo);

        // Assert
        $this->assertSame($newTokenPair, $response->tokens);
        $this->assertSame(1, $response->userId);
        $this->assertSame('new-jti', $response->sessionId);
        $this->assertSame(['read'], $response->permissions);
    }

    #[Test]
    public function refresh_無效的RefreshToken_應該拋出認證例外(): void
    {
        // Arrange
        $request = new RefreshRequestDTO(
            refreshToken: 'invalid-refresh-token',
        );

        $this->jwtTokenService
            ->expects($this->once())
            ->method('refreshTokens')
            ->with('invalid-refresh-token', $this->deviceInfo)
            ->willThrowException(new InvalidTokenException('Invalid token'));

        // Act & Assert
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $this->authenticationService->refresh($request, $this->deviceInfo);
    }

    #[Test]
    public function refresh_過期的RefreshToken_應該拋出認證例外(): void
    {
        // Arrange
        $request = new RefreshRequestDTO(
            refreshToken: 'expired-refresh-token',
        );

        $this->jwtTokenService
            ->expects($this->once())
            ->method('refreshTokens')
            ->with('expired-refresh-token', $this->deviceInfo)
            ->willThrowException(new TokenExpiredException('Token expired'));

        // Act & Assert
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $this->authenticationService->refresh($request, $this->deviceInfo);
    }

    #[Test]
    public function logout_單一Token登出_應該成功撤銷Token(): void
    {
        // Arrange
        $request = new LogoutRequestDTO(
            accessToken: 'access-token',
            refreshToken: 'refresh-token',
            revokeAllTokens: false,
        );

        $payload = $this->createMockJwtPayload();

        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->with('refresh-token')
            ->willReturn($payload);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revoke')
            ->with('test-jti', 'user_logout')
            ->willReturn(true);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('revokeToken')
            ->with('access-token', 'user_logout')
            ->willReturn(true);

        // Act
        $result = $this->authenticationService->logout($request);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function logout_全部Token登出_應該撤銷使用者所有Token(): void
    {
        // Arrange
        $request = new LogoutRequestDTO(
            accessToken: 'access-token',
            refreshToken: 'refresh-token',
            revokeAllTokens: true,
        );

        $payload = $this->createMockJwtPayload();

        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->with('refresh-token')
            ->willReturn($payload);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revokeAllByUserId')
            ->with(1, 'logout_all')
            ->willReturn(5);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('revokeToken')
            ->with('access-token', 'user_logout')
            ->willReturn(true);

        // Act
        $result = $this->authenticationService->logout($request);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function logout_沒有RefreshToken_應該仍然成功(): void
    {
        // Arrange
        $request = new LogoutRequestDTO(
            accessToken: 'access-token',
            refreshToken: null,
        );

        $this->jwtTokenService
            ->expects($this->once())
            ->method('revokeToken')
            ->with('access-token', 'user_logout')
            ->willReturn(true);

        // Act
        $result = $this->authenticationService->logout($request);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function validateAccessToken_有效的Token_應該返回true(): void
    {
        // Arrange
        $accessToken = 'valid-access-token';
        $payload = $this->createMockJwtPayload();

        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateAccessToken')
            ->with($accessToken)
            ->willReturn($payload);

        // Act
        $result = $this->authenticationService->validateAccessToken($accessToken);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function validateAccessToken_無效的Token_應該返回false(): void
    {
        // Arrange
        $accessToken = 'invalid-access-token';

        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateAccessToken')
            ->with($accessToken)
            ->willThrowException(new InvalidTokenException('Invalid token'));

        // Act
        $result = $this->authenticationService->validateAccessToken($accessToken);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function validateRefreshToken_有效的Token_應該返回true(): void
    {
        // Arrange
        $refreshToken = 'valid-refresh-token';
        $payload = $this->createMockJwtPayload();

        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateRefreshToken')
            ->with($refreshToken)
            ->willReturn($payload);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('isValid')
            ->with('test-jti')
            ->willReturn(true);

        // Act
        $result = $this->authenticationService->validateRefreshToken($refreshToken);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function validateRefreshToken_無效的Token_應該返回false(): void
    {
        // Arrange
        $refreshToken = 'invalid-refresh-token';

        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateRefreshToken')
            ->with($refreshToken)
            ->willThrowException(new InvalidTokenException('Invalid token'));

        // Act
        $result = $this->authenticationService->validateRefreshToken($refreshToken);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function validateRefreshToken_Token已被撤銷_應該返回false(): void
    {
        // Arrange
        $refreshToken = 'revoked-refresh-token';
        $payload = $this->createMockJwtPayload();

        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateRefreshToken')
            ->with($refreshToken)
            ->willReturn($payload);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('isValid')
            ->with('test-jti')
            ->willReturn(false);

        // Act
        $result = $this->authenticationService->validateRefreshToken($refreshToken);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function revokeRefreshToken_成功撤銷_應該返回true(): void
    {
        // Arrange
        $refreshToken = 'refresh-token';
        $payload = $this->createMockJwtPayload();

        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->with($refreshToken)
            ->willReturn($payload);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revoke')
            ->with('test-jti', 'manual_revocation')
            ->willReturn(true);

        // Act
        $result = $this->authenticationService->revokeRefreshToken($refreshToken);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function revokeRefreshToken_撤銷失敗_應該返回false(): void
    {
        // Arrange
        $refreshToken = 'refresh-token';
        $payload = $this->createMockJwtPayload();

        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->with($refreshToken)
            ->willReturn($payload);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revoke')
            ->with('test-jti', 'manual_revocation')
            ->willReturn(false);

        // Act
        $result = $this->authenticationService->revokeRefreshToken($refreshToken);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function revokeAllUserTokens_成功撤銷_應該返回撤銷數量(): void
    {
        // Arrange
        $userId = 1;
        $excludeJti = 'current-jti';
        $reason = 'admin_action';

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revokeAllByUserId')
            ->with($userId, $reason, $excludeJti)
            ->willReturn(3);

        // Act
        $result = $this->authenticationService->revokeAllUserTokens($userId, $excludeJti, $reason);

        // Assert
        $this->assertSame(3, $result);
    }

    #[Test]
    public function revokeDeviceTokens_成功撤銷_應該返回撤銷數量(): void
    {
        // Arrange
        $userId = 1;
        $deviceId = 'device-123';
        $reason = 'device_compromise';

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revokeAllByDevice')
            ->with($userId, $deviceId, $reason)
            ->willReturn(2);

        // Act
        $result = $this->authenticationService->revokeDeviceTokens($userId, $deviceId, $reason);

        // Assert
        $this->assertSame(2, $result);
    }

    #[Test]
    public function getUserTokenStats_成功取得統計_應該返回統計資料(): void
    {
        // Arrange
        $userId = 1;
        $expectedStats = [
            'total' => 10,
            'active' => 5,
            'expired' => 3,
            'revoked' => 2,
        ];

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('getUserTokenStats')
            ->with($userId)
            ->willReturn($expectedStats);

        // Act
        $result = $this->authenticationService->getUserTokenStats($userId);

        // Assert
        $this->assertSame($expectedStats, $result);
    }

    #[Test]
    public function getUserTokenStats_發生例外_應該返回預設統計(): void
    {
        // Arrange
        $userId = 1;

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('getUserTokenStats')
            ->with($userId)
            ->willThrowException(new RuntimeException('Database error'));

        // Act
        $result = $this->authenticationService->getUserTokenStats($userId);

        // Assert
        $this->assertSame([
            'total' => 0,
            'active' => 0,
            'expired' => 0,
            'revoked' => 0,
        ], $result);
    }

    #[Test]
    public function cleanupExpiredTokens_成功清理_應該返回清理數量(): void
    {
        // Arrange
        $beforeDate = new DateTime('2023-01-01');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanup')
            ->with($beforeDate)
            ->willReturn(15);

        // Act
        $result = $this->authenticationService->cleanupExpiredTokens($beforeDate);

        // Assert
        $this->assertSame(15, $result);
    }

    #[Test]
    public function cleanupExpiredTokens_沒有指定日期_應該使用預設日期(): void
    {
        // Arrange
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanup')
            ->with(null)
            ->willReturn(10);

        // Act
        $result = $this->authenticationService->cleanupExpiredTokens();

        // Assert
        $this->assertSame(10, $result);
    }

    #[Test]
    public function cleanupRevokedTokens_成功清理_應該返回清理數量(): void
    {
        // Arrange
        $days = 60;

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanupRevoked')
            ->with($days)
            ->willReturn(8);

        // Act
        $result = $this->authenticationService->cleanupRevokedTokens($days);

        // Assert
        $this->assertSame(8, $result);
    }

    #[Test]
    public function cleanupRevokedTokens_使用預設天數_應該清理30天前的記錄(): void
    {
        // Arrange
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanupRevoked')
            ->with(30)
            ->willReturn(5);

        // Act
        $result = $this->authenticationService->cleanupRevokedTokens();

        // Assert
        $this->assertSame(5, $result);
    }

    /**
     * 建立 Mock TokenPair.
     */
    private function createMockTokenPair(): TokenPair
    {
        // 建立基本的 JWT 格式 token（header.payload.signature）
        $accessToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
        $refreshToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyLCJqdGkiOiJ0ZXN0LWp0aSJ9.cV2l6X3yHCIl7pZGtD2YOwn8VUwlEWV-wjZNNv2CtjY';

        return new TokenPair(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            accessTokenExpiresAt: new DateTimeImmutable('+15 minutes'),
            refreshTokenExpiresAt: new DateTimeImmutable('+30 days'),
        );
    }

    /**
     * 建立 Mock JwtPayload.
     */
    private function createMockJwtPayload(string $jti = 'test-jti'): JwtPayload
    {
        return new JwtPayload(
            jti: $jti,
            sub: '1',
            iss: 'test-issuer',
            aud: ['test-audience'],
            iat: new DateTimeImmutable(),
            exp: new DateTimeImmutable('+15 minutes'),
        );
    }
}
