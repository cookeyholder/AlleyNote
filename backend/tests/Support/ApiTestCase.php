<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domains\Auth\Contracts\JwtProviderInterface;
use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\TokenGenerationException;
use App\Domains\Auth\Exceptions\TokenParsingException;
use App\Domains\Auth\Exceptions\TokenValidationException;
use App\Domains\Auth\Services\JwtTokenService;
use App\Domains\Auth\ValueObjects\DeviceInfo;
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
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\Assert;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

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
        $normalizedPath = '/' . ltrim($path, '/');
        $uri = new Uri('http://localhost' . $normalizedPath);
        $body = new Stream(fopen('php://temp', 'r+'));

        $isGet = strtoupper($method) === 'GET';
        $defaultHeaders = [
            'Accept' => 'application/json',
        ];
        if (!$isGet) {
            $defaultHeaders['Content-Type'] = 'application/json';
        }
        $allHeaders = array_merge($defaultHeaders, $headers);

        if ($data !== [] && !$isGet) {
            $encoded = json_encode($data, JSON_UNESCAPED_UNICODE) ?: '{}';
            $body->write($encoded);
            $body->rewind();
        }

        $request = new ServerRequest(
            strtoupper($method),
            $uri,
            $allHeaders,
            $body,
            '1.1',
            ['REMOTE_ADDR' => '127.0.0.1'],
        );

        if ($data !== []) {
            if ($isGet) {
                $request = $request->withQueryParams($data);
            } else {
                $request = $request->withParsedBody($data);
            }
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
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'password' => password_hash('Password123!', PASSWORD_BCRYPT),
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function getJwtTokenService(): JwtTokenService
    {
        if ($this->jwtTokenService !== null) {
            return $this->jwtTokenService;
        }

        $this->prepareJwtTestEnvironment();
        $config = new JwtConfig();
        $provider = new class ($config) implements JwtProviderInterface {
            public function __construct(private readonly JwtConfig $config) {}

            public function generateAccessToken(array $payload, ?int $ttl = null): string
            {
                return $this->encode($payload, $ttl ?? $this->config->getAccessTokenTtl(), 'access');
            }

            public function generateRefreshToken(array $payload, ?int $ttl = null): string
            {
                return $this->encode($payload, $ttl ?? $this->config->getRefreshTokenTtl(), 'refresh');
            }

            /**
             * @return array<string, mixed>
             */
            public function validateToken(string $token, ?string $expectedType = null): array
            {
                try {
                    /** @var array<string, mixed> $decoded */
                    $decoded = (array) JWT::decode($token, new Key((string) $this->config->getSecret(), $this->config->getAlgorithm()));
                    if ($expectedType !== null && ($decoded['type'] ?? null) !== $expectedType) {
                        throw new InvalidTokenException(
                            InvalidTokenException::REASON_CLAIMS_INVALID,
                            InvalidTokenException::ACCESS_TOKEN,
                            'Token type mismatch',
                        );
                    }

                    return $decoded;
                } catch (InvalidTokenException $e) {
                    throw $e;
                } catch (Throwable $e) {
                    throw new TokenValidationException('Token validate failed: ' . $e->getMessage(), previous: $e);
                }
            }

            /**
             * @return array<string, mixed>
             */
            public function parseTokenUnsafe(string $token): array
            {
                try {
                    $parts = explode('.', $token);
                    if (count($parts) !== 3) {
                        throw new TokenParsingException('Malformed token');
                    }
                    /** @var object|string $decoded */
                    $decoded = JWT::jsonDecode(JWT::urlsafeB64Decode($parts[1]));
                    if (!is_object($decoded)) {
                        throw new TokenParsingException('Malformed token payload');
                    }
                    /** @var array<string, mixed> $payload */
                    $payload = (array) $decoded;

                    return $payload;
                } catch (TokenParsingException $e) {
                    throw $e;
                } catch (Throwable $e) {
                    throw new TokenParsingException('Failed to parse token: ' . $e->getMessage(), previous: $e);
                }
            }

            public function getTokenExpiration(string $token): ?DateTimeImmutable
            {
                $payload = $this->parseTokenUnsafe($token);
                if (!isset($payload['exp'])) {
                    return null;
                }

                $exp = $payload['exp'];
                if (!is_int($exp) && !is_string($exp) && !is_float($exp)) {
                    return null;
                }

                return DateTimeImmutable::createFromFormat('U', (string) $exp) ?: null;
            }

            public function isTokenExpired(string $token): bool
            {
                $exp = $this->getTokenExpiration($token);
                if ($exp === null) {
                    return true;
                }

                return $exp->getTimestamp() <= time();
            }

            private function encode(array $payload, int $ttl, string $type): string
            {
                try {
                    $now = time();
                    $claims = array_merge($payload, [
                        'iss' => $this->config->getIssuer(),
                        'aud' => $this->config->getAudience(),
                        'iat' => $now,
                        'nbf' => $now,
                        'exp' => $now + $ttl,
                        'jti' => bin2hex(random_bytes(16)),
                        'type' => $type,
                    ]);

                    return JWT::encode($claims, (string) $this->config->getSecret(), $this->config->getAlgorithm());
                } catch (Throwable $e) {
                    throw new TokenGenerationException(
                        TokenGenerationException::REASON_ENCODING_FAILED,
                        $type === 'refresh' ? TokenGenerationException::REFRESH_TOKEN : TokenGenerationException::ACCESS_TOKEN,
                        'Failed to encode token: ' . $e->getMessage(),
                    );
                }
            }
        };
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
        $_ENV['JWT_ALGORITHM'] = 'HS256';
        $_ENV['JWT_SECRET'] = 'alleynote-testing-secret-key-with-minimum-32-chars';
        $_ENV['JWT_PRIVATE_KEY_PATH'] = '';
        $_ENV['JWT_PUBLIC_KEY_PATH'] = '';

        putenv('JWT_ALGORITHM=HS256');
        putenv('JWT_SECRET=alleynote-testing-secret-key-with-minimum-32-chars');
        putenv('JWT_PRIVATE_KEY_PATH=');
        putenv('JWT_PUBLIC_KEY_PATH=');
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
