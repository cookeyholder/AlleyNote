<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Config;

use App\Shared\Config\JwtConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class JwtConfigTest extends TestCase
{
    private array<mixed> $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 備份原始環境變數
        $this->originalEnv = [
            'JWT_ALGORITHM' => (is_array($_ENV) ? $_ENV['JWT_ALGORITHM'] : (is_object($_ENV) ? $_ENV->JWT_ALGORITHM : null)) ?? null,
            'JWT_PRIVATE_KEY' => (is_array($_ENV) ? $_ENV['JWT_PRIVATE_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PRIVATE_KEY : null)) ?? null,
            'JWT_PUBLIC_KEY' => (is_array($_ENV) ? $_ENV['JWT_PUBLIC_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PUBLIC_KEY : null)) ?? null,
            'JWT_ISSUER' => (is_array($_ENV) ? $_ENV['JWT_ISSUER'] : (is_object($_ENV) ? $_ENV->JWT_ISSUER : null)) ?? null,
            'JWT_AUDIENCE' => (is_array($_ENV) ? $_ENV['JWT_AUDIENCE'] : (is_object($_ENV) ? $_ENV->JWT_AUDIENCE : null)) ?? null,
            'JWT_ACCESS_TOKEN_TTL' => (is_array($_ENV) ? $_ENV['JWT_ACCESS_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_ACCESS_TOKEN_TTL : null)) ?? null,
            'JWT_REFRESH_TOKEN_TTL' => (is_array($_ENV) ? $_ENV['JWT_REFRESH_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_REFRESH_TOKEN_TTL : null)) ?? null,
        ];
    }

    protected function tearDown(): void
    {
        // 恢復原始環境變數
        foreach ($this->originalEnv as $key => $value) {
            if (value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }

        parent::tearDown();
    }

    public function testSuccessfulConfigurationLoad(): void
    {
        $this->setValidEnvironmentVariables();

        $config = new JwtConfig();

        $this->assertEquals('RS256', $config->getAlgorithm());
        $this->assertEquals('test-issuer', $config->getIssuer());
        $this->assertEquals('test-audience', $config->getAudience());
        $this->assertEquals(7200, $config->getAccessTokenTtl());
        $this->assertEquals(604800, $config->getRefreshTokenTtl());
        $this->assertTrue($config->isConfigured());
    }

    public function testPrivateKeyMissing(): void
    {
        $this->setValidEnvironmentVariables();
        unset((is_array($_ENV) ? $_ENV['JWT_PRIVATE_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PRIVATE_KEY : null)));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_PRIVATE_KEY 環境變數未設定');

        new JwtConfig();
    }

    public function testPublicKeyMissing(): void
    {
        $this->setValidEnvironmentVariables();
        unset((is_array($_ENV) ? $_ENV['JWT_PUBLIC_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PUBLIC_KEY : null)));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_PUBLIC_KEY 環境變數未設定');

        new JwtConfig();
    }

    public function testInvalidPrivateKeyFormat(): void
    {
        $this->setValidEnvironmentVariables();
        (is_array($_ENV) ? $_ENV['JWT_PRIVATE_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PRIVATE_KEY : null)) = 'invalid-key-format';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_PRIVATE_KEY 格式無效，必須是 PEM 格式的私鑰');

        new JwtConfig();
    }

    public function testInvalidPublicKeyFormat(): void
    {
        $this->setValidEnvironmentVariables();
        (is_array($_ENV) ? $_ENV['JWT_PUBLIC_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PUBLIC_KEY : null)) = 'invalid-key-format';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_PUBLIC_KEY 格式無效，必須是 PEM 格式的公鑰');

        new JwtConfig();
    }

    public function testUnsupportedAlgorithm(): void
    {
        $this->setValidEnvironmentVariables();
        (is_array($_ENV) ? $_ENV['JWT_ALGORITHM'] : (is_object($_ENV) ? $_ENV->JWT_ALGORITHM : null)) = 'HS256';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的演算法: HS256');

        new JwtConfig();
    }

    public function testInvalidAccessTokenTtl(): void
    {
        $this->setValidEnvironmentVariables();
        (is_array($_ENV) ? $_ENV['JWT_ACCESS_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_ACCESS_TOKEN_TTL : null)) = '0';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_ACCESS_TOKEN_TTL 必須大於 0');

        new JwtConfig();
    }

    public function testInvalidRefreshTokenTtl(): void
    {
        $this->setValidEnvironmentVariables();
        (is_array($_ENV) ? $_ENV['JWT_REFRESH_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_REFRESH_TOKEN_TTL : null)) = '0';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_REFRESH_TOKEN_TTL 必須大於 0');

        new JwtConfig();
    }

    public function testRefreshTokenTtlLessThanAccessToken(): void
    {
        $this->setValidEnvironmentVariables();
        (is_array($_ENV) ? $_ENV['JWT_ACCESS_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_ACCESS_TOKEN_TTL : null)) = '7200';
        (is_array($_ENV) ? $_ENV['JWT_REFRESH_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_REFRESH_TOKEN_TTL : null)) = '3600';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token 有效期必須大於 access token 有效期');

        new JwtConfig();
    }

    public function testDefaultValues(): void
    {
        $this->setValidEnvironmentVariables();

        // 移除可選的環境變數來測試預設值
        unset((is_array($_ENV) ? $_ENV['JWT_ALGORITHM'] : (is_object($_ENV) ? $_ENV->JWT_ALGORITHM : null)));
        unset((is_array($_ENV) ? $_ENV['JWT_ISSUER'] : (is_object($_ENV) ? $_ENV->JWT_ISSUER : null)));
        unset((is_array($_ENV) ? $_ENV['JWT_AUDIENCE'] : (is_object($_ENV) ? $_ENV->JWT_AUDIENCE : null)));
        unset((is_array($_ENV) ? $_ENV['JWT_ACCESS_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_ACCESS_TOKEN_TTL : null)));
        unset((is_array($_ENV) ? $_ENV['JWT_REFRESH_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_REFRESH_TOKEN_TTL : null)));

        $config = new JwtConfig();

        $this->assertEquals('RS256', $config->getAlgorithm());
        $this->assertEquals('alleynote-api', $config->getIssuer());
        $this->assertEquals('alleynote-client', $config->getAudience());
        $this->assertEquals(3600, $config->getAccessTokenTtl());
        $this->assertEquals(2592000, $config->getRefreshTokenTtl());
    }

    public function testGetBasePayload(): void
    {
        $this->setValidEnvironmentVariables();

        $config = new JwtConfig();
        $payload = $config->getBasePayload();

        $this->assertArrayHasKey('iss', $payload);
        $this->assertArrayHasKey('aud', $payload);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('nbf', $payload);

        $this->assertEquals('test-issuer', (is_array($payload) && isset((is_array($payload) ? $payload['iss'] : (is_object($payload) ? $payload->iss : null)))) ? (is_array($payload) ? $payload['iss'] : (is_object($payload) ? $payload->iss : null)) : null);
        $this->assertEquals('test-audience', (is_array($payload) && isset((is_array($payload) ? $payload['aud'] : (is_object($payload) ? $payload->aud : null)))) ? (is_array($payload) ? $payload['aud'] : (is_object($payload) ? $payload->aud : null)) : null);
        $this->assertIsInt((is_array($payload) && isset((is_array($payload) ? $payload['iat'] : (is_object($payload) ? $payload->iat : null)))) ? (is_array($payload) ? $payload['iat'] : (is_object($payload) ? $payload->iat : null)) : null);
        $this->assertIsInt((is_array($payload) && isset((is_array($payload) ? $payload['nbf'] : (is_object($payload) ? $payload->nbf : null)))) ? (is_array($payload) ? $payload['nbf'] : (is_object($payload) ? $payload->nbf : null)) : null);
        $this->assertEquals((is_array($payload) && isset((is_array($payload) ? $payload['iat'] : (is_object($payload) ? $payload->iat : null)))) ? (is_array($payload) ? $payload['iat'] : (is_object($payload) ? $payload->iat : null)) : null, (is_array($payload) && isset((is_array($payload) ? $payload['nbf'] : (is_object($payload) ? $payload->nbf : null)))) ? (is_array($payload) ? $payload['nbf'] : (is_object($payload) ? $payload->nbf : null)) : null);
    }

    public function testGetConfigSummary(): void
    {
        $this->setValidEnvironmentVariables();

        $config = new JwtConfig();
        $summary = $config->getConfigSummary();

        $this->assertArrayHasKey('algorithm', $summary);
        $this->assertArrayHasKey('issuer', $summary);
        $this->assertArrayHasKey('audience', $summary);
        $this->assertArrayHasKey('access_token_ttl', $summary);
        $this->assertArrayHasKey('refresh_token_ttl', $summary);
        $this->assertArrayHasKey('private_key_configured', $summary);
        $this->assertArrayHasKey('public_key_configured', $summary);

        $this->assertTrue((is_array($summary) && isset((is_array($summary) ? $summary['private_key_configured'] : (is_object($summary) ? $summary->private_key_configured : null)))) ? (is_array($summary) ? $summary['private_key_configured'] : (is_object($summary) ? $summary->private_key_configured : null)) : null);
        $this->assertTrue((is_array($summary) && isset((is_array($summary) ? $summary['public_key_configured'] : (is_object($summary) ? $summary->public_key_configured : null)))) ? (is_array($summary) ? $summary['public_key_configured'] : (is_object($summary) ? $summary->public_key_configured : null)) : null);

        // 確認摘要中不包含實際的金鑰內容
        $this->assertArrayNotHasKey('private_key', $summary);
        $this->assertArrayNotHasKey('public_key', $summary);
    }

    public function testExpiryTimestamps(): void
    {
        $this->setValidEnvironmentVariables();

        $config = new JwtConfig();
        $now = time();

        $accessExpiry = $config->getAccessTokenExpiryTimestamp();
        $refreshExpiry = $config->getRefreshTokenExpiryTimestamp();

        $this->assertGreaterThanOrEqual($now + 7200, $accessExpiry);
        $this->assertGreaterThanOrEqual($now + 604800, $refreshExpiry);
        $this->assertGreaterThan($accessExpiry, $refreshExpiry);
    }

    private function setValidEnvironmentVariables(): void
    {
        // 生成測試用的金鑰對
        $keyResource = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($keyResource, $privateKey);
        $publicKeyDetails = openssl_pkey_get_details($keyResource);
        $publicKey = (is_array($publicKeyDetails) && isset((is_array($publicKeyDetails) ? $publicKeyDetails['key'] : (is_object($publicKeyDetails) ? $publicKeyDetails->key : null)))) ? (is_array($publicKeyDetails) ? $publicKeyDetails['key'] : (is_object($publicKeyDetails) ? $publicKeyDetails->key : null)) : null;

        (is_array($_ENV) ? $_ENV['JWT_ALGORITHM'] : (is_object($_ENV) ? $_ENV->JWT_ALGORITHM : null)) = 'RS256';
        (is_array($_ENV) ? $_ENV['JWT_PRIVATE_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PRIVATE_KEY : null)) = str_replace("\n", '\\n', $privateKey);
        (is_array($_ENV) ? $_ENV['JWT_PUBLIC_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PUBLIC_KEY : null)) = str_replace("\n", '\\n', $publicKey);
        (is_array($_ENV) ? $_ENV['JWT_ISSUER'] : (is_object($_ENV) ? $_ENV->JWT_ISSUER : null)) = 'test-issuer';
        (is_array($_ENV) ? $_ENV['JWT_AUDIENCE'] : (is_object($_ENV) ? $_ENV->JWT_AUDIENCE : null)) = 'test-audience';
        (is_array($_ENV) ? $_ENV['JWT_ACCESS_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_ACCESS_TOKEN_TTL : null)) = '7200';
        (is_array($_ENV) ? $_ENV['JWT_REFRESH_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_REFRESH_TOKEN_TTL : null)) = '604800';
    }
}
