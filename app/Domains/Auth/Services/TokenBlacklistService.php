<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Services;

use AlleyNote\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use AlleyNote\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Token 黑名單服務.
 *
 * 提供高層次的 Token 黑名單管理功能，封裝複雜的業務邏輯。
 * 負責協調黑名單操作、統計分析、自動清理等功能。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
final class TokenBlacklistService
{
    /**
     * 預設的清理批次大小.
     */
    private const DEFAULT_CLEANUP_BATCH_SIZE = 1000;

    /**
     * 預設的黑名單大小限制.
     */
    private const DEFAULT_MAX_BLACKLIST_SIZE = 100000;

    /**
     * 高優先級原因清單.
     */
    private const HIGH_PRIORITY_REASONS = [
        TokenBlacklistEntry::REASON_SECURITY_BREACH,
        TokenBlacklistEntry::REASON_SUSPICIOUS_ACTIVITY,
        TokenBlacklistEntry::REASON_ACCOUNT_SUSPENDED,
        TokenBlacklistEntry::REASON_MANUAL_REVOCATION,
    ];

    public function __construct(
        private readonly TokenBlacklistRepositoryInterface $repository,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * 將 token 加入黑名單.
     *
     * @param string $jti JWT ID
     * @param string $tokenType Token 類型
     * @param int $userId 使用者 ID
     * @param DateTimeImmutable $expiresAt 過期時間
     * @param string $reason 黑名單原因
     * @param string|null $deviceId 裝置 ID
     * @param array<string, mixed>|null $metadata 額外資料
     * @return bool 成功時回傳 true
     * @throws InvalidArgumentException 當參數無效時
     */
    public function blacklistToken(
        string $jti,
        string $tokenType,
        int $userId,
        DateTimeImmutable $expiresAt,
        string $reason,
        ?string $deviceId = null,
        ?array $metadata = null,
    ): bool {
        $this->validateTokenType($tokenType);
        $this->validateReason($reason);

        try {
            $entry = new TokenBlacklistEntry(
                jti: $jti,
                tokenType: $tokenType,
                userId: $userId,
                expiresAt: $expiresAt,
                blacklistedAt: new DateTimeImmutable(),
                reason: $reason,
                deviceId: $deviceId,
                metadata: $metadata ?? [],
            );

            $result = $this->repository->addToBlacklist($entry);

            if ($result) {
                $this->logBlacklistAction('add', $jti, $reason, $userId, $deviceId);

                // 如果是高優先級原因，記錄額外日誌
                if (in_array($reason, self::HIGH_PRIORITY_REASONS, true)) {
                    $this->logHighPriorityBlacklist($entry);
                }
            }

            return $result;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to blacklist token', [
                'jti' => $jti,
                'reason' => $reason,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            return false;
        }
    }

    /**
     * 檢查 token 是否在黑名單中.
     *
     * @param string $jti JWT ID
     * @return bool 在黑名單中時回傳 true
     */
    public function isTokenBlacklisted(string $jti): bool
    {
        if (empty($jti)) {
            return false;
        }

        try {
            return $this->repository->isBlacklisted($jti);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to check blacklist status', [
                'jti' => $jti,
                'error' => $e->getMessage(),
            ]);

            // 預設為安全起見，認為 token 已被列入黑名單
            return true;
        }
    }

    /**
     * 批次檢查 token 是否在黑名單中.
     *
     * @param array<int, string> $jtis JTI 陣列
     * @return array<string, bool> JTI 為 key，是否在黑名單為值的陣列
     */
    public function batchCheckBlacklist(array $jtis): array
    {
        if (empty($jtis)) {
            return [];
        }

        $validJtis = array_filter($jtis, fn($jti) => !empty($jti));

        if (empty($validJtis)) {
            return [];
        }

        try {
            return $this->repository->batchIsBlacklisted($validJtis);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to batch check blacklist status', [
                'jtis_count' => count($jtis),
                'error' => $e->getMessage(),
            ]);

            // 預設為安全起見，認為所有 token 都已被列入黑名單
            return array_fill_keys($validJtis, true);
        }
    }

    /**
     * 將使用者的所有 token 加入黑名單.
     *
     * @param int $userId 使用者 ID
     * @param string $reason 黑名單原因
     * @param string|null $excludeJti 排除的 JTI
     * @return int 加入黑名單的 token 數量
     */
    public function blacklistUserTokens(int $userId, string $reason, ?string $excludeJti = null): int
    {
        $this->validateReason($reason);

        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be positive');
        }

        try {
            $count = $this->repository->blacklistAllUserTokens($userId, $reason, $excludeJti);

            $this->logger?->info('Blacklisted user tokens', [
                'user_id' => $userId,
                'reason' => $reason,
                'excluded_jti' => $excludeJti,
                'count' => $count,
            ]);

            return $count;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to blacklist user tokens', [
                'user_id' => $userId,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 將裝置的所有 token 加入黑名單.
     *
     * @param string $deviceId 裝置 ID
     * @param string $reason 黑名單原因
     * @return int 加入黑名單的 token 數量
     */
    public function blacklistDeviceTokens(string $deviceId, string $reason): int
    {
        $this->validateReason($reason);

        if (empty($deviceId)) {
            throw new InvalidArgumentException('Device ID cannot be empty');
        }

        try {
            $count = $this->repository->blacklistAllDeviceTokens($deviceId, $reason);

            $this->logger?->info('Blacklisted device tokens', [
                'device_id' => $deviceId,
                'reason' => $reason,
                'count' => $count,
            ]);

            return $count;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to blacklist device tokens', [
                'device_id' => $deviceId,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 從黑名單中移除 token.
     *
     * @param string $jti JWT ID
     * @return bool 移除成功時回傳 true
     */
    public function removeFromBlacklist(string $jti): bool
    {
        if (empty($jti)) {
            return false;
        }

        try {
            $result = $this->repository->removeFromBlacklist($jti);

            if ($result) {
                $this->logger?->info('Token removed from blacklist', [
                    'jti' => $jti,
                ]);
            }

            return $result;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to remove token from blacklist', [
                'jti' => $jti,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 批次從黑名單中移除 token.
     *
     * @param array<int, string> $jtis JTI 陣列
     * @return int 成功移除的數量
     */
    public function batchRemoveFromBlacklist(array $jtis): int
    {
        if (empty($jtis)) {
            return 0;
        }

        $validJtis = array_filter($jtis, fn($jti) => !empty($jti));

        if (empty($validJtis)) {
            return 0;
        }

        try {
            $count = $this->repository->batchRemoveFromBlacklist($validJtis);

            $this->logger?->info('Batch removed tokens from blacklist', [
                'removed_count' => $count,
                'requested_count' => count($validJtis),
            ]);

            return $count;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to batch remove tokens from blacklist', [
                'jtis_count' => count($jtis),
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 自動清理過期的黑名單項目.
     *
     * @param int $batchSize 批次大小
     * @return array<string, mixed> 清理結果統計
     */
    public function autoCleanup(int $batchSize = self::DEFAULT_CLEANUP_BATCH_SIZE): array
    {
        $startTime = microtime(true);
        $totalCleaned = 0;

        try {
            // 清理可清理的過期項目
            $expiredCleaned = $this->repository->cleanupExpiredEntries();
            $totalCleaned += $expiredCleaned;

            // 清理超過保留期限的舊項目
            $oldCleaned = $this->repository->cleanupOldEntries();
            $totalCleaned += $oldCleaned;

            $executionTime = microtime(true) - $startTime;

            $result = [
                'total_cleaned' => $totalCleaned,
                'expired_cleaned' => $expiredCleaned,
                'old_cleaned' => $oldCleaned,
                'execution_time' => round($executionTime, 3),
                'success' => true,
            ];

            $this->logger?->info('Auto cleanup completed', $result);

            return $result;
        } catch (Throwable $e) {
            $executionTime = microtime(true) - $startTime;

            $result = [
                'total_cleaned' => $totalCleaned,
                'execution_time' => round($executionTime, 3),
                'success' => false,
                'error' => $e->getMessage(),
            ];

            $this->logger?->error('Auto cleanup failed', $result);

            return $result;
        }
    }

    /**
     * 取得黑名單統計資訊.
     *
     * @return array<string, mixed> 統計資訊
     */
    public function getStatistics(): array
    {
        try {
            $stats = $this->repository->getBlacklistStats();
            $sizeInfo = $this->repository->getSizeInfo();

            return array_merge($stats, [
                'size_info' => $sizeInfo,
                'is_size_exceeded' => $this->repository->isSizeExceeded(),
                'generated_at' => new DateTimeImmutable(),
            ]);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to get blacklist statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => 'Unable to retrieve statistics',
                'generated_at' => new DateTimeImmutable(),
            ];
        }
    }

    /**
     * 取得使用者的黑名單統計.
     *
     * @param int $userId 使用者 ID
     * @return array<string, mixed> 使用者統計資訊
     */
    public function getUserStatistics(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be positive');
        }

        try {
            return $this->repository->getUserBlacklistStats($userId);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to get user blacklist statistics', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'user_id' => $userId,
                'error' => 'Unable to retrieve user statistics',
            ];
        }
    }

    /**
     * 搜尋黑名單項目.
     *
     * @param array<string, mixed> $criteria 搜尋條件
     * @param int|null $limit 限制數量
     * @param int $offset 偏移量
     * @return array<string, mixed> 搜尋結果
     */
    public function searchBlacklistEntries(
        array $criteria,
        ?int $limit = null,
        int $offset = 0,
    ): array {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset must be non-negative');
        }

        if ($limit !== null && $limit <= 0) {
            throw new InvalidArgumentException('Limit must be positive');
        }

        try {
            $entries = $this->repository->search($criteria, $limit, $offset);
            $total = $this->repository->countSearch($criteria);

            return [
                'entries' => $entries,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($limit !== null) && ($offset + count($entries) < $total),
            ];
        } catch (Throwable $e) {
            $this->logger?->error('Failed to search blacklist entries', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);

            return [
                'entries' => [],
                'total' => 0,
                'error' => 'Search failed',
            ];
        }
    }

    /**
     * 取得最近的高優先級黑名單項目.
     *
     * @param int $limit 限制數量
     * @return array<int, TokenBlacklistEntry> 黑名單項目陣列
     */
    public function getRecentHighPriorityEntries(int $limit = 50): array
    {
        try {
            return $this->repository->getHighPriorityEntries($limit);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to get high priority entries', [
                'limit' => $limit,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * 最佳化黑名單儲存.
     *
     * @return array<string, mixed> 最佳化結果
     */
    public function optimize(): array
    {
        try {
            $result = $this->repository->optimize();

            $this->logger?->info('Blacklist optimization completed', $result);

            return $result;
        } catch (Throwable $e) {
            $this->logger?->error('Blacklist optimization failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 檢查黑名單健康狀態.
     *
     * @return array<string, mixed> 健康狀態資訊
     */
    public function getHealthStatus(): array
    {
        try {
            $sizeInfo = $this->repository->getSizeInfo();
            $isOverLimit = $this->repository->isSizeExceeded();
            $stats = $this->repository->getBlacklistStats();

            $totalEntries = $sizeInfo['total_entries'] ?? 0;
            $isTooLarge = $totalEntries > self::DEFAULT_MAX_BLACKLIST_SIZE;

            $status = [
                'healthy' => !$isOverLimit && !$isTooLarge,
                'size_exceeded' => $isOverLimit,
                'too_large' => $isTooLarge,
                'max_recommended_size' => self::DEFAULT_MAX_BLACKLIST_SIZE,
                'total_entries' => $totalEntries,
                'active_entries' => $sizeInfo['active_entries'] ?? 0,
                'expired_entries' => $sizeInfo['expired_entries'] ?? 0,
                'cleanable_entries' => $sizeInfo['cleanable_entries'] ?? 0,
                'security_issues' => $stats['security_related'] ?? 0,
                'recommendations' => [],
            ];

            // 產生建議
            if ($isOverLimit) {
                $status['recommendations'][] = 'Run cleanup to reduce blacklist size';
            }

            if ($isTooLarge) {
                $status['recommendations'][] = 'Blacklist size exceeds recommended limit, consider cleanup';
            }

            if (($sizeInfo['expired_entries'] ?? 0) > 1000) {
                $status['recommendations'][] = 'High number of expired entries, consider cleanup';
            }

            if (($stats['security_related'] ?? 0) > 100) {
                $status['recommendations'][] = 'High security-related blacklist entries detected';
            }

            return $status;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to get blacklist health status', [
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'error' => 'Unable to determine health status',
            ];
        }
    }

    /**
     * 驗證 token 類型.
     *
     * @param string $tokenType Token 類型
     * @throws InvalidArgumentException 當 token 類型無效時
     */
    private function validateTokenType(string $tokenType): void
    {
        if (!in_array($tokenType, [
            TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            TokenBlacklistEntry::TOKEN_TYPE_REFRESH,
        ], true)) {
            throw new InvalidArgumentException("Invalid token type: {$tokenType}");
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
        $validReasons = [
            TokenBlacklistEntry::REASON_LOGOUT,
            TokenBlacklistEntry::REASON_REVOKED,
            TokenBlacklistEntry::REASON_SECURITY_BREACH,
            TokenBlacklistEntry::REASON_PASSWORD_CHANGED,
            TokenBlacklistEntry::REASON_ACCOUNT_SUSPENDED,
            TokenBlacklistEntry::REASON_MANUAL_REVOCATION,
            TokenBlacklistEntry::REASON_EXPIRED,
            TokenBlacklistEntry::REASON_INVALID_SIGNATURE,
            TokenBlacklistEntry::REASON_DEVICE_LOST,
            TokenBlacklistEntry::REASON_SUSPICIOUS_ACTIVITY,
        ];

        if (!in_array($reason, $validReasons, true)) {
            throw new InvalidArgumentException("Invalid blacklist reason: {$reason}");
        }
    }

    /**
     * 記錄黑名單操作.
     *
     * @param string $action 操作類型
     * @param string $jti JWT ID
     * @param string $reason 原因
     * @param int $userId 使用者 ID
     * @param string|null $deviceId 裝置 ID
     */
    private function logBlacklistAction(
        string $action,
        string $jti,
        string $reason,
        int $userId,
        ?string $deviceId,
    ): void {
        $this->logger?->info("Token blacklist {$action}", [
            'jti' => $jti,
            'reason' => $reason,
            'user_id' => $userId,
            'device_id' => $deviceId,
        ]);
    }

    /**
     * 記錄高優先級黑名單項目.
     *
     * @param TokenBlacklistEntry $entry 黑名單項目
     */
    private function logHighPriorityBlacklist(TokenBlacklistEntry $entry): void
    {
        $this->logger?->warning('High priority token blacklisted', [
            'jti' => $entry->getJti(),
            'reason' => $entry->getReason(),
            'user_id' => $entry->getUserId(),
            'device_id' => $entry->getDeviceId(),
            'token_type' => $entry->getTokenType(),
        ]);
    }
}
