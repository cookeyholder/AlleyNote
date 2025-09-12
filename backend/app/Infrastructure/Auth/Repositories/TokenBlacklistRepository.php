<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Repositories;

use App\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use App\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use DateTime;
use DateTimeImmutable;
use Exception;
use PDO;
use PDOException;

/**
 * Token 黑名單 Repository 實作.
 *
 * 提供 Token 黑名單的資料庫存取操作，包含新增、查詢、刪除等功能。
 * 負責管理被撤銷或無效 token 的黑名單記錄，確保 token 安全性。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class TokenBlacklistRepository implements TokenBlacklistRepositoryInterface
{
    /**
     * 建構 Token 黑名單 Repository.
     *
     * @param PDO $pdo 資料庫連線
     */
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * 將 token 加入黑名單.
     *
     * @param TokenBlacklistEntry $entry 黑名單項目
     * @return bool 加入成功時回傳 true
     */
    public function addToBlacklist(TokenBlacklistEntry $entry): bool
    {
        try {
            $sql = '
                INSERT INTO token_blacklist (
                    jti, token_type, user_id, expires_at, blacklisted_at, reason, device_id, metadata
                ) VALUES (
                    :jti, :token_type, :user_id, :expires_at, :blacklisted_at, :reason, :device_id, :metadata
                )
            ';

            $stmt = $this->pdo->prepare($sql);

            $data = $entry->toDatabaseArray();

            return $stmt->execute([
                'jti' => $data['jti'],
                'token_type' => $data['token_type'],
                'user_id' => $data['user_id'],
                'expires_at' => $data['expires_at'],
                'blacklisted_at' => $data['blacklisted_at'],
                'reason' => $data['reason'],
                'device_id' => $data['device_id'],
                'metadata' => $data['metadata'],
            ]);
        } catch (PDOException $e) {
            throw new Exception('無法將 Token 加入黑名單: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 檢查 token 是否在黑名單中.
     *
     * @param string $jti JWT ID
     * @return bool 在黑名單中時回傳 true
     */
    public function isBlacklisted(string $jti): bool
    {
        try {
            $currentTime = new DateTime();
            $sql = '
                SELECT COUNT(*)
                FROM token_blacklist
                WHERE jti = :jti
                AND expires_at > :current_time
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'jti' => $jti,
                'current_time' => $currentTime->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception('無法檢查 Token 黑名單狀態: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 檢查 token 是否在黑名單中（根據 token hash）.
     *
     * @param string $tokenHash token 的雜湊值
     * @return bool 在黑名單中時回傳 true
     */
    public function isTokenHashBlacklisted(string $tokenHash): bool
    {
        try {
            $currentTime = new DateTime();
            // 假設我們在 metadata 中儲存 token_hash
            $sql = '
                SELECT COUNT(*)
                FROM token_blacklist
                WHERE JSON_EXTRACT(metadata, "$.token_hash") = :token_hash
                AND expires_at > :current_time
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'token_hash' => $tokenHash,
                'current_time' => $currentTime->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception('無法檢查 Token Hash 黑名單狀態: ' . $e->getMessage(), 0, $e);
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
        try {
            $sql = 'DELETE FROM token_blacklist WHERE jti = :jti';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['jti' => $jti]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception('無法從黑名單移除 Token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 根據 JTI 查找黑名單項目.
     *
     * @param string $jti JWT ID
     * @return TokenBlacklistEntry|null 黑名單項目，找不到時回傳 null
     */
    public function findByJti(string $jti): ?TokenBlacklistEntry
    {
        try {
            $sql = '
                SELECT * FROM token_blacklist
                WHERE jti = :jti
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['jti' => $jti]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result === false) {
                return null;
            }

            return TokenBlacklistEntry::fromDatabaseArray($result);
        } catch (PDOException $e) {
            throw new Exception('無法查詢黑名單項目: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 取得使用者的所有黑名單項目.
     *
     * @param int $userId 使用者 ID
     * @return array<TokenBlacklistEntry> 黑名單項目陣列
     */
    public function findByUserId(int $userId): array
    {
        try {
            $sql = '
                SELECT * FROM token_blacklist
                WHERE user_id = :user_id
                ORDER BY blacklisted_at DESC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $entries = [];

            foreach ($results as $result) {
                $entries[] = TokenBlacklistEntry::fromDatabaseArray($result);
            }

            return $entries;
        } catch (PDOException $e) {
            throw new Exception('無法查詢使用者的黑名單項目: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 清理過期的黑名單項目.
     *
     * @param DateTime|null $beforeDate 指定清理此日期前的項目，若為 null 則清理所有過期項目
     * @return int 清理的項目數量
     */
    public function cleanup(?DateTime $beforeDate = null): int
    {
        try {
            $beforeDate ??= new DateTime();
            $sql = 'DELETE FROM token_blacklist WHERE expires_at < :before_date';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['before_date' => $beforeDate->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('無法清理過期的黑名單項目: ' . $e->getMessage(), 0, $e);
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
            $currentTime = new DateTime();
            $sql = '
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN expires_at > :current_time THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN expires_at <= :current_time THEN 1 ELSE 0 END) as expired,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT device_id) as unique_devices
                FROM token_blacklist
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['current_time' => $currentTime->format('Y-m-d H:i:s')]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: [];
        } catch (PDOException $e) {
            throw new Exception('無法取得黑名單統計資訊: ' . $e->getMessage(), 0, $e);
        }
    }
}
