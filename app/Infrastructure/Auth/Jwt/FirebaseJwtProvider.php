<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Jwt;

use AlleyNote\Domains\Auth\Contracts\JwtProviderInterface;
use AlleyNote\Domains\Auth\Exceptions\InvalidTokenException;
use AlleyNote\Domains\Auth\Exceptions\JwtConfigurationException;
use AlleyNote\Domains\Auth\Exceptions\TokenExpiredException;
use AlleyNote\Domains\Auth\Exceptions\TokenGenerationException;
use AlleyNote\Domains\Auth\Exceptions\TokenParsingException;
use AlleyNote\Domains\Auth\Exceptions\TokenValidationException;
use App\Shared\Config\JwtConfig;
use DateTimeImmutable;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Throwable;
use UnexpectedValueException;

/**
 * Firebase JWT 提供器.
 *
 * 封裝 Firebase JWT 函式庫，提供 RS256 演算法的 JWT token 產生、驗證、解析功能
 */
final class FirebaseJwtProvider implements JwtProviderInterface
{
    private JwtConfig $config;

    private string $privateKey;

    private string $publicKey;

    /**
     * 建構函式.
     *
     * @param JwtConfig $config JWT 配置
     *
     * @throws JwtConfigurationException 當配置無效時
     */
    public function __construct(JwtConfig $config)
    {
        $this->config = $config;

        try {
            $this->initializeKeys();
        } catch (Throwable $e) {
            throw JwtConfigurationException::invalidKeyFormat(
                $e->getMessage(),
                $e,
            );
        }
    }

    /**
     * 產生 JWT access token.
     *
     * @param array<string, mixed> $payload Token 載荷資料
     * @param int|null $ttl 存活時間（秒），null 使用預設值
     *
     * @return string JWT token 字串
     *
     * @throws TokenGenerationException 當 token 產生失敗時
     */
    public function generateAccessToken(array $payload, ?int $ttl = null): string
    {
        $ttl ??= $this->config->getAccessTokenTtl();

        return $this->generateToken($payload, $ttl, 'access');
    }

    /**
     * 產生 JWT refresh token.
     *
     * @param array<string, mixed> $payload Token 載荷資料
     * @param int|null $ttl 存活時間（秒），null 使用預設值
     *
     * @return string JWT token 字串
     *
     * @throws TokenGenerationException 當 token 產生失敗時
     */
    public function generateRefreshToken(array $payload, ?int $ttl = null): string
    {
        $ttl ??= $this->config->getRefreshTokenTtl();

        return $this->generateToken($payload, $ttl, 'refresh');
    }

    /**
     * 驗證 JWT token.
     *
     * @param string $token JWT token 字串
     * @param string|null $expectedType 預期的 token 類型（access 或 refresh）
     *
     * @return array<string, mixed> Token 載荷資料
     *
     * @throws InvalidTokenException 當 token 格式無效時
     * @throws TokenExpiredException 當 token 已過期時
     * @throws TokenValidationException 當 token 驗證失敗時
     */
    public function validateToken(string $token, ?string $expectedType = null): array
    {
        if (empty($token)) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_DECODE_FAILED,
                InvalidTokenException::ACCESS_TOKEN,
                'Token 不能為空',
            );
        }

        try {
            $decoded = JWT::decode($token, new Key($this->publicKey, $this->config->getAlgorithm()));
            $payload = (array) $decoded;

            // 驗證必要欄位
            $this->validateRequiredFields($payload);

            // 驗證 token 類型
            if ($expectedType !== null) {
                $this->validateTokenType($payload, $expectedType);
            }

            // 驗證 issuer 和 audience
            $this->validateIssuerAndAudience($payload);

            return $payload;
        } catch (ExpiredException $e) {
            // 嘗試從過期的 token 中取得過期時間
            $expiredAt = null;

            try {
                $unsafePayload = $this->parseTokenUnsafe($token);
                $expiredAt = $unsafePayload['exp'] ?? null;
            } catch (Throwable) {
                // 忽略解析錯誤
            }

            throw new TokenExpiredException(
                TokenExpiredException::ACCESS_TOKEN,
                $expiredAt,
                time(),
                'Token 已過期',
            );
        } catch (SignatureInvalidException $e) {
            throw TokenValidationException::invalidSignature($e);
        } catch (UnexpectedValueException $e) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_MALFORMED,
                InvalidTokenException::ACCESS_TOKEN,
                'Token 格式無效: ' . $e->getMessage(),
            );
        } catch (Throwable $e) {
            if (
                $e instanceof InvalidTokenException
                || $e instanceof TokenExpiredException
                || $e instanceof TokenValidationException
            ) {
                throw $e;
            }

            throw new TokenValidationException(
                'Token 驗證失敗: ' . $e->getMessage(),
                TokenValidationException::VALIDATION_FAILED,
                $e,
            );
        }
    }

    /**
     * 解析 JWT token 但不驗證（用於除錯或取得過期 token 的資訊）.
     *
     * @param string $token JWT token 字串
     *
     * @return array<string, mixed> Token 載荷資料
     *
     * @throws TokenParsingException 當 token 無法解析時
     */
    public function parseTokenUnsafe(string $token): array
    {
        if (empty($token)) {
            throw TokenParsingException::emptyToken();
        }

        try {
            // 分割 JWT token
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw TokenParsingException::invalidFormat('Token 格式無效，必須包含三個部分');
            }

            // 解碼 payload（第二部分）
            $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($parts[1]));

            return (array) $payload;
        } catch (Throwable $e) {
            if ($e instanceof TokenParsingException) {
                throw $e;
            }

            throw new TokenParsingException(
                'Token 解析失敗: ' . $e->getMessage(),
                TokenParsingException::PARSING_FAILED,
                $e,
            );
        }
    }

    /**
     * 取得 token 的過期時間.
     *
     * @param string $token JWT token 字串
     *
     * @return DateTimeImmutable|null 過期時間，如果 token 無效或沒有過期時間則回傳 null
     */
    public function getTokenExpiration(string $token): ?DateTimeImmutable
    {
        try {
            $payload = $this->parseTokenUnsafe($token);

            if (!isset($payload['exp']) || !is_int($payload['exp'])) {
                return null;
            }

            return DateTimeImmutable::createFromFormat('U', (string) $payload['exp']) ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * 檢查 token 是否已過期（不驗證簽名）.
     *
     * @param string $token JWT token 字串
     *
     * @return bool true 如果已過期，false 如果仍有效或無法判斷
     */
    public function isTokenExpired(string $token): bool
    {
        $expiration = $this->getTokenExpiration($token);

        if ($expiration === null) {
            return false; // 無法確定過期時間，假設未過期
        }

        return $expiration <= new DateTimeImmutable();
    }

    /**
     * 初始化 RSA 金鑰.
     *
     * @throws JwtConfigurationException 當金鑰格式無效時
     */
    private function initializeKeys(): void
    {
        // 直接從配置中獲取金鑰內容
        $this->privateKey = $this->config->getPrivateKey();
        $this->publicKey = $this->config->getPublicKey();

        // 驗證金鑰格式
        $this->validateKeyFormats();
    }

    /**
     * 驗證 RSA 金鑰格式.
     *
     * @throws JwtConfigurationException 當金鑰格式無效時
     */
    private function validateKeyFormats(): void
    {
        // 檢查私鑰
        $privateKeyResource = openssl_pkey_get_private($this->privateKey);
        if ($privateKeyResource === false) {
            throw new JwtConfigurationException(
                '私鑰格式無效',
                JwtConfigurationException::INVALID_PRIVATE_KEY_FORMAT,
            );
        }

        // 檢查公鑰
        $publicKeyResource = openssl_pkey_get_public($this->publicKey);
        if ($publicKeyResource === false) {
            throw new JwtConfigurationException(
                '公鑰格式無效',
                JwtConfigurationException::INVALID_PUBLIC_KEY_FORMAT,
            );
        }

        // 驗證金鑰匹配
        if (!$this->keysMatch($privateKeyResource, $publicKeyResource)) {
            throw new JwtConfigurationException(
                '私鑰和公鑰不匹配',
                JwtConfigurationException::KEY_MISMATCH,
            );
        }
    }

    /**
     * 檢查私鑰和公鑰是否匹配.
     *
     * @param mixed $privateKey 私鑰資源
     * @param mixed $publicKey 公鑰資源
     *
     * @return bool true 如果匹配，false 如果不匹配
     */
    private function keysMatch($privateKey, $publicKey): bool
    {
        $testData = 'test-data-for-key-verification';

        // 使用私鑰簽名
        $signature = '';
        $signResult = openssl_sign($testData, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$signResult) {
            return false;
        }

        // 使用公鑰驗證
        $verifyResult = openssl_verify($testData, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        return $verifyResult === 1;
    }

    /**
     * 產生 JWT token 的共用方法.
     *
     * @param array<string, mixed> $payload Token 載荷資料
     * @param int $ttl 存活時間（秒）
     * @param string $type Token 類型
     *
     * @return string JWT token 字串
     *
     * @throws TokenGenerationException 當 token 產生失敗時
     */
    private function generateToken(array $payload, int $ttl, string $type): string
    {
        $now = new DateTimeImmutable();

        // 準備標準 JWT 宣告
        $claims = [
            'iss' => $this->config->getIssuer(),
            'aud' => $this->config->getAudience(),
            'iat' => $now->getTimestamp(),
            'exp' => $now->getTimestamp() + $ttl,
            'jti' => $this->generateJti(),
            'type' => $type,
        ];

        // 合併自訂載荷
        $finalPayload = array_merge($claims, $payload);

        try {
            return JWT::encode($finalPayload, $this->privateKey, $this->config->getAlgorithm());
        } catch (Throwable $e) {
            throw TokenGenerationException::encodingFailed(
                'Token 產生失敗: ' . $e->getMessage(),
            );
        }
    }

    /**
     * 產生唯一的 JWT ID.
     *
     * @return string 唯一的 JWT ID
     */
    private function generateJti(): string
    {
        // 使用更精確的微秒時間戳和更多隨機位元組來確保唯一性
        $timestamp = number_format(microtime(true) * 1000000, 0, '', ''); // 精確到微秒
        $randomBytes = bin2hex(random_bytes(16)); // 增加隨機位元組
        $processId = getmypid(); // 加入進程 ID
        $uniqid = uniqid('', true); // 加入 PHP 的 uniqid

        return $timestamp . $processId . $randomBytes . $uniqid;
    }

    /**
     * 驗證必要欄位.
     *
     * @param array<string, mixed> $payload Token 載荷
     *
     * @throws InvalidTokenException 當必要欄位缺失時
     */
    private function validateRequiredFields(array $payload): void
    {
        $requiredFields = ['iss', 'aud', 'iat', 'exp', 'jti', 'type'];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new InvalidTokenException(
                    InvalidTokenException::REASON_CLAIMS_INVALID,
                    InvalidTokenException::ACCESS_TOKEN,
                    "Token 缺少必要欄位: {$field}",
                );
            }
        }
    }

    /**
     * 驗證 token 類型.
     *
     * @param array<string, mixed> $payload Token 載荷
     * @param string $expectedType 預期類型
     *
     * @throws InvalidTokenException 當 token 類型不符合預期時
     */
    private function validateTokenType(array $payload, string $expectedType): void
    {
        if (!isset($payload['type']) || $payload['type'] !== $expectedType) {
            throw new InvalidTokenException(
                InvalidTokenException::REASON_CLAIMS_INVALID,
                InvalidTokenException::ACCESS_TOKEN,
                "Token 類型錯誤，預期: {$expectedType}，實際: " . ($payload['type'] ?? 'unknown'),
            );
        }
    }

    /**
     * 驗證 issuer 和 audience.
     *
     * @param array<string, mixed> $payload Token 載荷
     *
     * @throws TokenValidationException 當 issuer 或 audience 無效時
     */
    private function validateIssuerAndAudience(array $payload): void
    {
        // 驗證 issuer
        if (!isset($payload['iss']) || $payload['iss'] !== $this->config->getIssuer()) {
            throw new TokenValidationException(
                'Token issuer 無效',
                TokenValidationException::INVALID_ISSUER,
            );
        }

        // 驗證 audience
        if (!isset($payload['aud']) || $payload['aud'] !== $this->config->getAudience()) {
            throw new TokenValidationException(
                'Token audience 無效',
                TokenValidationException::INVALID_AUDIENCE,
            );
        }
    }
}
