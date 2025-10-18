<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Throwable;

/**
 * Token 驗證例外.
 *
 * 當 JWT Token 驗證失敗時拋出此例外，包括簽名驗證、issuer/audience 驗證等
 */
class TokenValidationException extends JwtException
{
    /**
     * 錯誤類型標識.
     */
    protected string $errorType = 'token_validation_error';

    /**
     * 錯誤碼常數.
     */
    public const ERROR_CODE = 4003;

    /**
     * 驗證失敗原因常數.
     */
    public const INVALID_SIGNATURE = 'invalid_signature';

    public const INVALID_ISSUER = 'invalid_issuer';

    public const INVALID_AUDIENCE = 'invalid_audience';

    public const VALIDATION_FAILED = 'validation_failed';

    public const ALGORITHM_NOT_ALLOWED = 'algorithm_not_allowed';

    public const KEY_NOT_FOUND = 'key_not_found';

    /**
     * 建立 Token 驗證例外.
     *
     * @param string $message 錯誤訊息
     * @param string $reason 失敗原因
     * @param Throwable|null $previous 前一個例外
     * @param array<string, mixed> $additionalContext 額外上下文資訊
     */
    public function __construct(
        string $message,
        string $reason = self::VALIDATION_FAILED,
        ?Throwable $previous = null,
        array $additionalContext = [],
    ) {
        $context = array_merge([
            'reason' => $reason,
            'timestamp' => time(),
        ], $additionalContext);

        // Throwable 可以安全地傳遞給 Exception
        $exceptionPrevious = $previous instanceof \Exception ? $previous : null;
        parent::__construct($message, self::ERROR_CODE, $exceptionPrevious, $context);
    }

    /**
     * 取得驗證失敗原因.
     */
    public function getReason(): string
    {
        $reason = $this->context['reason'] ?? self::VALIDATION_FAILED;
        return is_string($reason) ? $reason : self::VALIDATION_FAILED;
    }

    /**
     * 取得用戶友好的錯誤訊息.
     */
    public function getUserFriendlyMessage(): string
    {
        $reason = $this->getReason();

        return match ($reason) {
            self::INVALID_SIGNATURE => 'Token 簽名驗證失敗，請重新登入。',
            self::INVALID_ISSUER => 'Token 發行者無效，請重新登入。',
            self::INVALID_AUDIENCE => 'Token 受眾無效，請重新登入。',
            self::ALGORITHM_NOT_ALLOWED => 'Token 演算法不被允許，請重新登入。',
            self::KEY_NOT_FOUND => 'Token 驗證金鑰不存在，請重新登入。',
            default => 'Token 驗證失敗，請重新登入。',
        };
    }

    /**
     * 靜態工廠方法：簽名無效.
     */
    public static function invalidSignature(?Throwable $previous = null): self
    {
        return new self('Token 簽名驗證失敗', self::INVALID_SIGNATURE, $previous);
    }

    /**
     * 靜態工廠方法：發行者無效.
     */
    public static function invalidIssuer(string $expected, string $actual, ?Throwable $previous = null): self
    {
        $message = "Token 發行者無效，期望: {$expected}，實際: {$actual}";

        return new self($message, self::INVALID_ISSUER, $previous, [
            'expected_issuer' => $expected,
            'actual_issuer' => $actual,
        ]);
    }

    /**
     * 靜態工廠方法：受眾無效.
     */
    public static function invalidAudience(string $expected, string $actual, ?Throwable $previous = null): self
    {
        $message = "Token 受眾無效，期望: {$expected}，實際: {$actual}";

        return new self($message, self::INVALID_AUDIENCE, $previous, [
            'expected_audience' => $expected,
            'actual_audience' => $actual,
        ]);
    }
}
