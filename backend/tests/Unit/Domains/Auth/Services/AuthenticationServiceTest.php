<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\UserRepositoryInterface;
use App\Domains\Auth\DTOs\LoginRequestDTO;
use App\Domains\Auth\DTOs\LogoutRequestDTO;
use App\Domains\Auth\DTOs\RefreshRequestDTO;
use App\Domains\Auth\Exceptions\AuthenticationException;
use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\TokenExpiredException;
use App\Domains\Auth\Services\AuthenticationService;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Domains\Auth\ValueObjects\TokenPair;
use DateTime;
use DateTimeImmutable;
use Exception;
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
            'id'         => 1,
            'email'      => 'test@example.com',
            'username'   => 'testuser',
            'deleted_at' => null,
        ];

        $userWithRoles = [
            'id'       => 1,
            'email'    => 'test@example.com',
            'username' => 'testuser',
            'roles'    => [
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
            'id'         => 1,
            'email'      => 'disabled@example.com',
            'username'   => 'disableduser',
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
            ->with($this->anything(), $this->anything(), $this->callback(function (mixed $claims) {
                return is_array($claims) && ($claims['role'] ?? null) === null;
            }))
            ->willReturn($tokenPair);

        $this->jwtTokenService->method('extractPayload')->willReturn($payload);

        $response = $this->authenticationService->login($request, $this->deviceInfo);
        $this->assertEmpty($response->roles);
    }

    // ========== refresh 測試 ==========

    /**
     * 建立測試用 TokenPair.
     */
    private function createTokenPair(): TokenPair
    {
        return new TokenPair(
            accessToken: 'header.payload.signature',
            refreshToken: 'a-long-enough-refresh-token-string',
            accessTokenExpiresAt: new DateTimeImmutable('+1 hour'),
            refreshTokenExpiresAt: new DateTimeImmutable('+7 days'),
        );
    }

    /**
     * 建立測試用 JwtPayload.
     *
     * @param array<string, mixed> $customClaims
     */
    private function createPayload(
        string $jti = 'payload-jti',
        string $sub = '1',
        int $expSeconds = 3600,
        array $customClaims = [],
    ): JwtPayload {
        return new JwtPayload(
            jti: $jti,
            sub: $sub,
            iss: 'alleynote-test',
            aud: ['alleynote-client'],
            iat: new DateTimeImmutable(),
            exp: new DateTimeImmutable()->modify("+{$expSeconds} seconds"),
            customClaims: $customClaims,
        );
    }

    public function testRefresh_成功刷新_應該返回刷新回應(): void
    {
        $request = new RefreshRequestDTO(refreshToken: 'old-refresh-token-value');
        $tokenPair = $this->createTokenPair();
        $newPayload = $this->createPayload(jti: 'new-jti', sub: '7', expSeconds: 7200);
        $oldPayload = $this->createPayload(jti: 'old-jti', sub: '7', expSeconds: 3600);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('refreshTokens')
            ->with($request->refreshToken, $this->deviceInfo)
            ->willReturn($tokenPair);

        $this->jwtTokenService
            ->expects($this->exactly(2))
            ->method('extractPayload')
            ->willReturnOnConsecutiveCalls($newPayload, $oldPayload);

        $response = $this->authenticationService->refresh($request, $this->deviceInfo);

        $this->assertSame($tokenPair, $response->tokens);
        $this->assertSame(7, $response->userId);
        $this->assertSame('new-jti', $response->sessionId);
        $this->assertSame($newPayload->getExpiresAt()->getTimestamp(), $response->expiresAt);
    }

    public function testRefresh_無效RefreshToken_應該拋出認證例外(): void
    {
        $request = new RefreshRequestDTO(refreshToken: 'invalid-refresh-token');

        $this->jwtTokenService
            ->expects($this->once())
            ->method('refreshTokens')
            ->willThrowException(new InvalidTokenException('Invalid token'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid refresh token:');

        $this->authenticationService->refresh($request, $this->deviceInfo);
    }

    public function testRefresh_過期Token_應該拋出認證例外(): void
    {
        $request = new RefreshRequestDTO(refreshToken: 'expired-refresh-token');

        $this->jwtTokenService
            ->expects($this->once())
            ->method('refreshTokens')
            ->willThrowException(new TokenExpiredException('access_token'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid refresh token:');

        $this->authenticationService->refresh($request, $this->deviceInfo);
    }

    public function testRefresh_未預期錯誤_應該拋出刷新失敗例外(): void
    {
        $request = new RefreshRequestDTO(refreshToken: 'any-refresh-token');

        $this->jwtTokenService
            ->expects($this->once())
            ->method('refreshTokens')
            ->willThrowException(new Exception('Storage error'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token refresh failed: Storage error');

        $this->authenticationService->refresh($request, $this->deviceInfo);
    }

    // ========== logout 測試 ==========

    public function testLogout_撤銷所有Token_應該呼叫全部撤銷並回傳成功(): void
    {
        $payload = $this->createPayload(jti: 'session-jti', sub: '9');
        $request = new LogoutRequestDTO(accessToken: '', refreshToken: 'refresh-token', revokeAllTokens: true);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->with($request->refreshToken)
            ->willReturn($payload);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revokeAllByUserId')
            ->with(9, 'logout_all')
            ->willReturn(2);

        $this->refreshTokenRepository->expects($this->never())->method('revoke');
        $this->jwtTokenService->expects($this->never())->method('revokeToken');

        $this->assertTrue($this->authenticationService->logout($request));
    }

    public function testLogout_單一登出_應該撤銷Refresh與AccessToken(): void
    {
        $payload = $this->createPayload(jti: 'session-jti', sub: '9');
        $request = new LogoutRequestDTO(accessToken: 'access-token', refreshToken: 'refresh-token');

        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->willReturn($payload);

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revoke')
            ->with('session-jti', 'user_logout')
            ->willReturn(true);

        $this->jwtTokenService
            ->expects($this->once())
            ->method('revokeToken')
            ->with('access-token', 'user_logout')
            ->willReturn(true);

        $this->assertTrue($this->authenticationService->logout($request));
    }

    public function testLogout_只有AccessToken_應該只撤銷AccessToken(): void
    {
        $request = new LogoutRequestDTO(accessToken: 'access-token-only');

        $this->jwtTokenService
            ->expects($this->once())
            ->method('revokeToken')
            ->with('access-token-only', 'user_logout')
            ->willReturn(true);

        $this->refreshTokenRepository->expects($this->never())->method('revoke');
        $this->refreshTokenRepository->expects($this->never())->method('revokeAllByUserId');

        $this->assertTrue($this->authenticationService->logout($request));
    }

    public function testLogout_發生例外_應該拋出認證例外(): void
    {
        $request = new LogoutRequestDTO(accessToken: '', refreshToken: 'broken-token');

        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->willThrowException(new Exception('Malformed token'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Logout failed: Malformed token');

        $this->authenticationService->logout($request);
    }

    // ========== validateAccessToken / validateRefreshToken 測試 ==========

    public function testValidateAccessToken_有效Token_應該回傳True(): void
    {
        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateAccessToken')
            ->with('valid-access-token')
            ->willReturn($this->createPayload());

        $this->assertTrue($this->authenticationService->validateAccessToken('valid-access-token'));
    }

    public function testValidateAccessToken_無效Token_應該回傳False(): void
    {
        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateAccessToken')
            ->willThrowException(new InvalidTokenException('Invalid token'));

        $this->assertFalse($this->authenticationService->validateAccessToken('invalid-token'));
    }

    public function testValidateRefreshToken_有效且未撤銷_應該回傳True(): void
    {
        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateRefreshToken')
            ->with('valid-refresh-token')
            ->willReturn($this->createPayload(jti: 'refresh-jti'));

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('isValid')
            ->with('refresh-jti')
            ->willReturn(true);

        $this->assertTrue($this->authenticationService->validateRefreshToken('valid-refresh-token'));
    }

    public function testValidateRefreshToken_已撤銷_應該回傳False(): void
    {
        $this->jwtTokenService
            ->method('validateRefreshToken')
            ->willReturn($this->createPayload(jti: 'revoked-jti'));

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('isValid')
            ->with('revoked-jti')
            ->willReturn(false);

        $this->assertFalse($this->authenticationService->validateRefreshToken('revoked-token'));
    }

    public function testValidateRefreshToken_驗證拋出例外_應該回傳False(): void
    {
        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateRefreshToken')
            ->willThrowException(new TokenExpiredException('refresh_token'));

        $this->assertFalse($this->authenticationService->validateRefreshToken('expired-token'));
    }

    // ========== revoke 系列測試 ==========

    public function testRevokeRefreshToken_成功撤銷_應該回傳True(): void
    {
        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->with('refresh-token')
            ->willReturn($this->createPayload(jti: 'target-jti'));

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revoke')
            ->with('target-jti', 'manual_revocation')
            ->willReturn(true);

        $this->assertTrue($this->authenticationService->revokeRefreshToken('refresh-token'));
    }

    public function testRevokeRefreshToken_自訂原因_應該傳遞給倉儲(): void
    {
        $this->jwtTokenService
            ->method('extractPayload')
            ->willReturn($this->createPayload(jti: 'target-jti'));

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revoke')
            ->with('target-jti', 'security_breach')
            ->willReturn(true);

        $this->assertTrue($this->authenticationService->revokeRefreshToken('refresh-token', 'security_breach'));
    }

    public function testRevokeRefreshToken_發生例外_應該回傳False(): void
    {
        $this->jwtTokenService
            ->expects($this->once())
            ->method('extractPayload')
            ->willThrowException(new Exception('Parse error'));

        $this->assertFalse($this->authenticationService->revokeRefreshToken('bad-token'));
    }

    public function testRevokeAllUserTokens_應該委派給倉儲(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revokeAllByUserId')
            ->with(5, 'logout_all', 'keep-jti')
            ->willReturn(3);

        $this->assertSame(3, $this->authenticationService->revokeAllUserTokens(5, 'keep-jti'));
    }

    public function testRevokeAllUserTokens_發生例外_應該回傳零(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revokeAllByUserId')
            ->willThrowException(new Exception('Database error'));

        $this->assertSame(0, $this->authenticationService->revokeAllUserTokens(5));
    }

    public function testRevokeDeviceTokens_應該委派給倉儲(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revokeAllByDevice')
            ->with(5, 'device-1', 'device_logout')
            ->willReturn(2);

        $this->assertSame(2, $this->authenticationService->revokeDeviceTokens(5, 'device-1'));
    }

    public function testRevokeDeviceTokens_發生例外_應該回傳零(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revokeAllByDevice')
            ->willThrowException(new Exception('Database error'));

        $this->assertSame(0, $this->authenticationService->revokeDeviceTokens(5, 'device-1'));
    }

    // ========== 統計與清理測試 ==========

    public function testGetUserTokenStats_應該回傳倉儲統計資料(): void
    {
        $stats = ['total' => 4, 'active' => 2, 'expired' => 1, 'revoked' => 1];

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('getUserTokenStats')
            ->with(11)
            ->willReturn($stats);

        $this->assertSame($stats, $this->authenticationService->getUserTokenStats(11));
    }

    public function testGetUserTokenStats_發生例外_應該回傳預設統計(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('getUserTokenStats')
            ->willThrowException(new Exception('Database error'));

        $expected = ['total' => 0, 'active' => 0, 'expired' => 0, 'revoked' => 0];
        $this->assertSame($expected, $this->authenticationService->getUserTokenStats(11));
    }

    public function testCleanupExpiredTokens_無指定日期_應該使用預設值(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanup')
            ->with(null)
            ->willReturn(6);

        $this->assertSame(6, $this->authenticationService->cleanupExpiredTokens());
    }

    public function testCleanupExpiredTokens_指定日期_應該傳遞日期(): void
    {
        $beforeDate = new DateTime('-1 day');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanup')
            ->with($this->callback(fn(?DateTime $date) => $date !== null && $date->getTimestamp() === $beforeDate->getTimestamp()))
            ->willReturn(2);

        $this->assertSame(2, $this->authenticationService->cleanupExpiredTokens($beforeDate));
    }

    public function testCleanupExpiredTokens_發生例外_應該回傳零(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanup')
            ->willThrowException(new Exception('Database error'));

        $this->assertSame(0, $this->authenticationService->cleanupExpiredTokens());
    }

    public function testCleanupRevokedTokens_應該使用預設天數(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanupRevoked')
            ->with(30)
            ->willReturn(8);

        $this->assertSame(8, $this->authenticationService->cleanupRevokedTokens());
    }

    public function testCleanupRevokedTokens_自訂天數與例外處理(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('cleanupRevoked')
            ->with(7)
            ->willThrowException(new Exception('Database error'));

        $this->assertSame(0, $this->authenticationService->cleanupRevokedTokens(7));
    }

    // ========== getUserFromToken 測試 ==========

    public function testGetUserFromToken_有效Token_應該回傳使用者與Token資訊(): void
    {
        $payload = $this->createPayload(
            jti: 'tok-jti',
            sub: '5',
            expSeconds: 600,
            customClaims: ['role' => 'admin'],
        );

        $user = ['id' => 5, 'username' => 'user5', 'email' => 'user5@example.com'];

        $this->jwtTokenService->method('validateAccessToken')->willReturn($payload);
        $this->jwtTokenService->expects($this->once())->method('extractPayload')->willReturn($payload);
        $this->userRepository
            ->expects($this->once())
            ->method('findByUuid')
            ->with('5')
            ->willReturn($user);

        $result = $this->authenticationService->getUserFromToken('access-token');

        $this->assertNotNull($result);
        $this->assertSame($user, $result['user']);
        $tokenInfo = $result['token_info'];
        $this->assertIsArray($tokenInfo);
        $this->assertSame(5, $tokenInfo['user_id']);
        $this->assertSame('5', $tokenInfo['subject']);
        $this->assertSame('tok-jti', $tokenInfo['token_id']);
        $this->assertSame(['role' => 'admin'], $tokenInfo['custom_claims']);
        $this->assertSame($payload->getIssuedAt()->getTimestamp(), $tokenInfo['issued_at']);
        $this->assertSame($payload->getExpiresAt()->getTimestamp(), $tokenInfo['expires_at']);
    }

    public function testGetUserFromToken_無效Token_應該回傳Null(): void
    {
        $this->jwtTokenService
            ->expects($this->once())
            ->method('validateAccessToken')
            ->willThrowException(new InvalidTokenException('Invalid token'));

        $this->jwtTokenService->expects($this->never())->method('extractPayload');
        $this->userRepository->expects($this->never())->method('findByUuid');

        $this->assertNull($this->authenticationService->getUserFromToken('invalid-token'));
    }

    public function testGetUserFromToken_使用者不存在_應該回傳Null(): void
    {
        $payload = $this->createPayload(sub: '999');

        $this->jwtTokenService->method('validateAccessToken')->willReturn($payload);
        $this->jwtTokenService->method('extractPayload')->willReturn($payload);
        $this->userRepository
            ->expects($this->once())
            ->method('findByUuid')
            ->with('999')
            ->willReturn(null);

        $this->assertNull($this->authenticationService->getUserFromToken('access-token'));
    }
}
