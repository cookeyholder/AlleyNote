<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Exception;
use Throwable;

/**
 * Token 解析例外.
 *
 * 當 JWT Token 無法解析時拋出此例外，用於不安全的解析操作（如取得過期 token 資訊）
 */
class TokenParsingException extends JwtException
{
    /**
     * 錯誤類型標識.
     */
    protected string $errorType = 'token_parsing_error';

    /**
     * 錯誤碼常數.
     */
    public const ERROR_CODE = 4004;

    /**
     * 解析失敗原因常數.
     */
    public const EMPTY_TOKEN = 'empty_token';

    public const INVALID_FORMAT = 'invalid_format';

    public const PARSING_FAILED = 'parsing_failed';

    public const JSON_DECODE_ERROR = 'json_decode_error';

    public const BASE64_DECODE_ERROR = 'base64_decode_error';

    /**
     * 建立 Token 解析例外.
     *
     * @param string $message 錯誤訊息
     * @param string $reason 失敗原因
     * @param Throwable|null $previous 前一個例外
     * @param array<string, mixed> $additionalContext 額外上下文資訊
     */
    public function __construct(
        string $message,
        string $reason = self::PARSING_FAILED,
        ?Throwable $previous = null,
        array $additionalContext = [],
    ) {
        $context = array_merge([
            'reason' => $reason,
            'timestamp' => time(),
        ], $additionalContext);

        // Throwable 可以安全地傳遞給 Exception
        $exceptionPrevious = $previous instanceof Exception ? $previous : null;
        parent::__construct($message, self::ERROR_CODE, $exceptionPrevious, $context);
    }

    /**
     * 取得解析失敗原因.
     */
    public function getReason(): string
    {
        $reason = $this->context['reason'] ?? self::PARSING_FAILED;

        return is_string($reason) ? $reason : self::PARSING_FAILED;
    }

    /**
     * 取得用戶友好的錯誤訊息.
     */
    public function getUserFriendlyMessage(): string
    {
        $reason = $this->getReason();

        return match ($reason) {
            self::EMPTY_TOKEN => 'Token 不能為空，請提供有效的 Token。',
            self::INVALID_FORMAT => 'Token 格式無效，請提供正確格式的 JWT Token。',
            self::JSON_DECODE_ERROR => 'Token 內容格式錯誤，無法解析 JSON 資料。',
            self::BASE64_DECODE_ERROR => 'Token 編碼格式錯誤，無法解析 Base64 資料。',
            default => 'Token 解析失敗，請提供有效的 Token。',
        };
    }

    /**
     * 靜態工廠方法：空 Token.
     */
    public static function emptyToken(): self
    {
        return new self('Token 不能為空', self::EMPTY_TOKEN);
    }

    /**
     * 靜態工廠方法：無效格式.
     */
    public static function invalidFormat(string $details = '', ?Throwable $previous = null): self
    {
        $message = 'Token 格式無效' . ($details ? ': ' . $details : '');

        return new self($message, self::INVALID_FORMAT, $previous);
    }

    /**
     * 靜態工廠方法：JSON 解碼錯誤.
     */
    public static function jsonDecodeError(?Throwable $previous = null): self
    {
        return new self('Token JSON 資料解碼失敗', self::JSON_DECODE_ERROR, $previous);
    }

    /**
     * 靜態工廠方法：Base64 解碼錯誤.
     */
    public static function base64DecodeError(?Throwable $previous = null): self
    {
        return new self('Token Base64 資料解碼失敗', self::BASE64_DECODE_ERROR, $previous);
    }
}
