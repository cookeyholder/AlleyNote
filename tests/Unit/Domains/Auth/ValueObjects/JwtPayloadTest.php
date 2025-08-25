<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\ValueObjects;

use AlleyNote\Domains\Auth\ValueObjects\JwtPayload;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * JWT Payload Value Object 單元測試.
 */
final class JwtPayloadTest extends TestCase
{
    private DateTimeImmutable $baseTime;

    private DateTimeImmutable $futureTime;

    protected function setUp(): void
    {
        $now = new DateTimeImmutable();
        $this->baseTime = $now;
        $this->futureTime = $now->add(new DateInterval('PT1H')); // 1小時後
    }

    public function testConstructorWithValidData(): void
    {
        $payload = new JwtPayload(
            jti: 'test-jti-123',
            sub: '42',
            iss: 'alleynote',
            aud: ['web', 'mobile'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );

        $this->assertSame('test-jti-123', $payload->getJti());
        $this->assertSame('42', $payload->getSubject());
        $this->assertSame(42, $payload->getUserId());
        $this->assertSame('alleynote', $payload->getIssuer());
        $this->assertSame(['web', 'mobile'], $payload->getAudience());
        $this->assertEquals($this->baseTime, $payload->getIssuedAt());
        $this->assertEquals($this->futureTime, $payload->getExpiresAt());
        $this->assertNull($payload->getNotBefore());
        $this->assertSame([], $payload->getCustomClaims());
    }

    public function testConstructorWithNotBeforeAndCustomClaims(): void
    {
        $nbf = new DateTimeImmutable('2024-01-01 12:30:00');
        $customClaims = ['role' => 'admin', 'permissions' => ['read', 'write']];

        $payload = new JwtPayload(
            jti: 'test-jti-456',
            sub: '24',
            iss: 'alleynote',
            aud: ['api'],
            iat: $this->baseTime,
            exp: $this->futureTime,
            nbf: $nbf,
            customClaims: $customClaims,
        );

        $this->assertEquals($nbf, $payload->getNotBefore());
        $this->assertSame($customClaims, $payload->getCustomClaims());
        $this->assertSame('admin', $payload->getCustomClaim('role'));
        $this->assertSame(['read', 'write'], $payload->getCustomClaim('permissions'));
        $this->assertNull($payload->getCustomClaim('non-existent'));
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'jti' => 'array-jti-789',
            'sub' => '99',
            'iss' => 'test-issuer',
            'aud' => ['client1', 'client2'],
            'iat' => $this->baseTime->getTimestamp(),
            'exp' => $this->futureTime->getTimestamp(),
            'custom_field' => 'custom_value',
        ];

        $payload = JwtPayload::fromArray($data);

        $this->assertSame('array-jti-789', $payload->getJti());
        $this->assertSame('99', $payload->getSubject());
        $this->assertSame('test-issuer', $payload->getIssuer());
        $this->assertSame(['client1', 'client2'], $payload->getAudience());
        $this->assertEquals($this->baseTime->getTimestamp(), $payload->getIssuedAt()->getTimestamp());
        $this->assertEquals($this->futureTime->getTimestamp(), $payload->getExpiresAt()->getTimestamp());
        $this->assertSame('custom_value', $payload->getCustomClaim('custom_field'));
    }

    public function testFromArrayWithStringAudience(): void
    {
        $data = [
            'jti' => 'string-aud-jti',
            'sub' => '1',
            'iss' => 'issuer',
            'aud' => 'single-client',
            'iat' => $this->baseTime->getTimestamp(),
            'exp' => $this->futureTime->getTimestamp(),
        ];

        $payload = JwtPayload::fromArray($data);

        $this->assertSame(['single-client'], $payload->getAudience());
    }

    public function testFromArrayWithNotBefore(): void
    {
        $nbf = new DateTimeImmutable('2024-01-01 12:30:00');
        $data = [
            'jti' => 'nbf-jti',
            'sub' => '1',
            'iss' => 'issuer',
            'aud' => ['client'],
            'iat' => $this->baseTime->getTimestamp(),
            'exp' => $this->futureTime->getTimestamp(),
            'nbf' => $nbf->getTimestamp(),
        ];

        $payload = JwtPayload::fromArray($data);

        $this->assertEquals($nbf->getTimestamp(), $payload->getNotBefore()->getTimestamp());
    }

    public function testIsExpired(): void
    {
        $payload = new JwtPayload(
            jti: 'exp-test',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );

        $this->assertFalse($payload->isExpired($this->baseTime));
        $this->assertFalse($payload->isExpired($this->futureTime->modify('-1 second')));
        $this->assertTrue($payload->isExpired($this->futureTime));
        $this->assertTrue($payload->isExpired($this->futureTime->modify('+1 hour')));
    }

    public function testIsActive(): void
    {
        $now = new DateTimeImmutable();
        $nbf = $now->add(new DateInterval('PT30M')); // 30分鐘後生效
        $exp = $now->add(new DateInterval('PT2H'));  // 2小時後過期

        $payload = new JwtPayload(
            jti: 'active-test',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $now,
            exp: $exp,
            nbf: $nbf,
        );

        // 在 nbf 之前
        $beforeNbf = $now->add(new DateInterval('PT15M')); // 15分鐘後（還未生效）
        $this->assertFalse($payload->isActive($beforeNbf));

        // 在 nbf 和 exp 之間
        $afterNbf = $now->add(new DateInterval('PT1H')); // 1小時後（已生效但未過期）
        $this->assertTrue($payload->isActive($afterNbf));

        // 在 exp 之後
        $afterExp = $now->add(new DateInterval('PT3H')); // 3小時後（已過期）
        $this->assertFalse($payload->isActive($afterExp));
    }

    public function testIsActiveWithoutNotBefore(): void
    {
        $payload = new JwtPayload(
            jti: 'no-nbf-test',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );

        $this->assertTrue($payload->isActive($this->baseTime));
        $this->assertTrue($payload->isActive($this->futureTime->modify('-1 second')));
        $this->assertFalse($payload->isActive($this->futureTime));
    }

    public function testHasAudience(): void
    {
        $payload = new JwtPayload(
            jti: 'aud-test',
            sub: '1',
            iss: 'issuer',
            aud: ['web', 'mobile', 'api'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );

        $this->assertTrue($payload->hasAudience('web'));
        $this->assertTrue($payload->hasAudience('mobile'));
        $this->assertTrue($payload->hasAudience('api'));
        $this->assertFalse($payload->hasAudience('desktop'));
        $this->assertFalse($payload->hasAudience(''));
    }

    public function testToArray(): void
    {
        $customClaims = ['role' => 'admin'];
        $payload = new JwtPayload(
            jti: 'to-array-test',
            sub: '123',
            iss: 'test-issuer',
            aud: ['client1', 'client2'],
            iat: $this->baseTime,
            exp: $this->futureTime,
            customClaims: $customClaims,
        );

        $expected = [
            'jti' => 'to-array-test',
            'sub' => '123',
            'iss' => 'test-issuer',
            'aud' => ['client1', 'client2'],
            'iat' => $this->baseTime->getTimestamp(),
            'exp' => $this->futureTime->getTimestamp(),
            'role' => 'admin',
        ];

        $this->assertEquals($expected, $payload->toArray());
    }

    public function testToArrayWithSingleAudience(): void
    {
        $payload = new JwtPayload(
            jti: 'single-aud-test',
            sub: '1',
            iss: 'issuer',
            aud: ['single-client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );

        $array = $payload->toArray();
        $this->assertSame('single-client', $array['aud']);
    }

    public function testJsonSerialize(): void
    {
        $payload = new JwtPayload(
            jti: 'json-test',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );

        $this->assertEquals($payload->toArray(), $payload->jsonSerialize());
    }

    public function testEquals(): void
    {
        $payload1 = new JwtPayload(
            jti: 'equals-test',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
            customClaims: ['role' => 'user'],
        );

        $payload2 = new JwtPayload(
            jti: 'equals-test',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
            customClaims: ['role' => 'user'],
        );

        $payload3 = new JwtPayload(
            jti: 'different-jti',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
            customClaims: ['role' => 'user'],
        );

        $this->assertTrue($payload1->equals($payload2));
        $this->assertFalse($payload1->equals($payload3));
    }

    public function testToString(): void
    {
        $payload = new JwtPayload(
            jti: 'to-string-test',
            sub: '42',
            iss: 'alleynote',
            aud: ['web', 'mobile'],
            iat: $this->baseTime,
            exp: $this->futureTime,
            customClaims: ['role' => 'admin', 'dept' => 'IT'],
        );

        $result = $payload->toString();

        // 檢查字串格式而不是具體時間
        $this->assertStringContainsString('JwtPayload(jti=to-string-test', $result);
        $this->assertStringContainsString('sub=42', $result);
        $this->assertStringContainsString('iss=alleynote', $result);
        $this->assertStringContainsString('aud=[web, mobile]', $result);
        $this->assertStringContainsString('nbf=null', $result);
        $this->assertStringContainsString('customClaims=2', $result);

        // 檢查時間格式（YYYY-MM-DD HH:MM:SS）
        $this->assertMatchesRegularExpression('/iat=\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result);
        $this->assertMatchesRegularExpression('/exp=\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result);

        // 測試 __toString 魔術方法
        $this->assertSame($result, (string) $payload);
    }

    public function testConstructorWithEmptyJti(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT ID (jti) cannot be empty');

        new JwtPayload(
            jti: '',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );
    }

    public function testConstructorWithTooLongJti(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT ID (jti) cannot exceed 255 characters');

        new JwtPayload(
            jti: str_repeat('a', 256),
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );
    }

    public function testConstructorWithEmptySubject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject (sub) cannot be empty');

        new JwtPayload(
            jti: 'test-jti',
            sub: '',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );
    }

    public function testConstructorWithInvalidSubject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject (sub) must be a valid positive integer');

        new JwtPayload(
            jti: 'test-jti',
            sub: '0', // 0 不是有效的正整數
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );
    }

    public function testConstructorWithNonNumericSubject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Subject (sub) must be a valid positive integer');

        new JwtPayload(
            jti: 'test-jti',
            sub: 'not-a-number',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );
    }

    public function testConstructorWithEmptyIssuer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Issuer (iss) cannot be empty');

        new JwtPayload(
            jti: 'test-jti',
            sub: '1',
            iss: '',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );
    }

    public function testConstructorWithEmptyAudience(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audience (aud) cannot be empty');

        new JwtPayload(
            jti: 'test-jti',
            sub: '1',
            iss: 'issuer',
            aud: [],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );
    }

    public function testConstructorWithInvalidAudienceValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All audience values must be non-empty strings');

        new JwtPayload(
            jti: 'test-jti',
            sub: '1',
            iss: 'issuer',
            aud: ['valid', ''],
            iat: $this->baseTime,
            exp: $this->futureTime,
        );
    }

    public function testConstructorWithExpirationBeforeIssuedTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration time (exp) must be after issued time (iat)');

        new JwtPayload(
            jti: 'test-jti',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->futureTime,
            exp: $this->baseTime,
        );
    }

    public function testConstructorWithNotBeforeAfterExpiration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not before time (nbf) cannot be after expiration time (exp)');

        $tooLateNbf = $this->futureTime->modify('+1 hour');

        new JwtPayload(
            jti: 'test-jti',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
            nbf: $tooLateNbf,
        );
    }

    public function testConstructorWithReservedCustomClaim(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot use reserved claim 'jti' as custom claim");

        new JwtPayload(
            jti: 'test-jti',
            sub: '1',
            iss: 'issuer',
            aud: ['client'],
            iat: $this->baseTime,
            exp: $this->futureTime,
            customClaims: ['jti' => 'should-not-work'],
        );
    }

    public function testFromArrayWithMissingRequiredField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: sub');

        JwtPayload::fromArray([
            'jti' => 'test-jti',
            'iss' => 'issuer',
            'aud' => ['client'],
            'iat' => $this->baseTime->getTimestamp(),
            'exp' => $this->futureTime->getTimestamp(),
        ]);
    }
}
