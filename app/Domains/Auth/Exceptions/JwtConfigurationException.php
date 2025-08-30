<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Throwable;

/**
 * JWT 配置例外.
 *
 * 當 JWT 配置無效、金鑰檔案無法讀取、或金鑰格式錯誤時拋出此例外
 */
class JwtConfigurationException extends JwtException
{
    /**
     * 錯誤類型標識.
     */
    protected string $errorType = 'jwt_configuration_error';

    /**
     * 錯誤碼常數.
     */
    public const ERROR_CODE = 5001;

    /**
     * 配置錯誤原因常數.
     */
    public const INVALID_KEY_FORMAT = 'invalid_key_format';

    public const KEY_FILE_NOT_READABLE = 'key_file_not_readable';

    public const KEY_FILE_READ_ERROR = 'key_file_read_error';

    public const INVALID_PRIVATE_KEY_FORMAT = 'invalid_private_key_format';

    public const INVALID_PUBLIC_KEY_FORMAT = 'invalid_public_key_format';

    public const KEY_MISMATCH = 'key_mismatch';

    public const MISSING_CONFIGURATION = 'missing_configuration';

    public const INVALID_TTL = 'invalid_ttl';

    public const INVALID_ALGORITHM = 'invalid_algorithm';

    /**
     * 建立 JWT 配置例外.
     *
     * @param string $message 錯誤訊息
     * @param string $reason 失敗原因
     * @param Throwable|null $previous 前一個例外
     * @param array $additionalContext 額外上下文資訊
     */
    public function __construct(
        string $message,
        string $reason = self::MISSING_CONFIGURATION,
        ?Throwable $previous = null,
        array $additionalContext = [],
    ) {
        $context = array_merge([
            'reason' => $reason,
            'timestamp' => time(),
        ], $additionalContext);

        parent::__construct($message, self::ERROR_CODE, $previous, $context);
    }

    /**
     * 取得配置錯誤原因.
     */
    public function getReason(): string
    {
        return $this->context['reason'] ?? self::MISSING_CONFIGURATION;
    }

    /**
     * 取得用戶友好的錯誤訊息.
     */
    public function getUserFriendlyMessage(): string
    {
        $reason = $this->getReason();

        return match ($reason) {
            self::INVALID_KEY_FORMAT,
            self::INVALID_PRIVATE_KEY_FORMAT,
            self::INVALID_PUBLIC_KEY_FORMAT => 'JWT 金鑰格式錯誤，請檢查金鑰檔案格式。',
            self::KEY_FILE_NOT_READABLE,
            self::KEY_FILE_READ_ERROR => 'JWT 金鑰檔案無法讀取，請檢查檔案權限和路徑。',
            self::KEY_MISMATCH => 'JWT 私鑰和公鑰不匹配，請檢查金鑰對。',
            self::MISSING_CONFIGURATION => 'JWT 配置缺失，請檢查環境變數設定。',
            self::INVALID_TTL => 'JWT Token 存活時間設定無效。',
            self::INVALID_ALGORITHM => 'JWT 演算法設定無效。',
            default => 'JWT 配置錯誤，請聯絡系統管理員。',
        };
    }

    /**
     * 靜態工廠方法：金鑰格式無效.
     */
    public static function invalidKeyFormat(string $details = '', ?Throwable $previous = null): self
    {
        $message = 'JWT 金鑰格式無效' . ($details ? ': ' . $details : '');

        return new self($message, self::INVALID_KEY_FORMAT, $previous);
    }

    /**
     * 靜態工廠方法：金鑰檔案無法讀取.
     */
    public static function keyFileNotReadable(string $filePath, ?Throwable $previous = null): self
    {
        $message = "JWT 金鑰檔案無法讀取: {$filePath}";

        return new self($message, self::KEY_FILE_NOT_READABLE, $previous, ['file_path' => $filePath]);
    }

    /**
     * 靜態工廠方法：私鑰格式無效.
     */
    public static function invalidPrivateKeyFormat(?Throwable $previous = null): self
    {
        return new self('JWT 私鑰格式無效', self::INVALID_PRIVATE_KEY_FORMAT, $previous);
    }

    /**
     * 靜態工廠方法：公鑰格式無效.
     */
    public static function invalidPublicKeyFormat(?Throwable $previous = null): self
    {
        return new self('JWT 公鑰格式無效', self::INVALID_PUBLIC_KEY_FORMAT, $previous);
    }

    /**
     * 靜態工廠方法：金鑰不匹配.
     */
    public static function keyMismatch(?Throwable $previous = null): self
    {
        return new self('JWT 私鑰和公鑰不匹配', self::KEY_MISMATCH, $previous);
    }
}
