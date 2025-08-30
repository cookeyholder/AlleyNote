<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\ValueObjects;

use App\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Token Blacklist Entry Value Object 單元測試.
 */
final class TokenBlacklistEntryTest extends TestCase
{
    private DateTimeImmutable $now;

    private DateTimeImmutable $futureExpiry;

    private DateTimeImmutable $blacklistedTime;

    private string $validJti;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable();
        $this->futureExpiry = $this->now->add(new DateInterval('PT1H')); // 1小時後
        $this->blacklistedTime = $this->now->sub(new DateInterval('PT30M')); // 30分鐘前
        $this->validJti = 'test-jti-12345';
    }

    public function testConstructorWithValidData(): void
    {
        $entry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 42,
            deviceId: 'device-123',
            metadata: ['ip' => '192.168.1.1'],
        );

        $this->assertSame($this->validJti, $entry->getJti());
        $this->assertSame(TokenBlacklistEntry::TOKEN_TYPE_ACCESS, $entry->getTokenType());
        $this->assertEquals($this->futureExpiry, $entry->getExpiresAt());
        $this->assertEquals($this->blacklistedTime, $entry->getBlacklistedAt());
        $this->assertSame(TokenBlacklistEntry::REASON_LOGOUT, $entry->getReason());
        $this->assertSame(42, $entry->getUserId());
        $this->assertSame('device-123', $entry->getDeviceId());
        $this->assertSame(['ip' => '192.168.1.1'], $entry->getMetadata());
        $this->assertSame('192.168.1.1', $entry->getMetadataValue('ip'));
        $this->assertNull($entry->getMetadataValue('non-existent'));
    }

    public function testConstructorWithMinimalData(): void
    {
        $entry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_REFRESH,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_EXPIRED,
        );

        $this->assertNull($entry->getUserId());
        $this->assertNull($entry->getDeviceId());
        $this->assertSame([], $entry->getMetadata());
        $this->assertTrue($entry->isRefreshToken());
        $this->assertFalse($entry->isAccessToken());
    }

    public function testFromArray(): void
    {
        $data = [
            'jti' => $this->validJti,
            'token_type' => TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            'expires_at' => $this->futureExpiry,
            'blacklisted_at' => $this->blacklistedTime,
            'reason' => TokenBlacklistEntry::REASON_LOGOUT,
            'user_id' => 123,
            'device_id' => 'test-device',
            'metadata' => ['session_id' => 'sess-456'],
        ];

        $entry = TokenBlacklistEntry::fromArray($data);

        $this->assertSame($this->validJti, $entry->getJti());
        $this->assertSame(TokenBlacklistEntry::TOKEN_TYPE_ACCESS, $entry->getTokenType());
        $this->assertEquals($this->futureExpiry, $entry->getExpiresAt());
        $this->assertEquals($this->blacklistedTime, $entry->getBlacklistedAt());
        $this->assertSame(TokenBlacklistEntry::REASON_LOGOUT, $entry->getReason());
        $this->assertSame(123, $entry->getUserId());
        $this->assertSame('test-device', $entry->getDeviceId());
        $this->assertSame(['session_id' => 'sess-456'], $entry->getMetadata());
    }

    public function testFromArrayWithStringDates(): void
    {
        $now = new DateTimeImmutable();
        $futureTime = $now->add(new DateInterval('PT1H'));
        $pastTime = $now->sub(new DateInterval('PT30M'));

        $data = [
            'jti' => $this->validJti,
            'token_type' => TokenBlacklistEntry::TOKEN_TYPE_REFRESH,
            'expires_at' => $futureTime->format('Y-m-d H:i:s'),
            'blacklisted_at' => $pastTime->format('Y-m-d H:i:s'),
            'reason' => TokenBlacklistEntry::REASON_REVOKED,
        ];

        $entry = TokenBlacklistEntry::fromArray($data);

        $this->assertEquals($futureTime->format('Y-m-d H:i:s'), $entry->getExpiresAt()->format('Y-m-d H:i:s'));
        $this->assertEquals($pastTime->format('Y-m-d H:i:s'), $entry->getBlacklistedAt()->format('Y-m-d H:i:s'));
    }

    public function testForUserLogout(): void
    {
        $entry = TokenBlacklistEntry::forUserLogout(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            userId: 42,
            deviceId: 'device-456',
        );

        $this->assertSame($this->validJti, $entry->getJti());
        $this->assertSame(TokenBlacklistEntry::TOKEN_TYPE_ACCESS, $entry->getTokenType());
        $this->assertEquals($this->futureExpiry, $entry->getExpiresAt());
        $this->assertSame(TokenBlacklistEntry::REASON_LOGOUT, $entry->getReason());
        $this->assertSame(42, $entry->getUserId());
        $this->assertSame('device-456', $entry->getDeviceId());
        $this->assertTrue($entry->isUserInitiated());
    }

    public function testForSecurityBreach(): void
    {
        $metadata = ['threat_level' => 'high', 'detected_by' => 'security_scan'];

        $entry = TokenBlacklistEntry::forSecurityBreach(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            securityReason: TokenBlacklistEntry::REASON_SUSPICIOUS_ACTIVITY,
            userId: 99,
            metadata: $metadata,
        );

        $this->assertSame(TokenBlacklistEntry::REASON_SUSPICIOUS_ACTIVITY, $entry->getReason());
        $this->assertSame(99, $entry->getUserId());
        $this->assertSame($metadata, $entry->getMetadata());
        $this->assertTrue($entry->isSecurityRelated());
        $this->assertFalse($entry->isUserInitiated());
    }

    public function testForSecurityBreachWithInvalidReason(): void
    {
        $entry = TokenBlacklistEntry::forSecurityBreach(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            securityReason: 'invalid_security_reason',
        );

        // 應該回退到預設的安全原因
        $this->assertSame(TokenBlacklistEntry::REASON_SECURITY_BREACH, $entry->getReason());
    }

    public function testForAccountChange(): void
    {
        $entry = TokenBlacklistEntry::forAccountChange(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_REFRESH,
            expiresAt: $this->futureExpiry,
            userId: 77,
            changeType: TokenBlacklistEntry::REASON_PASSWORD_CHANGED,
        );

        $this->assertSame(TokenBlacklistEntry::REASON_PASSWORD_CHANGED, $entry->getReason());
        $this->assertSame(77, $entry->getUserId());
        $this->assertTrue($entry->isSystemInitiated());
        $this->assertFalse($entry->isUserInitiated());
    }

    public function testCanBeCleanedUp(): void
    {
        $expiredEntry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->now->modify('-1 hour'), // 已過期
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        $activeEntry = new TokenBlacklistEntry(
            jti: $this->validJti . '2',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->now->modify('+1 hour'), // 未過期
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        $this->assertTrue($expiredEntry->canBeCleanedUp($this->now));
        $this->assertFalse($activeEntry->canBeCleanedUp($this->now));
    }

    public function testIsSecurityRelated(): void
    {
        $securityReasons = [
            TokenBlacklistEntry::REASON_SECURITY_BREACH,
            TokenBlacklistEntry::REASON_SUSPICIOUS_ACTIVITY,
            TokenBlacklistEntry::REASON_DEVICE_LOST,
            TokenBlacklistEntry::REASON_INVALID_SIGNATURE,
        ];

        foreach ($securityReasons as $reason) {
            $entry = new TokenBlacklistEntry(
                jti: $this->validJti . $reason,
                tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
                expiresAt: $this->futureExpiry,
                blacklistedAt: $this->blacklistedTime,
                reason: $reason,
            );

            $this->assertTrue($entry->isSecurityRelated(), "Failed for reason: {$reason}");
        }

        $nonSecurityEntry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        $this->assertFalse($nonSecurityEntry->isSecurityRelated());
    }

    public function testIsUserInitiated(): void
    {
        $userReasons = [
            TokenBlacklistEntry::REASON_LOGOUT,
            TokenBlacklistEntry::REASON_MANUAL_REVOCATION,
            TokenBlacklistEntry::REASON_DEVICE_LOST,
        ];

        foreach ($userReasons as $reason) {
            $entry = new TokenBlacklistEntry(
                jti: $this->validJti . $reason,
                tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
                expiresAt: $this->futureExpiry,
                blacklistedAt: $this->blacklistedTime,
                reason: $reason,
            );

            $this->assertTrue($entry->isUserInitiated(), "Failed for reason: {$reason}");
        }

        $systemEntry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_EXPIRED,
        );

        $this->assertFalse($systemEntry->isUserInitiated());
    }

    public function testIsSystemInitiated(): void
    {
        $systemReasons = [
            TokenBlacklistEntry::REASON_EXPIRED,
            TokenBlacklistEntry::REASON_ACCOUNT_SUSPENDED,
            TokenBlacklistEntry::REASON_SECURITY_BREACH,
            TokenBlacklistEntry::REASON_PASSWORD_CHANGED,
        ];

        foreach ($systemReasons as $reason) {
            $entry = new TokenBlacklistEntry(
                jti: $this->validJti . $reason,
                tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
                expiresAt: $this->futureExpiry,
                blacklistedAt: $this->blacklistedTime,
                reason: $reason,
            );

            $this->assertTrue($entry->isSystemInitiated(), "Failed for reason: {$reason}");
        }

        $userEntry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        $this->assertFalse($userEntry->isSystemInitiated());
    }

    public function testGetReasonDescription(): void
    {
        $entry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        $this->assertSame('User logged out', $entry->getReasonDescription());
    }

    public function testGetPriority(): void
    {
        // 已過期的項目 (優先級 1)
        $expiredEntry = new TokenBlacklistEntry(
            jti: $this->validJti . '1',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->now->modify('-1 hour'),
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        // 安全相關的項目 (優先級 2)
        $securityEntry = new TokenBlacklistEntry(
            jti: $this->validJti . '2',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_SECURITY_BREACH,
        );

        // 使用者主動的項目 (優先級 3)
        $userEntry = new TokenBlacklistEntry(
            jti: $this->validJti . '3',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        // 其他系統原因的項目 (優先級 4)
        $systemEntry = new TokenBlacklistEntry(
            jti: $this->validJti . '4',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_ACCOUNT_SUSPENDED,
        );

        $this->assertSame(1, $expiredEntry->getPriority());
        $this->assertSame(2, $securityEntry->getPriority());
        $this->assertSame(3, $userEntry->getPriority());
        $this->assertSame(4, $systemEntry->getPriority());
    }

    public function testIsActive(): void
    {
        $activeEntry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        $expiredEntry = new TokenBlacklistEntry(
            jti: $this->validJti . '2',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->now->modify('-1 hour'),
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        $this->assertTrue($activeEntry->isActive($this->now));
        $this->assertFalse($expiredEntry->isActive($this->now));
    }

    public function testToArray(): void
    {
        $metadata = ['ip' => '192.168.1.1'];
        $entry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 42,
            deviceId: 'device-123',
            metadata: $metadata,
        );

        $array<mixed> = $entry->toArray();

        $this->assertSame($this->validJti, (is_array($array) ? $array['jti'] : (is_object($array) ? $array->jti : null)));
        $this->assertSame(TokenBlacklistEntry::TOKEN_TYPE_ACCESS, (is_array($array) ? $array['token_type'] : (is_object($array) ? $array->token_type : null)));
        $this->assertSame($this->futureExpiry->format(DateTimeImmutable::ATOM), (is_array($array) ? $array['expires_at'] : (is_object($array) ? $array->expires_at : null)));
        $this->assertSame($this->blacklistedTime->format(DateTimeImmutable::ATOM), (is_array($array) ? $array['blacklisted_at'] : (is_object($array) ? $array->blacklisted_at : null)));
        $this->assertSame(TokenBlacklistEntry::REASON_LOGOUT, (is_array($array) ? $array['reason'] : (is_object($array) ? $array->reason : null)));
        $this->assertSame('User logged out', (is_array($array) ? $array['reason_description'] : (is_object($array) ? $array->reason_description : null)));
        $this->assertSame(42, (is_array($array) ? $array['user_id'] : (is_object($array) ? $array->user_id : null)));
        $this->assertSame('device-123', (is_array($array) ? $array['device_id'] : (is_object($array) ? $array->device_id : null)));
        $this->assertSame($metadata, (is_array($array) ? $array['metadata'] : (is_object($array) ? $array->metadata : null)));
        $this->assertFalse((is_array($array) ? $array['is_security_related'] : (is_object($array) ? $array->is_security_related : null)));
        $this->assertTrue((is_array($array) ? $array['is_user_initiated'] : (is_object($array) ? $array->is_user_initiated : null)));
        $this->assertTrue((is_array($array) ? $array['is_active'] : (is_object($array) ? $array->is_active : null)));
        $this->assertIsInt((is_array($array) ? $array['priority'] : (is_object($array) ? $array->priority : null)));
    }

    public function testToDatabaseArray(): void
    {
        $metadata = ['ip' => '192.168.1.1'];
        $entry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 42,
            deviceId: 'device-123',
            metadata: $metadata,
        );

        $dbArray = $entry->toDatabaseArray();

        $this->assertSame($this->validJti, (is_array($dbArray) ? $dbArray['jti'] : (is_object($dbArray) ? $dbArray->jti : null)));
        $this->assertSame(TokenBlacklistEntry::TOKEN_TYPE_ACCESS, (is_array($dbArray) ? $dbArray['token_type'] : (is_object($dbArray) ? $dbArray->token_type : null)));
        $this->assertSame($this->futureExpiry->format('Y-m-d H:i:s'), (is_array($dbArray) ? $dbArray['expires_at'] : (is_object($dbArray) ? $dbArray->expires_at : null)));
        $this->assertSame($this->blacklistedTime->format('Y-m-d H:i:s'), (is_array($dbArray) ? $dbArray['blacklisted_at'] : (is_object($dbArray) ? $dbArray->blacklisted_at : null)));
        $this->assertSame(TokenBlacklistEntry::REASON_LOGOUT, (is_array($dbArray) ? $dbArray['reason'] : (is_object($dbArray) ? $dbArray->reason : null)));
        $this->assertSame(42, (is_array($dbArray) ? $dbArray['user_id'] : (is_object($dbArray) ? $dbArray->user_id : null)));
        $this->assertSame('device-123', (is_array($dbArray) ? $dbArray['device_id'] : (is_object($dbArray) ? $dbArray->device_id : null)));
        $this->assertSame('{"ip":"192.168.1.1"}', (is_array($dbArray) ? $dbArray['metadata'] : (is_object($dbArray) ? $dbArray->metadata : null)));
    }

    public function testToDatabaseArrayWithEmptyMetadata(): void
    {
        $entry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        $dbArray = $entry->toDatabaseArray();

        $this->assertNull((is_array($dbArray) ? $dbArray['metadata'] : (is_object($dbArray) ? $dbArray->metadata : null)));
    }

    public function testJsonSerialize(): void
    {
        $entry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );

        $this->assertEquals($entry->toArray(), $entry->jsonSerialize());
    }

    public function testEquals(): void
    {
        $entry1 = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 42,
            deviceId: 'device-123',
            metadata: ['key' => 'value'],
        );

        $entry2 = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 42,
            deviceId: 'device-123',
            metadata: ['key' => 'value'],
        );

        $entry3 = new TokenBlacklistEntry(
            jti: $this->validJti . 'different',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 42,
            deviceId: 'device-123',
            metadata: ['key' => 'value'],
        );

        $this->assertTrue($entry1->equals($entry2));
        $this->assertFalse($entry1->equals($entry3));
    }

    public function testToString(): void
    {
        $entry = new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 42,
            deviceId: 'device-123',
        );

        $string = $entry->toString();

        $this->assertStringContainsString('TokenBlacklistEntry(', $string);
        $this->assertStringContainsString($this->validJti, $string);
        $this->assertStringContainsString('type=access', $string);
        $this->assertStringContainsString('reason=user_logout', $string);
        $this->assertStringContainsString('user:42', $string);
        $this->assertStringContainsString('device:device-123', $string);

        // 檢查時間格式而不是具體時間
        $this->assertMatchesRegularExpression('/blacklisted=\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $string);

        $this->assertSame($string, (string) $entry);
    }

    public function testGetValidTokenTypes(): void
    {
        $types = TokenBlacklistEntry::getValidTokenTypes();
        $this->assertContains(TokenBlacklistEntry::TOKEN_TYPE_ACCESS, $types);
        $this->assertContains(TokenBlacklistEntry::TOKEN_TYPE_REFRESH, $types);
        $this->assertCount(2, $types);
    }

    public function testGetValidReasons(): void
    {
        $reasons = TokenBlacklistEntry::getValidReasons();
        $this->assertContains(TokenBlacklistEntry::REASON_LOGOUT, $reasons);
        $this->assertContains(TokenBlacklistEntry::REASON_SECURITY_BREACH, $reasons);
        $this->assertContains(TokenBlacklistEntry::REASON_PASSWORD_CHANGED, $reasons);
        $this->assertGreaterThanOrEqual(10, count($reasons));
    }

    public function testConstructorWithEmptyJti(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT ID (jti) cannot be empty');

        new TokenBlacklistEntry(
            jti: '',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );
    }

    public function testConstructorWithTooLongJti(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT ID (jti) cannot exceed 255 characters');

        new TokenBlacklistEntry(
            jti: str_repeat('a', 256),
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );
    }

    public function testConstructorWithInvalidTokenType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Token type must be one of: access, refresh');

        new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: 'invalid',
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );
    }

    public function testConstructorWithInvalidReason(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reason must be one of:');

        new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $this->blacklistedTime,
            reason: 'invalid_reason',
        );
    }

    public function testConstructorWithBlacklistedTimeTooOld(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Blacklisted time cannot be more than 1 year ago');

        $tooOldTime = new DateTimeImmutable('-2 years');

        new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $tooOldTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );
    }

    public function testConstructorWithBlacklistedTimeTooFuture(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Blacklisted time cannot be more than 1 year in the future');

        $tooFutureTime = new DateTimeImmutable('+2 years');

        new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $tooFutureTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
        );
    }

    public function testConstructorWithInvalidUserId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be a positive integer');

        $validBlacklistedTime = new DateTimeImmutable(); // 使用當前時間

        new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $validBlacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 0, // 無效的使用者 ID
        );
    }

    public function testConstructorWithEmptyDeviceId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device ID cannot be empty when provided');

        $validBlacklistedTime = new DateTimeImmutable();

        new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $validBlacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            deviceId: '',
        );
    }

    public function testConstructorWithTooLongDeviceId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device ID cannot exceed 255 characters');

        $validBlacklistedTime = new DateTimeImmutable();

        new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $validBlacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            deviceId: str_repeat('a', 256),
        );
    }

    public function testConstructorWithNonSerializableMetadata(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Metadata must be JSON serializable');

        $validBlacklistedTime = new DateTimeImmutable();

        // 建立一個無法 JSON 序列化的資源
        $resource = fopen('php://memory', 'r');

        new TokenBlacklistEntry(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            blacklistedAt: $validBlacklistedTime,
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            metadata: ['resource' => $resource],
        );

        if ($resource) {
            fclose($resource);
        }
    }

    public function testForAccountChangeWithInvalidChangeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Change type must be one of:');

        TokenBlacklistEntry::forAccountChange(
            jti: $this->validJti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: $this->futureExpiry,
            userId: 42,
            changeType: 'invalid_change_type',
        );
    }

    public function testFromArrayWithMissingRequiredField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: reason');

        TokenBlacklistEntry::fromArray([
            'jti' => $this->validJti,
            'token_type' => TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            'expires_at' => $this->futureExpiry,
            'blacklisted_at' => $this->blacklistedTime,
        ]);
    }
}
