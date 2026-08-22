<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Http\Uri;
use App\Shared\Contracts\CacheServiceInterface;
use Firebase\JWT\JWT;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Auth API HTTP 整合測試基底類別.
 *
 * 提供完整的應用程式堆疊（路由、中介軟體、控制器、服務）與測試用 SQLite 資料庫，
 * 讓 Auth 領域的整合測試能以真實 PSR-7 請求驅動整條請求管線。
 */
abstract class AuthApiIntegrationTestCase extends IntegrationTestCase
{
    private const PASSWORD = 'Password123!';

    /**
     * 測試用 RS256 私鑰（僅供測試，與 JwtAuthenticationIntegrationTest 使用同一組測試金鑰）.
     */
    protected const TEST_PRIVATE_KEY = <<<'KEY'
        -----BEGIN PRIVATE KEY-----
        MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC7bjwmxz3eqbfh
        x2FiapDublS2Qx78RwQJCquPopfQKj1BxoGH7VQl65FU1Dytu+Q4E8o6FpjobU1i
        0+Ij7rAEe3mvLAqLmWZGFAQdF8mBhXEZLFVk3Fb7plwbBJMHWDrL9T19KJbp0TxL
        3XIao0YGfM5JqzlJ1dLVf9gBHhTGZpJiwLoA5prM5ABPS2QXMIBsNhzCQ0YR5dWj
        94fBTLYHPPj+9HeDtqq72iz6aTt+bgonm/5uqdH9Hcw0Vjiu6akvhPxE2HT4XXsE
        q7Z6LZDNRLUwKcQ+z9PHj8ZWM4PfF5OXGok90Vmj7NBVLpl7LZjaGY9wUC6mKgLa
        h+8WEmMlAgMBAAECggEACzhd5vdh84im7p/sK0NUYkWeEhwiCHmq2uy12P8jhe1l
        ZeDfg7bYJP39YP3klQTstFOw9TnBlRZn/czP2pVRGa+XmP4ysmkwN20+0swH/tYx
        b0+ZXBSZq25p0J89OwET4f5QHEQ4Bo7FRIhg6onQKRbDFaNnpk0j1i6VTHnTxg2n
        I2nGb84Yfi1dUIDb6QkCwEe3xKMqGfEbNmBq6Rl2flW/bfhUJ6TbI5eyFgqaPCA8
        O0n2HOGckxVSHtJ+c4xBimalEXVDiWgyhkPRW++JWudb5OiP6EW1iO7vhyOXdNJJ
        meGudIlAy9LQdETRzRyWlnSy+Y2kIgbAEjbMdZKnAQKBgQDipVL/cn0GoVGOXC6W
        xM5Kp+KuW+UW12940EWQRLEERfVD4FISaHJwGa802QFR2/7oe2uRbfiuLXuJc472
        skC+XStG5o3vN4j7UkalT5Ypofyyq1ku/ULUedw3zVrAw1cFNuJo+Pcans/feQYm
        RnJQp/sQvw/R4qYv5Tgi/U5rAQKBgQDTtLDEx4F9k0AhVuNJu1Bk08a+pyrlqoTn
        W6wClBdCYgucBBveqBA4lNWoKjw5hU0QHkUNRZ22x04B0WTS09CZ3cYT6WtcY3zz
        jeWGHTSLJKTs865J7vNPCD4W37B6ptZWzX95NabKId2cqcP9bjnPGfk1N+yM4Ex3
        uNC1JxjsJQKBgQCqiBhmCh/WgETcJ7IKUTSi6aVe6df6ksjWD2d4AKdsfrLnin5W
        SW5puHmi+vDKRgyLommyeBtX+vLr3h4gssiSM4ofg9QhvRh9eU+cjMCAvNhlGxY0
        i+zf8HzpI8N4LMJqMvyyXTmYNwxTqj0dSX4z/+ChnhDqLG48tWzCrvN1AQKBgGVB
        /XKBQgxAC+JmXpv7fb5cFKlH55ql7p+CF0m8b0uO/aKHzJS4qdmGRpMCcH/KpEtb
        TwfEDmVH+qWf86trKFEP5BfOA03TQAZ2DhwRh/otcrzq6KfwJGves2PZZd2kQsyN
        ybS91qLDg+3UvStQN1I5SBsOPpQ7DBgPS7P5mVAJAoGBAIC2SKgUbPxDM/IfeJF8
        I8qOp8rWxhTL5WTJN2zQ+pQnxAGuFwvr3SlAWvs2PcFzpitfJsT6qH+7/WtEdIte
        pRcYEQK1OWubudCUg6c6Ou1QS+vEJwTx7wrK3vOTOOq1JfAw6A9+sIP9T0oejPzY
        k0i7JrI2Y0POAZZXyi/bxcF/
        -----END PRIVATE KEY-----
        KEY;

    /**
     * 測試用 RS256 公鑰（與 TEST_PRIVATE_KEY 成對）.
     */
    protected const TEST_PUBLIC_KEY = <<<'KEY'
        -----BEGIN PUBLIC KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu248Jsc93qm34cdhYmqQ
        7m5UtkMe/EcECQqrj6KX0Co9QcaBh+1UJeuRVNQ8rbvkOBPKOhaY6G1NYtPiI+6w
        BHt5rywKi5lmRhQEHRfJgYVxGSxVZNxW+6ZcGwSTB1g6y/U9fSiW6dE8S91yGqNG
        BnzOSas5SdXS1X/YAR4UxmaSYsC6AOaazOQAT0tkFzCAbDYcwkNGEeXVo/eHwUy2
        Bzz4/vR3g7aqu9os+mk7fm4KJ5v+bqnR/R3MNFY4rumpL4T8RNh0+F17BKu2ei2Q
        zUS1MCnEPs/Tx4/GVjOD3xeTlxqJPdFZo+zQVS6Zey2Y2hmPcFAupioC2ofvFhJj
        JQIDAQAB
        -----END PUBLIC KEY-----
        KEY;

    private static int $ipSequence = 0;

    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml 的多行 env 值會被解析為空字串，且 .env.testing 的金鑰路徑指向已移除的檔案，
        // 因此在此直接注入成對的測試金鑰內容。
        $_ENV['JWT_PRIVATE_KEY'] = self::TEST_PRIVATE_KEY;
        $_ENV['JWT_PUBLIC_KEY'] = self::TEST_PUBLIC_KEY;
        putenv('JWT_PRIVATE_KEY=' . self::TEST_PRIVATE_KEY);
        putenv('JWT_PUBLIC_KEY=' . self::TEST_PUBLIC_KEY);
        unset($_ENV['JWT_PRIVATE_KEY_PATH'], $_ENV['JWT_PUBLIC_KEY_PATH']);
        putenv('JWT_PRIVATE_KEY_PATH');
        putenv('JWT_PUBLIC_KEY_PATH');

        $this->app = new Application();
        $this->clearAppCache();
    }

    protected function tearDown(): void
    {
        // 應用程式的檔案型快取會跨測試行程持久化，結束時一併清除避免汙染其他測試
        $this->clearAppCache();

        parent::tearDown();
    }

    /**
     * 清除應用程式容器內的檔案快取，確保測試隔離.
     */
    private function clearAppCache(): void
    {
        try {
            $cache = $this->app->getContainer()->get(CacheServiceInterface::class);
            if ($cache instanceof CacheServiceInterface) {
                $cache->clear();
            }
        } catch (Throwable) {
            // 快取清理失敗不影響測試執行
        }
    }

    /**
     * 建立帶有角色的測試使用者.
     *
     * @param array<string> $roleNames 角色名稱（roles 資料表已預置 admin 與 user）
     *
     * @return int 使用者 ID
     */
    protected function createAuthUser(string $username, string $email, array $roleNames = []): int
    {
        $userId = $this->insertTestUser([
            'username' => $username,
            'email'    => $email,
            'password' => password_hash(self::PASSWORD, PASSWORD_BCRYPT),
        ]);

        foreach ($roleNames as $roleName) {
            $stmt = $this->db->prepare(
                'INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, (SELECT id FROM roles WHERE name = :name))',
            );
            $stmt->execute(['user_id' => $userId, 'name' => $roleName]);
        }

        return $userId;
    }

    /**
     * 取得共用測試密碼.
     */
    protected function testPassword(): string
    {
        return self::PASSWORD;
    }

    /**
     * 發送 HTTP 請求並取得回應.
     *
     * @param array<string, mixed>|null $body JSON 請求主體
     * @param array<string, string> $headers 額外標頭
     * @param array<string, string> $cookies Cookie 參數
     */
    protected function request(
        string $method,
        string $path,
        ?array $body = null,
        array $headers = [],
        array $cookies = [],
        ?string $ip = null,
    ): ResponseInterface {
        $serverParams = $ip !== null ? ['REMOTE_ADDR' => $ip] : [];
        $uri = new Uri('http://localhost' . $path);
        $request = new ServerRequest($method, $uri, $headers, null, '1.1', $serverParams);

        // 解析 URI 查詢字串，讓控制器可透過 getQueryParams() 讀取（ServerRequest 不會自動解析）
        $queryString = $uri->getQuery();
        if ($queryString !== '') {
            $queryParams = [];
            parse_str($queryString, $queryParams);
            $request = $request->withQueryParams($queryParams);
        }

        if ($cookies !== []) {
            $request = $request->withCookieParams($cookies);
        }

        if ($body !== null) {
            try {
                $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException('無法編碼測試請求主體：' . $e->getMessage(), 0, $e);
            }
            $stream = new Stream(fopen('php://temp', 'r+'));
            $stream->write($json);
            $stream->rewind();
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($stream)
                ->withParsedBody($body);
        }

        return $this->app->run($request);
    }

    /**
     * 建立 CSRF 驗證所需的標頭與 Cookie（Double-Submit Cookie）.
     *
     * @param array<string, string> $headers 額外標頭
     * @param array<string, string> $cookies 額外 Cookie
     *
     * @return array{headers: array<string, string>, cookies: array<string, string>}
     */
    protected function csrfCredentials(array $headers = [], array $cookies = []): array
    {
        $token = bin2hex(random_bytes(32));
        $headers['X-CSRF-TOKEN'] = $token;
        $cookies['csrf_token'] = $token;

        return ['headers' => $headers, 'cookies' => $cookies];
    }

    /**
     * 以帳號密碼登入並回傳登入回應資料.
     *
     * @return array<string, mixed> 包含 access_token、refresh_token 等欄位
     */
    protected function loginUser(string $email, ?string $ip = null): array
    {
        $response = $this->request('POST', '/api/auth/login', [
            'email'    => $email,
            'password' => self::PASSWORD,
        ], ip: $ip ?? $this->uniqueClientIp());
        $this->assertSame(200, $response->getStatusCode(), '登入應該成功：' . $response->getBody());

        return $this->getJson($response);
    }

    /**
     * 產生不重複的模擬客戶端 IP，避免觸發登入速率限制.
     */
    protected function uniqueClientIp(): string
    {
        self::$ipSequence++;

        return sprintf(
            '10.%d.%d.%d',
            (self::$ipSequence >> 16) & 0xFF,
            (self::$ipSequence >> 8) & 0xFF,
            self::$ipSequence & 0xFF,
        );
    }

    /**
     * 從登入或刷新回應中取得 access token.
     */
    protected function accessTokenOf(array $responseData): string
    {
        return $this->getStringValue($responseData, 'access_token');
    }

    /**
     * 從登入或刷新回應中取得 refresh token.
     */
    protected function refreshTokenOf(array $responseData): string
    {
        return $this->getStringValue($responseData, 'refresh_token');
    }

    /**
     * 從登入回應中取得使用者 ID.
     */
    protected function userIdOf(array $responseData): int
    {
        return $this->getIntValue($responseData, 'user', 'id');
    }

    /**
     * 安全取得巢狀陣列中的原始值.
     */
    protected function getNestedValue(array $data, string ...$keys): mixed
    {
        $current = $data;
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * 安全取得字串值（非字串回傳空字串）.
     */
    protected function getStringValue(array $data, string ...$keys): string
    {
        $value = $this->getNestedValue($data, ...$keys);

        return is_string($value) ? $value : '';
    }

    /**
     * 安全取得整數值（非數值回傳 0）.
     */
    protected function getIntValue(array $data, string ...$keys): int
    {
        $value = $this->getNestedValue($data, ...$keys);

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    }

    /**
     * 安全取得陣列值（非陣列回傳空陣列）.
     *
     * @return array<string, mixed>
     */
    protected function getArrayValue(array $data, string ...$keys): array
    {
        $value = $this->getNestedValue($data, ...$keys);
        if (!is_array($value)) {
            return [];
        }
        /** @var array<string, mixed> $value */

        return $value;
    }

    /**
     * 解析 JSON 回應主體.
     *
     * @return array<string, mixed>
     */
    protected function getJson(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->fail('無法解析回應主體為 JSON：' . $e->getMessage() . '，原始內容：' . $body);
        }

        if (!is_array($data)) {
            $this->fail('JSON 回應內容必須為物件或陣列。');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * 斷言回應為錯誤回應（狀態碼與 success 標記）.
     *
     * @return array<string, mixed> 解析後的錯誤回應內容
     */
    protected function assertErrorResponse(ResponseInterface $response, int $expectedStatus): array
    {
        $data = $this->getJson($response);
        $this->assertSame(
            $expectedStatus,
            $response->getStatusCode(),
            '預期的錯誤回應狀態碼不符：' . $response->getBody(),
        );
        $this->assertFalse((bool) ($data['success'] ?? true), '錯誤回應的 success 必須為 false');

        return $data;
    }

    /**
     * 從 JWT 字串解出 payload（不驗證簽章）.
     *
     * @return array<string, mixed>
     */
    protected function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            $this->fail('JWT 格式不正確，應包含三個段落。');
        }
        $decoded = base64_decode(strtr($parts[1], '-_', '+/'));
        if ($decoded === false) {
            $this->fail('JWT payload Base64 解碼失敗。');
        }

        try {
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->fail('JWT payload 解析失敗：' . $e->getMessage());
        }

        if (!is_array($payload)) {
            $this->fail('JWT payload 必須為物件。');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    /**
     * 使用測試私鑰簽發任意 payload 的 JWT.
     *
     * @param array<string, mixed> $payload
     */
    protected function signJwt(array $payload): string
    {
        try {
            return JWT::encode($payload, self::TEST_PRIVATE_KEY, 'RS256');
        } catch (Throwable $e) {
            $this->fail('測試用 JWT 簽署失敗：' . $e->getMessage());

            return '';
        }
    }
}
