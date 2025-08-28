<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\Services;

use AlleyNote\Domains\Auth\Contracts\JwtTokenServiceInterface;
use AlleyNote\Domains\Auth\Contracts\RefreshTokenRepositoryInterface;
use AlleyNote\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use AlleyNote\Domains\Auth\Entities\RefreshToken;
use AlleyNote\Domains\Auth\Exceptions\AuthenticationException;
use AlleyNote\Domains\Auth\Exceptions\InvalidTokenException;
use AlleyNote\Domains\Auth\Exceptions\RefreshTokenException;
use AlleyNote\Domains\Auth\Exceptions\TokenExpiredException;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use AlleyNote\Domains\Auth\ValueObjects\JwtPayload;
use AlleyNote\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use AlleyNote\Domains\Auth\ValueObjects\TokenPair;
use DateTime;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Refresh Token 領域服務.
 *
 * 負責 refresh token 的完整業務邏輯管理，包括 token 生成、驗證、
 * 輪轉、撤銷和清理等功能。提供安全的 token 管理機制。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
final class RefreshTokenService
{
    /**
     * 每個使用者最大 refresh token 數量.
     */
    private const MAX_TOKENS_PER_USER = 10;

    /**
     * 每次清理過期 token 的批次大小.
     */
    private const CLEANUP_BATCH_SIZE = 500;

    /**
     * 清理操作的安全時間間隔（秒）.
     */
    private const MIN_CLEANUP_INTERVAL = 300; // 5 分鐘

    /**
     * Token 輪轉檢查寬限期（秒）.
     */
    private const ROTATION_GRACE_PERIOD = 30;

    public function __construct(
        private readonly JwtTokenServiceInterface $jwtTokenService,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly TokenBlacklistRepositoryInterface $blacklistRepository,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * 建立新的 refresh token.
     *
     * @param int $userId 使用者 ID
     * @param DeviceInfo $deviceInfo 裝置資訊
     * @param string|null $parentTokenJti 父 token JTI（token 輪轉時使用）
     * @return RefreshToken 新建立的 refresh token
     * @throws RefreshTokenException 當 token 建立失敗時
     */
    public function createRefreshToken(
        int $userId,
        DeviceInfo $deviceInfo,
        ?string $parentTokenJti = null,
    ): RefreshToken {
        try {
            // 1. 檢查使用者 token 數量限制
            $this->enforceTokenLimits($userId);

            // 2. 產生新的 token pair
            $tokenPair = $this->jwtTokenService->generateTokenPair($userId, $deviceInfo);
            $refreshPayload = $this->jwtTokenService->extractPayload($tokenPair->getRefreshToken());

            // 3. 建立 refresh token 實體
            $expiresAt = new DateTime();
            $expiresAt->setTimestamp($refreshPayload->getExpiresAt()->getTimestamp());

            $tokenHash = hash('sha256', $tokenPair->getRefreshToken());

            // 4. 儲存到資料庫
            $success = $this->refreshTokenRepository->create(
                $refreshPayload->getJti(),
                $userId,
                $tokenHash,
                $expiresAt,
                $deviceInfo,
                $parentTokenJti,
            );

            if (!$success) {
                throw new RefreshTokenException('Failed to create refresh token');
            }

            // 5. 建立 RefreshToken 實體
            $refreshToken = new RefreshToken(
                null, // 新建立的 token，ID 由資料庫生成
                $refreshPayload->getJti(),
                $userId,
                $tokenHash,
                $expiresAt,
                $deviceInfo,
                RefreshToken::STATUS_ACTIVE,
                null, // 未撤銷
                null, // 撤銷原因
                $parentTokenJti,
                null, // created_at (由資料庫生成)
                null, // updated_at (由資料庫生成)
            );

            $this->logger?->info('Refresh token created successfully', [
                'user_id' => $userId,
                'device_id' => $deviceInfo->getDeviceId(),
                'jti' => $refreshPayload->getJti(),
            ]);

            return $refreshToken;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to create refresh token', [
                'user_id' => $userId,
                'device_id' => $deviceInfo->getDeviceId(),
                'error' => $e->getMessage(),
            ]);

            throw new RefreshTokenException(
                RefreshTokenException::REASON_CREATION_FAILED,
                'Failed to create refresh token: ' . $e->getMessage(),
                ['previous_exception' => $e->getMessage()],
            );
        }
    }

    /**
     * 使用 refresh token 產生新的 access token.
     *
     * @param string $refreshToken 原始 refresh token
     * @param DeviceInfo $deviceInfo 當前裝置資訊
     * @param bool $rotateToken 是否進行 token 輪轉
     * @return TokenPair 新的 token pair
     * @throws InvalidTokenException|TokenExpiredException|AuthenticationException
     */
    public function refreshAccessToken(
        string $refreshToken,
        DeviceInfo $deviceInfo,
        bool $rotateToken = true,
    ): TokenPair {
        try {
            // 1. 驗證 refresh token
            $payload = $this->validateRefreshToken($refreshToken, $deviceInfo);

            // 2. 從資料庫取得 token 記錄
            $tokenData = $this->refreshTokenRepository->findByJti($payload->getJti());
            if ($tokenData === null) {
                throw new InvalidTokenException('Refresh token not found in database');
            }

            // 3. 檢查 token 狀態
            if ($tokenData['status'] !== RefreshToken::STATUS_ACTIVE) {
                throw new InvalidTokenException('Refresh token is not active');
            }

            // 4. 驗證裝置歸屬
            if (!$this->verifyDeviceMatchFromData($tokenData, $deviceInfo)) {
                throw new AuthenticationException(
                    AuthenticationException::REASON_INVALID_TOKEN,
                    'Device mismatch detected',
                );
            }

            // 5. 產生新的 access token
            $newTokenPair = $this->jwtTokenService->generateTokenPair(
                (int) $tokenData['user_id'],
                $deviceInfo,
            );

            // 6. 進行 token 輪轉（如果需要）
            if ($rotateToken) {
                $this->performTokenRotationFromData($tokenData, $deviceInfo);
            } else {
                // 不輪轉時，更新使用時間
                $this->refreshTokenRepository->updateLastUsed($tokenData['jti']);
            }

            $this->logger?->info('Access token refreshed successfully', [
                'user_id' => $tokenData['user_id'],
                'device_id' => $deviceInfo->getDeviceId(),
                'jti' => $payload->getJti(),
                'rotated' => $rotateToken,
            ]);

            return $newTokenPair;
        } catch (InvalidTokenException | TokenExpiredException | AuthenticationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to refresh access token', [
                'error' => $e->getMessage(),
                'device_id' => $deviceInfo->getDeviceId(),
            ]);

            throw new RefreshTokenException(
                RefreshTokenException::REASON_ROTATION_FAILED,
                'Failed to refresh access token: ' . $e->getMessage(),
                ['previous_exception' => $e->getMessage()],
            );
        }
    }

    /**
     * 撤銷單個 refresh token.
     *
     * @param string $refreshToken 要撤銷的 refresh token
     * @param string $reason 撤銷原因
     * @return bool 撤銷是否成功
     */
    public function revokeToken(string $refreshToken, string $reason = RefreshToken::REVOKE_REASON_MANUAL): bool
    {
        try {
            $payload = $this->jwtTokenService->extractPayload($refreshToken);
            $result = $this->refreshTokenRepository->revoke($payload->getJti(), $reason);

            if ($result) {
                // 同時加入黑名單
                $this->addToBlacklist($refreshToken, $reason);

                $this->logger?->info('Refresh token revoked', [
                    'jti' => $payload->getJti(),
                    'reason' => $reason,
                ]);
            }

            return $result;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to revoke refresh token', [
                'error' => $e->getMessage(),
                'reason' => $reason,
            ]);

            return false;
        }
    }

    /**
     * 撤銷使用者的所有 refresh token.
     *
     * @param int $userId 使用者 ID
     * @param string $reason 撤銷原因
     * @param string|null $exceptJti 例外的 JTI（不撤銷）
     * @return int 撤銷的 token 數量
     */
    public function revokeAllUserTokens(
        int $userId,
        string $reason = RefreshToken::REVOKE_REASON_LOGOUT_ALL,
        ?string $exceptJti = null,
    ): int {
        try {
            // 1. 取得使用者所有活躍 token
            $tokens = $this->refreshTokenRepository->findByUserId($userId);

            $revokedCount = 0;
            foreach ($tokens as $tokenData) {
                if ($exceptJti !== null && $tokenData['jti'] === $exceptJti) {
                    continue;
                }

                if ($this->refreshTokenRepository->revoke($tokenData['jti'], $reason)) {
                    $revokedCount++;
                }
            }

            $this->logger?->info('User tokens revoked', [
                'user_id' => $userId,
                'revoked_count' => $revokedCount,
                'reason' => $reason,
            ]);

            return $revokedCount;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to revoke user tokens', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 撤銷裝置的所有 refresh token.
     *
     * @param string $deviceId 裝置 ID
     * @param string $reason 撤銷原因
     * @return int 撤銷的 token 數量
     */
    public function revokeDeviceTokens(string $deviceId, string $reason = RefreshToken::REVOKE_REASON_SECURITY): int
    {
        try {
            $revokedCount = $this->refreshTokenRepository->revokeAllByDevice(0, $deviceId, $reason);

            $this->logger?->info('Device tokens revoked', [
                'device_id' => $deviceId,
                'revoked_count' => $revokedCount,
                'reason' => $reason,
            ]);

            return $revokedCount;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to revoke device tokens', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 清理過期的 refresh token.
     *
     * @return int 清理的 token 數量
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            $cleanedCount = $this->refreshTokenRepository->cleanup();

            if ($cleanedCount > 0) {
                $this->logger?->info('Expired refresh tokens cleaned up', [
                    'cleaned_count' => $cleanedCount,
                ]);
            }

            return $cleanedCount;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to cleanup expired tokens', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 取得使用者的活躍 refresh token 統計.
     *
     * @param int $userId 使用者 ID
     * @return array{total: int, by_device: array, by_status: array}
     */
    public function getUserTokenStats(int $userId): array
    {
        try {
            $tokens = $this->refreshTokenRepository->findByUserId($userId);

            $stats = [
                'total' => count($tokens),
                'by_device' => [],
                'by_status' => [],
            ];

            foreach ($tokens as $tokenData) {
                // 統計各裝置的 token 數量
                $deviceId = $tokenData['device_id'] ?? 'unknown';
                $stats['by_device'][$deviceId] = ($stats['by_device'][$deviceId] ?? 0) + 1;

                // 統計各狀態的 token 數量
                $status = $tokenData['status'] ?? 'unknown';
                $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;
            }

            return $stats;
        } catch (Throwable $e) {
            $this->logger?->error('Failed to get user token stats', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'total' => 0,
                'by_device' => [],
                'by_status' => [],
            ];
        }
    }

    /**
     * 驗證 refresh token 的有效性.
     */
    private function validateRefreshToken(string $refreshToken, DeviceInfo $deviceInfo): JwtPayload
    {
        // 1. 基本 JWT 驗證
        $payload = $this->jwtTokenService->validateRefreshToken($refreshToken);

        // 2. 檢查 token 類型
        $tokenType = $payload->getCustomClaim('type');
        if ($tokenType !== 'refresh') {
            throw new InvalidTokenException('Token is not a refresh token');
        }

        // 3. 檢查是否在黑名單中
        if ($this->blacklistRepository->isBlacklisted($payload->getJti())) {
            throw new InvalidTokenException('Refresh token is blacklisted');
        }

        // 4. 檢查 IP 和裝置一致性（可選的安全檢查）
        if ($this->shouldVerifyDeviceConsistency()) {
            $this->verifyDeviceConsistency($payload, $deviceInfo);
        }

        return $payload;
    }

    /**
     * 強制執行 token 數量限制.
     */
    private function enforceTokenLimits(int $userId): void
    {
        $activeTokens = $this->refreshTokenRepository->findByUserId($userId);

        if (count($activeTokens) >= self::MAX_TOKENS_PER_USER) {
            // 撤銷最舊的 token
            $oldestTokenData = $activeTokens[0]; // 假設已按時間排序
            $this->revokeToken($oldestTokenData['jti'], RefreshToken::REVOKE_REASON_MANUAL);

            $this->logger?->info('Token limit enforced, oldest token revoked', [
                'user_id' => $userId,
                'revoked_jti' => $oldestTokenData['jti'],
            ]);
        }
    }

    /**
     * 驗證裝置匹配（從資料庫資料）.
     */
    private function verifyDeviceMatchFromData(array $tokenData, DeviceInfo $currentDevice): bool
    {
        return $tokenData['device_id'] === $currentDevice->getDeviceId()
            && $tokenData['ip_address'] === $currentDevice->getIpAddress();
    }

    /**
     * 執行 token 輪轉（從資料庫資料）.
     */
    private function performTokenRotationFromData(array $tokenData, DeviceInfo $deviceInfo): RefreshToken
    {
        try {
            // 1. 建立新的 refresh token
            $newToken = $this->createRefreshToken(
                (int) $tokenData['user_id'],
                $deviceInfo,
                $tokenData['jti'], // 設定父 token JTI
            );

            // 2. 撤銷舊的 token（但給予寬限期）
            $this->refreshTokenRepository->revoke(
                $tokenData['jti'],
                RefreshToken::REVOKE_REASON_TOKEN_ROTATION,
            );

            return $newToken;
        } catch (Throwable $e) {
            $this->logger?->error('Token rotation failed', [
                'current_jti' => $tokenData['jti'],
                'user_id' => $tokenData['user_id'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 驗證裝置一致性.
     */
    private function verifyDeviceConsistency(JwtPayload $payload, DeviceInfo $deviceInfo): void
    {
        $tokenDeviceId = $payload->getCustomClaim('device_id');
        $tokenIpAddress = $payload->getCustomClaim('ip_address');

        if ($tokenDeviceId !== $deviceInfo->getDeviceId()) {
            throw new AuthenticationException(
                AuthenticationException::REASON_INVALID_TOKEN,
                'Device ID mismatch',
            );
        }

        if ($tokenIpAddress !== $deviceInfo->getIpAddress()) {
            throw new AuthenticationException(
                AuthenticationException::REASON_INVALID_TOKEN,
                'IP address mismatch',
            );
        }
    }

    /**
     * 將 token 加入黑名單.
     */
    private function addToBlacklist(string $token, string $reason): void
    {
        try {
            $payload = $this->jwtTokenService->extractPayload($token);
            $expiresAt = new DateTimeImmutable();
            $expiresAt->setTimestamp($payload->getExpiresAt()->getTimestamp());

            $entry = new TokenBlacklistEntry(
                $payload->getJti(),
                TokenBlacklistEntry::TOKEN_TYPE_REFRESH,
                $expiresAt,
                new DateTimeImmutable(),
                $reason,
                (int) $payload->getSubject(),
                null,
                [
                    'token_type' => 'refresh',
                    'user_id' => $payload->getSubject(),
                ],
            );

            $this->blacklistRepository->addToBlacklist($entry);
        } catch (Throwable $e) {
            $this->logger?->error('Failed to add token to blacklist', [
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 是否應該驗證裝置一致性.
     */
    private function shouldVerifyDeviceConsistency(): bool
    {
        // 可以從配置中讀取，這裡先返回 true
        return true;
    }

    /**
     * 獲取清理批次大小.
     */
    public function getCleanupBatchSize(): int
    {
        return self::CLEANUP_BATCH_SIZE;
    }

    /**
     * 取得最小清理間隔.
     */
    public function getMinCleanupInterval(): int
    {
        return self::MIN_CLEANUP_INTERVAL;
    }

    /**
     * 取得輪換寬限期
     */
    public function getRotationGracePeriod(): int
    {
        return self::ROTATION_GRACE_PERIOD;
    }
}
