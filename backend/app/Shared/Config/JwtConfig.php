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

    private string $privateKey;

    private string $publicKey;

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
        $this->privateKey = $this->loadPrivateKey();
        $this->publicKey = $this->loadPublicKey();
        $this->issuer = $_ENV['JWT_ISSUER'] ?? 'alleynote-api';
        $this->audience = $_ENV['JWT_AUDIENCE'] ?? 'alleynote-client';
        $this->accessTokenTtl = (int) ($_ENV['JWT_ACCESS_TOKEN_TTL'] ?? 3600);
        $this->refreshTokenTtl = (int) ($_ENV['JWT_REFRESH_TOKEN_TTL'] ?? 2592000);
    }

    /**
     * 載入私鑰.
     */
    private function loadPrivateKey(): string
    {
        $privateKey = $_ENV['JWT_PRIVATE_KEY'] ?? '';

        if (empty($privateKey)) {
            throw new InvalidArgumentException('JWT_PRIVATE_KEY 環境變數未設定');
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
        $publicKey = $_ENV['JWT_PUBLIC_KEY'] ?? '';

        if (empty($publicKey)) {
            throw new InvalidArgumentException('JWT_PUBLIC_KEY 環境變數未設定');
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
        if (!in_array($this->algorithm, ['RS256', 'RS384', 'RS512'])) {
            throw new InvalidArgumentException("不支援的演算法: {$this->algorithm}");
        }

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

        // 驗證金鑰對是否匹配（簡單測試）
        $this->validateKeyPair();
    }

    /**
     * 驗證金鑰對是否匹配.
     */
    private function validateKeyPair(): void
    {
        try {
            // 使用 openssl 函數驗證金鑰對
            $privateKeyResource = openssl_pkey_get_private($this->privateKey);
            $publicKeyResource = openssl_pkey_get_public($this->publicKey);

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
        return $this->privateKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
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
}
