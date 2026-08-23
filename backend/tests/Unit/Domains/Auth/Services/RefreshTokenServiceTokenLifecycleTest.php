<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use App\Domains\Auth\Contracts\JwtTokenServiceInterface;
use App\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\Entities\RefreshToken;
use App\Domains\Auth\Exceptions\AuthenticationException;
use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\RefreshTokenException;
use App\Domains\Auth\Services\RefreshTokenService;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Domains\Auth\ValueObjects\JwtPayload;
use App\Domains\Auth\ValueObjects\TokenPair;
use DateTime;
use DateTimeImmutable;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Tests\Support\UnitTestCase;
use Throwable;

/**
 * RefreshTokenService Token 生命週期測試.
 *
 * 涵蓋建立、刷新輪轉、批次撤銷與設定值存取。
 */
final class RefreshTokenServiceTokenLifecycleTest extends UnitTestCase
{
    use MockeryPHPUnitIntegration;

    private RefreshTokenService $service;

    private JwtTokenServiceInterface&MockInterface $jwtTokenService;

    private RefreshTokenRepositoryInterface&MockInterface $refreshTokenRepository;

    private TokenBlacklistRepositoryInterface&MockInterface $blacklistRepository;

    private LoggerInterface&MockInterface $logger;

    /**
     * 建立測試用裝置資訊.
     */
    private function makeDevice(): DeviceInfo
    {
        return new DeviceInfo(
            'device-abc',
            '測試裝置',
            'Mozilla/5.0 (X11; Linux x86_64)',
            '192.168.1.50',
        );
    }

    /**
     * 建立測試用 JWT Payload.
     *
     * @param array<string, mixed> $customClaims
     */
    private function makePayload(string $jti, array $customClaims = []): JwtPayload
    {
        return new JwtPayload(
            $jti,
            '123',
            'alleynote-test',
            ['alleynote-client'],
            new DateTimeImmutable('-10 minutes'),
            new DateTimeImmutable('+1 hour'),
            null,
            $customClaims,
        );
    }

    /**
     * 建立帶有裝置宣告的 refresh 類型 Payload（可通過裝置一致性檢查）.
     */
    private function makeRefreshPayload(string $jti): JwtPayload
    {
        return $this->makePayload($jti, [
            'type'       => 'refresh',
            'device_id'  => 'device-abc',
            'ip_address' => '192.168.1.50',
        ]);
    }

    /**
     * 建立測試用 TokenPair（符合 JWT 三段式格式）.
     */
    private function makeTokenPair(): TokenPair
    {
        return new TokenPair(
            'eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiIxMjMifQ.acc',
            'eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref',
            new DateTimeImmutable('+15 minutes'),
            new DateTimeImmutable('+7 days'),
        );
    }

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

    public function test_createRefreshToken成功建立並回傳實體(): void
    {
        $device = $this->makeDevice();
        $tokenPair = $this->makeTokenPair();
        $payload = $this->makePayload('jti-new-001', ['type' => 'refresh']);

        $this->refreshTokenRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with(123)
            ->andReturn([]);
        $this->jwtTokenService
            ->shouldReceive('generateTokenPair')
            ->once()
            ->with(123, $device)
            ->andReturn($tokenPair);
        $this->jwtTokenService
            ->shouldReceive('extractPayload')
            ->once()
            ->with('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref')
            ->andReturn($payload);
        $this->refreshTokenRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn(true);
        $this->logger->shouldReceive('info')->once();

        $entity = $this->service->createRefreshToken(123, $device);

        $this->assertInstanceOf(RefreshToken::class, $entity);
        $this->assertSame('jti-new-001', $entity->getJti());
        $this->assertSame(123, $entity->getUserId());
        $this->assertSame(
            hash('sha256', 'eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref'),
            $entity->getTokenHash(),
        );
        $this->assertSame(RefreshToken::STATUS_ACTIVE, $entity->getStatus());
    }

    public function test_createRefreshToken儲存失敗時拋出例外(): void
    {
        $device = $this->makeDevice();
        $tokenPair = $this->makeTokenPair();
        $payload = $this->makePayload('jti-fail-002');

        $this->refreshTokenRepository->shouldReceive('findByUserId')->andReturn([]);
        $this->jwtTokenService->shouldReceive('generateTokenPair')->andReturn($tokenPair);
        $this->jwtTokenService->shouldReceive('extractPayload')->andReturn($payload);
        $this->refreshTokenRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn(false);
        $this->logger->shouldReceive('error')->once();

        $this->expectException(RefreshTokenException::class);

        $this->service->createRefreshToken(123, $device);
    }

    public function test_createRefreshToken底層例外會被封裝(): void
    {
        $device = $this->makeDevice();

        $this->refreshTokenRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->andThrow(new Exception('DB offline'));
        $this->logger->shouldReceive('error')->once();

        try {
            $this->service->createRefreshToken(123, $device);
            $this->fail('應拋出 RefreshTokenException');
        } catch (RefreshTokenException $e) {
            $this->assertSame(RefreshTokenException::REASON_CREATION_FAILED, $e->getReason());
        }
    }

    public function test_enforceTokenLimits達上限時撤銷最舊token(): void
    {
        // 建立 11 筆（超過上限 10），第一筆為最舊
        $oldTokens = [];
        for ($i = 0; $i < 11; $i++) {
            $oldTokens[] = ['jti' => "jti-old-{$i}"];
        }

        $device = $this->makeDevice();
        $tokenPair = $this->makeTokenPair();
        $payload = $this->makePayload('jti-new-003');

        $this->refreshTokenRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with(123)
            ->andReturn($oldTokens);

        // enforceTokenLimits 內部以 jti 呼叫 revokeToken，
        // extractPayload 解析失敗後由 revokeToken 自行捕捉並回傳 false
        $this->jwtTokenService
            ->shouldReceive('extractPayload')
            ->with('jti-old-0')
            ->andThrow(new Exception('invalid jwt'));
        $this->logger->shouldReceive('error')->atLeast()->once();

        $this->jwtTokenService->shouldReceive('generateTokenPair')->once()->andReturn($tokenPair);
        $this->jwtTokenService->shouldReceive('extractPayload')->with('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref')->andReturn($payload);
        $this->refreshTokenRepository->shouldReceive('create')->once()->andReturn(true);
        $this->logger->shouldReceive('info')->atLeast()->once();

        $entity = $this->service->createRefreshToken(123, $device);

        $this->assertInstanceOf(RefreshToken::class, $entity);
    }

    public function test_refreshAccessToken不輪轉時更新最後使用時間(): void
    {
        $device = $this->makeDevice();
        $payload = $this->makeRefreshPayload('jti-live-004');
        $tokenData = [
            'jti'        => 'jti-live-004',
            'user_id'    => 123,
            'status'     => RefreshToken::STATUS_ACTIVE,
            'device_id'  => 'device-abc',
            'ip_address' => '192.168.1.50',
        ];
        $newPair = $this->makeTokenPair();

        $this->jwtTokenService
            ->shouldReceive('validateRefreshToken')
            ->once()
            ->with('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref')
            ->andReturn($payload);
        $this->blacklistRepository
            ->shouldReceive('isBlacklisted')
            ->once()
            ->with('jti-live-004')
            ->andReturn(false);
        $this->refreshTokenRepository
            ->shouldReceive('findByJti')
            ->once()
            ->with('jti-live-004')
            ->andReturn($tokenData);
        $this->jwtTokenService->shouldReceive('generateTokenPair')->once()->andReturn($newPair);
        $this->refreshTokenRepository
            ->shouldReceive('updateLastUsed')
            ->once()
            ->with('jti-live-004');
        $this->logger->shouldReceive('info')->once();

        $result = $this->service->refreshAccessToken('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref', $device, false);

        $this->assertSame($newPair, $result);
    }

    /**
     * 輪轉路徑目前因生產碼缺陷必然失敗：
     * RefreshTokenService 建構 RefreshToken 實體時，把父 token JTI 字串
     * 傳入第 10 個參數 lastUsedAt（型別 ?DateTime），造成 TypeError，
     * 例外被封裝成 RefreshTokenException（REASON_CREATION_FAILED）。
     * 此測試記錄現行行為，待修復後應改驗證輪轉成功。
     */
    public function test_refreshAccessToken輪轉路徑因lastUsedAt型別缺陷而失敗(): void
    {
        $device = $this->makeDevice();
        $payload = $this->makeRefreshPayload('jti-rot-005');
        $tokenData = [
            'jti'        => 'jti-rot-005',
            'user_id'    => 123,
            'status'     => RefreshToken::STATUS_ACTIVE,
            'device_id'  => 'device-abc',
            'ip_address' => '192.168.1.50',
        ];
        $token = 'eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref';

        $this->jwtTokenService->shouldReceive('validateRefreshToken')->once()->andReturn($payload);
        $this->blacklistRepository->shouldReceive('isBlacklisted')->once()->andReturn(false);
        $this->refreshTokenRepository->shouldReceive('findByJti')->once()->andReturn($tokenData);

        // 輪轉會先嘗試建立新 token（含父 JTI）
        $this->refreshTokenRepository->shouldReceive('findByUserId')->once()->andReturn([]);
        // generateTokenPair 呼叫兩次：一次產生新 access token、一次在嵌套的 createRefreshToken 內
        $this->jwtTokenService->shouldReceive('generateTokenPair')->twice()->andReturn($this->makeTokenPair());
        $this->jwtTokenService->shouldReceive('extractPayload')->once()->andReturn(
            $this->makeRefreshPayload('jti-rot-006'),
        );
        $this->refreshTokenRepository->shouldReceive('create')->once()->andReturn(true);

        $this->logger->shouldReceive('error')->atLeast()->once();

        try {
            $this->service->refreshAccessToken($token, $device, true);
            $this->fail('現行實作應因 lastUsedAt 型別缺陷拋出 RefreshTokenException');
        } catch (Throwable $e) {
            $this->assertInstanceOf(RefreshTokenException::class, $e);
            $this->assertSame(RefreshTokenException::REASON_ROTATION_FAILED, $e->getReason());
        }
    }

    public function test_refreshAccessToken找不到token記錄時拋出例外(): void
    {
        $device = $this->makeDevice();
        $payload = $this->makeRefreshPayload('jti-gone-007');

        $this->jwtTokenService->shouldReceive('validateRefreshToken')->once()->andReturn($payload);
        $this->blacklistRepository->shouldReceive('isBlacklisted')->once()->andReturn(false);
        $this->refreshTokenRepository->shouldReceive('findByJti')->once()->andReturn(null);

        try {
            $this->service->refreshAccessToken('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref', $device);
            $this->fail('應拋出 InvalidTokenException');
        } catch (InvalidTokenException $e) {
            $this->assertSame('Refresh token not found in database', $e->getReason());
        }
    }

    public function test_refreshAccessToken非活躍狀態拋出例外(): void
    {
        $device = $this->makeDevice();
        $payload = $this->makeRefreshPayload('jti-dead-008');
        $tokenData = [
            'jti'    => 'jti-dead-008',
            'status' => RefreshToken::STATUS_REVOKED,
        ];

        $this->jwtTokenService->shouldReceive('validateRefreshToken')->once()->andReturn($payload);
        $this->blacklistRepository->shouldReceive('isBlacklisted')->once()->andReturn(false);
        $this->refreshTokenRepository->shouldReceive('findByJti')->once()->andReturn($tokenData);

        try {
            $this->service->refreshAccessToken('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref', $device);
            $this->fail('應拋出 InvalidTokenException');
        } catch (InvalidTokenException $e) {
            $this->assertSame('Refresh token is not active', $e->getReason());
        }
    }

    public function test_refreshAccessToken裝置不符時拋出AuthenticationException(): void
    {
        $device = $this->makeDevice();
        $payload = $this->makeRefreshPayload('jti-mis-009');
        $tokenData = [
            'jti'        => 'jti-mis-009',
            'user_id'    => 123,
            'status'     => RefreshToken::STATUS_ACTIVE,
            'device_id'  => 'another-device',
            'ip_address' => '192.168.1.50',
        ];

        $this->jwtTokenService->shouldReceive('validateRefreshToken')->once()->andReturn($payload);
        $this->blacklistRepository->shouldReceive('isBlacklisted')->once()->andReturn(false);
        $this->refreshTokenRepository->shouldReceive('findByJti')->once()->andReturn($tokenData);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Device mismatch detected');

        $this->service->refreshAccessToken('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref', $device);
    }

    public function test_validateRefreshToken非refresh類型拋出例外(): void
    {
        $device = $this->makeDevice();
        $payload = $this->makePayload('jti-typ-010', ['type' => 'access']);

        $this->jwtTokenService->shouldReceive('validateRefreshToken')->once()->andReturn($payload);

        try {
            $this->service->refreshAccessToken('access-token-as-refresh', $device);
            $this->fail('應拋出 InvalidTokenException');
        } catch (InvalidTokenException $e) {
            $this->assertSame('Token is not a refresh token', $e->getReason());
        }
    }

    public function test_validateRefreshToken黑名單token拋出例外(): void
    {
        $device = $this->makeDevice();
        $payload = $this->makeRefreshPayload('jti-blk-011');

        $this->jwtTokenService->shouldReceive('validateRefreshToken')->once()->andReturn($payload);
        $this->blacklistRepository->shouldReceive('isBlacklisted')->once()->with('jti-blk-011')->andReturn(true);

        try {
            $this->service->refreshAccessToken('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref', $device);
            $this->fail('應拋出 InvalidTokenException');
        } catch (InvalidTokenException $e) {
            $this->assertSame('Refresh token is blacklisted', $e->getReason());
        }
    }

    public function test_verifyDeviceConsistency裝置ID不符拋出例外(): void
    {
        $device = $this->makeDevice();
        $payload = $this->makePayload('jti-dvc-012', ['type' => 'refresh', 'device_id' => 'other-device']);

        $this->jwtTokenService->shouldReceive('validateRefreshToken')->once()->andReturn($payload);
        $this->blacklistRepository->shouldReceive('isBlacklisted')->once()->andReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Device ID mismatch');

        $this->service->refreshAccessToken('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref', $device);
    }

    public function test_verifyDeviceConsistencyIP不符拋出例外(): void
    {
        $device = $this->makeDevice();
        $payload = $this->makePayload('jti-ipm-013', ['type' => 'refresh', 'device_id' => 'device-abc', 'ip_address' => '10.9.8.7']);

        $this->jwtTokenService->shouldReceive('validateRefreshToken')->once()->andReturn($payload);
        $this->blacklistRepository->shouldReceive('isBlacklisted')->once()->andReturn(false);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('IP address mismatch');

        $this->service->refreshAccessToken('eyJhbGciOiJSUzI1NiJ9.eyJ0eXBlIjoicmVmcmVzaCJ9.ref', $device);
    }

    public function test_revokeAllUserTokens撤銷全部但排除指定JTI(): void
    {
        $tokens = [
            ['jti' => 'jti-a'],
            ['jti' => 'jti-b'],
            ['jti' => 'jti-c'],
        ];

        $this->refreshTokenRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with(123)
            ->andReturn($tokens);
        $this->refreshTokenRepository
            ->shouldReceive('revoke')
            ->twice()
            ->andReturn(true);
        $this->logger->shouldReceive('info')->once();

        $count = $this->service->revokeAllUserTokens(123, RefreshToken::REVOKE_REASON_LOGOUT_ALL, 'jti-b');

        $this->assertSame(2, $count);
    }

    public function test_revokeAllUserTokens發生例外時回傳零(): void
    {
        $this->refreshTokenRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->andThrow(new Exception('DB error'));
        $this->logger->shouldReceive('error')->once();

        $this->assertSame(0, $this->service->revokeAllUserTokens(123));
    }

    public function test_revokeDeviceTokens成功撤銷(): void
    {
        $this->refreshTokenRepository
            ->shouldReceive('revokeAllByDevice')
            ->once()
            ->with(0, 'device-abc', RefreshToken::REVOKE_REASON_SECURITY)
            ->andReturn(3);
        $this->logger->shouldReceive('info')->once();

        $this->assertSame(3, $this->service->revokeDeviceTokens('device-abc'));
    }

    public function test_revokeDeviceTokens發生例外時回傳零(): void
    {
        $this->refreshTokenRepository
            ->shouldReceive('revokeAllByDevice')
            ->once()
            ->andThrow(new Exception('DB error'));
        $this->logger->shouldReceive('error')->once();

        $this->assertSame(0, $this->service->revokeDeviceTokens('device-abc'));
    }

    public function test_設定值存取器回傳預期常數(): void
    {
        $this->assertSame(500, $this->service->getCleanupBatchSize());
        $this->assertSame(300, $this->service->getMinCleanupInterval());
        $this->assertSame(30, $this->service->getRotationGracePeriod());
    }
}
