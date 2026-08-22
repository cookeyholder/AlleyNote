<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domains\Auth\Services\JwtTokenService;
use App\Domains\Auth\ValueObjects\DeviceInfo;
use App\Infrastructure\Auth\Jwt\FirebaseJwtProvider;
use App\Infrastructure\Auth\Repositories\RefreshTokenRepository;
use App\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use App\Infrastructure\Database\DatabaseConnection;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Http\Uri;
use App\Shared\Config\JwtConfig;
use DateTimeImmutable;
use DI\Container;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\Assert;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class ApiTestCase extends IntegrationTestCase
{
    /** @var array<string, string|null> */
    private array $jwtEnvBackup = [];

    /** @var array<string, string> */
    private array $pendingHeaders = [];

    private ?JwtTokenService $jwtTokenService = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupJwtEnvironment();
        $this->prepareJwtTestEnvironment();
        $this->pendingHeaders = [];
    }

    protected function tearDown(): void
    {
        $this->restoreJwtEnvironment();
        $this->jwtTokenService = null;
        parent::tearDown();
    }

    /**
     * @param array<string, string> $headers
     */
    protected function withHeaders(array $headers): static
    {
        $this->pendingHeaders = array_merge($this->pendingHeaders, $headers);

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    protected function json(
        string $method,
        string $path,
        array $data = [],
        array $headers = [],
    ): ServerRequestInterface {
        $mergedHeaders = array_merge($this->pendingHeaders, $headers);
        $this->pendingHeaders = [];

        return $this->createApiRequest($method, $path, $data, $mergedHeaders);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    protected function createApiRequest(
        string $method,
        string $path,
        array $data = [],
        array $headers = [],
    ): ServerRequestInterface {
        $normalizedMethod = strtoupper($method);
        $normalizedPath = '/' . ltrim($path, '/');
        $uri = new Uri('http://localhost' . $normalizedPath);
        $body = new Stream(fopen('php://temp', 'r+'));

        $defaultHeaders = [
            'Accept' => 'application/json',
        ];
        $allHeaders = array_merge($defaultHeaders, $headers);
        if ($normalizedMethod !== 'GET') {
            $allHeaders['Content-Type'] ??= 'application/json';
        } else {
            unset($allHeaders['Content-Type']);
        }

        if ($data !== [] && $normalizedMethod !== 'GET') {
            $encoded = json_encode($data, JSON_UNESCAPED_UNICODE) ?: '{}';
            $body->write($encoded);
            $body->rewind();
        }

        $request = new ServerRequest(
            $normalizedMethod,
            $uri,
            $allHeaders,
            $body,
            '1.1',
            ['REMOTE_ADDR' => '127.0.0.1'],
        );

        $queryParams = [];
        if ($uri->getQuery() !== '') {
            parse_str($uri->getQuery(), $queryParams);
        }
        if ($normalizedMethod === 'GET' && $data !== []) {
            $queryParams = array_merge($queryParams, $data);
        }
        $request = $request->withQueryParams($queryParams);

        if ($data !== [] && $normalizedMethod !== 'GET') {
            $request = $request->withParsedBody($data);
        }

        return $request->withCookieParams([]);
    }

    protected function createApiResponse(): ResponseInterface
    {
        return new Response();
    }

    /**
     * @param int|array{id:int,username?:string,email?:string} $user
     */
    protected function actingAs(int|array $user): static
    {
        $userId = is_array($user) ? (int) ($user['id'] ?? 0) : $user;
        if ($userId <= 0) {
            throw new InvalidArgumentException('actingAs user id 必須大於 0');
        }

        $this->ensureTestUserExists($userId, is_array($user) ? $user : []);

        $deviceInfo = DeviceInfo::fromUserAgent(
            'ApiTestCase-Agent',
            '127.0.0.1',
            'ApiTestCase Device',
        );

        $tokenPair = $this->getJwtTokenService()->generateTokenPair($userId, $deviceInfo);
        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenPair->getAccessToken()]);

        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function assertDatabaseHas(string $table, array $attributes): void
    {
        $count = $this->countMatchingRows($table, $attributes);
        Assert::assertGreaterThan(
            0,
            $count,
            sprintf('資料表 %s 找不到預期資料：%s', $table, json_encode($attributes, JSON_UNESCAPED_UNICODE) ?: ''),
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function assertDatabaseMissing(string $table, array $attributes): void
    {
        $count = $this->countMatchingRows($table, $attributes);
        Assert::assertSame(
            0,
            $count,
            sprintf('資料表 %s 不應存在資料：%s', $table, json_encode($attributes, JSON_UNESCAPED_UNICODE) ?: ''),
        );
    }

    protected function assertSharedPdoConnection(): void
    {
        $apiTestCasePdo = $this->db;
        $databaseConnectionPdo = DatabaseConnection::getInstance();
        /** @var array<class-string, mixed> $definitions */
        $definitions = require __DIR__ . '/../../app/Infrastructure/Config/container.php';
        /** @var callable(ContainerInterface): PDO $pdoFactory */
        $pdoFactory = $definitions[PDO::class];
        $containerPdo = $pdoFactory(new Container());

        Assert::assertSame($apiTestCasePdo, $databaseConnectionPdo, 'ApiTestCase 與 DatabaseConnection 未共用同一 PDO');
        Assert::assertSame($apiTestCasePdo, $containerPdo, 'ApiTestCase 與 DI 容器 PDO::class 未共用同一 PDO');
    }

    /**
     * @param array<string, mixed> $criteria
     */
    private function countMatchingRows(string $table, array $criteria): int
    {
        if ($criteria === []) {
            throw new InvalidArgumentException('資料庫斷言條件不可為空');
        }

        $where = [];
        $params = [];
        foreach ($criteria as $column => $value) {
            $where[] = sprintf('%s = :%s', $column, $column);
            $params[$column] = $value;
        }

        $sql = sprintf('SELECT COUNT(*) FROM %s WHERE %s', $table, implode(' AND ', $where));
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function ensureTestUserExists(int $userId, array $payload): void
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $username = $this->stringOrDefault($payload['username'] ?? null, 'user_' . $userId);
        $email = $this->stringOrDefault($payload['email'] ?? null, 'user_' . $userId . '@example.com');
        $now = new DateTimeImmutable()->format('Y-m-d H:i:s');

        $insert = $this->db->prepare(
            'INSERT INTO users (id, username, email, password, status, created_at, updated_at)
             VALUES (:id, :username, :email, :password, :status, :created_at, :updated_at)',
        );
        $insert->execute([
            'id'         => $userId,
            'username'   => $username,
            'email'      => $email,
            'password'   => password_hash('Password123!', PASSWORD_BCRYPT),
            'status'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function getJwtTokenService(): JwtTokenService
    {
        if ($this->jwtTokenService !== null) {
            return $this->jwtTokenService;
        }

        $config = new JwtConfig();
        $provider = new FirebaseJwtProvider($config);
        $refreshTokenRepository = new RefreshTokenRepository($this->db);
        $blacklistRepository = new TokenBlacklistRepository($this->db);

        $this->jwtTokenService = new JwtTokenService(
            $provider,
            $refreshTokenRepository,
            $blacklistRepository,
            $config,
        );

        return $this->jwtTokenService;
    }

    private function prepareJwtTestEnvironment(): void
    {
        $_ENV['JWT_ALGORITHM'] = 'RS256';
        $_ENV['JWT_ISSUER'] = 'alleynote-api-test';
        $_ENV['JWT_AUDIENCE'] = 'alleynote-client-test';
        $_ENV['JWT_ACCESS_TOKEN_TTL'] = '3600';
        $_ENV['JWT_REFRESH_TOKEN_TTL'] = '2592000';
        unset($_ENV['JWT_PRIVATE_KEY_PATH'], $_ENV['JWT_PUBLIC_KEY_PATH']);
        putenv('JWT_PRIVATE_KEY_PATH');
        putenv('JWT_PUBLIC_KEY_PATH');

        if (empty($_ENV['JWT_PRIVATE_KEY'])) {
            $_ENV['JWT_PRIVATE_KEY'] = "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC7bjwmxz3eqbfh\nx2FiapDublS2Qx78RwQJCquPopfQKj1BxoGH7VQl65FU1Dytu+Q4E8o6FpjobU1i\n0+Ij7rAEe3mvLAqLmWZGFAQdF8mBhXEZLFVk3Fb7plwbBJMHWDrL9T19KJbp0TxL\n3XIao0YGfM5JqzlJ1dLVf9gBHhTGZpJiwLoA5prM5ABPS2QXMIBsNhzCQ0YR5dWj\n94fBTLYHPPj+9HeDtqq72iz6aTt+bgonm/5uqdH9Hcw0Vjiu6akvhPxE2HT4XXsE\nq7Z6LZDNRLUwKcQ+z9PHj8ZWM4PfF5OXGok90Vmj7NBVLpl7LZjaGY9wUC6mKgLa\nh+8WEmMlAgMBAAECggEACzhd5vdh84im7p/sK0NUYkWeEhwiCHmq2uy12P8jhe1l\nZeDfg7bYJP39YP3klQTstFOw9TnBlRZn/czP2pVRGa+XmP4ysmkwN20+0swH/tYx\nb0+ZXBSZq25p0J89OwET4f5QHEQ4Bo7FRIhg6onQKRbDFaNnpk0j1i6VTHnTxg2n\nI2nGb84Yfi1dUIDb6QkCwEe3xKMqGfEbNmBq6Rl2flW/bfhUJ6TbI5eyFgqaPCA8\nO0n2HOGckxVSHtJ+c4xBimalEXVDiWgyhkPRW++JWudb5OiP6EW1iO7vhyOXdNJJ\nmeGudIlAy9LQdETRzRyWlnSy+Y2kIgbAEjbMdZKnAQKBgQDipVL/cn0GoVGOXC6W\nxM5Kp+KuW+UW12940EWQRLEERfVD4FISaHJwGa802QFR2/7oe2uRbfiuLXuJc472\nskC+XStG5o3vN4j7UkalT5Ypofyyq1ku/ULUedw3zVrAw1cFNuJo+Pcans/feQYm\nRnJQp/sQvw/R4qYv5Tgi/U5rAQKBgQDTtLDEx4F9k0AhVuNJu1Bk08a+pyrlqoTn\nW6wClBdCYgucBBveqBA4lNWoKjw5hU0QHkUNRZ22x04B0WTS09CZ3cYT6WtcY3zz\njeWGHTSLJKTs865J7vNPCD4W37B6ptZWzX95NabKId2cqcP9bjnPGfk1N+yM4Ex3\nuNC1JxjsJQKBgQCqiBhmCh/WgETcJ7IKUTSi6aVe6df6ksjWD2d4AKdsfrLnin5W\nSW5puHmi+vDKRgyLommyeBtX+vLr3h4gssiSM4ofg9QhvRh9eU+cjMCAvNhlGxY0\ni+zf8HzpI8N4LMJqMvyyXTmYNwxTqj0dSX4z/+ChnhDqLG48tWzCrvN1AQKBgGVB\n/XKBQgxAC+JmXpv7fb5cFKlH55ql7p+CF0m8b0uO/aKHzJS4qdmGRpMCcH/KpEtb\nTwfEDmVH+qWf86trKFEP5BfOA03TQAZ2DhwRh/otcrzq6KfwJGves2PZZd2kQsyN\nybS91qLDg+3UvStQN1I5SBsOPpQ7DBgPS7P5mVAJAoGBAIC2SKgUbPxDM/IfeJF8\nI8qOp8rWxhTL5WTJN2zQ+pQnxAGuFwvr3SlAWvs2PcFzpitfJsT6qH+7/WtEdIte\npRcYEQK1OWubudCUg6c6Ou1QS+vEJwTx7wrK3vOTOOq1JfAw6A9+sIP9T0oejPzY\nk0i7JrI2Y0POAZZXyi/bxcF/\n-----END PRIVATE KEY-----";
        }
        if (empty($_ENV['JWT_PUBLIC_KEY'])) {
            $_ENV['JWT_PUBLIC_KEY'] = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu248Jsc93qm34cdhYmqQ\n7m5UtkMe/EcECQqrj6KX0Co9QcaBh+1UJeuRVNQ8rbvkOBPKOhaY6G1NYtPiI+6w\nBHt5rywKi5lmRhQEHRfJgYVxGSxVZNxW+6ZcGwSTB1g6y/U9fSiW6dE8S91yGqNG\nBnzOSas5SdXS1X/YAR4UxmaSYsC6AOaazOQAT0tkFzCAbDYcwkNGEeXVo/eHwUy2\nBzz4/vR3g7aqu9os+mk7fm4KJ5v+bqnR/R3MNFY4rumpL4T8RNh0+F17BKu2ei2Q\nzUS1MCnEPs/Tx4/GVjOD3xeTlxqJPdFZo+zQVS6Zey2Y2hmPcFAupioC2ofvFhJj\nJQIDAQAB\n-----END PUBLIC KEY-----";
        }
    }

    private function stringOrDefault(mixed $value, string $default): string
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $default;
    }

    private function backupJwtEnvironment(): void
    {
        $keys = [
            'JWT_ALGORITHM',
            'JWT_SECRET',
            'JWT_PRIVATE_KEY_PATH',
            'JWT_PUBLIC_KEY_PATH',
        ];

        foreach ($keys as $key) {
            $envValue = $_ENV[$key] ?? getenv($key);
            $this->jwtEnvBackup[$key] = is_string($envValue) ? $envValue : null;
        }
    }

    private function restoreJwtEnvironment(): void
    {
        foreach ($this->jwtEnvBackup as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
                putenv($key);

                continue;
            }

            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
