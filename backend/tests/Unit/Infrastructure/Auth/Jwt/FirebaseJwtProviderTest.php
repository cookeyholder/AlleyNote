<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Auth\Jwt;

use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\TokenExpiredException;
use App\Domains\Auth\Exceptions\TokenParsingException;
use App\Domains\Auth\Exceptions\TokenValidationException;
use App\Infrastructure\Auth\Jwt\FirebaseJwtProvider;
use App\Shared\Config\JwtConfig;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * FirebaseJwtProvider 單元測試.
 *
 * 測試 Firebase JWT Provider 的所有功能，包括 token 產生、驗證、解析等
 */
#[CoversClass(FirebaseJwtProvider::class)]
final class FirebaseJwtProviderTest extends TestCase
{
    private string $validPrivateKey;

    private string $validPublicKey;

    protected function setUp(): void
    {
        // 建立測試用的 RSA 金鑰對
        $this->generateTestKeys();

        // 設定測試環境變數
        $_ENV['JWT_ALGORITHM'] = 'RS256';
        $_ENV['JWT_PRIVATE_KEY'] = str_replace("\n", '\\n', $this->validPrivateKey);
        $_ENV['JWT_PUBLIC_KEY'] = str_replace("\n", '\\n', $this->validPublicKey);
        $_ENV['JWT_ISSUER'] = 'alleynote-test';
        $_ENV['JWT_AUDIENCE'] = 'alleynote-app';
        $_ENV['JWT_ACCESS_TOKEN_TTL'] = '3600'; // 1 小時
        $_ENV['JWT_REFRESH_TOKEN_TTL'] = '2592000'; // 30 天
    }

    protected function tearDown(): void
    {
        // 清理環境變數
        unset(
            $_ENV['JWT_ALGORITHM'],
            $_ENV['JWT_PRIVATE_KEY'],
            $_ENV['JWT_PUBLIC_KEY'],
            $_ENV['JWT_ISSUER'],
            $_ENV['JWT_AUDIENCE'],
            $_ENV['JWT_ACCESS_TOKEN_TTL'],
            $_ENV['JWT_REFRESH_TOKEN_TTL'],
        );
    }

    /**
     * 測試建構函式成功初始化.
     */
    public function testConstructorSuccessfullyInitializesProvider(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);
        $this->assertInstanceOf(FirebaseJwtProvider::class, $provider);
    }

    /**
     * 測試成功產生 access token.
     */
    public function testGenerateAccessTokenSuccessfully(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $payload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $token = $provider->generateAccessToken($payload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // 驗證 token 結構（JWT 應該有三個部分）
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * 測試成功產生 refresh token.
     */
    public function testGenerateRefreshTokenSuccessfully(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $payload = [
            'sub' => 'user-123',
            'device_id' => 'device-456',
        ];

        $token = $provider->generateRefreshToken($payload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // 驗證 token 結構
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * 測試產生帶自訂 TTL 的 access token.
     */
    public function testGenerateAccessTokenWithCustomTtl(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $payload = ['sub' => 'user-123'];
        $customTtl = 1800; // 30 分鐘

        $token = $provider->generateAccessToken($payload, $customTtl);
        $decodedPayload = $provider->parseTokenUnsafe($token);

        // 驗證過期時間
        $expectedExp = time() + $customTtl;
        $actualExp = $decodedPayload['exp'];

        // 允許 5 秒的誤差
        $this->assertLessThanOrEqual(5, abs($expectedExp - $actualExp));
    }

    /**
     * 測試產生帶自訂 TTL 的 refresh token.
     */
    public function testGenerateRefreshTokenWithCustomTtl(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $payload = ['sub' => 'user-123'];
        $customTtl = 86400; // 1 天

        $token = $provider->generateRefreshToken($payload, $customTtl);
        $decodedPayload = $provider->parseTokenUnsafe($token);

        // 驗證過期時間
        $expectedExp = time() + $customTtl;
        $actualExp = $decodedPayload['exp'];

        // 允許 5 秒的誤差
        $this->assertLessThanOrEqual(5, abs($expectedExp - $actualExp));
    }

    /**
     * 測試成功驗證有效的 access token.
     */
    public function testValidateAccessTokenSuccessfully(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $originalPayload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'role' => 'admin',
        ];

        $token = $provider->generateAccessToken($originalPayload);
        $validatedPayload = $provider->validateToken($token, 'access');

        // 驗證自訂載荷
        $this->assertEquals('user-123', $validatedPayload['sub']);
        $this->assertEquals('test@example.com', $validatedPayload['email']);
        $this->assertEquals('admin', $validatedPayload['role']);

        // 驗證標準宣告
        $this->assertEquals('alleynote-test', $validatedPayload['iss']);
        $this->assertEquals('alleynote-app', $validatedPayload['aud']);
        $this->assertEquals('access', $validatedPayload['type']);
        $this->assertArrayHasKey('iat', $validatedPayload);
        $this->assertArrayHasKey('exp', $validatedPayload);
        $this->assertArrayHasKey('jti', $validatedPayload);
    }

    /**
     * 測試成功驗證有效的 refresh token.
     */
    public function testValidateRefreshTokenSuccessfully(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $originalPayload = [
            'sub' => 'user-123',
            'device_id' => 'device-456',
        ];

        $token = $provider->generateRefreshToken($originalPayload);
        $validatedPayload = $provider->validateToken($token, 'refresh');

        $this->assertEquals('user-123', $validatedPayload['sub']);
        $this->assertEquals('device-456', $validatedPayload['device_id']);
        $this->assertEquals('refresh', $validatedPayload['type']);
    }

    /**
     * 測試驗證空 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForEmptyToken(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token 不能為空');

        $provider->validateToken('');
    }

    /**
     * 測試驗證格式無效的 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForMalformedToken(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $this->expectException(InvalidTokenException::class);

        $provider->validateToken('invalid.token');
    }

    /**
     * 測試驗證過期 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForExpiredToken(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        // 產生一個已過期的 token（TTL = -1 秒）
        $payload = ['sub' => 'user-123'];
        $expiredToken = $provider->generateAccessToken($payload, -1);

        // 等待一秒確保 token 過期
        sleep(1);

        $this->expectException(TokenExpiredException::class);

        $provider->validateToken($expiredToken);
    }

    /**
     * 測試驗證錯誤類型的 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForWrongTokenType(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $token = $provider->generateAccessToken(['sub' => 'user-123']);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token 類型錯誤，預期: refresh，實際: access');

        $provider->validateToken($token, 'refresh');
    }

    /**
     * 測試驗證帶有無效簽名的 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForInvalidSignature(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $token = $provider->generateAccessToken(['sub' => 'user-123']);

        // 更大幅度地破壞簽名 - 將整個簽名部分替換為無效的簽名
        $parts = explode('.', $token);
        $parts[2] = 'invalid-signature-12345'; // 完全替換簽名部分

        $corruptedToken = implode('.', $parts);

        $this->expectException(TokenValidationException::class);

        $provider->validateToken($corruptedToken);
    }

    /**
     * 測試成功解析不安全的 token.
     */
    public function testParseTokenUnsafeSuccessfully(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $originalPayload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
        ];

        $token = $provider->generateAccessToken($originalPayload);
        $parsedPayload = $provider->parseTokenUnsafe($token);

        $this->assertEquals('user-123', $parsedPayload['sub']);
        $this->assertEquals('test@example.com', $parsedPayload['email']);
        $this->assertEquals('access', $parsedPayload['type']);
    }

    /**
     * 測試解析空 token 時拋出例外.
     */
    public function testParseTokenUnsafeThrowsExceptionForEmptyToken(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $this->expectException(TokenParsingException::class);

        $provider->parseTokenUnsafe('');
    }

    /**
     * 測試解析格式無效的 token 時拋出例外.
     */
    public function testParseTokenUnsafeThrowsExceptionForInvalidFormat(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $this->expectException(TokenParsingException::class);
        $this->expectExceptionMessage('Token 格式無效，必須包含三個部分');

        $provider->parseTokenUnsafe('invalid.token');
    }

    /**
     * 測試成功取得 token 的過期時間.
     */
    public function testGetTokenExpirationSuccessfully(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $token = $provider->generateAccessToken(['sub' => 'user-123']);
        $expiration = $provider->getTokenExpiration($token);

        $this->assertInstanceOf(DateTimeImmutable::class, $expiration);

        // 驗證過期時間大約在 1 小時後
        $expectedTime = new DateTimeImmutable()->getTimestamp() + 3600;
        $actualTime = $expiration->getTimestamp();

        // 允許 10 秒的誤差
        $this->assertLessThanOrEqual(10, abs($expectedTime - $actualTime));
    }

    /**
     * 測試取得無效 token 的過期時間回傳 null.
     */
    public function testGetTokenExpirationReturnsNullForInvalidToken(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $expiration = $provider->getTokenExpiration('invalid-token');

        $this->assertNull($expiration);
    }

    /**
     * 測試檢查 token 是否已過期.
     */
    public function testIsTokenExpiredForValidToken(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $token = $provider->generateAccessToken(['sub' => 'user-123']);
        $isExpired = $provider->isTokenExpired($token);

        $this->assertFalse($isExpired);
    }

    /**
     * 測試檢查過期 token.
     */
    public function testIsTokenExpiredForExpiredToken(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        // 產生已過期的 token
        $expiredToken = $provider->generateAccessToken(['sub' => 'user-123'], -1);

        // 等待確保過期
        sleep(1);

        $isExpired = $provider->isTokenExpired($expiredToken);

        $this->assertTrue($isExpired);
    }

    /**
     * 測試檢查無效 token 的過期狀態回傳 false.
     */
    public function testIsTokenExpiredReturnsFalseForInvalidToken(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $isExpired = $provider->isTokenExpired('invalid-token');

        $this->assertFalse($isExpired);
    }

    /**
     * 測試驗證帶有錯誤 issuer 的 token.
     */
    public function testValidateTokenThrowsExceptionForInvalidIssuer(): void
    {
        // 建立一個帶有不同 issuer 的 token
        // 先暫時修改環境變數
        $originalIssuer = $_ENV['JWT_ISSUER'];
        $_ENV['JWT_ISSUER'] = 'different-issuer';

        $differentConfig = new JwtConfig();
        $differentProvider = new FirebaseJwtProvider($differentConfig);
        $token = $differentProvider->generateAccessToken(['sub' => 'user-123']);

        // 還原環境變數
        $_ENV['JWT_ISSUER'] = $originalIssuer;

        $this->expectException(TokenValidationException::class);
        $this->expectExceptionMessage('Token issuer 無效');

        // 使用原來的 provider 驗證（有不同的 issuer）
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);
        $provider->validateToken($token);
    }

    /**
     * 測試驗證帶有錯誤 audience 的 token.
     */
    public function testValidateTokenThrowsExceptionForInvalidAudience(): void
    {
        // 建立一個帶有不同 audience 的 token
        // 先暫時修改環境變數
        $originalAudience = $_ENV['JWT_AUDIENCE'];
        $_ENV['JWT_AUDIENCE'] = 'different-audience';

        $differentConfig = new JwtConfig();
        $differentProvider = new FirebaseJwtProvider($differentConfig);
        $token = $differentProvider->generateAccessToken(['sub' => 'user-123']);

        // 還原環境變數
        $_ENV['JWT_AUDIENCE'] = $originalAudience;

        $this->expectException(TokenValidationException::class);
        $this->expectExceptionMessage('Token audience 無效');

        // 使用原來的 provider 驗證（有不同的 audience）
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);
        $provider->validateToken($token);
    }

    /**
     * 測試產生的 token 包含唯一的 JTI.
     */
    public function testGeneratedTokensHaveUniqueJti(): void
    {
        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);

        $payload = ['sub' => 'user-123'];

        $token1 = $provider->generateAccessToken($payload);
        $token2 = $provider->generateAccessToken($payload);

        $payload1 = $provider->parseTokenUnsafe($token1);
        $payload2 = $provider->parseTokenUnsafe($token2);

        $this->assertNotEquals($payload1['jti'], $payload2['jti']);
        $this->assertNotEmpty($payload1['jti']);
        $this->assertNotEmpty($payload2['jti']);
    }

    /**
     * 建立測試用的 RSA 金鑰對.
     */
    private function generateTestKeys(): void
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);

        if ($resource === false) {
            $this->fail('無法產生測試金鑰對');
        }

        // 初始化變數
        $this->validPrivateKey = '';

        // 匯出私鑰
        if (!openssl_pkey_export($resource, $this->validPrivateKey)) {
            $this->fail('無法匯出私鑰');
        }

        // 匯出公鑰
        $keyDetails = openssl_pkey_get_details($resource);
        if ($keyDetails === false) {
            $this->fail('無法取得公鑰');
        }

        $this->validPublicKey = $keyDetails['key'];
    }
}
