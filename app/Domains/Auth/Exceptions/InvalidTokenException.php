<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

/**
 * 無效 Token 例外.
 *
 * 當 JWT Token 格式錯誤、簽章驗證失敗、或內容無效時拋出此例外。
 * 包含具體的驗證失敗原因和相關詳細資訊。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class InvalidTokenException extends JwtException
{
    /**
     * 錯誤類型標識.
     */
    protected string $errorType = 'invalid_token';

    /**
     * 錯誤碼常數.
     */
    public const ERROR_CODE = 4002;

    /**
     * 驗證失敗原因常數.
     */
    public const REASON_MALFORMED = 'malformed';

    public const REASON_SIGNATURE_INVALID = 'signature_invalid';

    public const REASON_ALGORITHM_MISMATCH = 'algorithm_mismatch';

    public const REASON_ISSUER_INVALID = 'issuer_invalid';

    public const REASON_AUDIENCE_INVALID = 'audience_invalid';

    public const REASON_SUBJECT_MISSING = 'subject_missing';

    public const REASON_CLAIMS_INVALID = 'claims_invalid';

    public const REASON_DECODE_FAILED = 'decode_failed';

    public const REASON_BLACKLISTED = 'blacklisted';

    public const REASON_NOT_BEFORE = 'not_before';

    /**
     * Token 類型常數.
     */
    public const ACCESS_TOKEN = 'access_token';

    public const REFRESH_TOKEN = 'refresh_token';

    /**
     * 建立無效 Token 例外.
     *
     * @param string $reason 失敗原因
     * @param string $tokenType Token 類型
     * @param string $customMessage 自定義錯誤訊息
     * @param array<string, mixed> $additionalContext 額外上下文資訊
     */
    public function __construct(
        string $reason = self::REASON_DECODE_FAILED,
        string $tokenType = self::ACCESS_TOKEN,
        string $customMessage = '',
        array $additionalContext = [],
    ) {
        $message = $customMessage ?: $this->buildDefaultMessage($reason, $tokenType);

        $context = array_merge([
            'reason' => $reason,
            'token_type' => $tokenType,
            'timestamp' => time(),
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
            self::REASON_MALFORMED => sprintf('%s format is malformed', $tokenName),
            self::REASON_SIGNATURE_INVALID => sprintf('%s signature verification failed', $tokenName),
            self::REASON_ALGORITHM_MISMATCH => sprintf('%s algorithm does not match expected algorithm', $tokenName),
            self::REASON_ISSUER_INVALID => sprintf('%s issuer is invalid', $tokenName),
            self::REASON_AUDIENCE_INVALID => sprintf('%s audience is invalid', $tokenName),
            self::REASON_SUBJECT_MISSING => sprintf('%s subject is missing', $tokenName),
            self::REASON_CLAIMS_INVALID => sprintf('%s contains invalid claims', $tokenName),
            self::REASON_BLACKLISTED => sprintf('%s has been blacklisted', $tokenName),
            self::REASON_NOT_BEFORE => sprintf('%s is not valid yet', $tokenName),
            default => sprintf('%s is invalid', $tokenName),
        };
    }

    /**
     * 取得用戶友好的錯誤訊息.
     */
    public function getUserFriendlyMessage(): string
    {
        $reason = $this->getReason();

        return match ($reason) {
            self::REASON_MALFORMED,
            self::REASON_SIGNATURE_INVALID,
            self::REASON_ALGORITHM_MISMATCH,
            self::REASON_DECODE_FAILED => '提供的 Token 格式錯誤或已損壞，請重新登入。',
            self::REASON_ISSUER_INVALID,
            self::REASON_AUDIENCE_INVALID => '此 Token 不適用於當前應用程式，請重新登入。',
            self::REASON_SUBJECT_MISSING => 'Token 缺少必要的用戶資訊，請重新登入。',
            self::REASON_CLAIMS_INVALID => 'Token 包含無效的聲明資訊，請重新登入。',
            self::REASON_BLACKLISTED => '此 Token 已被撤銷，請重新登入。',
            self::REASON_NOT_BEFORE => '此 Token 尚未生效，請稍後再試或重新登入。',
            default => '提供的 Token 無效，請重新登入。',
        };
    }

    /**
     * 取得失敗原因.
     */
    public function getReason(): string
    {
        return $this->context['reason'] ?? self::REASON_DECODE_FAILED;
    }

    /**
     * 取得 Token 類型.
     */
    public function getTokenType(): string
    {
        return $this->context['token_type'] ?? self::ACCESS_TOKEN;
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
     * 檢查是否為 Access Token 無效.
     */
    public function isAccessTokenInvalid(): bool
    {
        return $this->getTokenType() === self::ACCESS_TOKEN;
    }

    /**
     * 檢查是否為 Refresh Token 無效.
     */
    public function isRefreshTokenInvalid(): bool
    {
        return $this->getTokenType() === self::REFRESH_TOKEN;
    }

    /**
     * 檢查是否為簽章相關錯誤.
     */
    public function isSignatureRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_SIGNATURE_INVALID,
            self::REASON_ALGORITHM_MISMATCH,
        ]);
    }

    /**
     * 檢查是否為格式相關錯誤.
     */
    public function isFormatRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_MALFORMED,
            self::REASON_DECODE_FAILED,
        ]);
    }

    /**
     * 檢查是否為聲明相關錯誤.
     */
    public function isClaimsRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_ISSUER_INVALID,
            self::REASON_AUDIENCE_INVALID,
            self::REASON_SUBJECT_MISSING,
            self::REASON_CLAIMS_INVALID,
        ]);
    }

    /**
     * 靜態工廠方法：格式錯誤.
     *
     * @param string $tokenType Token 類型
     * @param array<string, mixed> $context 上下文資訊
     */
    public static function malformed(string $tokenType = self::ACCESS_TOKEN, array $context = []): self
    {
        return new self(self::REASON_MALFORMED, $tokenType, '', $context);
    }

    /**
     * 靜態工廠方法：簽章無效.
     *
     * @param string $tokenType Token 類型
     * @param array<string, mixed> $context 上下文資訊
     */
    public static function signatureInvalid(string $tokenType = self::ACCESS_TOKEN, array $context = []): self
    {
        return new self(self::REASON_SIGNATURE_INVALID, $tokenType, '', $context);
    }

    /**
     * 靜態工廠方法：演算法不匹配.
     *
     * @param string $expectedAlgorithm 期望的演算法
     * @param string $actualAlgorithm 實際的演算法
     * @param string $tokenType Token 類型
     */
    public static function algorithmMismatch(
        string $expectedAlgorithm,
        string $actualAlgorithm,
        string $tokenType = self::ACCESS_TOKEN,
    ): self {
        return new self(self::REASON_ALGORITHM_MISMATCH, $tokenType, '', [
            'expected_algorithm' => $expectedAlgorithm,
            'actual_algorithm' => $actualAlgorithm,
        ]);
    }

    /**
     * 靜態工廠方法：發行者無效.
     *
     * @param string $expectedIssuer 期望的發行者
     * @param string $actualIssuer 實際的發行者
     * @param string $tokenType Token 類型
     */
    public static function issuerInvalid(
        string $expectedIssuer,
        string $actualIssuer,
        string $tokenType = self::ACCESS_TOKEN,
    ): self {
        return new self(self::REASON_ISSUER_INVALID, $tokenType, '', [
            'expected_issuer' => $expectedIssuer,
            'actual_issuer' => $actualIssuer,
        ]);
    }

    /**
     * 靜態工廠方法：受眾無效.
     *
     * @param string $expectedAudience 期望的受眾
     * @param string $actualAudience 實際的受眾
     * @param string $tokenType Token 類型
     */
    public static function audienceInvalid(
        string $expectedAudience,
        string $actualAudience,
        string $tokenType = self::ACCESS_TOKEN,
    ): self {
        return new self(self::REASON_AUDIENCE_INVALID, $tokenType, '', [
            'expected_audience' => $expectedAudience,
            'actual_audience' => $actualAudience,
        ]);
    }

    /**
     * 靜態工廠方法：主題遺失.
     *
     * @param string $tokenType Token 類型
     */
    public static function subjectMissing(string $tokenType = self::ACCESS_TOKEN): self
    {
        return new self(self::REASON_SUBJECT_MISSING, $tokenType);
    }

    /**
     * 靜態工廠方法：聲明無效.
     *
     * @param array<string, mixed> $invalidClaims 無效的聲明
     * @param string $tokenType Token 類型
     */
    public static function claimsInvalid(array $invalidClaims, string $tokenType = self::ACCESS_TOKEN): self
    {
        return new self(self::REASON_CLAIMS_INVALID, $tokenType, '', [
            'invalid_claims' => $invalidClaims,
        ]);
    }

    /**
     * 靜態工廠方法：Token 已被列入黑名單.
     *
     * @param string $tokenId Token ID
     * @param string $tokenType Token 類型
     */
    public static function blacklisted(string $tokenId, string $tokenType = self::ACCESS_TOKEN): self
    {
        return new self(self::REASON_BLACKLISTED, $tokenType, '', [
            'token_id' => $tokenId,
        ]);
    }

    /**
     * 靜態工廠方法：Token 尚未生效.
     *
     * @param int $notBefore 生效時間戳
     * @param string $tokenType Token 類型
     */
    public static function notBefore(int $notBefore, string $tokenType = self::ACCESS_TOKEN): self
    {
        return new self(self::REASON_NOT_BEFORE, $tokenType, '', [
            'not_before' => $notBefore,
            'not_before_human' => date('Y-m-d H:i:s', $notBefore),
        ]);
    }
}
