<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Entities;

use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use DateTime;
use InvalidArgumentException;
use JsonSerializable;

/**
 * RefreshToken Entity.
 *
 * 管理 JWT refresh token 的業務邏輯實體，負責處理 token 的生命週期、
 * 驗證規則、撤銷狀態等核心業務邏輯。
 */
class RefreshToken implements JsonSerializable
{
    /**
     * RefreshToken 狀態常數.
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_USED = 'used';

    /**
     * 撤銷原因常數.
     */
    public const REVOKE_REASON_MANUAL = 'manual_revocation';

    public const REVOKE_REASON_LOGOUT = 'user_logout';

    public const REVOKE_REASON_LOGOUT_ALL = 'logout_all_sessions';

    public const REVOKE_REASON_SECURITY = 'security_breach';

    public const REVOKE_REASON_TOKEN_ROTATION = 'token_rotation';

    public const REVOKE_REASON_EXPIRED = 'expired';

    /**
     * RefreshToken Entity 建構子.
     *
     * @param int|null $id 資料庫 ID（新建時為 null）
     * @param string $jti JWT ID（唯一識別碼）
     * @param int $userId 使用者 ID
     * @param string $tokenHash token 的 SHA256 雜湊值
     * @param DateTime $expiresAt 過期時間
     * @param DeviceInfo $deviceInfo 裝置資訊
     * @param string $status token 狀態
     * @param string|null $revokedReason 撤銷原因
     * @param DateTime|null $revokedAt 撤銷時間
     * @param DateTime|null $lastUsedAt 最後使用時間
     * @param string|null $parentTokenJti 父 token JTI（用於 token 輪轉）
     * @param DateTime|null $createdAt 建立時間
     * @param DateTime|null $updatedAt 更新時間
     *
     * @throws InvalidArgumentException 當參數無效時
     */
    public function __construct(
        private readonly ?int $id,
        private readonly string $jti,
        private readonly int $userId,
        private readonly string $tokenHash,
        private readonly DateTime $expiresAt,
        private readonly DeviceInfo $deviceInfo,
        private string $status = self::STATUS_ACTIVE,
        private ?string $revokedReason = null,
        private ?DateTime $revokedAt = null,
        private ?DateTime $lastUsedAt = null,
        private readonly ?string $parentTokenJti = null,
        private readonly ?DateTime $createdAt = null,
        private ?DateTime $updatedAt = null,
    ) {
        $this->validateJti($jti);
        $this->validateUserId($userId);
        $this->validateTokenHash($tokenHash);
        $this->validateStatus($status);
        $this->validateExpirationTime($expiresAt);
        $this->validateRevokedData($status, $revokedReason, $revokedAt);
    }

    /**
     * 取得資料庫 ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * 取得 JWT ID.
     */
    public function getJti(): string
    {
        return $this->jti;
    }

    /**
     * 取得使用者 ID.
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * 取得 token hash.
     */
    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    /**
     * 取得過期時間.
     */
    public function getExpiresAt(): DateTime
    {
        return clone $this->expiresAt;
    }

    /**
     * 取得裝置資訊.
     */
    public function getDeviceInfo(): DeviceInfo
    {
        return $this->deviceInfo;
    }

    /**
     * 取得狀態.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * 取得撤銷原因.
     */
    public function getRevokedReason(): ?string
    {
        return $this->revokedReason;
    }

    /**
     * 取得撤銷時間.
     */
    public function getRevokedAt(): ?DateTime
    {
        return $this->revokedAt ? clone $this->revokedAt : null;
    }

    /**
     * 取得最後使用時間.
     */
    public function getLastUsedAt(): ?DateTime
    {
        return $this->lastUsedAt ? clone $this->lastUsedAt : null;
    }

    /**
     * 取得父 token JTI.
     */
    public function getParentTokenJti(): ?string
    {
        return $this->parentTokenJti;
    }

    /**
     * 取得建立時間.
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt ? clone $this->createdAt : null;
    }

    /**
     * 取得更新時間.
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt ? clone $this->updatedAt : null;
    }

    /**
     * 檢查 token 是否已過期.
     */
    public function isExpired(?DateTime $now = null): bool
    {
        $now ??= new DateTime();

        return $this->expiresAt <= $now;
    }

    /**
     * 檢查 token 是否已撤銷.
     */
    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    /**
     * 檢查 token 是否有效（未過期且未撤銷）.
     */
    public function isValid(?DateTime $now = null): bool
    {
        return !$this->isExpired($now) && !$this->isRevoked();
    }

    /**
     * 檢查 token 是否可以用於刷新.
     */
    public function canBeRefreshed(?DateTime $now = null): bool
    {
        return $this->isValid($now) && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 撤銷 token.
     *
     * @param string $reason 撤銷原因
     * @param DateTime|null $revokedAt 撤銷時間（null 時使用當前時間）
     *
     * @return self 新的 RefreshToken 實例
     */
    public function markAsRevoked(string $reason, ?DateTime $revokedAt = null): self
    {
        if ($this->isRevoked()) {
            return $this;
        }

        $revokedAt ??= new DateTime();

        return new self(
            id: $this->id,
            jti: $this->jti,
            userId: $this->userId,
            tokenHash: $this->tokenHash,
            expiresAt: $this->expiresAt,
            deviceInfo: $this->deviceInfo,
            status: self::STATUS_REVOKED,
            revokedReason: $reason,
            revokedAt: $revokedAt,
            lastUsedAt: $this->lastUsedAt,
            parentTokenJti: $this->parentTokenJti,
            createdAt: $this->createdAt,
            updatedAt: new DateTime(),
        );
    }

    /**
     * 標記 token 為已使用.
     *
     * @param DateTime|null $usedAt 使用時間（null 時使用當前時間）
     *
     * @return self 新的 RefreshToken 實例
     */
    public function markAsUsed(?DateTime $usedAt = null): self
    {
        $usedAt ??= new DateTime();

        return new self(
            id: $this->id,
            jti: $this->jti,
            userId: $this->userId,
            tokenHash: $this->tokenHash,
            expiresAt: $this->expiresAt,
            deviceInfo: $this->deviceInfo,
            status: self::STATUS_USED,
            revokedReason: $this->revokedReason,
            revokedAt: $this->revokedAt,
            lastUsedAt: $usedAt,
            parentTokenJti: $this->parentTokenJti,
            createdAt: $this->createdAt,
            updatedAt: new DateTime(),
        );
    }

    /**
     * 更新最後使用時間.
     *
     * @param DateTime|null $lastUsedAt 最後使用時間（null 時使用當前時間）
     *
     * @return self 新的 RefreshToken 實例
     */
    public function updateLastUsed(?DateTime $lastUsedAt = null): self
    {
        $lastUsedAt ??= new DateTime();

        return new self(
            id: $this->id,
            jti: $this->jti,
            userId: $this->userId,
            tokenHash: $this->tokenHash,
            expiresAt: $this->expiresAt,
            deviceInfo: $this->deviceInfo,
            status: $this->status,
            revokedReason: $this->revokedReason,
            revokedAt: $this->revokedAt,
            lastUsedAt: $lastUsedAt,
            parentTokenJti: $this->parentTokenJti,
            createdAt: $this->createdAt,
            updatedAt: new DateTime(),
        );
    }

    /**
     * 檢查是否為同一個 token.
     */
    public function equals(RefreshToken $other): bool
    {
        return $this->jti === $other->jti;
    }

    /**
     * 檢查是否為同一個使用者的 token.
     */
    public function belongsToUser(int $userId): bool
    {
        return $this->userId === $userId;
    }

    /**
     * 檢查是否為同一個裝置的 token.
     */
    public function belongsToDevice(string $deviceId): bool
    {
        return $this->deviceInfo->getDeviceId() === $deviceId;
    }

    /**
     * 取得 token 剩餘有效時間（秒數）.
     */
    public function getRemainingTime(?DateTime $now = null): int
    {
        $now ??= new DateTime();

        if ($this->isExpired($now)) {
            return 0;
        }

        return $this->expiresAt->getTimestamp() - $now->getTimestamp();
    }

    /**
     * 檢查 token 是否接近過期.
     *
     * @param int $thresholdSeconds 臨界秒數（預設 3600 秒 = 1 小時）
     * @param DateTime|null $now 當前時間
     */
    public function isNearExpiry(int $thresholdSeconds = 3600, ?DateTime $now = null): bool
    {
        return $this->getRemainingTime($now) <= $thresholdSeconds;
    }

    /**
     * 實作 JsonSerializable 介面.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'jti' => $this->jti,
            'user_id' => $this->userId,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'device_info' => $this->deviceInfo->jsonSerialize(),
            'status' => $this->status,
            'revoked_reason' => $this->revokedReason,
            'revoked_at' => $this->revokedAt?->format('Y-m-d H:i:s'),
            'last_used_at' => $this->lastUsedAt?->format('Y-m-d H:i:s'),
            'parent_token_jti' => $this->parentTokenJti,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 轉換為陣列（包含敏感資料）.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'jti' => $this->jti,
            'user_id' => $this->userId,
            'token_hash' => $this->tokenHash,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
            'device_info' => $this->deviceInfo->toArray(),
            'status' => $this->status,
            'revoked_reason' => $this->revokedReason,
            'revoked_at' => $this->revokedAt?->format('Y-m-d H:i:s'),
            'last_used_at' => $this->lastUsedAt?->format('Y-m-d H:i:s'),
            'parent_token_jti' => $this->parentTokenJti,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 驗證 JTI 格式.
     */
    private function validateJti(string $jti): void
    {
        if (empty($jti)) {
            throw new InvalidArgumentException('JTI cannot be empty');
        }

        if (mb_strlen($jti) < 8 || mb_strlen($jti) > 255) {
            throw new InvalidArgumentException('JTI must be between 8 and 255 characters');
        }

        // 檢查 JTI 是否為合法格式（UUID 或隨機字串）
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $jti)) {
            throw new InvalidArgumentException('JTI contains invalid characters');
        }
    }

    /**
     * 驗證使用者 ID.
     */
    private function validateUserId(int $userId): void
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
    }

    /**
     * 驗證 token hash.
     */
    private function validateTokenHash(string $tokenHash): void
    {
        if (empty($tokenHash)) {
            throw new InvalidArgumentException('Token hash cannot be empty');
        }

        // 檢查是否為 64 字元的 hex 字串（SHA256）
        if (!preg_match('/^[a-f0-9]{64}$/', $tokenHash)) {
            throw new InvalidArgumentException('Token hash must be a valid SHA256 hash');
        }
    }

    /**
     * 驗證狀態.
     */
    private function validateStatus(string $status): void
    {
        $validStatuses = [
            self::STATUS_ACTIVE,
            self::STATUS_REVOKED,
            self::STATUS_EXPIRED,
            self::STATUS_USED,
        ];

        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException(
                'Status must be one of: ' . implode(', ', $validStatuses),
            );
        }
    }

    /**
     * 驗證過期時間.
     */
    private function validateExpirationTime(DateTime $expiresAt): void
    {
        $now = new DateTime();

        // 允許設定過去的時間（用於測試或匯入歷史資料）
        // 但不允許過於久遠的過期時間（超過 10 年）
        $maxExpiry = $now->modify('+10 years');
        if ($expiresAt > $maxExpiry) {
            throw new InvalidArgumentException('Expiration time cannot be more than 10 years in the future');
        }
    }

    /**
     * 驗證撤銷相關資料.
     */
    private function validateRevokedData(string $status, ?string $revokedReason, ?DateTime $revokedAt): void
    {
        if ($status === self::STATUS_REVOKED) {
            if (empty($revokedReason)) {
                throw new InvalidArgumentException('Revoked reason is required when status is revoked');
            }

            if ($revokedAt === null) {
                throw new InvalidArgumentException('Revoked time is required when status is revoked');
            }
        } elseif ($revokedReason !== null || $revokedAt !== null) {
            throw new InvalidArgumentException('Revoked reason and time should only be set when status is revoked');
        }
    }

    /**
     * 字串表示.
     */
    public function __toString(): string
    {
        return sprintf(
            'RefreshToken(jti=%s, userId=%d, status=%s, expiresAt=%s)',
            $this->jti,
            $this->userId,
            $this->status,
            $this->expiresAt->format('Y-m-d H:i:s'),
        );
    }
}
