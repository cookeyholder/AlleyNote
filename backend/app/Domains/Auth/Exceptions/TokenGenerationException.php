<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

/**
 * Token 生成失敗例外.
 *
 * 當生成 JWT Token 過程中發生錯誤時拋出此例外。
 * 包含生成失敗的具體原因和相關詳細資訊。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class TokenGenerationException extends JwtException
{
    /**
     * 錯誤類型標識.
     */
    protected string $errorType = 'token_generation_failed';

    /**
     * 錯誤碼常數.
     */
    public const ERROR_CODE = 5001;

    /**
     * 生成失敗原因常數.
     */
    public const REASON_KEY_INVALID = 'key_invalid';

    public const REASON_KEY_MISSING = 'key_missing';

    public const REASON_PAYLOAD_INVALID = 'payload_invalid';

    public const REASON_ALGORITHM_UNSUPPORTED = 'algorithm_unsupported';

    public const REASON_ENCODING_FAILED = 'encoding_failed';

    public const REASON_CLAIMS_INVALID = 'claims_invalid';

    public const REASON_SIGNATURE_FAILED = 'signature_failed';

    public const REASON_RESOURCE_EXHAUSTED = 'resource_exhausted';

    /**
     * Token 類型常數.
     */
    public const ACCESS_TOKEN = 'access_token';

    public const REFRESH_TOKEN = 'refresh_token';

    /**
     * 建立 Token 生成失敗例外.
     *
     * @param string $reason 失敗原因
     * @param string $tokenType Token 類型
     * @param string $customMessage 自定義錯誤訊息
     * @param array<string, mixed> $additionalContext 額外上下文資訊
     */
    public function __construct(
        string $reason = self::REASON_ENCODING_FAILED,
        string $tokenType = self::ACCESS_TOKEN,
        string $customMessage = '',
        array $additionalContext = [],
    ) {
        $message = $customMessage ?: $this->buildDefaultMessage($reason, $tokenType);

        $context = array_merge([
            'reason' => $reason,
            'token_type' => $tokenType,
            'timestamp' => time(),
            'generation_attempt_id' => uniqid('gen_', true),
        ], $additionalContext);

        parent::__construct($message, self::ERROR_CODE, null, $context);
    }

    /**
     * 建構預設錯誤訊息.
     *
     * @param string $reason 失敗原因
     * @param string $tokenType Token 類型
     */
    private function buildDefaultMessage(string $reason, string $tokenType): string
    {
        $tokenName = $tokenType === self::ACCESS_TOKEN ? 'Access token' : 'Refresh token';

        return match ($reason) {
            self::REASON_KEY_INVALID => sprintf('Failed to generate %s: private key is invalid or corrupted', $tokenName),
            self::REASON_KEY_MISSING => sprintf('Failed to generate %s: private key is missing', $tokenName),
            self::REASON_PAYLOAD_INVALID => sprintf('Failed to generate %s: payload contains invalid data', $tokenName),
            self::REASON_ALGORITHM_UNSUPPORTED => sprintf('Failed to generate %s: algorithm is not supported', $tokenName),
            self::REASON_CLAIMS_INVALID => sprintf('Failed to generate %s: claims validation failed', $tokenName),
            self::REASON_SIGNATURE_FAILED => sprintf('Failed to generate %s: signature generation failed', $tokenName),
            self::REASON_RESOURCE_EXHAUSTED => sprintf('Failed to generate %s: system resources exhausted', $tokenName),
            default => sprintf('Failed to generate %s: encoding process failed', $tokenName),
        };
    }

    /**
     * 取得用戶友好的錯誤訊息.
     */
    public function getUserFriendlyMessage(): string
    {
        $reason = $this->getReason();

        return match ($reason) {
            self::REASON_KEY_INVALID,
            self::REASON_KEY_MISSING => '系統配置錯誤，無法產生安全 Token。請聯絡系統管理員。',
            self::REASON_PAYLOAD_INVALID,
            self::REASON_CLAIMS_INVALID => '提供的用戶資訊格式錯誤，請檢查後重試。',
            self::REASON_ALGORITHM_UNSUPPORTED => '系統安全演算法配置錯誤，請聯絡系統管理員。',
            self::REASON_RESOURCE_EXHAUSTED => '系統資源不足，請稍後重試。',
            self::REASON_SIGNATURE_FAILED => '數位簽章產生失敗，請聯絡系統管理員。',
            default => 'Token 生成過程發生錯誤，請稍後重試。如問題持續發生，請聯絡系統管理員。',
        };
    }

    /**
     * 取得失敗原因.
     */
    public function getReason(): string
    {
        $reason = $this->context['reason'] ?? self::REASON_ENCODING_FAILED;

        return is_string($reason) ? $reason : self::REASON_ENCODING_FAILED;
    }

    /**
     * 取得 Token 類型.
     */
    public function getTokenType(): string
    {
        $tokenType = $this->context['token_type'] ?? self::ACCESS_TOKEN;

        return is_string($tokenType) ? $tokenType : self::ACCESS_TOKEN;
    }

    /**
     * 取得生成嘗試 ID.
     */
    public function getGenerationAttemptId(): ?string
    {
        $attemptId = $this->context['generation_attempt_id'] ?? null;

        return is_string($attemptId) ? $attemptId : null;
    }

    /**
     * 檢查是否為特定失敗原因.
     *
     * @param string $reason 原因
     */
    public function isReason(string $reason): bool
    {
        return $this->getReason() === $reason;
    }

    /**
     * 檢查是否為 Access Token 生成失敗.
     */
    public function isAccessTokenGeneration(): bool
    {
        return $this->getTokenType() === self::ACCESS_TOKEN;
    }

    /**
     * 檢查是否為 Refresh Token 生成失敗.
     */
    public function isRefreshTokenGeneration(): bool
    {
        return $this->getTokenType() === self::REFRESH_TOKEN;
    }

    /**
     * 檢查是否為金鑰相關錯誤.
     */
    public function isKeyRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_KEY_INVALID,
            self::REASON_KEY_MISSING,
        ]);
    }

    /**
     * 檢查是否為資料相關錯誤.
     */
    public function isDataRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_PAYLOAD_INVALID,
            self::REASON_CLAIMS_INVALID,
        ]);
    }

    /**
     * 檢查是否為系統配置錯誤.
     */
    public function isSystemConfigurationError(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_KEY_INVALID,
            self::REASON_KEY_MISSING,
            self::REASON_ALGORITHM_UNSUPPORTED,
        ]);
    }

    /**
     * 檢查是否為暫時性錯誤（可重試）.
     */
    public function isRetryable(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_RESOURCE_EXHAUSTED,
            self::REASON_ENCODING_FAILED,
        ]);
    }

    /**
     * 靜態工廠方法：金鑰無效.
     *
     * @param string $keyInfo 金鑰資訊
     * @param string $tokenType Token 類型
     */
    public static function keyInvalid(string $keyInfo = '', string $tokenType = self::ACCESS_TOKEN): self
    {
        $context = $keyInfo ? ['key_info' => $keyInfo] : [];

        return new self(self::REASON_KEY_INVALID, $tokenType, '', $context);
    }

    /**
     * 靜態工廠方法：金鑰遺失.
     *
     * @param string $tokenType Token 類型
     */
    public static function keyMissing(string $tokenType = self::ACCESS_TOKEN): self
    {
        return new self(self::REASON_KEY_MISSING, $tokenType);
    }

    /**
     * 靜態工廠方法：載荷無效.
     *
     * @param array<string, mixed> $invalidFields 無效欄位
     * @param string $tokenType Token 類型
     */
    public static function payloadInvalid(array $invalidFields, string $tokenType = self::ACCESS_TOKEN): self
    {
        return new self(self::REASON_PAYLOAD_INVALID, $tokenType, '', [
            'invalid_fields' => $invalidFields,
        ]);
    }

    /**
     * 靜態工廠方法：演算法不支援.
     *
     * @param string $algorithm 演算法名稱
     * @param string $tokenType Token 類型
     */
    public static function algorithmUnsupported(string $algorithm, string $tokenType = self::ACCESS_TOKEN): self
    {
        return new self(self::REASON_ALGORITHM_UNSUPPORTED, $tokenType, '', [
            'algorithm' => $algorithm,
        ]);
    }

    /**
     * 靜態工廠方法：聲明無效.
     *
     * @param array<string, mixed> $invalidClaims 無效聲明
     * @param string $tokenType Token 類型
     */
    public static function claimsInvalid(array $invalidClaims, string $tokenType = self::ACCESS_TOKEN): self
    {
        return new self(self::REASON_CLAIMS_INVALID, $tokenType, '', [
            'invalid_claims' => $invalidClaims,
        ]);
    }

    /**
     * 靜態工廠方法：簽章生成失敗.
     *
     * @param string $details 失敗詳情
     * @param string $tokenType Token 類型
     */
    public static function signatureFailed(string $details = '', string $tokenType = self::ACCESS_TOKEN): self
    {
        $context = $details ? ['failure_details' => $details] : [];

        return new self(self::REASON_SIGNATURE_FAILED, $tokenType, '', $context);
    }

    /**
     * 靜態工廠方法：資源耗盡
     *
     * @param string $resourceType 資源類型
     * @param string $tokenType Token 類型
     */
    public static function resourceExhausted(string $resourceType = 'memory', string $tokenType = self::ACCESS_TOKEN): self
    {
        return new self(self::REASON_RESOURCE_EXHAUSTED, $tokenType, '', [
            'resource_type' => $resourceType,
        ]);
    }

    /**
     * 靜態工廠方法：編碼失敗.
     *
     * @param string $details 失敗詳情
     * @param string $tokenType Token 類型
     */
    public static function encodingFailed(string $details = '', string $tokenType = self::ACCESS_TOKEN): self
    {
        $context = $details ? ['failure_details' => $details] : [];

        return new self(self::REASON_ENCODING_FAILED, $tokenType, '', $context);
    }
}
