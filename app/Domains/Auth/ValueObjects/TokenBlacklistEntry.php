<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;

/**
 * Token Blacklist Entry Value Object.
 *
 * 表示 Token 黑名單項目，用於追蹤被撤銷或無效的 JWT Token。
 * 此類別是不可變的，確保黑名單項目的完整性和一致性。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
final readonly class TokenBlacklistEntry implements JsonSerializable
{
    /**
     * Token 類型常數.
     */
    public const TOKEN_TYPE_ACCESS = 'access';

    public const TOKEN_TYPE_REFRESH = 'refresh';

    /**
     * 黑名單原因常數.
     */
    public const REASON_LOGOUT = 'user_logout';

    public const REASON_REVOKED = 'token_revoked';

    public const REASON_SECURITY_BREACH = 'security_breach';

    public const REASON_PASSWORD_CHANGED = 'password_changed';

    public const REASON_ACCOUNT_SUSPENDED = 'account_suspended';

    public const REASON_MANUAL_REVOCATION = 'manual_revocation';

    public const REASON_EXPIRED = 'token_expired';

    public const REASON_INVALID_SIGNATURE = 'invalid_signature';

    public const REASON_DEVICE_LOST = 'device_lost';

    public const REASON_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    /**
     * 建構黑名單項目.
     *
     * @param string $jti JWT 唯一識別符
     * @param string $tokenType Token 類型 (access 或 refresh)
     * @param DateTimeImmutable $expiresAt Token 原始過期時間
     * @param DateTimeImmutable $blacklistedAt 加入黑名單的時間
     * @param string $reason 加入黑名單的原因
     * @param int|null $userId 相關使用者 ID
     * @param string|null $deviceId 相關裝置 ID
     * @param array<string, mixed> $metadata 額外的元資料
     *
     * @throws InvalidArgumentException 當參數無效時
     */
    public function __construct(
        private string $jti,
        private string $tokenType,
        private DateTimeImmutable $expiresAt,
        private DateTimeImmutable $blacklistedAt,
        private string $reason,
        private ?int $userId = null,
        private ?string $deviceId = null,
        private array $metadata = [],
    ) {
        $this->validateJti($jti);
        $this->validateTokenType($tokenType);
        $this->validateReason($reason);
        $this->validateTimes($expiresAt, $blacklistedAt);
        $this->validateUserId($userId);
        $this->validateDeviceId($deviceId);
        $this->validateMetadata($metadata);
    }

    /**
     * 從陣列建立黑名單項目.
     *
     * @param array<string, mixed> $data 黑名單資料
     * @throws InvalidArgumentException 當資料格式無效時
     */
    public static function fromArray(array $data): self
    {
        $requiredFields = ['jti', 'token_type', 'expires_at', 'blacklisted_at', 'reason'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $expiresAt = $data['expires_at'] instanceof DateTimeImmutable
            ? $data['expires_at']
            : new DateTimeImmutable($data['expires_at']);

        $blacklistedAt = $data['blacklisted_at'] instanceof DateTimeImmutable
            ? $data['blacklisted_at']
            : new DateTimeImmutable($data['blacklisted_at']);

        return new self(
            jti: $data['jti'],
            tokenType: $data['token_type'],
            expiresAt: $expiresAt,
            blacklistedAt: $blacklistedAt,
            reason: $data['reason'],
            userId: $data['user_id'] ?? null,
            deviceId: $data['device_id'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * 建立使用者登出的黑名單項目.
     *
     * @param string $jti JWT ID
     * @param string $tokenType Token 類型
     * @param DateTimeImmutable $expiresAt 過期時間
     * @param int $userId 使用者 ID
     * @param string|null $deviceId 裝置 ID
     */
    public static function forUserLogout(
        string $jti,
        string $tokenType,
        DateTimeImmutable $expiresAt,
        int $userId,
        ?string $deviceId = null,
    ): self {
        return new self(
            jti: $jti,
            tokenType: $tokenType,
            expiresAt: $expiresAt,
            blacklistedAt: new DateTimeImmutable(),
            reason: self::REASON_LOGOUT,
            userId: $userId,
            deviceId: $deviceId,
        );
    }

    /**
     * 建立安全性問題的黑名單項目.
     *
     * @param string $jti JWT ID
     * @param string $tokenType Token 類型
     * @param DateTimeImmutable $expiresAt 過期時間
     * @param string $securityReason 安全原因
     * @param int|null $userId 使用者 ID
     * @param array<string, mixed> $metadata 額外資訊
     */
    public static function forSecurityBreach(
        string $jti,
        string $tokenType,
        DateTimeImmutable $expiresAt,
        string $securityReason,
        ?int $userId = null,
        array $metadata = [],
    ): self {
        $validSecurityReasons = [
            self::REASON_SECURITY_BREACH,
            self::REASON_SUSPICIOUS_ACTIVITY,
            self::REASON_DEVICE_LOST,
            self::REASON_INVALID_SIGNATURE,
        ];

        if (!in_array($securityReason, $validSecurityReasons, true)) {
            $securityReason = self::REASON_SECURITY_BREACH;
        }

        return new self(
            jti: $jti,
            tokenType: $tokenType,
            expiresAt: $expiresAt,
            blacklistedAt: new DateTimeImmutable(),
            reason: $securityReason,
            userId: $userId,
            metadata: $metadata,
        );
    }

    /**
     * 建立帳戶變更的黑名單項目.
     *
     * @param string $jti JWT ID
     * @param string $tokenType Token 類型
     * @param DateTimeImmutable $expiresAt 過期時間
     * @param int $userId 使用者 ID
     * @param string $changeType 變更類型 (password_changed, account_suspended)
     */
    public static function forAccountChange(
        string $jti,
        string $tokenType,
        DateTimeImmutable $expiresAt,
        int $userId,
        string $changeType,
    ): self {
        $validChangeTypes = [self::REASON_PASSWORD_CHANGED, self::REASON_ACCOUNT_SUSPENDED];

        if (!in_array($changeType, $validChangeTypes, true)) {
            throw new InvalidArgumentException(
                'Change type must be one of: ' . implode(', ', $validChangeTypes),
            );
        }

        return new self(
            jti: $jti,
            tokenType: $tokenType,
            expiresAt: $expiresAt,
            blacklistedAt: new DateTimeImmutable(),
            reason: $changeType,
            userId: $userId,
        );
    }

    /**
     * 取得 JWT ID.
     */
    public function getJti(): string
    {
        return $this->jti;
    }

    /**
     * 取得 Token 類型.
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * 取得原始過期時間.
     */
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * 取得加入黑名單的時間.
     */
    public function getBlacklistedAt(): DateTimeImmutable
    {
        return $this->blacklistedAt;
    }

    /**
     * 取得黑名單原因.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * 取得使用者 ID.
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * 取得裝置 ID.
     */
    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    /**
     * 取得元資料.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * 取得特定元資料.
     *
     * @param string $key 元資料鍵
     * @return mixed|null
     */
    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    /**
     * 檢查是否為 Access Token.
     */
    public function isAccessToken(): bool
    {
        return $this->tokenType === self::TOKEN_TYPE_ACCESS;
    }

    /**
     * 檢查是否為 Refresh Token.
     */
    public function isRefreshToken(): bool
    {
        return $this->tokenType === self::TOKEN_TYPE_REFRESH;
    }

    /**
     * 檢查是否已過原始過期時間（可以清理）.
     *
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     */
    public function canBeCleanedUp(?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();

        return $this->expiresAt <= $now;
    }

    /**
     * 檢查是否因安全問題而被列入黑名單.
     */
    public function isSecurityRelated(): bool
    {
        $securityReasons = [
            self::REASON_SECURITY_BREACH,
            self::REASON_SUSPICIOUS_ACTIVITY,
            self::REASON_DEVICE_LOST,
            self::REASON_INVALID_SIGNATURE,
        ];

        return in_array($this->reason, $securityReasons, true);
    }

    /**
     * 檢查是否因使用者行為而被列入黑名單.
     */
    public function isUserInitiated(): bool
    {
        $userReasons = [
            self::REASON_LOGOUT,
            self::REASON_MANUAL_REVOCATION,
            self::REASON_DEVICE_LOST,
        ];

        return in_array($this->reason, $userReasons, true);
    }

    /**
     * 檢查是否因系統原因而被列入黑名單.
     */
    public function isSystemInitiated(): bool
    {
        $systemReasons = [
            self::REASON_EXPIRED,
            self::REASON_ACCOUNT_SUSPENDED,
            self::REASON_SECURITY_BREACH,
            self::REASON_PASSWORD_CHANGED,
        ];

        return in_array($this->reason, $systemReasons, true);
    }

    /**
     * 取得黑名單原因的描述.
     */
    public function getReasonDescription(): string
    {
        $descriptions = [
            self::REASON_LOGOUT => 'User logged out',
            self::REASON_REVOKED => 'Token manually revoked',
            self::REASON_SECURITY_BREACH => 'Security breach detected',
            self::REASON_PASSWORD_CHANGED => 'Password changed',
            self::REASON_ACCOUNT_SUSPENDED => 'Account suspended',
            self::REASON_MANUAL_REVOCATION => 'Manual revocation',
            self::REASON_EXPIRED => 'Token expired',
            self::REASON_INVALID_SIGNATURE => 'Invalid signature',
            self::REASON_DEVICE_LOST => 'Device reported lost',
            self::REASON_SUSPICIOUS_ACTIVITY => 'Suspicious activity detected',
        ];

        return $descriptions[$this->reason] ?? 'Unknown reason';
    }

    /**
     * 取得優先級（用於清理順序）.
     *
     * @return int 數字越小優先級越高
     */
    public function getPriority(): int
    {
        if ($this->canBeCleanedUp()) {
            return 1; // 已過期的最優先清理
        }

        if ($this->isSecurityRelated()) {
            return 2; // 安全相關的次優先
        }

        if ($this->isUserInitiated()) {
            return 3; // 使用者主動的再次之
        }

        return 4; // 其他系統原因的最後清理
    }

    /**
     * 檢查黑名單項目是否仍然有效.
     *
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     */
    public function isActive(?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();

        // 如果原始 token 已過期，黑名單項目就沒有作用了
        return $this->expiresAt > $now;
    }

    /**
     * 轉換為陣列格式.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'jti' => $this->jti,
            'token_type' => $this->tokenType,
            'expires_at' => $this->expiresAt->format(DateTimeImmutable::ATOM),
            'blacklisted_at' => $this->blacklistedAt->format(DateTimeImmutable::ATOM),
            'reason' => $this->reason,
            'reason_description' => $this->getReasonDescription(),
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'metadata' => $this->metadata,
            'is_security_related' => $this->isSecurityRelated(),
            'is_user_initiated' => $this->isUserInitiated(),
            'is_active' => $this->isActive(),
            'priority' => $this->getPriority(),
        ];
    }

    /**
     * 轉換為資料庫儲存格式.
     *
     * @return array<string, mixed>
     */
    public function toDatabaseArray(): array
    {
        return [
            'jti' => $this->jti,
            'token_type' => $this->tokenType,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'blacklisted_at' => $this->blacklistedAt->format('Y-m-d H:i:s'),
            'reason' => $this->reason,
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'metadata' => !empty($this->metadata) ? json_encode($this->metadata) : null,
        ];
    }

    /**
     * JsonSerializable 實作.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查與另一個 TokenBlacklistEntry 是否相等.
     *
     * @param TokenBlacklistEntry $other 另一個 TokenBlacklistEntry
     */
    public function equals(TokenBlacklistEntry $other): bool
    {
        return $this->jti === $other->jti
            && $this->tokenType === $other->tokenType
            && $this->expiresAt->getTimestamp() === $other->expiresAt->getTimestamp()
            && $this->blacklistedAt->getTimestamp() === $other->blacklistedAt->getTimestamp()
            && $this->reason === $other->reason
            && $this->userId === $other->userId
            && $this->deviceId === $other->deviceId
            && $this->metadata === $other->metadata;
    }

    /**
     * 轉換為字串表示.
     */
    public function toString(): string
    {
        $userInfo = $this->userId !== null ? "user:{$this->userId}" : 'no-user';
        $deviceInfo = $this->deviceId !== null ? "device:{$this->deviceId}" : 'no-device';

        return sprintf(
            'TokenBlacklistEntry(jti=%s, type=%s, reason=%s, %s, %s, blacklisted=%s)',
            $this->jti,
            $this->tokenType,
            $this->reason,
            $userInfo,
            $deviceInfo,
            $this->blacklistedAt->format('Y-m-d H:i:s'),
        );
    }

    /**
     * __toString 魔術方法.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * 取得所有有效的 Token 類型.
     *
     * @return array<string>
     */
    public static function getValidTokenTypes(): array
    {
        return [self::TOKEN_TYPE_ACCESS, self::TOKEN_TYPE_REFRESH];
    }

    /**
     * 取得所有有效的黑名單原因.
     *
     * @return array<string>
     */
    public static function getValidReasons(): array
    {
        return [
            self::REASON_LOGOUT,
            self::REASON_REVOKED,
            self::REASON_SECURITY_BREACH,
            self::REASON_PASSWORD_CHANGED,
            self::REASON_ACCOUNT_SUSPENDED,
            self::REASON_MANUAL_REVOCATION,
            self::REASON_EXPIRED,
            self::REASON_INVALID_SIGNATURE,
            self::REASON_DEVICE_LOST,
            self::REASON_SUSPICIOUS_ACTIVITY,
        ];
    }

    /**
     * 驗證 JWT ID.
     *
     * @param string $jti JWT ID
     * @throws InvalidArgumentException 當 JTI 無效時
     */
    private function validateJti(string $jti): void
    {
        if (empty($jti)) {
            throw new InvalidArgumentException('JWT ID (jti) cannot be empty');
        }

        if (mb_strlen($jti) > 255) {
            throw new InvalidArgumentException('JWT ID (jti) cannot exceed 255 characters');
        }
    }

    /**
     * 驗證 Token 類型.
     *
     * @param string $tokenType Token 類型
     * @throws InvalidArgumentException 當 Token 類型無效時
     */
    private function validateTokenType(string $tokenType): void
    {
        if (!in_array($tokenType, self::getValidTokenTypes(), true)) {
            throw new InvalidArgumentException(
                'Token type must be one of: ' . implode(', ', self::getValidTokenTypes()),
            );
        }
    }

    /**
     * 驗證黑名單原因.
     *
     * @param string $reason 黑名單原因
     * @throws InvalidArgumentException 當原因無效時
     */
    private function validateReason(string $reason): void
    {
        if (!in_array($reason, self::getValidReasons(), true)) {
            throw new InvalidArgumentException(
                'Reason must be one of: ' . implode(', ', self::getValidReasons()),
            );
        }
    }

    /**
     * 驗證時間設定.
     *
     * @param DateTimeImmutable $expiresAt 過期時間
     * @param DateTimeImmutable $blacklistedAt 黑名單時間
     * @throws InvalidArgumentException 當時間設定無效時
     */
    private function validateTimes(DateTimeImmutable $expiresAt, DateTimeImmutable $blacklistedAt): void
    {
        // 允許黑名單時間晚於過期時間，因為可能是事後處理
        // 但黑名單時間不能太早（比如一年前）
        $oneYearAgo = new DateTimeImmutable('-1 year');
        if ($blacklistedAt < $oneYearAgo) {
            throw new InvalidArgumentException('Blacklisted time cannot be more than 1 year ago');
        }

        // 黑名單時間不能是未來太遠的時間
        $oneYearLater = new DateTimeImmutable('+1 year');
        if ($blacklistedAt > $oneYearLater) {
            throw new InvalidArgumentException('Blacklisted time cannot be more than 1 year in the future');
        }
    }

    /**
     * 驗證使用者 ID.
     *
     * @param int|null $userId 使用者 ID
     * @throws InvalidArgumentException 當使用者 ID 無效時
     */
    private function validateUserId(?int $userId): void
    {
        if ($userId !== null && $userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
    }

    /**
     * 驗證裝置 ID.
     *
     * @param string|null $deviceId 裝置 ID
     * @throws InvalidArgumentException 當裝置 ID 無效時
     */
    private function validateDeviceId(?string $deviceId): void
    {
        if ($deviceId !== null) {
            if (empty($deviceId)) {
                throw new InvalidArgumentException('Device ID cannot be empty when provided');
            }

            if (mb_strlen($deviceId) > 255) {
                throw new InvalidArgumentException('Device ID cannot exceed 255 characters');
            }
        }
    }

    /**
     * 驗證元資料.
     *
     * @param array<string, mixed> $metadata 元資料
     * @throws InvalidArgumentException 當元資料無效時
     */
    private function validateMetadata(array $metadata): void
    {
        // 檢查 JSON 序列化是否可能
        try {
            json_encode($metadata, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Metadata must be JSON serializable: ' . $e->getMessage());
        }

        // 限制元資料大小
        $serializedSize = strlen(json_encode($metadata));
        if ($serializedSize > 65535) { // 64KB limit
            throw new InvalidArgumentException('Metadata size cannot exceed 64KB');
        }
    }
}
