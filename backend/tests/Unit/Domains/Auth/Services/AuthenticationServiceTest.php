<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\DTOs\LoginRequestDTO;
use App\Domains\Auth\Exceptions\AuthenticationException;
use App\Domains\Auth\Services\AuthenticationService;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Domains\Auth\ValueObjects\TokenPair;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\UnitTestCase;

final class AuthenticationServiceTest extends UnitTestCase
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

    public function testLogin_成功登入_應該返回登入回應(): void
    {
        $request = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
            rememberMe: false,
            scopes: ['read', 'write'],
        );

        $userData = [
            'id' => 1,
            'email' => 'test@example.com',
            'username' => 'testuser',
            'deleted_at' => null,
        ];

        $userWithRoles = [
            'id' => 1,
            'email' => 'test@example.com',
            'username' => 'testuser',
            'roles' => [
                ['name' => 'user'],
            ],
        ];

        $accessToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';
        $refreshToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        $tokenPair = new TokenPair(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            accessTokenExpiresAt: new DateTimeImmutable('+1 hour'),
            refreshTokenExpiresAt: new DateTimeImmutable('+7 days'),
        );

        $payload = new JwtPayload(
            jti: 'test-jti',
            sub: '1',
            iss: 'alleynote',
            aud: ['alleynote-api'],
            iat: new DateTimeImmutable(),
            exp: new DateTimeImmutable('+ 1 hour'),
            nbf: new DateTimeImmutable(),
        );

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

        $this->userRepository
            ->expects($this->once())
            ->method('findByIdWithRoles')
            ->with(1)
            ->willReturn($userWithRoles);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('generateTokenPair')
            ->with($this->anything(), $this->anything(), $this->anything())
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

        $response = $this->authenticationService->login($request, $this->deviceInfo);

        $this->assertSame($tokenPair, $response->tokens);
        $this->assertSame(1, $response->userId);
        $this->assertSame('test@example.com', $response->userEmail);
        $this->assertSame('test-jti', $response->sessionId);
        $this->assertSame(['read', 'write'], $response->permissions);
    }

    public function testlogin_使用者不存在_應該拋出認證例外(): void
    {
        $request = new LoginRequestDTO(
            email: 'nonexistent@example.com',
            password: 'password123',
        );

        $this->userRepository
            ->expects($this->once())
            ->method('validateCredentials')
            ->with('nonexistent@example.com', 'password123')
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials provided');

        $this->authenticationService->login($request, $this->deviceInfo);
    }

    public function testLogin_帳號已停用_應該拋出例外(): void
    {
        $request = new LoginRequestDTO(
            email: 'disabled@example.com',
            password: 'password123',
        );

        $userData = [
            'id' => 1,
            'email' => 'disabled@example.com',
            'username' => 'disableduser',
            'deleted_at' => '2023-01-01 00:00:00',
        ];

        $this->userRepository
            ->expects($this->once())
            ->method('validateCredentials')
            ->willReturn($userData);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User account has been deactivated');

        $this->authenticationService->login($request, $this->deviceInfo);
    }

    public function testLogin_Token超過限制_應該撤銷最舊的Token(): void
    {
        $request = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
        );

        $userData = ['id' => 1, 'email' => 'test@example.com', 'deleted_at' => null];
        $userWithRoles = ['roles' => []];

        $tokens = array_fill(0, 50, ['jti' => 'old-jti']);
        $tokens[0] = ['jti' => 'oldest-jti']; // The first is the oldest

        $this->userRepository->method('validateCredentials')->willReturn($userData);
        $this->userRepository->method('findByIdWithRoles')->willReturn($userWithRoles);

        $this->refreshTokenRepository->expects($this->once())->method('cleanup')->willReturn(0);
        $this->refreshTokenRepository->expects($this->once())->method('findByUserId')->willReturn($tokens);

        // Expect revoke to be called for the oldest token
        $this->refreshTokenRepository->expects($this->once())->method('revoke')->with('oldest-jti', 'max_tokens_exceeded');

        // Mock token generation
        $now = new DateTimeImmutable();
        $accessTokenExpiresAt = $now->modify('+1 hour');
        $refreshTokenExpiresAt = $now->modify('+7 days');
        $tokenPair = new TokenPair('header.payload.signature', 'a-long-enough-refresh-token-string', $accessTokenExpiresAt, $refreshTokenExpiresAt);
        $payload = new JwtPayload('new-jti', '1', 'iss', ['aud'], $now, $accessTokenExpiresAt, $now);
        $this->jwtTokenService->method('generateTokenPair')->willReturn($tokenPair);
        $this->jwtTokenService->method('extractPayload')->willReturn($payload);

        $response = $this->authenticationService->login($request, $this->deviceInfo);
        $this->assertSame('new-jti', $response->sessionId);
    }

    public function testLogin_無角色使用者_成功登入(): void
    {
        $request = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123',
        );

        $userData = ['id' => 1, 'email' => 'test@example.com', 'deleted_at' => null];
        $userWithRoles = ['roles' => []];

        $this->userRepository->method('validateCredentials')->willReturn($userData);
        $this->userRepository->method('findByIdWithRoles')->willReturn($userWithRoles);
        $this->refreshTokenRepository->method('findByUserId')->willReturn([]);

        $now = new DateTimeImmutable();
        $accessTokenExpiresAt = $now->modify('+1 hour');
        $refreshTokenExpiresAt = $now->modify('+7 days');
        $tokenPair = new TokenPair('header.payload.signature', 'a-long-enough-refresh-token-string', $accessTokenExpiresAt, $refreshTokenExpiresAt);
        $payload = new JwtPayload('new-jti', '1', 'iss', ['aud'], $now, $accessTokenExpiresAt, $now);

        // Assert generateTokenPair is called with empty role
        $this->jwtTokenService->expects($this->once())
            ->method('generateTokenPair')
            ->with($this->anything(), $this->anything(), $this->callback(function ($claims) {
                return $claims['role'] === null;
            }))
            ->willReturn($tokenPair);

        $this->jwtTokenService->method('extractPayload')->willReturn($payload);

        $response = $this->authenticationService->login($request, $this->deviceInfo);
        $this->assertEmpty($response->roles);
    }
}
