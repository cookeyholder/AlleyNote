<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Exceptions;

/**
 * Refresh Token 操作例外.
 *
 * 當 Refresh Token 相關操作（重新整理、撤銷、儲存等）發生錯誤時拋出此例外。
 * 包含操作失敗的具體原因和相關詳細資訊。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class RefreshTokenException extends JwtException
{
    /**
     * 錯誤類型標識.
     */
    protected string $errorType = 'refresh_token_error';

    /**
     * 錯誤碼常數.
     */
    public const ERROR_CODE = 4003;

    /**
     * 操作失敗原因常數.
     */
    public const REASON_NOT_FOUND = 'not_found';

    public const REASON_REVOKED = 'revoked';

    public const REASON_ALREADY_USED = 'already_used';

    public const REASON_DEVICE_MISMATCH = 'device_mismatch';

    public const REASON_USER_MISMATCH = 'user_mismatch';

    public const REASON_STORAGE_FAILED = 'storage_failed';

    public const REASON_DELETION_FAILED = 'deletion_failed';

    public const REASON_ROTATION_FAILED = 'rotation_failed';

    public const REASON_LIMIT_EXCEEDED = 'limit_exceeded';

    public const REASON_FAMILY_MISMATCH = 'family_mismatch';

    public const REASON_CREATION_FAILED = 'creation_failed';

    public const REASON_DATABASE_ERROR = 'database_error';

    public const REASON_UPDATE_FAILED = 'update_failed';

    public const REASON_REVOCATION_FAILED = 'revocation_failed';

    public const REASON_CLEANUP_FAILED = 'cleanup_failed';

    public const REASON_FAMILY_REVOCATION_FAILED = 'family_revocation_failed';

    public const REASON_BATCH_OPERATION_FAILED = 'batch_operation_failed';

    /**
     * 建立 Refresh Token 操作例外.
     *
     * @param string $reason 失敗原因
     * @param string $customMessage 自定義錯誤訊息
     * @param array<string, mixed> $additionalContext 額外上下文資訊
     */
    public function __construct(
        string $reason = self::REASON_NOT_FOUND,
        string $customMessage = '',
        array $additionalContext = [],
    ) {
        $message = $customMessage ?: $this->buildDefaultMessage($reason);

        $context = array_merge([
            'reason' => $reason,
            'timestamp' => time(),
            'operation_id' => uniqid('refresh_', true),
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
            self::REASON_NOT_FOUND => 'Refresh token not found or does not exist',
            self::REASON_REVOKED => 'Refresh token has been revoked',
            self::REASON_ALREADY_USED => 'Refresh token has already been used',
            self::REASON_DEVICE_MISMATCH => 'Refresh token device fingerprint does not match',
            self::REASON_USER_MISMATCH => 'Refresh token does not belong to the specified user',
            self::REASON_STORAGE_FAILED => 'Failed to store refresh token in database',
            self::REASON_DELETION_FAILED => 'Failed to delete refresh token from database',
            self::REASON_ROTATION_FAILED => 'Failed to rotate refresh token',
            self::REASON_LIMIT_EXCEEDED => 'Refresh token limit exceeded for this user',
            self::REASON_FAMILY_MISMATCH => 'Refresh token does not belong to the expected token family',
            self::REASON_CREATION_FAILED => 'Failed to create refresh token',
            self::REASON_DATABASE_ERROR => 'Database operation failed',
            self::REASON_UPDATE_FAILED => 'Failed to update refresh token',
            self::REASON_REVOCATION_FAILED => 'Failed to revoke refresh token',
            self::REASON_CLEANUP_FAILED => 'Failed to cleanup refresh tokens',
            self::REASON_FAMILY_REVOCATION_FAILED => 'Failed to revoke token family',
            self::REASON_BATCH_OPERATION_FAILED => 'Batch operation failed',
            default => 'Refresh token operation failed',
        };
    }

    /**
     * 取得用戶友好的錯誤訊息.
     */
    public function getUserFriendlyMessage(): string
    {
        $reason = $this->getReason();

        return match ($reason) {
            self::REASON_NOT_FOUND => '找不到有效的 Refresh Token，請重新登入。',
            self::REASON_REVOKED => '您的登入憑證已被撤銷，請重新登入。',
            self::REASON_ALREADY_USED => '此 Refresh Token 已經使用過，請重新登入。',
            self::REASON_DEVICE_MISMATCH => '裝置驗證失敗，可能有安全風險。請重新登入。',
            self::REASON_USER_MISMATCH => '此 Token 不屬於當前用戶，請重新登入。',
            self::REASON_STORAGE_FAILED,
            self::REASON_DELETION_FAILED => '系統暫時無法處理您的請求，請稍後重試。',
            self::REASON_ROTATION_FAILED => 'Token 更新失敗，請重新登入。',
            self::REASON_LIMIT_EXCEEDED => '您的登入裝置數量已達上限，請登出其他裝置後重試。',
            self::REASON_FAMILY_MISMATCH => 'Token 系列驗證失敗，請重新登入。',
            default => 'Token 操作失敗，請重新登入。',
        };
    }

    /**
     * 取得失敗原因.
     */
    public function getReason(): string
    {
        return $this->context['reason'] ?? self::REASON_NOT_FOUND;
    }

    /**
     * 取得操作 ID.
     */
    public function getOperationId(): ?string
    {
        return $this->context['operation_id'] ?? null;
    }

    /**
     * 取得用戶 ID（如果有）.
     */
    public function getUserId(): ?int
    {
        return $this->context['user_id'] ?? null;
    }

    /**
     * 取得 Token ID（如果有）.
     */
    public function getTokenId(): ?string
    {
        return $this->context['token_id'] ?? null;
    }

    /**
     * 取得裝置資訊（如果有）.
     *
     * @return array<string, mixed>|null
     */
    public function getDeviceInfo(): ?array
    {
        return $this->context['device_info'] ?? null;
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
     * 檢查是否為安全相關錯誤.
     */
    public function isSecurityRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_REVOKED,
            self::REASON_ALREADY_USED,
            self::REASON_DEVICE_MISMATCH,
            self::REASON_USER_MISMATCH,
            self::REASON_FAMILY_MISMATCH,
        ]);
    }

    /**
     * 檢查是否為資料庫操作錯誤.
     */
    public function isDatabaseRelated(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_STORAGE_FAILED,
            self::REASON_DELETION_FAILED,
        ]);
    }

    /**
     * 檢查是否為暫時性錯誤（可重試）.
     */
    public function isRetryable(): bool
    {
        return in_array($this->getReason(), [
            self::REASON_STORAGE_FAILED,
            self::REASON_DELETION_FAILED,
            self::REASON_ROTATION_FAILED,
        ]);
    }

    /**
     * 檢查是否需要重新登入.
     */
    public function requiresReauth(): bool
    {
        return !$this->isDatabaseRelated();
    }

    /**
     * 靜態工廠方法：Token 不存在.
     *
     * @param string $tokenId Token ID
     * @param int|null $userId 用戶 ID
     */
    public static function notFound(string $tokenId, ?int $userId = null): self
    {
        $context = ['token_id' => $tokenId];
        if ($userId !== null) {
            $context['user_id'] = $userId;
        }

        return new self(self::REASON_NOT_FOUND, '', $context);
    }

    /**
     * 靜態工廠方法：Token 已被撤銷.
     *
     * @param string $tokenId Token ID
     * @param int $revokedAt 撤銷時間戳
     * @param string $revokedReason 撤銷原因
     */
    public static function revoked(string $tokenId, int $revokedAt, string $revokedReason = ''): self
    {
        return new self(self::REASON_REVOKED, '', [
            'token_id' => $tokenId,
            'revoked_at' => $revokedAt,
            'revoked_at_human' => date('Y-m-d H:i:s', $revokedAt),
            'revoked_reason' => $revokedReason,
        ]);
    }

    /**
     * 靜態工廠方法：Token 已使用.
     *
     * @param string $tokenId Token ID
     * @param int $usedAt 使用時間戳
     */
    public static function alreadyUsed(string $tokenId, int $usedAt): self
    {
        return new self(self::REASON_ALREADY_USED, '', [
            'token_id' => $tokenId,
            'used_at' => $usedAt,
            'used_at_human' => date('Y-m-d H:i:s', $usedAt),
        ]);
    }

    /**
     * 靜態工廠方法：裝置不匹配.
     *
     * @param string $expectedFingerprint 期望的裝置指紋
     * @param string $actualFingerprint 實際的裝置指紋
     * @param string $tokenId Token ID
     */
    public static function deviceMismatch(
        string $expectedFingerprint,
        string $actualFingerprint,
        string $tokenId,
    ): self {
        return new self(self::REASON_DEVICE_MISMATCH, '', [
            'token_id' => $tokenId,
            'expected_fingerprint' => $expectedFingerprint,
            'actual_fingerprint' => $actualFingerprint,
        ]);
    }

    /**
     * 靜態工廠方法：用戶不匹配.
     *
     * @param int $expectedUserId 期望的用戶 ID
     * @param int $actualUserId 實際的用戶 ID
     * @param string $tokenId Token ID
     */
    public static function userMismatch(int $expectedUserId, int $actualUserId, string $tokenId): self
    {
        return new self(self::REASON_USER_MISMATCH, '', [
            'token_id' => $tokenId,
            'expected_user_id' => $expectedUserId,
            'actual_user_id' => $actualUserId,
        ]);
    }

    /**
     * 靜態工廠方法：儲存失敗.
     *
     * @param string $error 錯誤詳情
     * @param array<string, mixed> $tokenData Token 資料
     */
    public static function storageFailed(string $error, array $tokenData = []): self
    {
        return new self(self::REASON_STORAGE_FAILED, '', [
            'storage_error' => $error,
            'token_data' => $tokenData,
        ]);
    }

    /**
     * 靜態工廠方法：刪除失敗.
     *
     * @param string $tokenId Token ID
     * @param string $error 錯誤詳情
     */
    public static function deletionFailed(string $tokenId, string $error): self
    {
        return new self(self::REASON_DELETION_FAILED, '', [
            'token_id' => $tokenId,
            'deletion_error' => $error,
        ]);
    }

    /**
     * 靜態工廠方法：輪換失敗.
     *
     * @param string $oldTokenId 舊 Token ID
     * @param string $error 錯誤詳情
     */
    public static function rotationFailed(string $oldTokenId, string $error): self
    {
        return new self(self::REASON_ROTATION_FAILED, '', [
            'old_token_id' => $oldTokenId,
            'rotation_error' => $error,
        ]);
    }

    /**
     * 靜態工廠方法：數量限制超出.
     *
     * @param int $userId 用戶 ID
     * @param int $currentCount 目前數量
     * @param int $maxLimit 最大限制
     */
    public static function limitExceeded(int $userId, int $currentCount, int $maxLimit): self
    {
        return new self(self::REASON_LIMIT_EXCEEDED, '', [
            'user_id' => $userId,
            'current_count' => $currentCount,
            'max_limit' => $maxLimit,
        ]);
    }

    /**
     * 靜態工廠方法：Token 系列不匹配.
     *
     * @param string $expectedFamily 期望的 Token 系列
     * @param string $actualFamily 實際的 Token 系列
     * @param string $tokenId Token ID
     */
    public static function familyMismatch(string $expectedFamily, string $actualFamily, string $tokenId): self
    {
        return new self(self::REASON_FAMILY_MISMATCH, '', [
            'token_id' => $tokenId,
            'expected_family' => $expectedFamily,
            'actual_family' => $actualFamily,
        ]);
    }
}
