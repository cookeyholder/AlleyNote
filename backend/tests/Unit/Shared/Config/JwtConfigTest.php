<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Config;

use App\Shared\Config\JwtConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class JwtConfigTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 備份原始環境變數
        $this->originalEnv = [
            'JWT_ALGORITHM' => $_ENV['JWT_ALGORITHM'] ?? null,
            'JWT_PRIVATE_KEY' => $_ENV['JWT_PRIVATE_KEY'] ?? null,
            'JWT_PUBLIC_KEY' => $_ENV['JWT_PUBLIC_KEY'] ?? null,
            'JWT_ISSUER' => $_ENV['JWT_ISSUER'] ?? null,
            'JWT_AUDIENCE' => $_ENV['JWT_AUDIENCE'] ?? null,
            'JWT_ACCESS_TOKEN_TTL' => $_ENV['JWT_ACCESS_TOKEN_TTL'] ?? null,
            'JWT_REFRESH_TOKEN_TTL' => $_ENV['JWT_REFRESH_TOKEN_TTL'] ?? null,
        ];
    }

    protected function tearDown(): void
    {
        // 恢復原始環境變數
        foreach ($this->originalEnv as $key => $value) {
            if ($value === null) {
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
        unset($_ENV['JWT_PRIVATE_KEY']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_PRIVATE_KEY 環境變數未設定');

        new JwtConfig();
    }

    public function testPublicKeyMissing(): void
    {
        $this->setValidEnvironmentVariables();
        unset($_ENV['JWT_PUBLIC_KEY']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_PUBLIC_KEY 環境變數未設定');

        new JwtConfig();
    }

    public function testInvalidPrivateKeyFormat(): void
    {
        $this->setValidEnvironmentVariables();
        $_ENV['JWT_PRIVATE_KEY'] = 'invalid-key-format';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_PRIVATE_KEY 格式無效，必須是 PEM 格式的私鑰');

        new JwtConfig();
    }

    public function testInvalidPublicKeyFormat(): void
    {
        $this->setValidEnvironmentVariables();
        $_ENV['JWT_PUBLIC_KEY'] = 'invalid-key-format';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_PUBLIC_KEY 格式無效，必須是 PEM 格式的公鑰');

        new JwtConfig();
    }

    public function testUnsupportedAlgorithm(): void
    {
        $this->setValidEnvironmentVariables();
        $_ENV['JWT_ALGORITHM'] = 'INVALID_ALGO';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的演算法: INVALID_ALGO');

        new JwtConfig();
    }

    public function testInvalidAccessTokenTtl(): void
    {
        $this->setValidEnvironmentVariables();
        $_ENV['JWT_ACCESS_TOKEN_TTL'] = '0';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_ACCESS_TOKEN_TTL 必須大於 0');

        new JwtConfig();
    }

    public function testInvalidRefreshTokenTtl(): void
    {
        $this->setValidEnvironmentVariables();
        $_ENV['JWT_REFRESH_TOKEN_TTL'] = '0';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWT_REFRESH_TOKEN_TTL 必須大於 0');

        new JwtConfig();
    }

    public function testRefreshTokenTtlLessThanAccessToken(): void
    {
        $this->setValidEnvironmentVariables();
        $_ENV['JWT_ACCESS_TOKEN_TTL'] = '7200';
        $_ENV['JWT_REFRESH_TOKEN_TTL'] = '3600';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Refresh token 有效期必須大於 access token 有效期');

        new JwtConfig();
    }

    public function testDefaultValues(): void
    {
        $this->setValidEnvironmentVariables();

        // 移除可選的環境變數來測試預設值
        unset($_ENV['JWT_ALGORITHM']);
        unset($_ENV['JWT_ISSUER']);
        unset($_ENV['JWT_AUDIENCE']);
        unset($_ENV['JWT_ACCESS_TOKEN_TTL']);
        unset($_ENV['JWT_REFRESH_TOKEN_TTL']);

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

        $this->assertEquals('test-issuer', $payload['iss']);
        $this->assertEquals('test-audience', $payload['aud']);
        $this->assertIsInt($payload['iat']);
        $this->assertIsInt($payload['nbf']);
        $this->assertEquals($payload['iat'], $payload['nbf']);
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

        $this->assertTrue($summary['private_key_configured']);
        $this->assertTrue($summary['public_key_configured']);

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
        $publicKey = $publicKeyDetails['key'];

        $_ENV['JWT_ALGORITHM'] = 'RS256';
        $_ENV['JWT_PRIVATE_KEY'] = str_replace("\n", '\\n', $privateKey);
        $_ENV['JWT_PUBLIC_KEY'] = str_replace("\n", '\\n', $publicKey);
        $_ENV['JWT_ISSUER'] = 'test-issuer';
        $_ENV['JWT_AUDIENCE'] = 'test-audience';
        $_ENV['JWT_ACCESS_TOKEN_TTL'] = '7200';
        $_ENV['JWT_REFRESH_TOKEN_TTL'] = '604800';
    }
}
