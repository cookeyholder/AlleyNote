<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

/**
 * 身份驗證失敗例外.
 *
 * 當用戶身份驗證過程中發生錯誤時拋出此例外。
 * 包含驗證失敗的具體原因和相關詳細資訊。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class AuthenticationException extends JwtException
{
    /**
     * 錯誤類型標識.
     */
    protected string $errorType = 'authentication_failed';

    /**
     * 錯誤碼常數.
     */
    public const ERROR_CODE = 4001;

    /**
     * 驗證失敗原因常數.
     */
    public const REASON_INVALID_CREDENTIALS = 'invalid_credentials';

    public const REASON_ACCOUNT_LOCKED = 'account_locked';

    public const REASON_ACCOUNT_DISABLED = 'account_disabled';

    public const REASON_ACCOUNT_NOT_VERIFIED = 'account_not_verified';

    public const REASON_TOO_MANY_ATTEMPTS = 'too_many_attempts';

    public const REASON_USER_NOT_FOUND = 'user_not_found';

    public const REASON_PASSWORD_EXPIRED = 'password_expired';

    public const REASON_MISSING_CREDENTIALS = 'missing_credentials';

    public const REASON_INVALID_TOKEN = 'invalid_token';

    public const REASON_INVALID_REFRESH_TOKEN = 'invalid_refresh_token';

    public const REASON_TOKEN_REFRESH_FAILED = 'token_refresh_failed';

    public const REASON_TOKEN_REQUIRED = 'token_required';

    public const REASON_INSUFFICIENT_PRIVILEGES = 'insufficient_privileges';

    /**
     * 建立身份驗證失敗例外.
     *
     * @param string $reason 失敗原因
     * @param string $customMessage 自定義錯誤訊息
     * @param array $additionalContext 額外上下文資訊
     */
    public function __construct(
        string $reason = self::REASON_INVALID_CREDENTIALS,
        string $customMessage = '',
        array $additionalContext = [],
    ) {
        $message = $customMessage ?: $this->buildDefaultMessage($reason);

        $context = array_merge([
            'reason' => $reason,
            'timestamp' => time(),
            'attempt_id' => uniqid('auth_', true),
        ], $additionalContext);

        parent::__construct($message, self::ERROR_CODE, null, $context);
    }

    /**
     * 建構預設錯誤訊息.
     *
     * @param string $reason 失敗原因
     */
    private function buildDefaultMessage(string $reason): string
    {
        return match ($reason) {
            self::REASON_INVALID_CREDENTIALS => 'Invalid username or password',
            self::REASON_ACCOUNT_LOCKED => 'Account has been locked due to security reasons',
            self::REASON_ACCOUNT_DISABLED => 'Account has been disabled',
            self::REASON_ACCOUNT_NOT_VERIFIED => 'Account has not been verified',
            self::REASON_TOO_MANY_ATTEMPTS => 'Too many failed authentication attempts',
            self::REASON_USER_NOT_FOUND => 'User not found',
            self::REASON_PASSWORD_EXPIRED => 'Password has expired and needs to be changed',
            self::REASON_MISSING_CREDENTIALS => 'Authentication credentials are missing',
            self::REASON_INVALID_TOKEN => 'Invalid authentication token',
            self::REASON_INVALID_REFRESH_TOKEN => 'Invalid refresh token',
            self::REASON_TOKEN_REFRESH_FAILED => 'Token refresh failed',
            self::REASON_TOKEN_REQUIRED => 'Authentication token is required',
            self::REASON_INSUFFICIENT_PRIVILEGES => 'Insufficient privileges to access this resource',
            default => 'Authentication failed',
        };
    }

    /**
     * 取得用戶友好的錯誤訊息.
     */
    public function getUserFriendlyMessage(): string
    {
        $reason = $this->getReason();

        return match ($reason) {
            self::REASON_INVALID_CREDENTIALS => '用戶名或密碼錯誤，請檢查後重新輸入。',
            self::REASON_ACCOUNT_LOCKED => '您的帳戶已被鎖定，請聯絡系統管理員。',
            self::REASON_ACCOUNT_DISABLED => '您的帳戶已被停用，請聯絡系統管理員。',
            self::REASON_ACCOUNT_NOT_VERIFIED => '您的帳戶尚未驗證，請檢查電子郵件並完成驗證。',
            self::REASON_TOO_MANY_ATTEMPTS => '登入嘗試次數過多，請稍後重試。',
            self::REASON_USER_NOT_FOUND => '找不到此用戶，請檢查用戶名是否正確。',
            self::REASON_PASSWORD_EXPIRED => '您的密碼已過期，請更新密碼。',
            self::REASON_MISSING_CREDENTIALS => '請提供完整的登入資訊。',
            self::REASON_INVALID_TOKEN => '認證 Token 無效，請重新登入。',
            self::REASON_INVALID_REFRESH_TOKEN => '重新整理 Token 無效，請重新登入。',
            self::REASON_TOKEN_REFRESH_FAILED => 'Token 重新整理失敗，請重新登入。',
            self::REASON_TOKEN_REQUIRED => '需要提供認證 Token 才能存取此資源。',
            self::REASON_INSUFFICIENT_PRIVILEGES => '您沒有足夠的權限存取此資源。',
            default => '身份驗證失敗，請重新嘗試。',
        };
    }

    /**
     * 取得失敗原因.
     */
    public function getReason(): string
    {
        return $this->context['reason'] ?? self::REASON_INVALID_CREDENTIALS;
    }

    /**
     * 取得嘗試 ID.
     */
    public function getAttemptId(): ?string
    {
        return $this->context['attempt_id'] ?? null;
    }

    /**
     * 取得用戶 ID（如果有）.
     */
    public function getUserId(): ?int
    {
        return $this->context['user_id'] ?? null;
    }

    /**
     * 取得用戶名（如果有）.
     */
    public function getUsername(): ?string
    {
        return $this->context['username'] ?? null;
    }

    /**
     * 取得 IP 位址（如果有）.
     */
    public function getIpAddress(): ?string
    {
        return $this->context['ip_address'] ?? null;
    }

    /**
     * 取得嘗試次數（如果有）.
     */
    public function getAttemptCount(): ?int
    {
        return $this->context['attempt_count'] ?? null;
    }

    /**
     * 取得鎖定時間（如果有）.
     */
    public function getLockoutUntil(): ?int
    {
        return $this->context['lockout_until'] ?? null;
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
     * 檢查是否為帳戶相關錯誤.
     */
    public function isAccountRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_ACCOUNT_LOCKED,
            self::REASON_ACCOUNT_DISABLED,
            self::REASON_ACCOUNT_NOT_VERIFIED,
            self::REASON_PASSWORD_EXPIRED,
        ]);
    }

    /**
     * 檢查是否為憑證相關錯誤.
     */
    public function isCredentialsRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_INVALID_CREDENTIALS,
            self::REASON_MISSING_CREDENTIALS,
            self::REASON_USER_NOT_FOUND,
        ]);
    }

    /**
     * 檢查是否為 Token 相關錯誤.
     */
    public function isTokenRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_INVALID_TOKEN,
            self::REASON_INVALID_REFRESH_TOKEN,
            self::REASON_TOKEN_REFRESH_FAILED,
            self::REASON_TOKEN_REQUIRED,
        ]);
    }

    /**
     * 檢查是否為安全相關錯誤.
     */
    public function isSecurityRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_ACCOUNT_LOCKED,
            self::REASON_TOO_MANY_ATTEMPTS,
        ]);
    }

    /**
     * 檢查是否可以重試.
     */
    public function isRetryable(): bool
    {
        return !in_array($this->getReason(), [
            self::REASON_ACCOUNT_LOCKED,
            self::REASON_ACCOUNT_DISABLED,
            self::REASON_TOO_MANY_ATTEMPTS,
        ]);
    }

    /**
     * 檢查是否需要帳戶操作.
     */
    public function requiresAccountAction(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_ACCOUNT_NOT_VERIFIED,
            self::REASON_PASSWORD_EXPIRED,
        ]);
    }

    /**
     * 靜態工廠方法：無效憑證.
     *
     * @param string $username 用戶名
     * @param string $ipAddress IP 位址
     */
    public static function invalidCredentials(string $username = '', string $ipAddress = ''): self
    {
        $context = [];
        if ($username) {
            $context['username'] = $username;
        }
        if ($ipAddress) {
            $context['ip_address'] = $ipAddress;
        }

        return new self(self::REASON_INVALID_CREDENTIALS, '', $context);
    }

    /**
     * 靜態工廠方法：帳戶已鎖定.
     *
     * @param int $userId 用戶 ID
     * @param int $lockoutUntil 鎖定至時間戳
     * @param string $reason 鎖定原因
     */
    public static function accountLocked(int $userId, int $lockoutUntil, string $reason = ''): self
    {
        return new self(self::REASON_ACCOUNT_LOCKED, '', [
            'user_id' => $userId,
            'lockout_until' => $lockoutUntil,
            'lockout_until_human' => date('Y-m-d H:i:s', $lockoutUntil),
            'lock_reason' => $reason,
        ]);
    }

    /**
     * 靜態工廠方法：帳戶已停用.
     *
     * @param int $userId 用戶 ID
     * @param string $reason 停用原因
     */
    public static function accountDisabled(int $userId, string $reason = ''): self
    {
        return new self(self::REASON_ACCOUNT_DISABLED, '', [
            'user_id' => $userId,
            'disable_reason' => $reason,
        ]);
    }

    /**
     * 靜態工廠方法：帳戶未驗證.
     *
     * @param int $userId 用戶 ID
     * @param string $email 電子郵件
     */
    public static function accountNotVerified(int $userId, string $email = ''): self
    {
        $context = ['user_id' => $userId];
        if ($email) {
            $context['email'] = $email;
        }

        return new self(self::REASON_ACCOUNT_NOT_VERIFIED, '', $context);
    }

    /**
     * 靜態工廠方法：嘗試次數過多.
     *
     * @param string $username 用戶名
     * @param int $attemptCount 嘗試次數
     * @param int $lockoutUntil 鎖定至時間戳
     * @param string $ipAddress IP 位址
     */
    public static function tooManyAttempts(
        string $username,
        int $attemptCount,
        int $lockoutUntil,
        string $ipAddress = '',
    ): self {
        $context = [
            'username' => $username,
            'attempt_count' => $attemptCount,
            'lockout_until' => $lockoutUntil,
            'lockout_until_human' => date('Y-m-d H:i:s', $lockoutUntil),
        ];

        if ($ipAddress) {
            $context['ip_address'] = $ipAddress;
        }

        return new self(self::REASON_TOO_MANY_ATTEMPTS, '', $context);
    }

    /**
     * 靜態工廠方法：用戶不存在.
     *
     * @param string $username 用戶名
     */
    public static function userNotFound(string $username): self
    {
        return new self(self::REASON_USER_NOT_FOUND, '', [
            'username' => $username,
        ]);
    }

    /**
     * 靜態工廠方法：密碼已過期
     *
     * @param int $userId 用戶 ID
     * @param int $expiredAt 過期時間戳
     */
    public static function passwordExpired(int $userId, int $expiredAt): self
    {
        return new self(self::REASON_PASSWORD_EXPIRED, '', [
            'user_id' => $userId,
            'expired_at' => $expiredAt,
            'expired_at_human' => date('Y-m-d H:i:s', $expiredAt),
        ]);
    }

    /**
     * 靜態工廠方法：憑證遺失.
     *
     * @param array $missingFields 遺失的欄位
     */
    public static function missingCredentials(array $missingFields = []): self
    {
        return new self(self::REASON_MISSING_CREDENTIALS, '', [
            'missing_fields' => $missingFields,
        ]);
    }

    /**
     * 靜態工廠方法：無效 Token.
     *
     * @param string $tokenType Token 類型
     * @param string $reason 無效原因
     */
    public static function invalidToken(string $tokenType = 'access_token', string $reason = ''): self
    {
        return new self(self::REASON_INVALID_TOKEN, '', [
            'token_type' => $tokenType,
            'invalid_reason' => $reason,
        ]);
    }

    /**
     * 靜態工廠方法：需要 Token.
     *
     * @param string $resource 需要存取的資源
     */
    public static function tokenRequired(string $resource = ''): self
    {
        $context = $resource ? ['required_for_resource' => $resource] : [];

        return new self(self::REASON_TOKEN_REQUIRED, '', $context);
    }

    /**
     * 靜態工廠方法：權限不足.
     *
     * @param string $requiredPrivilege 需要的權限
     * @param array $userPrivileges 用戶擁有的權限
     * @param int|null $userId 用戶 ID
     */
    public static function insufficientPrivileges(
        string $requiredPrivilege,
        array $userPrivileges = [],
        ?int $userId = null,
    ): self {
        $context = [
            'required_privilege' => $requiredPrivilege,
            'user_privileges' => $userPrivileges,
        ];

        if ($userId !== null) {
            $context['user_id'] = $userId;
        }

        return new self(self::REASON_INSUFFICIENT_PRIVILEGES, '', $context);
    }
}
