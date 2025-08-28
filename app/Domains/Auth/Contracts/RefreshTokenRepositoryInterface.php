<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Contracts;

use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use DateTime;

/**
 * Refresh Token Repository 介面.
 *
 * 定義refresh token的資料存取操作，包含建立、查詢、更新、刪除等功能。
 * 負責管理refresh token的持久化儲存和生命週期管理。
 */
interface RefreshTokenRepositoryInterface
{
    /**
     * 建立新的refresh token記錄.
     *
     * @param string $jti JWT ID（唯一識別碼）
     * @param int $userId 使用者ID
     * @param string $tokenHash token的雜湊值
     * @param DateTime $expiresAt 過期時間
     * @param DeviceInfo $deviceInfo 裝置資訊
     * @param string|null $parentTokenJti 父token的JTI（用於token輪轉）
     * @return bool 建立成功時回傳true
     */
    public function create(
        string $jti,
        int $userId,
        string $tokenHash,
        DateTime $expiresAt,
        DeviceInfo $deviceInfo,
        ?string $parentTokenJti = null,
    ): bool;

    /**
     * 根據JTI查找refresh token.
     *
     * @param string $jti JWT ID
     * @return array<string, mixed>|null token資料，找不到時回傳null
     */
    public function findByJti(string $jti): ?array;

    /**
     * 根據token雜湊值查找refresh token.
     *
     * @param string $tokenHash token的雜湊值
     * @return array<string, mixed>|null token資料，找不到時回傳null
     */
    public function findByTokenHash(string $tokenHash): ?array;

    /**
     * 取得使用者的所有有效refresh token.
     *
     * @param int $userId 使用者ID
     * @param bool $includeExpired 是否包含已過期的token，預設為false
     * @return array<int, array<string, mixed>> token資料陣列
     */
    public function findByUserId(int $userId, bool $includeExpired = false): array;

    /**
     * 取得特定裝置的refresh token.
     *
     * @param int $userId 使用者ID
     * @param string $deviceId 裝置ID
     * @return array<int, array<string, mixed>> token資料陣列
     */
    public function findByUserIdAndDevice(int $userId, string $deviceId): array;

    /**
     * 更新refresh token的最後使用時間.
     *
     * @param string $jti JWT ID
     * @param DateTime|null $lastUsedAt 最後使用時間，null時使用目前時間
     * @return bool 更新成功時回傳true
     */
    public function updateLastUsed(string $jti, ?DateTime $lastUsedAt = null): bool;

    /**
     * 撤銷refresh token（軟刪除）.
     *
     * @param string $jti JWT ID
     * @param string $reason 撤銷原因
     * @return bool 撤銷成功時回傳true
     */
    public function revoke(string $jti, string $reason = 'manual_revocation'): bool;

    /**
     * 撤銷使用者的所有refresh token.
     *
     * @param int $userId 使用者ID
     * @param string $reason 撤銷原因
     * @param string|null $excludeJti 排除的JTI（通常是目前使用的token）
     * @return int 撤銷的token數量
     */
    public function revokeAllByUserId(int $userId, string $reason = 'revoke_all_sessions', ?string $excludeJti = null): int;

    /**
     * 撤銷特定裝置的所有refresh token.
     *
     * @param int $userId 使用者ID
     * @param string $deviceId 裝置ID
     * @param string $reason 撤銷原因
     * @return int 撤銷的token數量
     */
    public function revokeAllByDevice(int $userId, string $deviceId, string $reason = 'device_logout'): int;

    /**
     * 刪除refresh token記錄.
     *
     * @param string $jti JWT ID
     * @return bool 刪除成功時回傳true
     */
    public function delete(string $jti): bool;

    /**
     * 檢查refresh token是否已被撤銷.
     *
     * @param string $jti JWT ID
     * @return bool 已撤銷時回傳true
     */
    public function isRevoked(string $jti): bool;

    /**
     * 檢查refresh token是否已過期
     *
     * @param string $jti JWT ID
     * @return bool 已過期時回傳true
     */
    public function isExpired(string $jti): bool;

    /**
     * 檢查refresh token是否存在且有效.
     *
     * @param string $jti JWT ID
     * @return bool 存在且有效時回傳true
     */
    public function isValid(string $jti): bool;

    /**
     * 清理過期的refresh token.
     *
     * @param DateTime|null $beforeDate 清理此日期之前的記錄，null時使用目前時間
     * @return int 清理的記錄數量
     */
    public function cleanup(?DateTime $beforeDate = null): int;

    /**
     * 清理已撤銷的refresh token（超過指定天數）.
     *
     * @param int $days 保留天數，預設30天
     * @return int 清理的記錄數量
     */
    public function cleanupRevoked(int $days = 30): int;

    /**
     * 取得使用者的refresh token統計資訊.
     *
     * @param int $userId 使用者ID
     * @return array<string, mixed> 統計資訊
     *                              - total: 總數
     *                              - active: 有效數量
     *                              - expired: 已過期數量
     *                              - revoked: 已撤銷數量
     */
    public function getUserTokenStats(int $userId): array;

    /**
     * 取得token家族的所有相關token.
     *
     * @param string $rootJti 根token的JTI
     * @return array<int, array<string, mixed>> token資料陣列
     */
    public function getTokenFamily(string $rootJti): array;

    /**
     * 撤銷整個token家族.
     *
     * @param string $rootJti 根token的JTI
     * @param string $reason 撤銷原因
     * @return int 撤銷的token數量
     */
    public function revokeTokenFamily(string $rootJti, string $reason = 'family_revocation'): int;

    /**
     * 批次建立refresh token記錄.
     *
     * @param array<int, array<string, mixed>> $tokens token資料陣列
     * @return int 建立成功的記錄數量
     */
    public function batchCreate(array $tokens): int;

    /**
     * 批次撤銷refresh token.
     *
     * @param array<int, string> $jtis JTI陣列
     * @param string $reason 撤銷原因
     * @return int 撤銷成功的數量
     */
    public function batchRevoke(array $jtis, string $reason = 'batch_revocation'): int;

    /**
     * 取得即將過期的refresh token.
     *
     * @param int $thresholdHours 臨界小時數，預設24小時
     * @return array<int, array<string, mixed>> token資料陣列
     */
    public function getTokensNearExpiry(int $thresholdHours = 24): array;

    /**
     * 統計系統中的refresh token資訊.
     *
     * @return array<string, mixed> 系統統計資訊
     *                              - total_tokens: 總token數量
     *                              - active_tokens: 有效token數量
     *                              - expired_tokens: 過期token數量
     *                              - revoked_tokens: 撤銷token數量
     *                              - unique_users: 擁有token的使用者數量
     *                              - unique_devices: 裝置數量
     */
    public function getSystemStats(): array;
}
