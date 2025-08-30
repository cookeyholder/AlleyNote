<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Entities;

use App\Domains\Auth\Entities\RefreshToken;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * RefreshToken Entity 單元測試.
 */
class RefreshTokenTest extends TestCase
{
    private DeviceInfo $deviceInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deviceInfo = new DeviceInfo(
            deviceId: 'test-device-123',
            deviceName: 'Test Device',
            ipAddress: '192.168.1.100',
            userAgent: 'Test User Agent',
            platform: 'Linux',
            browser: 'Chrome',
        );
    }

    // === 建構子測試 ===

    public function test_constructor_should_create_valid_refresh_token(): void
    {
        // Arrange
        $jti = 'test-jti-12345678';
        $userId = 123;
        $tokenHash = hash('sha256', 'test-token');
        $expiresAt = new DateTime('+1 hour');

        // Act
        $token = new RefreshToken(
            id: null,
            jti: $jti,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
        );

        // Assert
        $this->assertNull($token->getId());
        $this->assertSame($jti, $token->getJti());
        $this->assertSame($userId, $token->getUserId());
        $this->assertSame($tokenHash, $token->getTokenHash());
        $this->assertEquals($expiresAt, $token->getExpiresAt());
        $this->assertEquals($this->deviceInfo, $token->getDeviceInfo());
        $this->assertSame(RefreshToken::STATUS_ACTIVE, $token->getStatus());
        $this->assertNull($token->getRevokedReason());
        $this->assertNull($token->getRevokedAt());
    }

    public function test_constructor_should_create_revoked_token(): void
    {
        // Arrange
        $jti = 'revoked-token-123';
        $userId = 456;
        $tokenHash = hash('sha256', 'revoked-token');
        $expiresAt = new DateTime('+1 hour');
        $revokedAt = new DateTime();

        // Act
        $token = new RefreshToken(
            id: 1,
            jti: $jti,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
            status: RefreshToken::STATUS_REVOKED,
            revokedReason: RefreshToken::REVOKE_REASON_MANUAL,
            revokedAt: $revokedAt,
        );

        // Assert
        $this->assertSame(1, $token->getId());
        $this->assertSame(RefreshToken::STATUS_REVOKED, $token->getStatus());
        $this->assertSame(RefreshToken::REVOKE_REASON_MANUAL, $token->getRevokedReason());
        $this->assertEquals($revokedAt, $token->getRevokedAt());
    }

    // === 驗證測試 ===

    public function test_constructor_should_throw_exception_when_jti_empty(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JTI cannot be empty');

        new RefreshToken(
            id: null,
            jti: '',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );
    }

    public function test_constructor_should_throw_exception_when_jti_too_short(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JTI must be between 8 and 255 characters');

        new RefreshToken(
            id: null,
            jti: 'short',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );
    }

    public function test_constructor_should_throw_exception_when_jti_contains_invalid_characters(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JTI contains invalid characters');

        new RefreshToken(
            id: null,
            jti: 'invalid@jti!',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );
    }

    public function test_constructor_should_throw_exception_when_user_id_invalid(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        new RefreshToken(
            id: null,
            jti: 'valid-jti-123',
            userId: 0,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );
    }

    public function test_constructor_should_throw_exception_when_token_hash_invalid(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token hash must be a valid SHA256 hash');

        new RefreshToken(
            id: null,
            jti: 'valid-jti-123',
            userId: 123,
            tokenHash: 'invalid-hash',
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );
    }

    public function test_constructor_should_throw_exception_when_status_invalid(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Status must be one of:');

        new RefreshToken(
            id: null,
            jti: 'valid-jti-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
            status: 'invalid_status',
        );
    }

    public function test_constructor_should_throw_exception_when_revoked_without_reason(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Revoked reason is required when status is revoked');

        new RefreshToken(
            id: null,
            jti: 'valid-jti-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
            status: RefreshToken::STATUS_REVOKED,
        );
    }

    // === 業務邏輯測試 ===

    public function test_isExpired_should_return_true_when_token_expired(): void
    {
        // Arrange
        $expiresAt = new DateTime('-1 hour'); // 過期時間是一小時前
        $token = new RefreshToken(
            id: null,
            jti: 'expired-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->isExpired();

        // Assert
        $this->assertTrue($result);
    }

    public function test_isExpired_should_return_false_when_token_not_expired(): void
    {
        // Arrange
        $expiresAt = new DateTime('+1 hour'); // 過期時間是一小時後
        $token = new RefreshToken(
            id: null,
            jti: 'valid-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->isExpired();

        // Assert
        $this->assertFalse($result);
    }

    public function test_isRevoked_should_return_true_when_token_revoked(): void
    {
        // Arrange
        $token = new RefreshToken(
            id: null,
            jti: 'revoked-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
            status: RefreshToken::STATUS_REVOKED,
            revokedReason: RefreshToken::REVOKE_REASON_MANUAL,
            revokedAt: new DateTime(),
        );

        // Act
        $result = $token->isRevoked();

        // Assert
        $this->assertTrue($result);
    }

    public function test_isRevoked_should_return_false_when_token_active(): void
    {
        // Arrange
        $token = new RefreshToken(
            id: null,
            jti: 'active-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->isRevoked();

        // Assert
        $this->assertFalse($result);
    }

    public function test_isValid_should_return_true_when_token_active_and_not_expired(): void
    {
        // Arrange
        $token = new RefreshToken(
            id: null,
            jti: 'valid-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->isValid();

        // Assert
        $this->assertTrue($result);
    }

    public function test_isValid_should_return_false_when_token_expired(): void
    {
        // Arrange
        $token = new RefreshToken(
            id: null,
            jti: 'expired-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('-1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->isValid();

        // Assert
        $this->assertFalse($result);
    }

    public function test_isValid_should_return_false_when_token_revoked(): void
    {
        // Arrange
        $token = new RefreshToken(
            id: null,
            jti: 'revoked-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
            status: RefreshToken::STATUS_REVOKED,
            revokedReason: RefreshToken::REVOKE_REASON_MANUAL,
            revokedAt: new DateTime(),
        );

        // Act
        $result = $token->isValid();

        // Assert
        $this->assertFalse($result);
    }

    public function test_canBeRefreshed_should_return_true_when_token_valid_and_active(): void
    {
        // Arrange
        $token = new RefreshToken(
            id: null,
            jti: 'refreshable-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->canBeRefreshed();

        // Assert
        $this->assertTrue($result);
    }

    public function test_canBeRefreshed_should_return_false_when_token_used(): void
    {
        // Arrange
        $token = new RefreshToken(
            id: null,
            jti: 'used-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
            status: RefreshToken::STATUS_USED,
        );

        // Act
        $result = $token->canBeRefreshed();

        // Assert
        $this->assertFalse($result);
    }

    public function test_markAsRevoked_should_return_new_revoked_token(): void
    {
        // Arrange
        $originalToken = new RefreshToken(
            id: 1,
            jti: 'to-revoke-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $revokedToken = $originalToken->markAsRevoked(RefreshToken::REVOKE_REASON_MANUAL);

        // Assert
        $this->assertNotSame($originalToken, $revokedToken);
        $this->assertSame(RefreshToken::STATUS_ACTIVE, $originalToken->getStatus());
        $this->assertSame(RefreshToken::STATUS_REVOKED, $revokedToken->getStatus());
        $this->assertSame(RefreshToken::REVOKE_REASON_MANUAL, $revokedToken->getRevokedReason());
        $this->assertNotNull($revokedToken->getRevokedAt());
        $this->assertNotNull($revokedToken->getUpdatedAt());
    }

    public function test_markAsRevoked_should_return_same_token_when_already_revoked(): void
    {
        // Arrange
        $revokedToken = new RefreshToken(
            id: 1,
            jti: 'already-revoked-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
            status: RefreshToken::STATUS_REVOKED,
            revokedReason: RefreshToken::REVOKE_REASON_LOGOUT,
            revokedAt: new DateTime(),
        );

        // Act
        $result = $revokedToken->markAsRevoked(RefreshToken::REVOKE_REASON_MANUAL);

        // Assert
        $this->assertSame($revokedToken, $result);
    }

    public function test_markAsUsed_should_return_new_used_token(): void
    {
        // Arrange
        $originalToken = new RefreshToken(
            id: 1,
            jti: 'to-use-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $usedToken = $originalToken->markAsUsed();

        // Assert
        $this->assertNotSame($originalToken, $usedToken);
        $this->assertSame(RefreshToken::STATUS_ACTIVE, $originalToken->getStatus());
        $this->assertSame(RefreshToken::STATUS_USED, $usedToken->getStatus());
        $this->assertNotNull($usedToken->getLastUsedAt());
        $this->assertNotNull($usedToken->getUpdatedAt());
    }

    public function test_updateLastUsed_should_return_new_token_with_updated_time(): void
    {
        // Arrange
        $originalToken = new RefreshToken(
            id: 1,
            jti: 'token-to-update-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $updatedToken = $originalToken->updateLastUsed();

        // Assert
        $this->assertNotSame($originalToken, $updatedToken);
        $this->assertNull($originalToken->getLastUsedAt());
        $this->assertNotNull($updatedToken->getLastUsedAt());
        $this->assertNotNull($updatedToken->getUpdatedAt());
    }

    // === 比較方法測試 ===

    public function test_equals_should_return_true_when_same_jti(): void
    {
        // Arrange
        $jti = 'same-jti-123';
        $token1 = new RefreshToken(
            id: 1,
            jti: $jti,
            userId: 123,
            tokenHash: hash('sha256', 'test1'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        $token2 = new RefreshToken(
            id: 2,
            jti: $jti,
            userId: 456,
            tokenHash: hash('sha256', 'test2'),
            expiresAt: new DateTime('+2 hours'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token1->equals($token2);

        // Assert
        $this->assertTrue($result);
    }

    public function test_equals_should_return_false_when_different_jti(): void
    {
        // Arrange
        $token1 = new RefreshToken(
            id: 1,
            jti: 'jti-1-123',
            userId: 123,
            tokenHash: hash('sha256', 'test1'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        $token2 = new RefreshToken(
            id: 2,
            jti: 'jti-2-456',
            userId: 123,
            tokenHash: hash('sha256', 'test2'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token1->equals($token2);

        // Assert
        $this->assertFalse($result);
    }

    public function test_belongsToUser_should_return_true_when_same_user(): void
    {
        // Arrange
        $userId = 123;
        $token = new RefreshToken(
            id: null,
            jti: 'user-token-123',
            userId: $userId,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->belongsToUser($userId);

        // Assert
        $this->assertTrue($result);
    }

    public function test_belongsToUser_should_return_false_when_different_user(): void
    {
        // Arrange
        $token = new RefreshToken(
            id: null,
            jti: 'user-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->belongsToUser(456);

        // Assert
        $this->assertFalse($result);
    }

    public function test_belongsToDevice_should_return_true_when_same_device(): void
    {
        // Arrange
        $deviceId = 'test-device-123';
        $token = new RefreshToken(
            id: null,
            jti: 'device-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->belongsToDevice($deviceId);

        // Assert
        $this->assertTrue($result);
    }

    public function test_belongsToDevice_should_return_false_when_different_device(): void
    {
        // Arrange
        $token = new RefreshToken(
            id: null,
            jti: 'device-token-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->belongsToDevice('different-device-456');

        // Assert
        $this->assertFalse($result);
    }

    // === 時間相關測試 ===

    public function test_getRemainingTime_should_return_correct_seconds(): void
    {
        // Arrange
        $now = new DateTime();
        $expiresAt = (clone $now)->modify('+3600 seconds'); // 1 小時後
        $token = new RefreshToken(
            id: null,
            jti: 'remaining-time-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->getRemainingTime($now);

        // Assert
        $this->assertSame(3600, $result);
    }

    public function test_getRemainingTime_should_return_zero_when_expired(): void
    {
        // Arrange
        $now = new DateTime();
        $expiresAt = (clone $now)->modify('-1 hour');
        $token = new RefreshToken(
            id: null,
            jti: 'expired-time-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->getRemainingTime($now);

        // Assert
        $this->assertSame(0, $result);
    }

    public function test_isNearExpiry_should_return_true_when_near_expiry(): void
    {
        // Arrange
        $now = new DateTime();
        $expiresAt = (clone $now)->modify('+1800 seconds'); // 30 分鐘後
        $token = new RefreshToken(
            id: null,
            jti: 'near-expiry-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->isNearExpiry(3600, $now); // 臨界值 1 小時

        // Assert
        $this->assertTrue($result);
    }

    public function test_isNearExpiry_should_return_false_when_not_near_expiry(): void
    {
        // Arrange
        $now = new DateTime();
        $expiresAt = (clone $now)->modify('+7200 seconds'); // 2 小時後
        $token = new RefreshToken(
            id: null,
            jti: 'not-near-expiry-123',
            userId: 123,
            tokenHash: hash('sha256', 'test'),
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->isNearExpiry(3600, $now); // 臨界值 1 小時

        // Assert
        $this->assertFalse($result);
    }

    // === 序列化測試 ===

    public function test_jsonSerialize_should_return_expected_array(): void
    {
        // Arrange
        $id = 123;
        $jti = 'json-test-123';
        $userId = 456;
        $tokenHash = hash('sha256', 'test');
        $expiresAt = new DateTime('2025-08-26 12:00:00');
        $createdAt = new DateTime('2025-08-26 10:00:00');

        $token = new RefreshToken(
            id: $id,
            jti: $jti,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
            createdAt: $createdAt,
        );

        // Act
        $result = $token->jsonSerialize();

        // Assert
        $this->assertIsArray($result);
        $this->assertSame($id, (is_array($result) ? $result['id'] : (is_object($result) ? $result->id : null)));
        $this->assertSame($jti, (is_array($result) ? $result['jti'] : (is_object($result) ? $result->jti : null)));
        $this->assertSame($userId, (is_array($result) ? $result['user_id'] : (is_object($result) ? $result->user_id : null)));
        $this->assertSame('2025-08-26 12:00:00', (is_array($result) ? $result['expires_at'] : (is_object($result) ? $result->expires_at : null)));
        $this->assertSame('2025-08-26 10:00:00', (is_array($result) ? $result['created_at'] : (is_object($result) ? $result->created_at : null)));
        $this->assertArrayHasKey('device_info', $result);
        $this->assertArrayNotHasKey('token_hash', $result); // 敏感資料不應出現在 JSON 序列化中
    }

    public function test_toArray_should_return_complete_array_with_sensitive_data(): void
    {
        // Arrange
        $id = 123;
        $jti = 'array<mixed>-test-123';
        $userId = 456;
        $tokenHash = hash('sha256', 'test');
        $expiresAt = new DateTime('2025-08-26 12:00:00');

        $token = new RefreshToken(
            id: $id,
            jti: $jti,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = $token->toArray();

        // Assert
        $this->assertIsArray($result);
        $this->assertSame($id, (is_array($result) ? $result['id'] : (is_object($result) ? $result->id : null)));
        $this->assertSame($jti, (is_array($result) ? $result['jti'] : (is_object($result) ? $result->jti : null)));
        $this->assertSame($userId, (is_array($result) ? $result['user_id'] : (is_object($result) ? $result->user_id : null)));
        $this->assertSame($tokenHash, (is_array($result) ? $result['token_hash'] : (is_object($result) ? $result->token_hash : null))); // 敏感資料應出現在 toArray 中
        $this->assertSame('2025-08-26 12:00:00', (is_array($result) ? $result['expires_at'] : (is_object($result) ? $result->expires_at : null)));
    }

    // === 字串表示測試 ===

    public function test_toString_should_return_expected_format(): void
    {
        // Arrange
        $jti = 'string-test-123';
        $userId = 456;
        $expiresAt = new DateTime('2025-08-26 12:00:00');

        $token = new RefreshToken(
            id: null,
            jti: $jti,
            userId: $userId,
            tokenHash: hash('sha256', 'test'),
            expiresAt: $expiresAt,
            deviceInfo: $this->deviceInfo,
        );

        // Act
        $result = (string) $token;

        // Assert
        $expected = 'RefreshToken(jti=string-test-123, userId=456, status=active, expiresAt=2025-08-26 12:00:00)';
        $this->assertSame($expected, $result);
    }

    // === 邊界值測試 ===

    public function test_constructor_should_accept_minimum_valid_jti_length(): void
    {
        // Arrange
        $jti = '12345678'; // 8 字元（最小長度）

        // Act & Assert (不應拋出例外)
        $token = new RefreshToken(
            id: null,
            jti: $jti,
            userId: 1,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        $this->assertSame($jti, $token->getJti());
    }

    public function test_constructor_should_accept_maximum_valid_jti_length(): void
    {
        // Arrange
        $jti = str_repeat('a', 255); // 255 字元（最大長度）

        // Act & Assert (不應拋出例外)
        $token = new RefreshToken(
            id: null,
            jti: $jti,
            userId: 1,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );

        $this->assertSame($jti, $token->getJti());
    }

    public function test_constructor_should_reject_jti_exceeding_maximum_length(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JTI must be between 8 and 255 characters');

        new RefreshToken(
            id: null,
            jti: str_repeat('a', 256), // 256 字元（超過最大長度）
            userId: 1,
            tokenHash: hash('sha256', 'test'),
            expiresAt: new DateTime('+1 hour'),
            deviceInfo: $this->deviceInfo,
        );
    }
}
