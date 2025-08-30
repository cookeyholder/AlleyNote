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
use InvalidArgumentException;
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
    private FirebaseJwtProvider $provider;

    private string $validPrivateKey;

    private string $validPublicKey;

    private string $invalidPrivateKey;

    private string $invalidPublicKey;

    protected function setUp(): void
    {
        // 建立測試用的 RSA 金鑰對
        $this->generateTestKeys();

        // 設定測試環境變數
        (is_array($_ENV) ? $_ENV['JWT_ALGORITHM'] : (is_object($_ENV) ? $_ENV->JWT_ALGORITHM : null)) = 'RS256';
        (is_array($_ENV) ? $_ENV['JWT_PRIVATE_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PRIVATE_KEY : null)) = str_replace("\n", '\\n', $this->validPrivateKey);
        (is_array($_ENV) ? $_ENV['JWT_PUBLIC_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PUBLIC_KEY : null)) = str_replace("\n", '\\n', $this->validPublicKey);
        (is_array($_ENV) ? $_ENV['JWT_ISSUER'] : (is_object($_ENV) ? $_ENV->JWT_ISSUER : null)) = 'alleynote-test';
        (is_array($_ENV) ? $_ENV['JWT_AUDIENCE'] : (is_object($_ENV) ? $_ENV->JWT_AUDIENCE : null)) = 'alleynote-app';
        (is_array($_ENV) ? $_ENV['JWT_ACCESS_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_ACCESS_TOKEN_TTL : null)) = '3600'; // 1 小時
        (is_array($_ENV) ? $_ENV['JWT_REFRESH_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_REFRESH_TOKEN_TTL : null)) = '2592000'; // 30 天

        // 建立真實的 JwtConfig 物件
        $config = new JwtConfig();
        $this->provider = new FirebaseJwtProvider($config);
    }

    protected function tearDown(): void
    {
        // 清理環境變數
        unset(
            (is_array($_ENV) ? $_ENV['JWT_ALGORITHM'] : (is_object($_ENV) ? $_ENV->JWT_ALGORITHM : null)),
            (is_array($_ENV) ? $_ENV['JWT_PRIVATE_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PRIVATE_KEY : null)),
            (is_array($_ENV) ? $_ENV['JWT_PUBLIC_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PUBLIC_KEY : null)),
            (is_array($_ENV) ? $_ENV['JWT_ISSUER'] : (is_object($_ENV) ? $_ENV->JWT_ISSUER : null)),
            (is_array($_ENV) ? $_ENV['JWT_AUDIENCE'] : (is_object($_ENV) ? $_ENV->JWT_AUDIENCE : null)),
            (is_array($_ENV) ? $_ENV['JWT_ACCESS_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_ACCESS_TOKEN_TTL : null)),
            (is_array($_ENV) ? $_ENV['JWT_REFRESH_TOKEN_TTL'] : (is_object($_ENV) ? $_ENV->JWT_REFRESH_TOKEN_TTL : null)),
        );
    }

    /**
     * 測試建構函式成功初始化.
     */
    public function testConstructorSuccessfullyInitializesProvider(): void
    {
        $this->assertInstanceOf(FirebaseJwtProvider::class, $this->provider);
    }

    /**
     * 測試建構函式在無效私鑰時拋出例外.
     */
    public function testConstructorThrowsExceptionForInvalidPrivateKey(): void
    {
        // 暫時修改環境變數
        (is_array($_ENV) ? $_ENV['JWT_PRIVATE_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PRIVATE_KEY : null)) = 'invalid-private-key';

        $this->expectException(InvalidArgumentException::class);

        new JwtConfig();
    }

    /**
     * 測試建構函式在無效公鑰時拋出例外.
     */
    public function testConstructorThrowsExceptionForInvalidPublicKey(): void
    {
        // 暫時修改環境變數
        (is_array($_ENV) ? $_ENV['JWT_PUBLIC_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PUBLIC_KEY : null)) = 'invalid-public-key';

        $this->expectException(InvalidArgumentException::class);

        new JwtConfig();
    }

    /**
     * 測試建構函式在金鑰不匹配時拋出例外.
     */
    public function testConstructorThrowsExceptionForMismatchedKeys(): void
    {
        // 建立另一組金鑰對
        $this->generateAlternativeKeys();

        // 暫時修改環境變數（使用不匹配的公鑰）
        (is_array($_ENV) ? $_ENV['JWT_PUBLIC_KEY'] : (is_object($_ENV) ? $_ENV->JWT_PUBLIC_KEY : null)) = str_replace("\n", '\\n', $this->invalidPublicKey);

        $this->expectException(InvalidArgumentException::class);

        new JwtConfig();
    }

    /**
     * 測試成功產生 access token.
     */
    public function testGenerateAccessTokenSuccessfully(): void
    {
        $payload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'role' => 'user',
        ];

        $token = $this->provider->generateAccessToken($payload);

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
        $payload = [
            'sub' => 'user-123',
            'device_id' => 'device-456',
        ];

        $token = $this->provider->generateRefreshToken($payload);

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
        $payload = ['sub' => 'user-123'];
        $customTtl = 1800; // 30 分鐘

        $token = $this->provider->generateAccessToken($payload, $customTtl);
        $decodedPayload = $this->provider->parseTokenUnsafe($token);

        // 驗證過期時間
        $expectedExp = time() + $customTtl;
        $actualExp = (is_array($decodedPayload) ? $decodedPayload['exp'] : (is_object($decodedPayload) ? $decodedPayload->exp : null));

        // 允許 5 秒的誤差
        $this->assertLessThanOrEqual(5, abs($expectedExp - $actualExp));
    }

    /**
     * 測試產生帶自訂 TTL 的 refresh token.
     */
    public function testGenerateRefreshTokenWithCustomTtl(): void
    {
        $payload = ['sub' => 'user-123'];
        $customTtl = 86400; // 1 天

        $token = $this->provider->generateRefreshToken($payload, $customTtl);
        $decodedPayload = $this->provider->parseTokenUnsafe($token);

        // 驗證過期時間
        $expectedExp = time() + $customTtl;
        $actualExp = (is_array($decodedPayload) ? $decodedPayload['exp'] : (is_object($decodedPayload) ? $decodedPayload->exp : null));

        // 允許 5 秒的誤差
        $this->assertLessThanOrEqual(5, abs($expectedExp - $actualExp));
    }

    /**
     * 測試成功驗證有效的 access token.
     */
    public function testValidateAccessTokenSuccessfully(): void
    {
        $originalPayload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
            'role' => 'admin',
        ];

        $token = $this->provider->generateAccessToken($originalPayload);
        $validatedPayload = $this->provider->validateToken($token, 'access');

        // 驗證自訂載荷
        $this->assertEquals('user-123', (is_array($validatedPayload) ? $validatedPayload['sub'] : (is_object($validatedPayload) ? $validatedPayload->sub : null)));
        $this->assertEquals('test@example.com', (is_array($validatedPayload) ? $validatedPayload['email'] : (is_object($validatedPayload) ? $validatedPayload->email : null)));
        $this->assertEquals('admin', (is_array($validatedPayload) ? $validatedPayload['role'] : (is_object($validatedPayload) ? $validatedPayload->role : null)));

        // 驗證標準宣告
        $this->assertEquals('alleynote-test', (is_array($validatedPayload) ? $validatedPayload['iss'] : (is_object($validatedPayload) ? $validatedPayload->iss : null)));
        $this->assertEquals('alleynote-app', (is_array($validatedPayload) ? $validatedPayload['aud'] : (is_object($validatedPayload) ? $validatedPayload->aud : null)));
        $this->assertEquals('access', (is_array($validatedPayload) ? $validatedPayload['type'] : (is_object($validatedPayload) ? $validatedPayload->type : null)));
        $this->assertArrayHasKey('iat', $validatedPayload);
        $this->assertArrayHasKey('exp', $validatedPayload);
        $this->assertArrayHasKey('jti', $validatedPayload);
    }

    /**
     * 測試成功驗證有效的 refresh token.
     */
    public function testValidateRefreshTokenSuccessfully(): void
    {
        $originalPayload = [
            'sub' => 'user-123',
            'device_id' => 'device-456',
        ];

        $token = $this->provider->generateRefreshToken($originalPayload);
        $validatedPayload = $this->provider->validateToken($token, 'refresh');

        $this->assertEquals('user-123', (is_array($validatedPayload) ? $validatedPayload['sub'] : (is_object($validatedPayload) ? $validatedPayload->sub : null)));
        $this->assertEquals('device-456', (is_array($validatedPayload) ? $validatedPayload['device_id'] : (is_object($validatedPayload) ? $validatedPayload->device_id : null)));
        $this->assertEquals('refresh', (is_array($validatedPayload) ? $validatedPayload['type'] : (is_object($validatedPayload) ? $validatedPayload->type : null)));
    }

    /**
     * 測試驗證空 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForEmptyToken(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token 不能為空');

        $this->provider->validateToken('');
    }

    /**
     * 測試驗證格式無效的 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForMalformedToken(): void
    {
        $this->expectException(InvalidTokenException::class);

        $this->provider->validateToken('invalid.token');
    }

    /**
     * 測試驗證過期 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForExpiredToken(): void
    {
        // 產生一個已過期的 token（TTL = -1 秒）
        $payload = ['sub' => 'user-123'];
        $expiredToken = $this->provider->generateAccessToken($payload, -1);

        // 等待一秒確保 token 過期
        sleep(1);

        $this->expectException(TokenExpiredException::class);

        $this->provider->validateToken($expiredToken);
    }

    /**
     * 測試驗證錯誤類型的 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForWrongTokenType(): void
    {
        $token = $this->provider->generateAccessToken(['sub' => 'user-123']);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token 類型錯誤，預期: refresh，實際: access');

        $this->provider->validateToken($token, 'refresh');
    }

    /**
     * 測試驗證帶有無效簽名的 token 時拋出例外.
     */
    public function testValidateTokenThrowsExceptionForInvalidSignature(): void
    {
        $token = $this->provider->generateAccessToken(['sub' => 'user-123']);

        // 更大幅度地破壞簽名 - 將整個簽名部分替換為無效的簽名
        $parts = explode('.', $token);
        $parts[2] = 'invalid-signature-12345'; // 完全替換簽名部分

        $corruptedToken = implode('.', $parts);

        $this->expectException(TokenValidationException::class);

        $this->provider->validateToken($corruptedToken);
    }

    /**
     * 測試成功解析不安全的 token.
     */
    public function testParseTokenUnsafeSuccessfully(): void
    {
        $originalPayload = [
            'sub' => 'user-123',
            'email' => 'test@example.com',
        ];

        $token = $this->provider->generateAccessToken($originalPayload);
        $parsedPayload = $this->provider->parseTokenUnsafe($token);

        $this->assertEquals('user-123', (is_array($parsedPayload) ? $parsedPayload['sub'] : (is_object($parsedPayload) ? $parsedPayload->sub : null)));
        $this->assertEquals('test@example.com', (is_array($parsedPayload) ? $parsedPayload['email'] : (is_object($parsedPayload) ? $parsedPayload->email : null)));
        $this->assertEquals('access', (is_array($parsedPayload) ? $parsedPayload['type'] : (is_object($parsedPayload) ? $parsedPayload->type : null)));
    }

    /**
     * 測試解析空 token 時拋出例外.
     */
    public function testParseTokenUnsafeThrowsExceptionForEmptyToken(): void
    {
        $this->expectException(TokenParsingException::class);

        $this->provider->parseTokenUnsafe('');
    }

    /**
     * 測試解析格式無效的 token 時拋出例外.
     */
    public function testParseTokenUnsafeThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(TokenParsingException::class);
        $this->expectExceptionMessage('Token 格式無效，必須包含三個部分');

        $this->provider->parseTokenUnsafe('invalid.token');
    }

    /**
     * 測試成功取得 token 的過期時間.
     */
    public function testGetTokenExpirationSuccessfully(): void
    {
        $token = $this->provider->generateAccessToken(['sub' => 'user-123']);
        $expiration = $this->provider->getTokenExpiration($token);

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
        $expiration = $this->provider->getTokenExpiration('invalid-token');

        $this->assertNull($expiration);
    }

    /**
     * 測試檢查 token 是否已過期.
     */
    public function testIsTokenExpiredForValidToken(): void
    {
        $token = $this->provider->generateAccessToken(['sub' => 'user-123']);
        $isExpired = $this->provider->isTokenExpired($token);

        $this->assertFalse($isExpired);
    }

    /**
     * 測試檢查過期 token.
     */
    public function testIsTokenExpiredForExpiredToken(): void
    {
        // 產生已過期的 token
        $expiredToken = $this->provider->generateAccessToken(['sub' => 'user-123'], -1);

        // 等待確保過期
        sleep(1);

        $isExpired = $this->provider->isTokenExpired($expiredToken);

        $this->assertTrue($isExpired);
    }

    /**
     * 測試檢查無效 token 的過期狀態回傳 false.
     */
    public function testIsTokenExpiredReturnsFalseForInvalidToken(): void
    {
        $isExpired = $this->provider->isTokenExpired('invalid-token');

        $this->assertFalse($isExpired);
    }

    /**
     * 測試驗證帶有錯誤 issuer 的 token.
     */
    public function testValidateTokenThrowsExceptionForInvalidIssuer(): void
    {
        // 建立一個帶有不同 issuer 的 token
        // 先暫時修改環境變數
        $originalIssuer = (is_array($_ENV) ? $_ENV['JWT_ISSUER'] : (is_object($_ENV) ? $_ENV->JWT_ISSUER : null));
        (is_array($_ENV) ? $_ENV['JWT_ISSUER'] : (is_object($_ENV) ? $_ENV->JWT_ISSUER : null)) = 'different-issuer';

        $differentConfig = new JwtConfig();
        $differentProvider = new FirebaseJwtProvider($differentConfig);
        $token = $differentProvider->generateAccessToken(['sub' => 'user-123']);

        // 還原環境變數
        (is_array($_ENV) ? $_ENV['JWT_ISSUER'] : (is_object($_ENV) ? $_ENV->JWT_ISSUER : null)) = $originalIssuer;

        $this->expectException(TokenValidationException::class);
        $this->expectExceptionMessage('Token issuer 無效');

        // 使用原來的 provider 驗證（有不同的 issuer）
        $this->provider->validateToken($token);
    }

    /**
     * 測試驗證帶有錯誤 audience 的 token.
     */
    public function testValidateTokenThrowsExceptionForInvalidAudience(): void
    {
        // 建立一個帶有不同 audience 的 token
        // 先暫時修改環境變數
        $originalAudience = (is_array($_ENV) ? $_ENV['JWT_AUDIENCE'] : (is_object($_ENV) ? $_ENV->JWT_AUDIENCE : null));
        (is_array($_ENV) ? $_ENV['JWT_AUDIENCE'] : (is_object($_ENV) ? $_ENV->JWT_AUDIENCE : null)) = 'different-audience';

        $differentConfig = new JwtConfig();
        $differentProvider = new FirebaseJwtProvider($differentConfig);
        $token = $differentProvider->generateAccessToken(['sub' => 'user-123']);

        // 還原環境變數
        (is_array($_ENV) ? $_ENV['JWT_AUDIENCE'] : (is_object($_ENV) ? $_ENV->JWT_AUDIENCE : null)) = $originalAudience;

        $this->expectException(TokenValidationException::class);
        $this->expectExceptionMessage('Token audience 無效');

        // 使用原來的 provider 驗證（有不同的 audience）
        $this->provider->validateToken($token);
    }

    /**
     * 測試產生的 token 包含唯一的 JTI.
     */
    public function testGeneratedTokensHaveUniqueJti(): void
    {
        $payload = ['sub' => 'user-123'];

        $token1 = $this->provider->generateAccessToken($payload);
        $token2 = $this->provider->generateAccessToken($payload);

        $payload1 = $this->provider->parseTokenUnsafe($token1);
        $payload2 = $this->provider->parseTokenUnsafe($token2);

        $this->assertNotEquals((is_array($payload1) ? $payload1['jti'] : (is_object($payload1) ? $payload1->jti : null)), (is_array($payload2) ? $payload2['jti'] : (is_object($payload2) ? $payload2->jti : null)));
        $this->assertNotEmpty((is_array($payload1) ? $payload1['jti'] : (is_object($payload1) ? $payload1->jti : null)));
        $this->assertNotEmpty((is_array($payload2) ? $payload2['jti'] : (is_object($payload2) ? $payload2->jti : null)));
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

        $this->validPublicKey = (is_array($keyDetails) ? $keyDetails['key'] : (is_object($keyDetails) ? $keyDetails->key : null));
    }

    /**
     * 建立測試用的替代金鑰對（用於測試不匹配的情況）.
     */
    private function generateAlternativeKeys(): void
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);

        if ($resource === false) {
            $this->fail('無法產生替代測試金鑰對');
        }

        // 初始化變數
        $this->invalidPrivateKey = '';

        // 匯出私鑰
        if (!openssl_pkey_export($resource, $this->invalidPrivateKey)) {
            $this->fail('無法匯出替代私鑰');
        }

        // 匯出公鑰
        $keyDetails = openssl_pkey_get_details($resource);
        if ($keyDetails === false) {
            $this->fail('無法取得替代公鑰');
        }

        $this->invalidPublicKey = (is_array($keyDetails) ? $keyDetails['key'] : (is_object($keyDetails) ? $keyDetails->key : null));
    }
}
