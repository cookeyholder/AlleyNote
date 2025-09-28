<?php

declare(strict_types=1);

namespace App\Shared\Config;

use Exception;
use InvalidArgumentException;

/**
 * JWT 配置管理類別.
 *
 * 負責載入和驗證 JWT 相關的配置參數，包括 RS256 金鑰對管理
 */
final class JwtConfig
{
    private string $algorithm;

    private ?string $privateKey = null;

    private ?string $publicKey = null;

    private ?string $secret = null;

    private string $issuer;

    private string $audience;

    private int $accessTokenTtl;

    private int $refreshTokenTtl;

    public function __construct()
    {
        $this->loadFromEnvironment();
        $this->validateConfiguration();
    }

    /**
     * 從環境變數載入配置.
     */
    private function loadFromEnvironment(): void
    {
        $this->algorithm = $_ENV['JWT_ALGORITHM'] ?? 'RS256';

        // 先驗證算法是否支援
        if (!in_array($this->algorithm, ['RS256', 'RS384', 'RS512', 'HS256', 'HS384', 'HS512'])) {
            throw new InvalidArgumentException("不支援的演算法: {$this->algorithm}");
        }

        // 根據算法載入不同的金鑰
        if ($this->isSymmetricAlgorithm($this->algorithm)) {
            $this->secret = $this->loadSecret();
        } else {
            $this->privateKey = $this->loadPrivateKey();
            $this->publicKey = $this->loadPublicKey();
        }

        $this->issuer = $_ENV['JWT_ISSUER'] ?? 'alleynote-api';
        $this->audience = $_ENV['JWT_AUDIENCE'] ?? 'alleynote-client';
        $this->accessTokenTtl = (int) ($_ENV['JWT_ACCESS_TOKEN_TTL'] ?? 3600);
        $this->refreshTokenTtl = (int) ($_ENV['JWT_REFRESH_TOKEN_TTL'] ?? 2592000);
    }

    /**
     * 檢查是否為對稱算法.
     */
    private function isSymmetricAlgorithm(string $algorithm): bool
    {
        return in_array($algorithm, ['HS256', 'HS384', 'HS512'], true);
    }

    /**
     * 載入對稱金鑰（用於 HS256 等算法）.
     */
    private function loadSecret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? '';

        if (empty($secret)) {
            throw new InvalidArgumentException('JWT_SECRET 環境變數未設定');
        }

        if (!is_string($secret)) {
            throw new InvalidArgumentException('JWT_SECRET 必須是字串');
        }

        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('JWT_SECRET 長度至少需要 32 個字元');
        }

        return $secret;
    }

    /**
     * 載入私鑰.
     */
    private function loadPrivateKey(): string
    {
        // 優先從路徑載入
        $privateKeyFromPath = $this->loadKeyFromPath('JWT_PRIVATE_KEY_PATH');
        if ($privateKeyFromPath !== null && $privateKeyFromPath !== '') {
            return $privateKeyFromPath;
        }

        // 若路徑未設定，則從環境變數讀取
        $privateKeyEnv = $_ENV['JWT_PRIVATE_KEY'] ?? getenv('JWT_PRIVATE_KEY');
        $privateKey = is_string($privateKeyEnv) ? $privateKeyEnv : '';

        if ($privateKey === '') {
            throw new InvalidArgumentException('JWT_PRIVATE_KEY 或 JWT_PRIVATE_KEY_PATH 環境變數至少需要設定一個');
        }

        // 將環境變數中的 \n 轉換為實際的換行符
        $privateKey = str_replace('\\n', "\n", $privateKey);

        // 驗證私鑰格式
        if (!str_contains($privateKey, 'BEGIN PRIVATE KEY')) {
            throw new InvalidArgumentException('JWT_PRIVATE_KEY 格式無效，必須是 PEM 格式的私鑰');
        }

        return $privateKey;
    }

    /**
     * 載入公鑰.
     */
    private function loadPublicKey(): string
    {
        // 優先從路徑載入
        $publicKeyFromPath = $this->loadKeyFromPath('JWT_PUBLIC_KEY_PATH');
        if ($publicKeyFromPath !== null && $publicKeyFromPath !== '') {
            return $publicKeyFromPath;
        }

        // 若路徑未設定，則從環境變數讀取
        $publicKeyEnv = $_ENV['JWT_PUBLIC_KEY'] ?? getenv('JWT_PUBLIC_KEY');
        $publicKey = is_string($publicKeyEnv) ? $publicKeyEnv : '';

        if ($publicKey === '') {
            throw new InvalidArgumentException('JWT_PUBLIC_KEY 或 JWT_PUBLIC_KEY_PATH 環境變數至少需要設定一個');
        }

        // 將環境變數中的 \n 轉換為實際的換行符
        $publicKey = str_replace('\\n', "\n", $publicKey);

        // 驗證公鑰格式
        if (!str_contains($publicKey, 'BEGIN PUBLIC KEY')) {
            throw new InvalidArgumentException('JWT_PUBLIC_KEY 格式無效，必須是 PEM 格式的公鑰');
        }

        return $publicKey;
    }

    /**
     * 驗證配置完整性.
     */
    private function validateConfiguration(): void
    {
        if (empty($this->issuer)) {
            throw new InvalidArgumentException('JWT_ISSUER 不能為空');
        }

        if (empty($this->audience)) {
            throw new InvalidArgumentException('JWT_AUDIENCE 不能為空');
        }

        if ($this->accessTokenTtl <= 0) {
            throw new InvalidArgumentException('JWT_ACCESS_TOKEN_TTL 必須大於 0');
        }

        if ($this->refreshTokenTtl <= 0) {
            throw new InvalidArgumentException('JWT_REFRESH_TOKEN_TTL 必須大於 0');
        }

        if ($this->refreshTokenTtl <= $this->accessTokenTtl) {
            throw new InvalidArgumentException('Refresh token 有效期必須大於 access token 有效期');
        }

        // 驗證金鑰對是否匹配（僅非對稱算法需要）
        if (!$this->isSymmetricAlgorithm($this->algorithm)) {
            $this->validateKeyPair();
        }
    }

    /**
     * 驗證金鑰對是否匹配.
     */
    private function validateKeyPair(): void
    {
        try {
            // 使用 openssl 函數驗證金鑰對
            $privateKeyResource = openssl_pkey_get_private($this->privateKey ?? '');
            $publicKeyResource = openssl_pkey_get_public($this->publicKey ?? '');

            if (!$privateKeyResource) {
                throw new InvalidArgumentException('私鑰無效或格式錯誤');
            }

            if (!$publicKeyResource) {
                throw new InvalidArgumentException('公鑰無效或格式錯誤');
            }

            // 簡單的金鑰對匹配測試
            $testData = 'jwt-config-validation-test';
            $signature = '';

            if (!openssl_sign($testData, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
                throw new InvalidArgumentException('私鑰簽名測試失敗');
            }

            if (openssl_verify($testData, $signature, $publicKeyResource, OPENSSL_ALGO_SHA256) !== 1) {
                throw new InvalidArgumentException('金鑰對不匹配，公鑰無法驗證私鑰簽名');
            }
        } catch (Exception $e) {
            throw new InvalidArgumentException('金鑰驗證失敗: ' . $e->getMessage());
        }
    }

    // Getter 方法

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey ?? '';
    }

    public function getPublicKey(): string
    {
        return $this->publicKey ?? '';
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function getAudience(): string
    {
        return $this->audience;
    }

    public function getAccessTokenTtl(): int
    {
        return $this->accessTokenTtl;
    }

    public function getRefreshTokenTtl(): int
    {
        return $this->refreshTokenTtl;
    }

    /**
     * 取得 access token 過期時間戳記.
     */
    public function getAccessTokenExpiryTimestamp(): int
    {
        return time() + $this->accessTokenTtl;
    }

    /**
     * 取得 refresh token 過期時間戳記.
     */
    public function getRefreshTokenExpiryTimestamp(): int
    {
        return time() + $this->refreshTokenTtl;
    }

    /**
     * 取得基本 JWT payload 結構.
     */
    public function getBasePayload(): array
    {
        $now = time();

        return [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
        ];
    }

    /**
     * 檢查配置是否已正確載入.
     */
    public function isConfigured(): bool
    {
        return !empty($this->privateKey)
            && !empty($this->publicKey)
            && !empty($this->issuer)
            && !empty($this->audience);
    }

    /**
     * 取得配置摘要（用於日誌記錄，不包含敏感資訊）.
     */
    public function getConfigSummary(): array
    {
        return [
            'algorithm' => $this->algorithm,
            'issuer' => $this->issuer,
            'audience' => $this->audience,
            'access_token_ttl' => $this->accessTokenTtl,
            'refresh_token_ttl' => $this->refreshTokenTtl,
            'private_key_configured' => !empty($this->privateKey),
            'public_key_configured' => !empty($this->publicKey),
        ];
    }

    private function loadKeyFromPath(string $pathEnvKey): ?string
    {
        $pathEnv = $_ENV[$pathEnvKey] ?? getenv($pathEnvKey);

        if (!is_string($pathEnv) || $pathEnv === '') {
            return null;
        }

        $candidatePaths = [$pathEnv];
        $basePath = dirname(__DIR__, 3);
        $resolvedRelative = $basePath . DIRECTORY_SEPARATOR . ltrim($pathEnv, DIRECTORY_SEPARATOR);

        if ($resolvedRelative !== $pathEnv) {
            $candidatePaths[] = $resolvedRelative;
        }

        foreach ($candidatePaths as $candidatePath) {
            if (is_file($candidatePath) && is_readable($candidatePath)) {
                $contents = file_get_contents($candidatePath);

                if ($contents === false) {
                    // If file exists but cannot be read, it's a hard error.
                    throw new InvalidArgumentException(
                        sprintf('%s 指定的金鑰檔案無法讀取: %s', $pathEnvKey, $candidatePath),
                    );
                }

                return trim($contents);
            }
        }

        // Only throw if the path was specified but no file was found/readable.
        throw new InvalidArgumentException(
            sprintf('%s 指定的金鑰檔案不存在或不可讀: %s', $pathEnvKey, $pathEnv),
        );
    }
}
